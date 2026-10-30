<?php
// models/RenglonesModel.php - Modelo para la persistencia y consultas de datos (Patrón MVT)

require_once __DIR__ . '/Database.php';

class RenglonesModel {
    private $pdo;
    private $cacheColumnas = null;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getPdo() {
        return $this->pdo;
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
                if (in_array($c, $usadas, true)) continue;
                if (strcasecmp($c, $cand) === 0) {
                    $usadas[] = $c;
                    return $c;
                }
            }
        }

        // 2. Coincidencia por palabra/token exacto separando snake_case y camelCase
        //    (ej: 'id_um' o 'codUm' tienen el token 'um', pero 'documento_pdf' o 'numero' NO)
        foreach ($candidatos as $cand) {
            $candLower = strtolower($cand);
            foreach ($cols as $c) {
                if (in_array($c, $usadas, true)) continue;
                $separado = preg_replace('/([a-z])([A-Z])/', '$1_$2', $c);
                $tokens = preg_split('/[^a-z0-9]+/i', strtolower($separado), -1, PREG_SPLIT_NO_EMPTY);
                if (in_array($candLower, $tokens, true)) {
                    $usadas[] = $c;
                    return $c;
                }
            }
        }

        // 3. Coincidencia parcial de subcadena sólo sobre columnas libres
        foreach ($candidatos as $cand) {
            if ($permitirParcialCorto || strlen($cand) > 3) {
                foreach ($cols as $c) {
                    if (in_array($c, $usadas, true)) continue;
                    if (stripos($c, $cand) !== false) {
                        $usadas[] = $c;
                        return $c;
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
    private function detectarColumnas() {
        if ($this->cacheColumnas !== null) {
            return $this->cacheColumnas;
        }

        $metaFilas = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_ASSOC);
        $cols = [];
        $tipos = [];
        $extras = [];
        $usadas = [];

        foreach ($metaFilas as $m) {
            $campo = $m['Field'];
            $tipo  = strtolower($m['Type'] ?? '');
            $extra = strtolower($m['Extra'] ?? '');

            $cols[] = $campo;
            $tipos[$campo] = $tipo;
            $extras[$campo] = $extra;

            // Reservar columnas ID autoincrementales para no confundirlas con datos del formulario
            if (strpos($extra, 'auto_increment') !== false || in_array(strtolower($campo), ['id', 'id_renglon'], true)) {
                $usadas[] = $campo;
            }
        }

        // 1. Detectar primero columna binaria / PDF y reservarla
        $colPdf = $this->buscarColumna($cols, [
            'documentoPdf', 'documento_pdf', 'archivo_pdf', 'pdf_binario', 'pdf', 'binario', 'documento', 'archivo'
        ], $usadas);
        if ($colPdf === null) {
            foreach ($cols as $c) {
                if (!in_array($c, $usadas, true) && (strpos($tipos[$c], 'blob') !== false || strpos($tipos[$c], 'binary') !== false)) {
                    $colPdf = $c;
                    $usadas[] = $c;
                    break;
                }
            }
        }

        // 2. Detectar columnas principales inequívocas
        $colNro   = $this->buscarColumna($cols, ['nroFactura', 'nro_factura', 'numero_factura', 'num_factura', 'id_factura', 'factura', 'nro', 'numero'], $usadas);
        $colFecha = $this->buscarColumna($cols, ['fechaFactura', 'fecha_factura', 'fec_factura', 'fecha'], $usadas);
        if ($colFecha === null) {
            foreach ($cols as $c) {
                if (!in_array($c, $usadas, true) && strpos($tipos[$c], 'date') !== false) {
                    $colFecha = $c;
                    $usadas[] = $c;
                    break;
                }
            }
        }

        $colCod  = $this->buscarColumna($cols, ['codArticulo', 'cod_articulo', 'codigo_articulo', 'cod_art', 'codigo_art', 'id_articulo', 'id_art', 'articulo', 'art'], $usadas);
        $colDesc = $this->buscarColumna($cols, ['descripcion', 'descripcion_articulo', 'detalle', 'concepto', 'desc'], $usadas);
        $colCant = $this->buscarColumna($cols, ['cantidad', 'cant'], $usadas);
        $colPrec = $this->buscarColumna($cols, ['precioUnitario', 'precio_unitario', 'precio_unit', 'p_unitario', 'precio'], $usadas);
        $colImp  = $this->buscarColumna($cols, ['importeRenglon', 'importe_renglon', 'importe_total', 'subtotal', 'importe', 'total'], $usadas);

        // 3. Detectar Unidad de Medida únicamente entre las columnas que quedaron libres
        $colUm = $this->buscarColumna($cols, [
            'unidadMedida', 'unidad_medida', 'codUm', 'cod_um', 'codigo_um',
            'id_unidad', 'unidad_id', 'id_um', 'um_id', 'fk_unidad', 'fk_um',
            'u_medida', 'u_med', 'medida', 'cod_medida', 'id_medida',
            'udm', 'unid', 'um', 'unidad'
        ], $usadas, true);

        // Si aún no coincidió por nombre pero queda alguna columna libre (no BLOB ni generada), usarla como UM
        if ($colUm === null) {
            foreach ($cols as $c) {
                if (in_array($c, $usadas, true)) continue;
                if (strpos($tipos[$c], 'blob') !== false || strpos($tipos[$c], 'binary') !== false) continue;
                if (strpos($extras[$c], 'generated') !== false) continue;
                $colUm = $c;
                $usadas[] = $c;
                break;
            }
        }

        $umEsInt = ($colUm !== null && isset($tipos[$colUm]) && preg_match('/int/i', $tipos[$colUm]));
        $impGenerada = ($colImp !== null && isset($extras[$colImp]) && strpos($extras[$colImp], 'generated') !== false);

        $this->cacheColumnas = [
            'nro'          => $colNro,
            'fecha'        => $colFecha,
            'cod'          => $colCod,
            'desc'         => $colDesc,
            'um'           => $colUm,
            'cant'         => $colCant,
            'prec'         => $colPrec,
            'imp'          => $colImp,
            'pdf'          => $colPdf,
            'um_es_int'    => $umEsInt,
            'imp_generada' => $impGenerada,
        ];

        return $this->cacheColumnas;
    }

    /**
     * Construye mapas de conversión entre id numérico, código ('UN', 'MT') y descripción
     * leyendo la tabla unidades_medida para compatibilidad total (FK entera o VARCHAR).
     */
    private function obtenerInfoUnidades() {
        $mapaMostrar = []; // Cualquier valor en BD -> código para mostrar/seleccionar en frontend
        $filas = $this->getUnidades();

        foreach ($filas as $u) {
            $vals    = array_values($u);
            $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
            $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
            $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? $codVal);
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
        $umStr = trim((string)$umRecibida);
        if (!$umEsInt || $umStr === '' || ctype_digit($umStr)) {
            return $umStr;
        }

        $filas = $this->getUnidades();
        foreach ($filas as $u) {
            $vals    = array_values($u);
            $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? ($vals[0] ?? null);
            $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? '';
            $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? '';

            if (
                ($codVal !== '' && strcasecmp((string)$codVal, $umStr) === 0) ||
                ($descVal !== '' && strcasecmp((string)$descVal, $umStr) === 0)
            ) {
                return $idVal;
            }
        }

        return $umStr;
    }

    public function getUnidades() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Database::logError('Unidades: ' . $e->getMessage());
            return [];
        }
    }

    public function getRenglones($filtros = [], $orden = 'nroFactura') {
        if (!$this->pdo) return ['renglones' => [], 'cantidad' => 0];

        $camposPermitidos = [
            'nroFactura', 'codArticulo', 'unidadMedida',
            'descripcion', 'fechaFactura', 'cantidad',
            'precioUnitario', 'importeRenglon'
        ];
        if (!in_array($orden, $camposPermitidos, true)) {
            $orden = 'nroFactura';
        }

        try {
            $c = $this->detectarColumnas();
            $infoUm = $this->obtenerInfoUnidades();
            $mapaUmTexto = $infoUm['mapaMostrar'];

            $mapaOrden = [
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

            $condiciones = [];
            $params = [];

            if (!empty($filtros['f_nroFactura']) && $c['nro']) {
                $condiciones[] = "`{$c['nro']}` LIKE :f_nro";
                $params[':f_nro'] = '%' . $filtros['f_nroFactura'] . '%';
            }
            if (!empty($filtros['f_codArticulo']) && $c['cod']) {
                $condiciones[] = "`{$c['cod']}` LIKE :f_cod";
                $params[':f_cod'] = '%' . $filtros['f_codArticulo'] . '%';
            }
            if (!empty($filtros['f_unidadMedida']) && $c['um']) {
                $fUm = trim((string)$filtros['f_unidadMedida']);
                $equivalencias = [$fUm];
                foreach ($infoUm['filas'] as $u) {
                    $vals    = array_values($u);
                    $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
                    $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
                    $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? '');
                    if (
                        ($idVal !== null && strcasecmp((string)$idVal, $fUm) === 0) ||
                        ($codVal !== '' && strcasecmp((string)$codVal, $fUm) === 0) ||
                        ($descVal !== '' && strcasecmp((string)$descVal, $fUm) === 0)
                    ) {
                        if ($idVal !== null) $equivalencias[] = (string)$idVal;
                        if ($codVal !== '')  $equivalencias[] = (string)$codVal;
                        if ($descVal !== '') $equivalencias[] = (string)$descVal;
                    }
                }
                $equivalencias = array_values(array_unique(array_filter($equivalencias, fn($v) => $v !== '')));
                $phUm = [];
                foreach ($equivalencias as $idx => $eqVal) {
                    $key = ":f_um_$idx";
                    $phUm[] = $key;
                    $params[$key] = $eqVal;
                }
                $condiciones[] = "`{$c['um']}` IN (" . implode(', ', $phUm) . ")";
            }
            if (!empty($filtros['f_descripcion']) && $c['desc']) {
                $condiciones[] = "`{$c['desc']}` LIKE :f_desc";
                $params[':f_desc'] = '%' . $filtros['f_descripcion'] . '%';
            }
            if (!empty($filtros['f_fechaFactura']) && $c['fecha']) {
                $condiciones[] = "`{$c['fecha']}` = :f_fecha";
                $params[':f_fecha'] = $filtros['f_fechaFactura'];
            }

            $selUm  = $c['um']  ? "`{$c['um']}` AS unidadMedida" : "'' AS unidadMedida";
            $selImp = $c['imp'] ? "`{$c['imp']}` AS importeRenglon" : "(`{$c['cant']}` * `{$c['prec']}`) AS importeRenglon";

            $sql = "SELECT `{$c['nro']}` AS nroFactura, `{$c['fecha']}` AS fechaFactura, `{$c['cod']}` AS codArticulo, `{$c['desc']}` AS descripcion, $selUm, `{$c['cant']}` AS cantidad, `{$c['prec']}` AS precioUnitario, $selImp FROM renglones_factura";
            if (!empty($condiciones)) {
                $sql .= " WHERE " . implode(' AND ', $condiciones);
            }
            if ($colOrden) {
                $sql .= " ORDER BY `$colOrden` ASC";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

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
                'cantidad'  => count($filas),
            ];
        } catch (PDOException $e) {
            Database::logError('Renglones: ' . $e->getMessage());
            return ['renglones' => [], 'cantidad' => 0];
        }
    }

    public function insertarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = $datos['nroFactura'] ?? '';
        $fecha   = $datos['fechaFactura'] ?? '';
        $cod     = $datos['codArticulo'] ?? '';
        $desc    = $datos['descripcion'] ?? '';
        $um      = $datos['unidadMedida'] ?? '';
        $cant    = (float)($datos['cantidad'] ?? 0);
        $precio  = (float)($datos['precioUnitario'] ?? 0);
        $importe = $cant * $precio;

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
            $c = $this->detectarColumnas();
            $valorUmBD = $this->resolverValorUmParaBD($um, $c['um_es_int']);

            // Construcción dinámica garantizando unicidad absoluta de columnas en INSERT
            $mapaCampos = [
                ['col' => $c['nro'],   'ph' => ':nroFactura',     'val' => $nro],
                ['col' => $c['fecha'], 'ph' => ':fechaFactura',   'val' => $fecha],
                ['col' => $c['cod'],   'ph' => ':codArticulo',    'val' => $cod],
                ['col' => $c['desc'],  'ph' => ':descripcion',    'val' => $desc],
                ['col' => $c['um'],    'ph' => ':unidadMedida',   'val' => $valorUmBD],
                ['col' => $c['cant'],  'ph' => ':cantidad',       'val' => $cant],
                ['col' => $c['prec'],  'ph' => ':precioUnitario', 'val' => $precio],
            ];

            // Sólo incluir importe si la columna existe y NO es generada automáticamente por MySQL
            if (!empty($c['imp']) && empty($c['imp_generada'])) {
                $mapaCampos[] = ['col' => $c['imp'], 'ph' => ':importeRenglon', 'val' => $importe];
            }

            $colsSql = [];
            $phsSql  = [];
            $binds   = [];
            $colsVistas = [];

            foreach ($mapaCampos as $item) {
                $nombreCol = $item['col'];
                if (!empty($nombreCol) && !isset($colsVistas[$nombreCol])) {
                    $colsVistas[$nombreCol] = true;
                    $colsSql[] = "`$nombreCol`";
                    $phsSql[]  = $item['ph'];
                    $binds[$item['ph']] = $item['val'];
                }
            }

            $sql = "INSERT INTO renglones_factura (" . implode(', ', $colsSql) . ") VALUES (" . implode(', ', $phsSql) . ")";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val);
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute();
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nAlta de renglón $nro / $cod correcta";

            // Manejo de archivo PDF binario si se envió
            if ($archivoPdf && isset($archivoPdf['tmp_name']) && !empty($archivoPdf['name'])) {
                try {
                    if ($c['pdf']) {
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `{$c['pdf']}` = :doc WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
                        $stmtPdf->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB);
                        $stmtPdf->bindParam(':nro', $nro);
                        $stmtPdf->bindParam(':cod', $cod);
                        $stmtPdf->execute();
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

    public function actualizarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = $datos['nroFactura'] ?? '';
        $fecha   = $datos['fechaFactura'] ?? '';
        $cod     = $datos['codArticulo'] ?? '';
        $desc    = $datos['descripcion'] ?? '';
        $um      = $datos['unidadMedida'] ?? '';
        $cant    = (float)($datos['cantidad'] ?? 0);
        $precio  = (float)($datos['precioUnitario'] ?? 0);
        $importe = $cant * $precio;

        $traza .= "Parte Modificacion simple de datos\n";
        $traza .= "nroFactura: $nro / codArticulo: $cod\n";

        try {
            $c = $this->detectarColumnas();
            $valorUmBD = $this->resolverValorUmParaBD($um, $c['um_es_int']);

            $mapaSets = [
                ['col' => $c['fecha'], 'ph' => ':fechaFactura',   'val' => $fecha],
                ['col' => $c['desc'],  'ph' => ':descripcion',    'val' => $desc],
                ['col' => $c['um'],    'ph' => ':unidadMedida',   'val' => $valorUmBD],
                ['col' => $c['cant'],  'ph' => ':cantidad',       'val' => $cant],
                ['col' => $c['prec'],  'ph' => ':precioUnitario', 'val' => $precio],
            ];

            if (!empty($c['imp']) && empty($c['imp_generada'])) {
                $mapaSets[] = ['col' => $c['imp'], 'ph' => ':importeRenglon', 'val' => $importe];
            }

            $setsSql = [];
            $binds   = [];
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

            $binds[':nroFactura']  = $nro;
            $binds[':codArticulo'] = $cod;

            $sql = "UPDATE renglones_factura SET " . implode(', ', $setsSql) . " WHERE `{$c['nro']}`=:nroFactura AND `{$c['cod']}`=:codArticulo";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val);
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute();
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nModificacion de renglón $nro / $cod correcta";

            // Manejo de archivo PDF binario si se envió
            if ($archivoPdf && isset($archivoPdf['tmp_name']) && !empty($archivoPdf['name'])) {
                try {
                    if ($c['pdf']) {
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `{$c['pdf']}` = :doc WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
                        $stmtPdf->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB);
                        $stmtPdf->bindParam(':nro', $nro);
                        $stmtPdf->bindParam(':cod', $cod);
                        $stmtPdf->execute();
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

    public function eliminarRenglon($nroFactura, $codArticulo, &$traza = '') {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        try {
            $c = $this->detectarColumnas();

            $sql = "DELETE FROM renglones_factura WHERE `{$c['nro']}`=:nroFactura AND `{$c['cod']}`=:codArticulo";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "Preparacion exitosa!\n";

            $stmt->bindParam(':nroFactura', $nroFactura);
            $stmt->bindParam(':codArticulo', $codArticulo);
            $traza .= "Binding exitoso!\n";

            $stmt->execute();
            $traza .= "Ejecucion exitosa!\n";
            $traza .= "Baja de renglón $nroFactura / $codArticulo correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Baja: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    public function getPdfBase64($nroFactura, $codArticulo) {
        if (!$this->pdo) return null;
        try {
            $c = $this->detectarColumnas();
            if (!$c['pdf']) return null;

            $stmt = $this->pdo->prepare("SELECT `{$c['pdf']}` FROM renglones_factura WHERE `{$c['nro']}` = :nro AND `{$c['cod']}` = :cod");
            $stmt->bindParam(':nro', $nroFactura);
            $stmt->bindParam(':cod', $codArticulo);
            $stmt->execute();
            $fila = $stmt->fetch();
            if ($fila && !empty($fila[$c['pdf']])) {
                return base64_encode($fila[$c['pdf']]);
            }
            return null;
        } catch (PDOException $e) {
            Database::logError('traeBinario: ' . $e->getMessage());
            return null;
        }
    }
}
