<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
header('Content-Type: application/json; charset=utf-8');
sleep(1); // Espera didáctica del listado.

$orden = $_POST['orden'] ?? 'nroFactura'; // Usa el orden recibido o nroFactura como valor inicial.
$filtros = [ // Agrupa los cinco filtros recibidos por este PHP.
    'f_nroFactura'   => $_POST['f_nroFactura'] ?? '',
    'f_codArticulo'  => $_POST['f_codArticulo'] ?? '',
    'f_unidadMedida' => $_POST['f_unidadMedida'] ?? '',
    'f_descripcion'  => $_POST['f_descripcion'] ?? '',
    'f_fechaFactura' => $_POST['f_fechaFactura'] ?? '',
];

// Aplica los filtros y ejecuta el SELECT en este mismo archivo.
if (!$pdo) {
    echo json_encode(['renglones'=>[], 'cantidad'=>0]);
    return;
}

$mapaOrden = [ // Campo del navegador => columna real de MySQL.
    'nroFactura' => 'nro_factura', 'codArticulo' => 'cod_articulo',
    'unidadMedida' => 'cod_um', 'descripcion' => 'descripcion',
    'fechaFactura' => 'fecha_factura', 'cantidad' => 'cantidad',
    'precioUnitario' => 'precio_unitario', 'importeRenglon' => 'importe_renglon',
];
if (!in_array($orden, array_keys($mapaOrden), true)) { // Un orden desconocido usa el valor inicial.
    $orden = 'nroFactura';
}

try {
    $unidades = [];
    try {
        $unidades = $pdo->query("SELECT * FROM unidades_medida")->fetchAll();
    } catch (PDOException $e) {
        Database::logError('Unidades: ' . $e->getMessage());
    }
    $mapaMostrar = []; // Cualquier valor en BD -> código para mostrar/seleccionar en frontend
    $filas = $unidades; // Lee la tabla auxiliar de unidades.

    foreach ($filas as $u) {
        $vals    = array_values($u); // Permite consultar los valores también por posición.
        // Intenta reconocer el identificador numérico de la unidad.
        $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
        // Intenta reconocer el código de unidad, por ejemplo UN.
        $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
        // Intenta reconocer la descripción visible de la unidad.
        $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? $codVal);
        // Para mostrar la unidad, prefiere el código a la descripción.
        $preferido = ($codVal !== '') ? (string)$codVal : (string)$descVal;

        if ($idVal !== null) $mapaMostrar[(string)$idVal] = $preferido;
        if ($codVal !== '')  $mapaMostrar[(string)$codVal] = (string)$codVal;
        if ($descVal !== '') $mapaMostrar[(string)$descVal] = $preferido;
    }

    $infoUm = ['filas'=>$filas, 'mapaMostrar'=>$mapaMostrar];
    $mapaUmTexto = $infoUm['mapaMostrar'];

    $colOrden = $mapaOrden[$orden];

    $condiciones = []; // Acumula las condiciones que formarán el WHERE.
    $params = []; // Guarda los valores que se vincularán a los parámetros SQL.

    $fNro   = trim((string)($filtros['f_nroFactura'] ?? '')); // Lee el filtro de factura y quita espacios externos.
    // Lee el filtro de artículo y quita espacios externos.
    $fCod   = trim((string)($filtros['f_codArticulo'] ?? ''));
    $fUm    = trim((string)($filtros['f_unidadMedida'] ?? '')); // Lee la unidad elegida en el filtro.
    $fDesc  = trim((string)($filtros['f_descripcion'] ?? '')); // Lee el texto de búsqueda de la descripción.
    $fFecha = trim((string)($filtros['f_fechaFactura'] ?? '')); // Lee la fecha usada como filtro.

    if ($fNro !== '') {
        $condiciones[] = "LOWER(CAST(`nro_factura` AS CHAR)) LIKE LOWER(:f_nro)";
        $params[':f_nro'] = '%' . $fNro . '%'; // Los signos % permiten buscar una coincidencia parcial.
    }
    if ($fCod !== '') {
        $condiciones[] = "LOWER(CAST(`cod_articulo` AS CHAR)) LIKE LOWER(:f_cod)";
        $params[':f_cod'] = '%' . $fCod . '%'; // Busca el texto del código dentro del valor de la columna.
    }
    if ($fUm !== '') {
        $equivalencias = [strtolower($fUm)]; // Permite filtrar una unidad por ID, código o descripción.
        foreach ($infoUm['filas'] as $u) {
            $vals    = array_values($u); // Permite consultar los valores también por posición.
            // Intenta reconocer el identificador numérico de la unidad.
            $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
            // Intenta reconocer el código de unidad, por ejemplo UN.
            $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
            // Intenta reconocer la descripción visible de la unidad.
            $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? '');
            if (
                ($idVal !== null && strcasecmp((string)$idVal, $fUm) === 0) ||
                ($codVal !== '' && strcasecmp((string)$codVal, $fUm) === 0) ||
                ($descVal !== '' && strcasecmp((string)$descVal, $fUm) === 0)
            ) {
                if ($idVal !== null) $equivalencias[] = strtolower(trim((string)$idVal));
                if ($codVal !== '')  $equivalencias[] = strtolower(trim((string)$codVal));
                if ($descVal !== '') $equivalencias[] = strtolower(trim((string)$descVal));
            }
        }
        // Quita equivalencias vacías o repetidas antes de armar el IN.
        $equivalencias = array_values(array_unique(array_filter($equivalencias, function($v) {
            return $v !== '';
        })));
        $phUm = [];
        foreach ($equivalencias as $idx => $eqVal) {
            $key = ":f_um_$idx"; // Crea un parámetro SQL distinto para cada equivalencia.
            $phUm[] = $key;
            $params[$key] = $eqVal;
        }
        $condiciones[] = "LOWER(TRIM(CAST(`cod_um` AS CHAR))) IN (" . implode(', ', $phUm) . ")";
    }
    if ($fDesc !== '') {
        $condiciones[] = "LOWER(CAST(`descripcion` AS CHAR)) LIKE LOWER(:f_desc)";
        $params[':f_desc'] = '%' . $fDesc . '%'; // Busca la descripción aunque el texto esté en el medio.
    }
    if ($fFecha !== '') {
        $condiciones[] = "CAST(`fecha_factura` AS CHAR) LIKE :f_fecha";
        $params[':f_fecha'] = $fFecha . '%'; // El prefijo de fecha permite comparar también fechas con hora.
    }

    // AS conserva los nombres que recibe abm.js.
    $sql = "SELECT nro_factura AS nroFactura, fecha_factura AS fechaFactura,
        cod_articulo AS codArticulo, descripcion, cod_um AS unidadMedida,
        cantidad, precio_unitario AS precioUnitario, importe_renglon AS importeRenglon
        FROM renglones_factura";
    if (!empty($condiciones)) {
        $sql .= " WHERE " . implode(' AND ', $condiciones); // Une con AND todos los filtros que se completaron.
    }
    $sql .= " ORDER BY `$colOrden` ASC"; // El nombre proviene del mapa permitido, no del usuario.

    $stmt = $pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
    $stmt->execute($params); // SELECT: ejecuta la búsqueda con los valores de los filtros.
    $filas = $stmt->fetchAll(); // Recupera todos los registros encontrados.

    // Normalizar código de Unidad de Medida para visualización y edición consistente
    foreach ($filas as &$fila) {
        $rawUm = (string)($fila['unidadMedida'] ?? '');
        if ($rawUm !== '' && isset($mapaUmTexto[$rawUm])) {
            $fila['unidadMedida'] = $mapaUmTexto[$rawUm];
        }
    }
    unset($fila);

    $resultado = [
        'renglones' => $filas,
        'cantidad'  => count($filas), // La cantidad se calcula sobre las filas que realmente devolvió la consulta.
    ];
} catch (PDOException $e) {
    Database::logError('Renglones: ' . $e->getMessage());
    $resultado = ['renglones' => [], 'cantidad' => 0];
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
