<?php
require_once __DIR__ . '/../utils.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuarioId  = $_SESSION['usuario_id'];
$db         = conectarDB();
$grupoId    = (int)($_POST['grupo_id'] ?? 0);
$pagadorId  = (int)($_POST['pagador_id'] ?? 0);
$receptorId = (int)($_POST['receptor_id'] ?? 0);
$importe    = (float)($_POST['importe'] ?? 0);

if (obtenerRolEnGrupo($db, $grupoId, $usuarioId) === false) {
    echo json_encode(['ok' => false, 'error' => 'Sin acceso']);
    exit;
}

if ($pagadorId !== $receptorId && $importe > 0) {
    crearTransferencia($db, $grupoId, $pagadorId, $receptorId, $importe);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
}
