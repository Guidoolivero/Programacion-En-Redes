<?php
// models/RenglonesModel.php - Modelo para la persistencia y consultas de datos (Patrón MVT)

require_once __DIR__ . '/Database.php';

class RenglonesModel {
    private $pdo; // Guarda la conexión del modelo con MySQL.
    private $cacheColumnas = null; // Conserva el mapa de columnas durante esta petición.

    // Al crear el modelo, obtiene su conexión con MySQL.
    public function __construct() {
        $this->pdo = Database::getConnection(); // Pide la conexión a la clase Database.
    }

    // Devuelve la conexión que ya usa este modelo.
    public function getPdo() {
        return $this->pdo; // Entrega la conexión al código que la solicite.
    }

    /**
     * Busca una columna disponible dentro de $cols que AÚN NO haya sido asignada en $usadas.
     * Al registrar cada columna encontrada en $usadas, es matemáticamente imposible que dos
     * campos lógicos (por ejemplo 'desc' y 'um') apunten a la misma columna física en SQL.
     */
    private function buscarColumna($cols, $candidatos, &$usadas, $permitirParcialCorto = false) {
        // 1. Coincidencia EXACTA (insensible a mayúsculas/minúsculas)
        foreach ($candidatos as $cand) {
            foreach ($cols as $c) {
                if (in_array($c, $usadas, true)) continue; // Salta una columna que ya fue asignada a otro campo.
                if (strcasecmp($c, $cand) === 0) { // Compara los nombres sin distinguir mayúsculas.
                    $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                    return $c; // Devuelve el nombre real que encontró.
                }
            }
        }

        // 2. Coincidencia por palabra/token exacto separando snake_case y camelCase
        //    (ej: 'id_um' o 'codUm' tienen el token 'um', pero 'documento_pdf' o 'numero' NO)
        foreach ($candidatos as $cand) {
            $candLower = strtolower($cand); // Normaliza el nombre candidato para compararlo.
            foreach ($cols as $c) {
                if (in_array($c, $usadas, true)) continue; // Salta una columna que ya fue asignada a otro campo.
                // Separa palabras de nombres como codUm para poder compararlas.
                $separado = preg_replace('/([a-z])([A-Z])/', '$1_$2', $c);
                // Divide el nombre de columna en palabras.
                $tokens = preg_split('/[^a-z0-9]+/i', strtolower($separado), -1, PREG_SPLIT_NO_EMPTY);
                if (in_array($candLower, $tokens, true)) { // Busca una palabra completa, no solo una parte del nombre.
                    $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                    return $c; // Devuelve el nombre real que encontró.
                }
            }
        }

        // 3. Coincidencia parcial de subcadena sólo sobre columnas libres
        foreach ($candidatos as $cand) {
            if ($permitirParcialCorto || strlen($cand) > 3) { // Limita las coincidencias parciales de nombres muy cortos.
                foreach ($cols as $c) {
                    if (in_array($c, $usadas, true)) continue; // Salta una columna que ya fue asignada a otro campo.
                    if (stripos($c, $cand) !== false) { // Busca el candidato dentro del nombre sin distinguir mayúsculas.
                        $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                        return $c; // Devuelve el nombre real que encontró.
                    }
                }
            }
        }

        return null;
    }

    /**
     * Inspecciona los metadatos reales de SHOW COLUMNS FROM renglones_factura
     * evitando reutilizar columnas, pisar claves auto_increment o insertar en columnas generadas.
     */
    private function detectarColumnas() { // private: solo se usa desde esta clase.
        if ($this->cacheColumnas !== null) { // Si ya detectó la estructura, no vuelve a consultar MySQL.
            return $this->cacheColumnas; // Devuelve el mapa de nombres reales de las columnas.
        }

        // SHOW COLUMNS obtiene estructura y tipos, no las facturas.
        $metaFilas = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_ASSOC);
        $cols = []; // Lista de nombres reales de columnas.
        $tipos = []; // Relaciona cada nombre de columna con su tipo de dato.
        $extras = []; // Guarda características como auto_increment o generated.
        $usadas = []; // Recuerda qué columnas ya se asignaron a un dato.

        foreach ($metaFilas as $m) { // Revisa una columna de la estructura en cada vuelta.
            $campo = $m['Field']; // Lee el nombre de la columna.
            $tipo  = strtolower($m['Type'] ?? ''); // Lee su tipo y lo pasa a minúsculas; ?? evita un valor ausente.
            $extra = strtolower($m['Extra'] ?? ''); // Lee sus características adicionales.

            $cols[] = $campo; // Agrega el nombre a la lista de columnas.
            $tipos[$campo] = $tipo; // Guarda el tipo usando el nombre como clave.
            $extras[$campo] = $extra; // Guarda las características usando el nombre como clave.

            // Reservar columnas ID autoincrementales para no confundirlas con datos del formulario
            if (strpos($extra, 'auto_increment') !== false || in_array(strtolower($campo), ['id', 'id_renglon'], true)) {
                $usadas[] = $campo; // Reserva el ID para no confundirlo con otro campo.
            }
        }

        // 1. Detectar primero columna binaria / PDF y reservarla
        $colPdf = $this->buscarColumna($cols, [
            'documentoPdf', 'documento_pdf', 'archivo_pdf', 'pdf_binario', 'pdf', 'binario', 'documento', 'archivo'
        ], $usadas);
        if ($colPdf === null) { // Si no reconoció el PDF por nombre, prueba por tipo binario.
            foreach ($cols as $c) {
                if (!in_array($c, $usadas, true) && (strpos($tipos[$c], 'blob') !== false || strpos($tipos[$c], 'binary') !== false)) {
                    $colPdf = $c; // Esta columna libre será la del archivo PDF.
                    $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                    break;
                }
            }
        }

        // 2. Detectar columnas principales inequívocas
        $colNro   = $this->buscarColumna($cols, ['nroFactura', 'nro_factura', 'numero_factura', 'num_factura', 'id_factura', 'factura', 'nro', 'numero'], $usadas); // Busca cuál es la columna real del número de factura.
        $colFecha = $this->buscarColumna($cols, ['fechaFactura', 'fecha_factura', 'fec_factura', 'fecha'], $usadas); // Busca la columna real de fecha entre los nombres posibles.
        if ($colFecha === null) { // Si no reconoció la fecha por nombre, prueba por tipo date.
            foreach ($cols as $c) {
                if (!in_array($c, $usadas, true) && strpos($tipos[$c], 'date') !== false) {
                    $colFecha = $c; // Esta columna libre será la fecha de la factura.
                    $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                    break;
                }
            }
        }

        $colCod  = $this->buscarColumna($cols, ['codArticulo', 'cod_articulo', 'codigo_articulo', 'cod_art', 'codigo_art', 'id_articulo', 'id_art', 'articulo', 'art'], $usadas); // Busca la columna real del código de artículo.
        $colDesc = $this->buscarColumna($cols, ['descripcion', 'descripcion_articulo', 'detalle', 'concepto', 'desc'], $usadas); // Busca la columna real de descripción.
        $colCant = $this->buscarColumna($cols, ['cantidad', 'cant'], $usadas); // Busca la columna real de cantidad.
        $colPrec = $this->buscarColumna($cols, ['precioUnitario', 'precio_unitario', 'precio_unit', 'p_unitario', 'precio'], $usadas); // Busca la columna real de precio unitario.
        $colImp  = $this->buscarColumna($cols, ['importeRenglon', 'importe_renglon', 'importe_total', 'subtotal', 'importe', 'total'], $usadas); // Busca la columna real de importe.

        // 3. Detectar Unidad de Medida únicamente entre las columnas que quedaron libres
        $colUm = $this->buscarColumna($cols, [
            'unidadMedida', 'unidad_medida', 'codUm', 'cod_um', 'codigo_um',
            'id_unidad', 'unidad_id', 'id_um', 'um_id', 'fk_unidad', 'fk_um',
            'u_medida', 'u_med', 'medida', 'cod_medida', 'id_medida',
            'udm', 'unid', 'um', 'unidad'
        ], $usadas, true);

        // Si aún no coincidió por nombre pero queda alguna columna libre (no BLOB ni generada), usarla como UM
        if ($colUm === null) { // Último recurso: busca una columna libre para la unidad.
            foreach ($cols as $c) {
                if (in_array($c, $usadas, true)) continue; // Salta una columna que ya fue asignada a otro campo.
                // Una columna binaria no sirve como unidad de medida.
                if (strpos($tipos[$c], 'blob') !== false || strpos($tipos[$c], 'binary') !== false) continue;
                // No usa una columna calculada como unidad de medida.
                if (strpos($extras[$c], 'generated') !== false) continue;
                $colUm = $c; // Conserva el nombre elegido para la unidad.
                $usadas[] = $c; // Reserva la columna para que no vuelva a elegirse.
                break;
            }
        }

        // Indica si la base espera un ID numérico para la unidad.
        $umEsInt = ($colUm !== null && isset($tipos[$colUm]) && preg_match('/int/i', $tipos[$colUm]));
        // Indica si MySQL calcula el importe automáticamente.
        $impGenerada = ($colImp !== null && isset($extras[$colImp]) && strpos($extras[$colImp], 'generated') !== false);

        $this->cacheColumnas = [ // Arma el mapa que luego usarán SELECT, INSERT, UPDATE y DELETE.
            'nro'          => $colNro,
            'fecha'        => $colFecha,
            'cod'          => $colCod,
            'desc'         => $colDesc,
            'um'           => $colUm,
            'cant'         => $colCant,
            'prec'         => $colPrec,
            'imp'          => $colImp,
            'pdf'          => $colPdf, // Nombre real de la columna donde queda el binario del PDF.
            'um_es_int'    => $umEsInt,
            'imp_generada' => $impGenerada,
        ];

        return $this->cacheColumnas; // Devuelve el mapa de nombres reales de las columnas.
    }

    /**
     * Construye mapas de conversión entre id numérico, código ('UN', 'MT') y descripción
     * leyendo la tabla unidades_medida para compatibilidad total (FK entera o VARCHAR).
     */
    private function obtenerInfoUnidades() {
        $mapaMostrar = []; // Cualquier valor en BD -> código para mostrar/seleccionar en frontend
        $filas = $this->getUnidades(); // Lee la tabla auxiliar de unidades.

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

        return ['filas' => $filas, 'mapaMostrar' => $mapaMostrar];
    }

    /**
     * Convierte el valor de unidadMedida enviado por el formulario ('UN', 'MT', etc.)
     * al tipo que espera la columna real en renglones_factura (ID numérico si es INT, o código si es VARCHAR).
     */
    private function resolverValorUmParaBD($umRecibida, $umEsInt) {
        $umStr = trim((string)$umRecibida); // Normaliza la unidad que llegó del formulario.
        if (!$umEsInt || $umStr === '' || ctype_digit($umStr)) {
            return $umStr;
        }

        $filas = $this->getUnidades(); // Lee la tabla auxiliar de unidades.
        foreach ($filas as $u) {
            $vals    = array_values($u); // Permite consultar los valores también por posición.
            // Intenta reconocer el identificador numérico de la unidad.
            $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? ($vals[0] ?? null);
            // Intenta reconocer el código de unidad, por ejemplo UN.
            $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? '';
            // Intenta reconocer la descripción visible de la unidad.
            $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? '';

            if (
                ($codVal !== '' && strcasecmp((string)$codVal, $umStr) === 0) ||
                ($descVal !== '' && strcasecmp((string)$descVal, $umStr) === 0)
            ) {
                return $idVal; // Devuelve el ID que corresponde al código recibido.
            }
        }

        return $umStr;
    }

    /**
     * Verifica si la combinación (nroFactura + codArticulo) ya existe en renglones_factura.
     * Permite repetir el mismo codArticulo en distintas facturas y varios artículos en una misma factura.
     */
    public function existeRenglon($nroFactura, $codArticulo) {
        if (!$this->pdo) return false;
        $nroLimpio = trim((string)$nroFactura); // Normaliza el número antes de buscar duplicados.
        $codLimpio = trim((string)$codArticulo); // Normaliza el artículo antes de buscar duplicados.
        if ($nroLimpio === '' || $codLimpio === '') return false; // Normaliza el número antes de buscar duplicados.

        try {
            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.
            if (empty($c['nro']) || empty($c['cod'])) return false;

            // Cuenta coincidencias de la clave factura + artículo.
            $sql = "SELECT COUNT(*) FROM renglones_factura WHERE LOWER(TRIM(CAST(`{$c['nro']}` AS CHAR))) = LOWER(:nro) AND LOWER(TRIM(CAST(`{$c['cod']}` AS CHAR))) = LOWER(:cod)";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $stmt->bindValue(':nro', $nroLimpio); // Asocia el número de factura al parámetro de la consulta.
            $stmt->bindValue(':cod', $codLimpio); // Asocia el código de artículo al parámetro de la consulta.
            $stmt->execute(); // Ejecuta el SELECT que comprueba si la clave está repetida.
            return ((int)$stmt->fetchColumn()) > 0; // Devuelve true si la combinación ya existe.
        } catch (PDOException $e) {
            Database::logError('Validar renglón: ' . $e->getMessage());
            return false;
        }
    }

    // SELECT de las opciones del selector de unidades.
    public function getUnidades() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida"); // query ejecuta directamente el SELECT de unidades.
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Database::logError('Unidades: ' . $e->getMessage());
            return [];
        }
    }

    // SELECT del listado: aplica filtros y orden antes de devolver las filas.
    public function getRenglones($filtros = [], $orden = 'nroFactura') {
        if (!$this->pdo) return ['renglones' => [], 'cantidad' => 0];

        $camposPermitidos = [ // Solo estos campos pueden usarse para ordenar.
            'nroFactura', 'codArticulo', 'unidadMedida',
            'descripcion', 'fechaFactura', 'cantidad',
            'precioUnitario', 'importeRenglon'
        ];
        if (!in_array($orden, $camposPermitidos, true)) { // Un orden desconocido se reemplaza por nroFactura.
            $orden = 'nroFactura';
        }

        try {
            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.
            $infoUm = $this->obtenerInfoUnidades();
            $mapaUmTexto = $infoUm['mapaMostrar'];

            $mapaOrden = [ // Relaciona el campo del navegador con la columna de MySQL.
                'nroFactura'     => $c['nro'],
                'codArticulo'    => $c['cod'],
                'unidadMedida'   => $c['um'] ?? $c['nro'],
                'descripcion'    => $c['desc'],
                'fechaFactura'   => $c['fecha'],
                'cantidad'       => $c['cant'],
                'precioUnitario' => $c['prec'],
                'importeRenglon' => $c['imp'] ?? $c['nro'],
            ];
            $colOrden = $mapaOrden[$orden] ?? $c['nro'];

            $condiciones = []; // Acumula las condiciones que formarán el WHERE.
            $params = []; // Guarda los valores que se vincularán a los parámetros SQL.

            $fNro   = trim((string)($filtros['f_nroFactura'] ?? '')); // Lee el filtro de factura y quita espacios externos.
            // Lee el filtro de artículo y quita espacios externos.
            $fCod   = trim((string)($filtros['f_codArticulo'] ?? ''));
            $fUm    = trim((string)($filtros['f_unidadMedida'] ?? '')); // Lee la unidad elegida en el filtro.
            $fDesc  = trim((string)($filtros['f_descripcion'] ?? '')); // Lee el texto de búsqueda de la descripción.
            $fFecha = trim((string)($filtros['f_fechaFactura'] ?? '')); // Lee la fecha usada como filtro.

            if ($fNro !== '' && $c['nro']) {
                $condiciones[] = "LOWER(CAST(`{$c['nro']}` AS CHAR)) LIKE LOWER(:f_nro)";
                $params[':f_nro'] = '%' . $fNro . '%'; // Los signos % permiten buscar una coincidencia parcial.
            }
            if ($fCod !== '' && $c['cod']) {
                $condiciones[] = "LOWER(CAST(`{$c['cod']}` AS CHAR)) LIKE LOWER(:f_cod)";
                $params[':f_cod'] = '%' . $fCod . '%'; // Busca el texto del código dentro del valor de la columna.
            }
            if ($fUm !== '' && $c['um']) {
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
                $condiciones[] = "LOWER(TRIM(CAST(`{$c['um']}` AS CHAR))) IN (" . implode(', ', $phUm) . ")";
            }
            if ($fDesc !== '' && $c['desc']) {
                $condiciones[] = "LOWER(CAST(`{$c['desc']}` AS CHAR)) LIKE LOWER(:f_desc)";
                $params[':f_desc'] = '%' . $fDesc . '%'; // Busca la descripción aunque el texto esté en el medio.
            }
            if ($fFecha !== '' && $c['fecha']) {
                $condiciones[] = "CAST(`{$c['fecha']}` AS CHAR) LIKE :f_fecha";
                $params[':f_fecha'] = $fFecha . '%'; // El prefijo de fecha permite comparar también fechas con hora.
            }

            $selUm  = $c['um']  ? "`{$c['um']}` AS unidadMedida" : "'' AS unidadMedida";
            $selImp = $c['imp'] ? "`{$c['imp']}` AS importeRenglon" : "(`{$c['cant']}` * `{$c['prec']}`) AS importeRenglon";

            // Arma el SELECT; AS da a los resultados los nombres usados por JavaScript.
            $sql = "SELECT `{$c['nro']}` AS nroFactura, `{$c['fecha']}` AS fechaFactura, `{$c['cod']}` AS codArticulo, `{$c['desc']}` AS descripcion, $selUm, `{$c['cant']}` AS cantidad, `{$c['prec']}` AS precioUnitario, $selImp FROM renglones_factura";
            if (!empty($condiciones)) {
                $sql .= " WHERE " . implode(' AND ', $condiciones); // Une con AND todos los filtros que se completaron.
            }
            if ($colOrden) {
                $sql .= " ORDER BY `$colOrden` ASC"; // Agrega la columna de ordenamiento en orden ascendente.
            }

            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
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

            return [
                'renglones' => $filas,
                'cantidad'  => count($filas), // La cantidad se calcula sobre las filas que realmente devolvió la consulta.
            ];
        } catch (PDOException $e) {
            Database::logError('Renglones: ' . $e->getMessage());
            return ['renglones' => [], 'cantidad' => 0];
        }
    }

    // ALTA: valida la clave, inserta el registro y guarda el PDF opcional.
    public function insertarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = trim((string)($datos['nroFactura'] ?? ''));
        $fecha   = trim((string)($datos['fechaFactura'] ?? ''));
        $cod     = trim((string)($datos['codArticulo'] ?? ''));
        $desc    = trim((string)($datos['descripcion'] ?? ''));
        $um      = trim((string)($datos['unidadMedida'] ?? ''));
        $cant    = (float)($datos['cantidad'] ?? 0); // Convierte la cantidad recibida a un número decimal.
        $precio  = (float)($datos['precioUnitario'] ?? 0); // Convierte el precio recibido a un número decimal.
        $importe = $cant * $precio; // Calcula el importe del renglón en el servidor.

        // Comienza el mensaje que se mostrará en el navegador.
        $traza .= "Respuesta del servidor al alta. Entradas recibidas en el req http:\n";
        $traza .= "nroFactura: " . $nro . "\n";
        $traza .= "codArticulo: " . $cod . "\n";
        $traza .= "fechaFactura: " . $fecha . "\n";
        $traza .= "unidadMedida: " . $um . "\n";
        $traza .= "descripcion: " . $desc . "\n";
        $traza .= "cantidad: " . $cant . "\n";
        $traza .= "precioUnitario: " . $precio . "\n";
        $traza .= "importeRenglon: " . $importe . "\n";

        try {
            // Se permite repetir codArticulo en distintas facturas, pero no duplicar la clave compuesta (nroFactura + codArticulo)
            // El servidor vuelve a rechazar una clave repetida aunque falle el control JS.
            if ($this->existeRenglon($nro, $cod)) {
                $msgDup = "En la factura '$nro' ya está cargado el artículo '$cod'. (Podés repetir '$cod' en otra factura distinta o modificar ese renglón).";
                Database::logError('Alta rechazada (renglón duplicado en misma factura): ' . $msgDup);
                $traza .= "\nERROR DE VALIDACIÓN: " . $msgDup;
                return false;
            }

            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.
            // Convierte la unidad al código o ID que espera la columna.
            $valorUmBD = $this->resolverValorUmParaBD($um, $c['um_es_int']);

            // Construcción dinámica garantizando unicidad absoluta de columnas en INSERT
            $mapaCampos = [ // Relaciona columna, parámetro y valor de cada campo del INSERT.
                ['col' => $c['nro'],   'ph' => ':nroFactura',     'val' => $nro],
                ['col' => $c['fecha'], 'ph' => ':fechaFactura',   'val' => $fecha],
                ['col' => $c['cod'],   'ph' => ':codArticulo',    'val' => $cod],
                ['col' => $c['desc'],  'ph' => ':descripcion',    'val' => $desc],
                ['col' => $c['um'],    'ph' => ':unidadMedida',   'val' => $valorUmBD],
                ['col' => $c['cant'],  'ph' => ':cantidad',       'val' => $cant],
                ['col' => $c['prec'],  'ph' => ':precioUnitario', 'val' => $precio],
            ];

            // Sólo incluir importe si la columna existe y NO es generada automáticamente por MySQL
            // Solo escribe el importe si MySQL no lo calcula automáticamente.
            if (!empty($c['imp']) && empty($c['imp_generada'])) {
                $mapaCampos[] = ['col' => $c['imp'], 'ph' => ':importeRenglon', 'val' => $importe];
            }

            $colsSql = []; // Nombres de columnas que se incluirán en el INSERT.
            $phsSql  = []; // Marcadores SQL, como :nroFactura, que recibirán los valores.
            $binds   = []; // Valores que se vincularán a los marcadores SQL.
            $colsVistas = []; // Evita repetir una columna al construir el INSERT.

            foreach ($mapaCampos as $item) {
                $nombreCol = $item['col'];
                if (!empty($nombreCol) && !isset($colsVistas[$nombreCol])) {
                    $colsVistas[$nombreCol] = true;
                    $colsSql[] = "`$nombreCol`";
                    $phsSql[]  = $item['ph'];
                    $binds[$item['ph']] = $item['val'];
                }
            }

            // INSERT: construye el alta de un renglón nuevo.
            $sql = "INSERT INTO renglones_factura (" . implode(', ', $colsSql) . ") VALUES (" . implode(', ', $phsSql) . ")";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute(); // INSERT: ejecuta el alta del registro.
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nAlta de renglón $nro / $cod correcta";

            // Manejo de archivo PDF binario si se envió
            if ($archivoPdf && isset($archivoPdf['tmp_name']) && !empty($archivoPdf['name'])) {
                try {
                    if ($c['pdf']) {
                        // Lee el contenido binario del archivo temporal que recibió PHP.
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        // UPDATE PDF: prepara el guardado del binario en la fila indicada.
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `{$c['pdf']}` = :doc WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
                        //PDO::PARAM_LOB: Es el punto clave. 
                        //Le indica a PDO que el contenido de $contenidoPdf es un LOB (Large Object / Objeto Grande)
                        //bindParam() es un método de PDO en PHP
                        //no guarda el valor actual de la variable, sino una referencia en memoria a ella. 
                        //El valor real no se lee hasta el momento exacto en que llamas a $stmt->execute().
                        // PDO::PARAM_LOB indica que se envía contenido binario.
                        $stmtPdf->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB);
                        $stmtPdf->bindParam(':nro', $nro);
                        $stmtPdf->bindParam(':cod', $cod);
                        $stmtPdf->execute(); // Ejecuta el UPDATE que guarda o reemplaza el documento PDF.
                        $traza .= "\nDocumento PDF actualizado correctamente.";
                    }
                } catch (Exception $ePdf) {
                    Database::logError("Error guardando PDF alta: " . $ePdf->getMessage());
                }
            }

            return true;
        } catch (PDOException $e) {
            Database::logError('Alta: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // MODIFICAR: actualiza los campos de una fila y su PDF opcional.
    public function actualizarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = trim((string)($datos['nroFactura'] ?? ''));
        $fecha   = trim((string)($datos['fechaFactura'] ?? ''));
        $cod     = trim((string)($datos['codArticulo'] ?? ''));
        $desc    = trim((string)($datos['descripcion'] ?? ''));
        $um      = trim((string)($datos['unidadMedida'] ?? ''));
        $cant    = (float)($datos['cantidad'] ?? 0); // Convierte la cantidad recibida a un número decimal.
        $precio  = (float)($datos['precioUnitario'] ?? 0); // Convierte el precio recibido a un número decimal.
        $importe = $cant * $precio; // Calcula el importe del renglón en el servidor.

        $traza .= "Parte Modificacion simple de datos\n";
        $traza .= "nroFactura: $nro / codArticulo: $cod\n";

        try {
            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.
            // Convierte la unidad al código o ID que espera la columna.
            $valorUmBD = $this->resolverValorUmParaBD($um, $c['um_es_int']);

            $mapaSets = [ // Define qué campos pueden cambiar en el UPDATE.
                ['col' => $c['fecha'], 'ph' => ':fechaFactura',   'val' => $fecha],
                ['col' => $c['desc'],  'ph' => ':descripcion',    'val' => $desc],
                ['col' => $c['um'],    'ph' => ':unidadMedida',   'val' => $valorUmBD],
                ['col' => $c['cant'],  'ph' => ':cantidad',       'val' => $cant],
                ['col' => $c['prec'],  'ph' => ':precioUnitario', 'val' => $precio],
            ];

            // Solo escribe el importe si MySQL no lo calcula automáticamente.
            if (!empty($c['imp']) && empty($c['imp_generada'])) {
                $mapaSets[] = ['col' => $c['imp'], 'ph' => ':importeRenglon', 'val' => $importe];
            }

            $setsSql = [];
            $binds   = []; // Valores que se vincularán a los marcadores SQL.
            $colsVistas = [
                $c['nro'] => true,
                $c['cod'] => true,
            ];

            foreach ($mapaSets as $item) {
                $nombreCol = $item['col'];
                if (!empty($nombreCol) && !isset($colsVistas[$nombreCol])) {
                    $colsVistas[$nombreCol] = true;
                    $setsSql[] = "`$nombreCol`=" . $item['ph'];
                    $binds[$item['ph']] = $item['val'];
                }
            }

            $binds[':nroFactura']  = $nro; // La factura identifica la fila; no se cambia en el SET.
            $binds[':codArticulo'] = $cod; // El artículo completa la clave del registro que se modifica.

            // UPDATE: arma los cambios y limita la fila por factura + artículo.
            $sql = "UPDATE renglones_factura SET " . implode(', ', $setsSql) . " WHERE `{$c['nro']}`=:nroFactura AND `{$c['cod']}`=:codArticulo";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
                //$binds (El arreglo de datos)
                //$ph (Placeholder / Marcador): Contiene la clave, por ejemplo ':id'.
                //$val (Value / Valor): Contiene el dato real a insertar, por ejemplo 15.
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute(); // UPDATE: ejecuta los cambios en los campos.
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nModificacion de renglón $nro / $cod correcta";

            // Manejo de archivo PDF binario si se envió
            if ($archivoPdf && isset($archivoPdf['tmp_name']) && !empty($archivoPdf['name'])) {
                try {
                    if ($c['pdf']) {
                        // Lee el contenido binario del archivo temporal que recibió PHP.
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        // UPDATE PDF: prepara el guardado del binario en la fila indicada.
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `{$c['pdf']}` = :doc WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
                        // PDO::PARAM_LOB indica que se envía contenido binario.
                        $stmtPdf->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB);
                        $stmtPdf->bindParam(':nro', $nro);
                        $stmtPdf->bindParam(':cod', $cod);
                        $stmtPdf->execute(); // Ejecuta el UPDATE que guarda o reemplaza el documento PDF.
                        $traza .= "\nDocumento PDF actualizado correctamente.";
                    }
                } catch (Exception $ePdf) {
                    Database::logError("Error actualizando PDF modi: " . $ePdf->getMessage());
                }
            }

            return true;
        } catch (PDOException $e) {
            Database::logError('Modi: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // BORRAR: elimina la fila indicada por factura + artículo.
    public function eliminarRenglon($nroFactura, $codArticulo, &$traza = '') {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        try {
            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.

            // DELETE: borra solo el renglón que coincide con los dos datos de la clave.
            $sql = "DELETE FROM renglones_factura WHERE `{$c['nro']}`=:nroFactura AND `{$c['cod']}`=:codArticulo";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "Preparacion exitosa!\n";

            $stmt->bindParam(':nroFactura', $nroFactura);
            $stmt->bindParam(':codArticulo', $codArticulo);
            $traza .= "Binding exitoso!\n";

            $stmt->execute(); // DELETE: ejecuta el borrado definitivo de la fila.
            $traza .= "Ejecucion exitosa!\n";
            $traza .= "Baja de renglón $nroFactura / $codArticulo correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Baja: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // VISTA PREVIA: lee el binario y lo convierte a Base64 para el JSON.
    public function getPdfBase64($nroFactura, $codArticulo) {
        if (!$this->pdo) return null;
        try {
            $c = $this->detectarColumnas(); // Obtiene el mapa de nombres reales de la tabla.
            if (!$c['pdf']) return null;

            // SELECT PDF: busca únicamente el binario del renglón elegido.
            $stmt = $this->pdo->prepare("SELECT `{$c['pdf']}` FROM renglones_factura WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
            $stmt->bindParam(':nro', $nroFactura);
            $stmt->bindParam(':cod', $codArticulo);
            $stmt->execute(); // Ejecuta el SELECT del archivo PDF.
            $fila = $stmt->fetch(); // Obtiene una sola fila de la respuesta SQL.
            if ($fila && !empty($fila[$c['pdf']])) {
                return base64_encode($fila[$c['pdf']]); // Convierte el binario a texto Base64 para poder enviarlo en JSON.
            }
            return null;
        } catch (PDOException $e) {
            Database::logError('traeBinario: ' . $e->getMessage());
            return null;
        }
    }
}
