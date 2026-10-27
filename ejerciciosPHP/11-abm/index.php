<?php
// index.php - Punto de entrada principal en arquitectura MVT (Model-View-Template)

require_once __DIR__ . '/views/RenglonesView.php';

$view = new RenglonesView();
$view->render();
