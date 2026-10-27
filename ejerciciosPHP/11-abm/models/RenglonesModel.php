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
            $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
            $colNro   = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : ($cols[0] ?? 'nroFactura'));
            $colFecha = in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : ($cols[1] ?? 'fechaFactura')));
            $colCod   = in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : ($cols[2] ?? 'codArticulo'));
            $colDesc  = in_array('descripcion', $cols) ? 'descripcion' : ($cols[3] ?? 'descripcion');
            $colUm    = in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : ($cols[4] ?? 'unidadMedida')));
            $colCant  = in_array('cantidad', $cols) ? 'cantidad' : ($cols[5] ?? 'cantidad');
            $colPrec  = in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : ($cols[6] ?? 'precioUnitario'));
            $colImp   = in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : ($cols[7] ?? 'importeRenglon')));
            $colPdf   = in_array('documentoPdf', $cols) ? 'documentoPdf' : (in_array('documento_pdf', $cols) ? 'documento_pdf' : (in_array('pdf', $cols) ? 'pdf' : null));

            $mapaOrden = [
                'nroFactura'     => $colNro,
                'codArticulo'    => $colCod,
                'unidadMedida'   => $colUm,
                'descripcion'    => $colDesc,
                'fechaFactura'   => $colFecha,
                'cantidad'       => $colCant,
                'precioUnitario' => $colPrec,
                'importeRenglon' => $colImp,
            ];
            $colOrdenSql = $mapaOrden[$orden] ?? $colNro;

            $sql = "SELECT * FROM renglones_factura WHERE 1=1";
            $params = [];

            $fNro   = trim($filtros['f_nroFactura'] ?? '');
            $fCod   = trim($filtros['f_codArticulo'] ?? '');
            $fUm    = trim($filtros['f_unidadMedida'] ?? '');
            $fDesc  = trim($filtros['f_descripcion'] ?? '');
            $fFecha = trim($filtros['f_fechaFactura'] ?? '');

            if ($fNro !== '')   { $sql .= " AND `$colNro` LIKE ?";   $params[] = "%$fNro%"; }
            if ($fCod !== '')   { $sql .= " AND `$colCod` LIKE ?";   $params[] = "%$fCod%"; }
            if ($fUm !== '')    { $sql .= " AND (`$colUm` = ? OR `$colUm` LIKE ?)"; $params[] = $fUm; $params[] = "%$fUm%"; }
            if ($fDesc !== '')  { $sql .= " AND `$colDesc` LIKE ?";  $params[] = "%$fDesc%"; }
            if ($fFecha !== '') { $sql .= " AND `$colFecha` = ?";    $params[] = $fFecha; }

            $sql .= " ORDER BY `$colOrdenSql`";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $filasRaw = $stmt->fetchAll();

            $filas = array_map(function($r) use ($colNro, $colCod, $colUm, $colDesc, $colFecha, $colCant, $colPrec, $colImp, $colPdf) {
                $cant = $r[$colCant] ?? '';
                $prec = $r[$colPrec] ?? '';
                $imp  = $r[$colImp] ?? (($cant !== '' && $prec !== '') ? number_format((float)$cant * (float)$prec, 2, '.', '') : '');
                $tienePdf = ($colPdf && !empty($r[$colPdf])) ? 1 : 0;
                return [
                    'nroFactura'     => $r[$colNro] ?? '',
                    'codArticulo'    => $r[$colCod] ?? '',
                    'unidadMedida'   => $r[$colUm] ?? '',
                    'descripcion'    => $r[$colDesc] ?? '',
                    'fechaFactura'   => $r[$colFecha] ?? '',
                    'cantidad'       => $cant,
                    'precioUnitario' => $prec,
                    'importeRenglon' => $imp,
                    'tienePdf'       => $tienePdf,
                ];
            }, $filasRaw);

            return ['renglones' => $filas, 'cantidad' => count($filas)];
        } catch (PDOException $e) {
            Database::logError('Renglones: ' . $e->getMessage());
            return ['error' => $e->getMessage(), 'renglones' => [], 'cantidad' => 0];
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
            $sql = "INSERT INTO renglones_factura (nroFactura, fechaFactura, codArticulo, descripcion, unidadMedida, cantidad, precioUnitario, importeRenglon) VALUES (:nroFactura, :fechaFactura, :codArticulo, :descripcion, :unidadMedida, :cantidad, :precioUnitario, :importeRenglon)";
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
                    $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
                    $colPdf = in_array('documentoPdf', $cols) ? 'documentoPdf' : (in_array('documento_pdf', $cols) ? 'documento_pdf' : null);
                    if ($colPdf) {
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `$colPdf` = :doc WHERE nroFactura = :nro AND codArticulo = :cod");
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
            $sql = "UPDATE renglones_factura SET fechaFactura=:fechaFactura, descripcion=:descripcion, unidadMedida=:unidadMedida, cantidad=:cantidad, precioUnitario=:precioUnitario, importeRenglon=:importeRenglon WHERE nroFactura=:nroFactura AND codArticulo=:codArticulo";
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
                    $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
                    $colPdf = in_array('documentoPdf', $cols) ? 'documentoPdf' : (in_array('documento_pdf', $cols) ? 'documento_pdf' : null);
                    if ($colPdf) {
                        $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
                        $stmtPdf = $this->pdo->prepare("UPDATE renglones_factura SET `$colPdf` = :doc WHERE nroFactura = :nro AND codArticulo = :cod");
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
            $sql = "DELETE FROM renglones_factura WHERE nroFactura=:nroFactura AND codArticulo=:codArticulo";
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
            $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
            $colPdf = in_array('documentoPdf', $cols) ? 'documentoPdf' : (in_array('documento_pdf', $cols) ? 'documento_pdf' : null);
            if (!$colPdf) return null;

            $stmt = $this->pdo->prepare("SELECT `$colPdf` FROM renglones_factura WHERE nroFactura = :nro AND codArticulo = :cod");
            $stmt->bindParam(':nro', $nroFactura);
            $stmt->bindParam(':cod', $codArticulo);
            $stmt->execute();
            $fila = $stmt->fetch();
            if ($fila && !empty($fila[$colPdf])) {
                return base64_encode($fila[$colPdf]);
            }
            return null;
        } catch (PDOException $e) {
            Database::logError('traeBinario: ' . $e->getMessage());
            return null;
        }
    }
}
