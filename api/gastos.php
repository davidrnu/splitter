<?php
require_once __DIR__ . '/../utils.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$db        = conectarDB();
$grupoId   = (int)($_POST['grupo_id'] ?? 0);
$rol       = obtenerRolEnGrupo($db, $grupoId, $usuarioId);

if ($rol === false) {
    echo json_encode(['ok' => false, 'error' => 'Sin acceso']);
    exit;
}

if ($_POST['accion'] === 'crear') {
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $importe       = (float)($_POST['importe'] ?? 0);
    $pagadorId     = (int)($_POST['pagador_id'] ?? 0);
    $participantes = $_POST['participantes'] ?? [];

    if ($descripcion && $importe > 0 && count($participantes) > 0) {
        crearGasto($db, $grupoId, $pagadorId, $descripcion, $importe, $participantes);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    }
}

if ($_POST['accion'] === 'borrar') {
    $movimientoId = (int)($_POST['movimiento_id'] ?? 0);
    borrarGasto($db, $movimientoId, $grupoId, $rol, $usuarioId);
    echo json_encode(['ok' => true]);
}
