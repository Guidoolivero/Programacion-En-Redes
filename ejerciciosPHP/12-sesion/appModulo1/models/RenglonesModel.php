<?php
// models/RenglonesModel.php - Modelo para la persistencia y consultas de datos

require_once __DIR__ . '/Database.php';

class RenglonesModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getPdo() {
        return $this->pdo;
    }

    // Helper para mapear columnas según el esquema real de la tabla
    private function detectarColumnas() {
        $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
        return [
            'nro'    => in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : ($cols[0] ?? 'nroFactura')),
            'fecha'  => in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : ($cols[1] ?? 'fechaFactura'))),
            'cod'    => in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : ($cols[2] ?? 'codArticulo')),
            'desc'   => in_array('descripcion', $cols) ? 'descripcion' : ($cols[3] ?? 'descripcion'),
            'um'     => in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : ($cols[4] ?? 'unidadMedida'))),
            'cant'   => in_array('cantidad', $cols) ? 'cantidad' : ($cols[5] ?? 'cantidad'),
            'prec'   => in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : ($cols[6] ?? 'precioUnitario')),
            'imp'    => in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : ($cols[7] ?? 'importeRenglon'))),
            'pdf'    => in_array('documentoPdf', $cols) ? 'documentoPdf' : (in_array('documento_pdf', $cols) ? 'documento_pdf' : (in_array('pdf', $cols) ? 'pdf' : null)),
        ];
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
        if (!in_array($orden, $camposPermitidos)) {
            $orden = 'nroFactura';
        }

        try {
            $c = $this->detectarColumnas();

            $mapaOrden = [
                'nroFactura'     => $c['nro'],
                'codArticulo'    => $c['cod'],
                'unidadMedida'   => $c['um'],
                'descripcion'    => $c['desc'],
                'fechaFactura'   => $c['fecha'],
                'cantidad'       => $c['cant'],
                'precioUnitario' => $c['prec'],
                'importeRenglon' => $c['imp'],
            ];
            $colOrden = $mapaOrden[$orden] ?? $c['nro'];

            $condiciones = [];
            $params = [];

            if (!empty($filtros['f_nroFactura'])) {
                $condiciones[] = "`{$c['nro']}` LIKE :f_nro";
                $params[':f_nro'] = '%' . $filtros['f_nroFactura'] . '%';
            }
            if (!empty($filtros['f_codArticulo'])) {
                $condiciones[] = "`{$c['cod']}` LIKE :f_cod";
                $params[':f_cod'] = '%' . $filtros['f_codArticulo'] . '%';
            }
            if (!empty($filtros['f_unidadMedida'])) {
                $condiciones[] = "`{$c['um']}` = :f_um";
                $params[':f_um'] = $filtros['f_unidadMedida'];
            }
            if (!empty($filtros['f_descripcion'])) {
                $condiciones[] = "`{$c['desc']}` LIKE :f_desc";
                $params[':f_desc'] = '%' . $filtros['f_descripcion'] . '%';
            }
            if (!empty($filtros['f_fechaFactura'])) {
                $condiciones[] = "`{$c['fecha']}` = :f_fecha";
                $params[':f_fecha'] = $filtros['f_fechaFactura'];
            }

            $sql = "SELECT `{$c['nro']}` AS nroFactura, `{$c['fecha']}` AS fechaFactura, `{$c['cod']}` AS codArticulo, `{$c['desc']}` AS descripcion, `{$c['um']}` AS unidadMedida, `{$c['cant']}` AS cantidad, `{$c['prec']}` AS precioUnitario, `{$c['imp']}` AS importeRenglon FROM renglones_factura";
            if (!empty($condiciones)) {
                $sql .= " WHERE " . implode(' AND ', $condiciones);
            }
            $sql .= " ORDER BY `$colOrden` ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

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

            $sql = "INSERT INTO renglones_factura (`{$c['nro']}`, `{$c['fecha']}`, `{$c['cod']}`, `{$c['desc']}`, `{$c['um']}`, `{$c['cant']}`, `{$c['prec']}`, `{$c['imp']}`) VALUES (:nroFactura, :fechaFactura, :codArticulo, :descripcion, :unidadMedida, :cantidad, :precioUnitario, :importeRenglon)";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "\nPreparacion exitosa!";

            $stmt->bindParam(':nroFactura', $nro);
            $stmt->bindParam(':fechaFactura', $fecha);
            $stmt->bindParam(':codArticulo', $cod);
            $stmt->bindParam(':descripcion', $desc);
            $stmt->bindParam(':unidadMedida', $um);
            $stmt->bindParam(':cantidad', $cant);
            $stmt->bindParam(':precioUnitario', $precio);
            $stmt->bindParam(':importeRenglon', $importe);
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

        $traza .= "Parte Modificacion simple de datos <br />\n";
        $traza .= "nroFactura: $nro / codArticulo: $cod\n";

        try {
            $c = $this->detectarColumnas();

            $sql = "UPDATE renglones_factura SET `{$c['fecha']}`=:fechaFactura, `{$c['desc']}`=:descripcion, `{$c['um']}`=:unidadMedida, `{$c['cant']}`=:cantidad, `{$c['prec']}`=:precioUnitario, `{$c['imp']}`=:importeRenglon WHERE `{$c['nro']}`=:nroFactura AND `{$c['cod']}`=:codArticulo";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "\nPreparacion exitosa!";

            $stmt->bindParam(':fechaFactura', $fecha);
            $stmt->bindParam(':descripcion', $desc);
            $stmt->bindParam(':unidadMedida', $um);
            $stmt->bindParam(':cantidad', $cant);
            $stmt->bindParam(':precioUnitario', $precio);
            $stmt->bindParam(':importeRenglon', $importe);
            $stmt->bindParam(':nroFactura', $nro);
            $stmt->bindParam(':codArticulo', $cod);
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
