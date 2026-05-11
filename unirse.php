<?php
require_once('utils.php');
comprobarSesion();

$token = $_GET['token'] ?? '';
$usuarioId = $_SESSION['usuario_id'];

if ($token === '') {
  header("location: index.php");
  exit;
}

$db = conectarDB();

$stmt = $db->prepare("SELECT grupo_id FROM invitaciones WHERE token = ?");
$stmt->execute([$token]);
$invitacion = $stmt->fetch();

if (!$invitacion) {
  header("location: index.php");
  exit;
}

$grupoId = $invitacion['grupo_id'];

$stmt = $db->prepare("SELECT id FROM miembros WHERE grupo_id = ? AND usuario_id = ?");
$stmt->execute([$grupoId, $usuarioId]);

if (!$stmt->fetch()) {
  $stmt = $db->prepare("INSERT INTO miembros (grupo_id, usuario_id, rol) VALUES (?, ?, 'miembro')");
  $stmt->execute([$grupoId, $usuarioId]);
}

header("location: grupo.php?id=$grupoId");
exit;