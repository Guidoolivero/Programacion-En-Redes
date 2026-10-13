<?php
// models/FacturaModel.php - Modelo para interactuar con las tablas de la BD

require_once __DIR__ . '/Database.php';

class FacturaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getUnidadesMedida() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRenglonesFactura() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM renglones_factura");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function insertarRenglon($datos) {
        $sql = "INSERT INTO renglones_factura 
                (nroFactura, fechaFactura, codArticulo, descripcion, unidadMedida, cantidad, precioUnitario, importeRenglon) 
                VALUES (:nroFactura, :fechaFactura, :codArticulo, :descripcion, :unidadMedida, :cantidad, :precioUnitario, :importeRenglon)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nroFactura'     => $datos['nroFactura'],
            ':fechaFactura'   => $datos['fechaFactura'],
            ':codArticulo'    => $datos['codArticulo'],
            ':descripcion'    => $datos['descripcion'],
            ':unidadMedida'   => $datos['unidadMedida'],
            ':cantidad'       => $datos['cantidad'],
            ':precioUnitario' => $datos['precioUnitario'],
            ':importeRenglon' => $datos['cantidad'] * $datos['precioUnitario']
        ]);
    }
}
