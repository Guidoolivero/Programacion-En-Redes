<?php
// models/FacturaModel.php - Modelo para interactuar con las tablas de la BD

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/logger.php';

class FacturaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getUnidadesMedida() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarError("Error al consultar unidades_medida: " . $e->getMessage(), 'MODEL_SELECT');
            return [];
        }
    }

    public function getRenglonesFactura() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM renglones_factura ORDER BY 1 DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            registrarError("Error al consultar renglones_factura: " . $e->getMessage(), 'MODEL_SELECT');
            return [];
        }
    }

    public function existeFactura($nroFactura) {
        if (!$this->pdo) return false;
        try {
            $stmtCols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura");
            $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
            $colNro = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : $cols[0]);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM renglones_factura WHERE `$colNro` = ?");
            $stmt->execute([$nroFactura]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            registrarError("Error al verificar existencia de factura $nroFactura: " . $e->getMessage(), 'MODEL_SELECT');
            return false;
        }
    }

    public function insertarRenglon($datos, &$errorMsg = null) {
        if (!$this->pdo) {
            $errorMsg = "No hay conexión PDO activa con la base de datos.";
            registrarError("Fallo al insertar renglón: $errorMsg", 'MODEL_INSERT');
            return false;
        }
        try {
            $stmtCols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura");
            $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

            $colNro = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : $cols[0]);
            $colFecha = in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : $cols[1]));
            $colCod = in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : $cols[2]);
            $colDesc = in_array('descripcion', $cols) ? 'descripcion' : $cols[3];
            $colUm = in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : $cols[4]));
            $colCant = in_array('cantidad', $cols) ? 'cantidad' : $cols[5];
            $colPrec = in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : $cols[6]);
            $colImp = in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : (isset($cols[7]) ? $cols[7] : null)));

            $fields = ["`$colNro`", "`$colFecha`", "`$colCod`", "`$colDesc`", "`$colUm`", "`$colCant`", "`$colPrec`"];
            $params = [
                $datos['nroFactura'],
                $datos['fechaFactura'],
                $datos['codArticulo'],
                $datos['descripcion'],
                $datos['unidadMedida'],
                $datos['cantidad'],
                $datos['precioUnitario'],
            ];

            if ($colImp) {
                $fields[] = "`$colImp`";
                $params[] = $datos['cantidad'] * $datos['precioUnitario'];
            }

            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO renglones_factura (" . implode(', ', $fields) . ") VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            registrarError("Error SQL al insertar factura {$datos['nroFactura']}: $errorMsg", 'MODEL_INSERT');
            return false;
        }
    }

    public function eliminarRenglon($nroFactura, &$errorMsg = null) {
        if (!$this->pdo) {
            $errorMsg = "Sin conexión con la BD.";
            registrarError("Fallo al eliminar renglón $nroFactura: $errorMsg", 'MODEL_DELETE');
            return false;
        }
        try {
            $stmtCols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura");
            $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
            $colNro = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : $cols[0]);

            $stmt = $this->pdo->prepare("DELETE FROM renglones_factura WHERE `$colNro` = ?");
            return $stmt->execute([$nroFactura]);
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            registrarError("Error SQL al eliminar factura $nroFactura: $errorMsg", 'MODEL_DELETE');
            return false;
        }
    }

    public function actualizarRenglon($datos, &$errorMsg = null) {
        if (!$this->pdo) {
            $errorMsg = "Sin conexión con la BD.";
            registrarError("Fallo al actualizar renglón {$datos['nroFactura']}: $errorMsg", 'MODEL_UPDATE');
            return false;
        }
        try {
            $stmtCols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura");
            $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

            $colNro = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : $cols[0]);
            $colFecha = in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : $cols[1]));
            $colCod = in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : $cols[2]);
            $colDesc = in_array('descripcion', $cols) ? 'descripcion' : $cols[3];
            $colUm = in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : $cols[4]));
            $colCant = in_array('cantidad', $cols) ? 'cantidad' : $cols[5];
            $colPrec = in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : $cols[6]);
            $colImp = in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : (isset($cols[7]) ? $cols[7] : null)));

            $sets = [
                "`$colFecha` = ?",
                "`$colCod` = ?",
                "`$colDesc` = ?",
                "`$colUm` = ?",
                "`$colCant` = ?",
                "`$colPrec` = ?",
            ];
            $params = [
                $datos['fechaFactura'],
                $datos['codArticulo'],
                $datos['descripcion'],
                $datos['unidadMedida'],
                $datos['cantidad'],
                $datos['precioUnitario'],
            ];

            if ($colImp) {
                $sets[] = "`$colImp` = ?";
                $params[] = $datos['cantidad'] * $datos['precioUnitario'];
            }

            $params[] = $datos['nroFactura'];

            $sql = "UPDATE renglones_factura SET " . implode(', ', $sets) . " WHERE `$colNro` = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            registrarError("Error SQL al actualizar factura {$datos['nroFactura']}: $errorMsg", 'MODEL_UPDATE');
            return false;
        }
    }
}
