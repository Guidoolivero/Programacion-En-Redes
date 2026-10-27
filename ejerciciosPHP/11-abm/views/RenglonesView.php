<?php
// views/RenglonesView.php - Vista / Controlador de flujo en el patrón MVT

require_once __DIR__ . '/../models/RenglonesModel.php';

class RenglonesView {
    private $model;

    public function __construct() {
        $this->model = new RenglonesModel();
    }

    // Renderiza la plantilla principal del ejercicio (Template)
    public function render() {
        require __DIR__ . '/../templates/abm_template.php';
    }

    // Emite el JSON de unidades de medida (tabla auxiliar)
    public function emitirJsonUnidades() {
        header('Content-Type: application/json; charset=utf-8');
        sleep(2);
        $unidades = $this->model->getUnidades();
        echo json_encode(['unidades' => $unidades], JSON_UNESCAPED_UNICODE);
    }

    // Emite el JSON de renglones de factura filtrados y ordenados
    public function emitirJsonRenglones($filtros, $orden) {
        header('Content-Type: application/json; charset=utf-8');
        sleep(1);
        $resultado = $this->model->getRenglones($filtros, $orden);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    // Procesa el alta enviada por AJAX y devuelve la traza al cliente
    public function procesarAlta($datos, $archivoPdf = null) {
        $traza = '';
        $this->model->insertarRenglon($datos, $traza, $archivoPdf);
        echo $traza;
    }

    // Procesa la modificación enviada por AJAX y devuelve la traza al cliente
    public function procesarModi($datos, $archivoPdf = null) {
        $traza = '';
        $this->model->actualizarRenglon($datos, $traza, $archivoPdf);
        echo $traza;
    }

    // Procesa la baja enviada por AJAX y devuelve la traza al cliente
    public function procesarBaja($nroFactura, $codArticulo) {
        $traza = '';
        $this->model->eliminarRenglon($nroFactura, $codArticulo, $traza);
        echo $traza;
    }

    // Trae documento PDF en base64 para visualizar en modal (traeBinario)
    public function emitirBinarioPdf($nroFactura, $codArticulo) {
        header('Content-Type: application/json; charset=utf-8');
        $base64 = $this->model->getPdfBase64($nroFactura, $codArticulo);
        $obj = new stdClass();
        $obj->documentoPdf = $base64;
        echo json_encode($obj, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
