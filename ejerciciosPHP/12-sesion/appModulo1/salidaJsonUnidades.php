<?php
include __DIR__ . '/../manejoSesion.inc'; // Solo responde dentro de una sesión autenticada.
require_once __DIR__ . '/models/RenglonesModel.php';

// Vista de unidades: consulta el modelo y devuelve las opciones como JSON.
header('Content-Type: application/json; charset=utf-8');
sleep(2); // Espera didáctica de unidades, en segundos.
$modelo = new RenglonesModel();
echo json_encode(['unidades' => $modelo->getUnidades()], JSON_UNESCAPED_UNICODE);
