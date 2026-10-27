<?php
// salidaJsonUnidades.php - Endpoint que delega a la capa View (MVT)

require_once __DIR__ . '/views/RenglonesView.php';

$view = new RenglonesView();
$view->emitirJsonUnidades();
