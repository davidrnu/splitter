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

if (obtenerRolEnGrupo($db, $grupoId, $usuarioId) !== 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Solo el admin puede generar invitaciones']);
    exit;
}

$token = bin2hex(random_bytes(32));
$stmt  = $db->prepare("INSERT INTO invitaciones (grupo_id, token) VALUES (?, ?)");
$stmt->execute([$grupoId, $token]);

echo json_encode(['ok' => true, 'enlace' => $_ENV['DOMAIN'] . "/unirse.php?token=$token"]);
