<?php
// Punto de entrada principal que delega a la Vista (MVT)

require_once __DIR__ . '/views/FacturaView.php';

$app = new FacturaView();
$app->render();
