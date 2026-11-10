<?php
// views/RenglonesView.php - Vista / Controlador de flujo en el patrón MVT

require_once __DIR__ . '/../models/RenglonesModel.php'; // Carga la clase que realiza las consultas SQL.

class RenglonesView {
    private $model;

    public function __construct() {
        $this->model = new RenglonesModel(); // Crea el modelo y obtiene su conexión a MySQL.
    }

    // Renderiza la plantilla principal del ejercicio (Template)
    public function render() {
        require __DIR__ . '/../templates/abm_template.php'; // Genera el HTML del tablero con sus formularios y ventanas.
    }

    // Emite el JSON de unidades de medida (tabla auxiliar)
    public function emitirJsonUnidades() {
        // Indica al navegador que el cuerpo de la respuesta es JSON.
        header('Content-Type: application/json; charset=utf-8');
        sleep(2); // Espera dos segundos antes de consultar las unidades.
        $unidades = $this->model->getUnidades(); // Pide al modelo las opciones de unidades_medida.
        // Convierte las unidades a JSON y las envía al navegador.
        echo json_encode(['unidades' => $unidades], JSON_UNESCAPED_UNICODE);
    }

    // Emite el JSON de renglones de factura filtrados y ordenados
    public function emitirJsonRenglones($filtros, $orden) {
        // Indica al navegador que el cuerpo de la respuesta es JSON.
        header('Content-Type: application/json; charset=utf-8');
        sleep(1); // Espera un segundo antes de consultar el listado.
        $resultado = $this->model->getRenglones($filtros, $orden); // El modelo aplica los filtros y ejecuta el SELECT.
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE); // Envía los registros encontrados y su cantidad.
    }

    // Procesa el alta enviada por AJAX y devuelve la traza al cliente
    public function procesarAlta($datos, $archivoPdf = null) {
        $traza = ''; // La traza acumulará el mensaje del modelo; se pasa por referencia.
        // El modelo valida duplicados, hace INSERT y guarda el PDF opcional.
        $this->model->insertarRenglon($datos, $traza, $archivoPdf);
        echo $traza; // Devuelve el mensaje que JavaScript mostrará en el modal.
    }

    // Procesa la modificación enviada por AJAX y devuelve la traza al cliente
    public function procesarModi($datos, $archivoPdf = null) {
        $traza = ''; // La traza acumulará el mensaje del modelo; se pasa por referencia.
        // El modelo ejecuta el UPDATE de los campos y del PDF opcional.
        $this->model->actualizarRenglon($datos, $traza, $archivoPdf);
        echo $traza; // Devuelve el mensaje que JavaScript mostrará en el modal.
    }

    // Procesa la baja enviada por AJAX y devuelve la traza al cliente
    public function procesarBaja($nroFactura, $codArticulo) {
        $traza = ''; // La traza acumulará el mensaje del modelo; se pasa por referencia.
        // El modelo ejecuta el DELETE usando la clave compuesta.
        $this->model->eliminarRenglon($nroFactura, $codArticulo, $traza);
        echo $traza; // Devuelve el mensaje que JavaScript mostrará en el modal.
    }

    // Trae documento PDF en base64 para visualizar en modal (traeBinario)
    public function emitirBinarioPdf($nroFactura, $codArticulo) {
        // Indica al navegador que el cuerpo de la respuesta es JSON.
        header('Content-Type: application/json; charset=utf-8');
        // Lee el PDF de MySQL y lo convierte a texto Base64.
        $base64 = $this->model->getPdfBase64($nroFactura, $codArticulo);
        // Devuelve la propiedad que lee mostrarPdf en abm.js, con el PDF o null.
        echo json_encode(['documentoPdf' => $base64], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
