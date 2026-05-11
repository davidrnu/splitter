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

// GET ?id=X → devuelve balances y gastos actualizados del grupo
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $grupoId = (int)($_GET['id'] ?? 0);
    $rol     = obtenerRolEnGrupo($db, $grupoId, $usuarioId);

    if ($rol === false) {
        echo json_encode(['ok' => false, 'error' => 'Sin acceso']);
        exit;
    }

    $miembros    = obtenerMiembros($db, $grupoId);
    $movimientos = obtenerMovimientos($db, $grupoId);
    $participantes = obtenerParticipantesPorMovimiento($db, $grupoId);
    $deudas      = calcularDeudas($db, $grupoId, $miembros);

    $deudasFormateadas = [];
    foreach ($deudas as $d) {
        if (round($d['neto'], 2) == 0) continue;
        if ($d['neto'] > 0) {
            $deudasFormateadas[] = ['deudor' => $d['nombres'][$d['a']], 'acreedor' => $d['nombres'][$d['b']], 'cantidad' => round($d['neto'], 2)];
        } else {
            $deudasFormateadas[] = ['deudor' => $d['nombres'][$d['b']], 'acreedor' => $d['nombres'][$d['a']], 'cantidad' => round(abs($d['neto']), 2)];
        }
    }

    foreach ($movimientos as &$mov) {
        $mov['participantes']  = $participantes[$mov['id']] ?? [];
        $mov['puede_borrar'] = ($rol === 'admin' || (int)$mov['pagador_id'] === $usuarioId);
    }

    echo json_encode(['ok' => true, 'deudas' => $deudasFormateadas, 'gastos' => $movimientos]);
    exit;
}

// POST accion=crear → crea grupo
if ($_POST['accion'] === 'crear') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '') {
        echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO grupos (nombre, descripcion, creado_por) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $descripcion ?: null, $usuarioId]);
    $grupoId = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO miembros (grupo_id, usuario_id, rol) VALUES (?, ?, 'admin')");
    $stmt->execute([$grupoId, $usuarioId]);

    echo json_encode(['ok' => true, 'id' => $grupoId, 'nombre' => $nombre, 'descripcion' => $descripcion]);
    exit;
}

// POST accion=borrar → borra grupo
if ($_POST['accion'] === 'borrar') {
    $grupoId = (int)($_POST['grupo_id'] ?? 0);

    if (obtenerRolEnGrupo($db, $grupoId, $usuarioId) !== 'admin') {
        echo json_encode(['ok' => false, 'error' => 'Solo el admin puede borrar el grupo']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM grupos WHERE id = ?");
    $stmt->execute([$grupoId]);
    echo json_encode(['ok' => true]);
    exit;
}
