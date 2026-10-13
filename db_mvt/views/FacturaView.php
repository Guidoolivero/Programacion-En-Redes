<?php
// views/FacturaView.php - Vista / Controlador: Maneja las peticiones y orquesta Modelo y Template

require_once __DIR__ . '/../models/FacturaModel.php';

class FacturaView {
    private $model;

    public function __construct() {
        $this->model = new FacturaModel();
    }

    public function render() {
        // Si es una petición POST vía AJAX
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Simulamos demora de red en el servidor (opcional) o procesamos directo
            if (!isset($_POST['nroFactura'])) {
                http_response_code(400);
                echo "<p style='color:red;'>Faltan datos en el requerimiento.</p>";
                exit;
            }

            $datos = [
                'nroFactura'     => htmlspecialchars($_POST['nroFactura']),
                'fechaFactura'   => htmlspecialchars($_POST['fechaFactura']),
                'codArticulo'    => htmlspecialchars($_POST['codArticulo']),
                'descripcion'    => htmlspecialchars($_POST['descripcion']),
                'unidadMedida'   => htmlspecialchars($_POST['unidadMedida']),
                'cantidad'       => (float) $_POST['cantidad'],
                'precioUnitario' => (float) $_POST['precioUnitario'],
            ];

            $importeRenglon = $datos['cantidad'] * $datos['precioUnitario'];

            // Intentar persistir en el Modelo (Base de Datos)
            $dbSuccess = false;
            try {
                $dbSuccess = $this->model->insertarRenglon($datos);
            } catch (Exception $e) {
                $dbSuccess = false;
            }

            // Devolver tabla HTML de respuesta por AJAX tal como en los ejercicios
            echo "<h3>Respuesta recibida por AJAX (completado tras el timer de 5s)</h3>";
            if ($dbSuccess) {
                echo "<p style='color: green; font-weight: bold;'>✔ Registro insertado correctamente en InfinityFree.</p>";
            } else {
                echo "<p style='color: #856404; background: #fff3cd; padding: 6px; border-radius: 4px;'>ℹ Datos recibidos y procesados por el Modelo (aviso: la base remota no confirmó la inserción).</p>";
            }

            echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr><th>Nro Factura</th><td>{$datos['nroFactura']}</td></tr>";
            echo "<tr><th>Fecha</th><td>{$datos['fechaFactura']}</td></tr>";
            echo "<tr><th>Cód. Artículo</th><td>{$datos['codArticulo']}</td></tr>";
            echo "<tr><th>Descripción</th><td>{$datos['descripcion']}</td></tr>";
            echo "<tr><th>Unidad de Medida</th><td>{$datos['unidadMedida']}</td></tr>";
            echo "<tr><th>Cantidad</th><td>{$datos['cantidad']}</td></tr>";
            echo "<tr><th>Precio Unitario</th><td>{$datos['precioUnitario']}</td></tr>";
            echo "<tr><th>Importe Renglón</th><td>{$importeRenglon}</td></tr>";
            echo "</table>";
            echo "<p><em>La página no se recargó (asincrónico vía AJAX).</em></p>";
            exit;
        }

        // Si es petición GET: obtiene los datos del Modelo y delega al Template
        $unidades = $this->model->getUnidadesMedida();
        $renglones = $this->model->getRenglonesFactura();

        // Renderizado del Template
        require __DIR__ . '/../templates/factura_template.php';
    }
}
