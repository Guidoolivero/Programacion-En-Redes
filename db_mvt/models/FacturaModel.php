<?php
// models/FacturaModel.php - Modelo para interactuar con las tablas de la BD

require_once __DIR__ . '/Database.php';

class FacturaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::::getConnection();
    }

    public function getUnidadesMedida() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRenglonesFactura() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM renglones_factura ORDER BY 1 DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function insertarRenglon($datos) {
        // Obtenemos los nombres reales de las columnas de la tabla para insertar correctamente
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

        $fields = [$colNro, $colFecha, $colCod, $colDesc, $colUm, $colCant, $colPrec];
        $params = [
            ":$colNro"  => $datos['nroFactura'],
            ":$colFecha"=> $datos['fechaFactura'],
            ":$colCod"  => $datos['codArticulo'],
            ":$colDesc" => $datos['descripcion'],
            ":$colUm"   => $datos['unidadMedida'],
            ":$colCant" => $datos['cantidad'],
            ":$colPrec" => $datos['precioUnitario'],
        ];

        if ($colImp) {
            $fields[] = $colImp;
            $params[":$colImp"] = $datos['cantidad'] * $datos['precioUnitario'];
        }

        $sql = "INSERT INTO renglones_factura (" . implode(', ', $fields) . ") VALUES (" . implode(', ', array_keys($params)) . ")";
        if (!$this->pdo) return false;
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

            $fields = [$colNro, $colFecha, $colCod, $colDesc, $colUm, $colCant, $colPrec];
            $params = [
                ":$colNro"  => $datos['nroFactura'],
                ":$colFecha"=> $datos['fechaFactura'],
                ":$colCod"  => $datos['codArticulo'],
                ":$colDesc" => $datos['descripcion'],
                ":$colUm"   => $datos['unidadMedida'],
                ":$colCant" => $datos['cantidad'],
                ":$colPrec" => $datos['precioUnitario'],
            ];

            if ($colImp) {
                $fields[] = $colImp;
                $params[":$colImp"] = $datos['cantidad'] * $datos['precioUnitario'];
            }

            $sql = "INSERT INTO renglones_factura (" . implode(', ', $fields) . ") VALUES (" . implode(', ', array_keys($params)) . ")";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }
}
