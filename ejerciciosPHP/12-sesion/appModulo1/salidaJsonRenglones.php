<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
header('Content-Type: application/json; charset=utf-8');
sleep(1); // Espera didáctica del listado.

$orden = $_POST['orden'] ?? 'nroFactura'; // Usa el orden recibido o nroFactura como valor inicial.

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
    $colOrden = $mapaOrden[$orden]; //Nombre Columna

    $condiciones = []; // Acumula las condiciones que formarán el WHERE.
    $params = []; // Guarda los valores que se vincularán a los parámetros SQL.

    $fNro   = trim((string)($_POST['f_nroFactura'] ?? '')); // Lee el filtro de factura y quita espacios externos.
    // Lee el filtro de artículo y quita espacios externos.
    $fCod   = trim((string)($_POST['f_codArticulo'] ?? ''));
    $fUm    = trim((string)($_POST['f_unidadMedida'] ?? '')); // Lee la unidad elegida en el filtro.
    $fDesc  = trim((string)($_POST['f_descripcion'] ?? '')); // Lee el texto de búsqueda de la descripción.
    $fFecha = trim((string)($_POST['f_fechaFactura'] ?? '')); // Lee la fecha usada como filtro.

    if ($fNro !== '') {
        $condiciones[] = "`nro_factura` LIKE :f_nro";
        $params[':f_nro'] = '%' . $fNro . '%'; // Los signos % permiten buscar una coincidencia parcial.
    }
    if ($fCod !== '') {
        $condiciones[] = "LOWER(`cod_articulo`) LIKE LOWER(:f_cod)";
        $params[':f_cod'] = '%' . $fCod . '%'; // Busca el texto del código dentro del valor de la columna.
    }
    if ($fUm !== '') {
        $condiciones[] = "`cod_um` = :f_um"; // La unidad se guarda y se filtra por su código (UN, KG...).
        $params[':f_um'] = $fUm;
    }
    if ($fDesc !== '') {
        $condiciones[] = "LOWER(`descripcion`) LIKE LOWER(:f_desc)";
        $params[':f_desc'] = '%' . $fDesc . '%'; // Busca la descripción aunque el texto esté en el medio.
    }
    if ($fFecha !== '') {
        $condiciones[] = "`fecha_factura` LIKE :f_fecha";
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

    $resultado = [
        'renglones' => $filas,
        'cantidad'  => count($filas), // La cantidad se calcula sobre las filas que realmente devolvió la consulta.
    ];
} catch (PDOException $e) {
    Database::logError('Renglones: ' . $e->getMessage());
    $resultado = ['renglones' => [], 'cantidad' => 0];
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
