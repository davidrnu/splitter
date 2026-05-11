<?php

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);


function comprobarSesion() {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  if (!isset($_SESSION['usuario_id'])) {
    header('location: login.php');
    exit;
  } 
}

function conectarDB(): PDO {
  $dbHost = $_ENV['DB_HOST'];
  $dbName = $_ENV['DB_NAME'];
  $dbUser = $_ENV['DB_USER'];
  $dbPass = $_ENV['DB_PASS'];
  $ccon = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

  return new PDO($ccon, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function registrarUsuario(string $nombre, string $email, string $pass): true|string {
  $pdo = conectarDB();
  $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    return 'Ya existe una cuenta con ese email.';
  }
  $passHash = password_hash($pass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)');
  $stmt->execute([$nombre, $email, $passHash]);
  return true;
}

function loginUsuario(string $email, string $pass): array|false {
  $pdo = conectarDB();

  $stmt = $pdo->prepare('SELECT id, nombre, password FROM usuarios WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario && password_verify($pass, $usuario['password'])) {
    return $usuario;
  }

  return false;
}

function obtenerGrupo(PDO $db, int $grupoId): array|false {
  $stmt = $db->prepare("SELECT nombre, descripcion FROM grupos WHERE id = ? LIMIT 1");
  $stmt->execute([$grupoId]);
  return $stmt->fetch();
}

function obtenerRolEnGrupo(PDO $db, int $grupoId, int $usuarioId): string|false {
  $stmt = $db->prepare("SELECT rol FROM miembros WHERE grupo_id = ? AND usuario_id = ? LIMIT 1");
  $stmt->execute([$grupoId, $usuarioId]);
  $miembro = $stmt->fetch();
  return $miembro ? $miembro['rol'] : false;
}

function obtenerMiembros(PDO $db, int $grupoId): array {
  $stmt = $db->prepare("
    SELECT u.id, u.nombre, m.rol
    FROM miembros m
    JOIN usuarios u ON u.id = m.usuario_id
    WHERE m.grupo_id = ?
    ORDER BY m.rol ASC, u.nombre ASC
  ");
  $stmt->execute([$grupoId]);
  return $stmt->fetchAll();
}

function obtenerMovimientos(PDO $db, int $grupoId): array {
  $stmt = $db->prepare("
    SELECT m.id, m.descripcion, m.importe, m.creado_en, m.usuario_id AS pagador_id, u.nombre AS pagador
    FROM movimientos m
    JOIN usuarios u ON u.id = m.usuario_id
    WHERE m.grupo_id = ? AND m.tipo = 'gasto'
    ORDER BY m.creado_en DESC
  ");
  $stmt->execute([$grupoId]);
  return $stmt->fetchAll();
}

function obtenerParticipantesPorMovimiento(PDO $db, int $grupoId): array {
  $stmt = $db->prepare("
    SELECT p.movimiento_id, u.nombre
    FROM participantes p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN movimientos m ON m.id = p.movimiento_id
    WHERE m.grupo_id = ?
  ");
  $stmt->execute([$grupoId]);
  $resultado = [];
  foreach ($stmt->fetchAll() as $row) {
    $resultado[$row['movimiento_id']][] = $row['nombre'];
  }
  return $resultado;
}

function calcularDeudas(PDO $db, int $grupoId, array $miembros): array {
  $nombres = [];
  foreach ($miembros as $m) {
    $nombres[$m['id']] = $m['nombre'];
  }

  $stmt = $db->prepare("
    SELECT p.usuario_id AS deudor_id, m.usuario_id AS acreedor_id, SUM(p.importe) AS total
    FROM participantes p
    JOIN movimientos m ON m.id = p.movimiento_id
    WHERE m.grupo_id = ? AND p.usuario_id != m.usuario_id
    GROUP BY p.usuario_id, m.usuario_id
  ");
  $stmt->execute([$grupoId]);

  $deudas = [];
  foreach ($stmt->fetchAll() as $fila) {
    $a = $fila['deudor_id'];
    $b = $fila['acreedor_id'];
    $clave = min($a, $b) . '_' . max($a, $b);
    if (!isset($deudas[$clave])) {
      $deudas[$clave] = ['a' => min($a, $b), 'b' => max($a, $b), 'neto' => 0, 'nombres' => $nombres];
    }
    $a < $b ? $deudas[$clave]['neto'] += $fila['total'] : $deudas[$clave]['neto'] -= $fila['total'];
  }

  $stmt = $db->prepare("
    SELECT usuario_id AS pagador, destinatario_id AS receptor, SUM(importe) AS total
    FROM movimientos
    WHERE grupo_id = ? AND tipo = 'transferencia'
    GROUP BY usuario_id, destinatario_id
  ");
  $stmt->execute([$grupoId]);
  foreach ($stmt->fetchAll() as $t) {
    $a = $t['pagador'];
    $b = $t['receptor'];
    $clave = min($a, $b) . '_' . max($a, $b);
    if (!isset($deudas[$clave])) {
      $deudas[$clave] = ['a' => min($a, $b), 'b' => max($a, $b), 'neto' => 0, 'nombres' => $nombres];
    }
    $a < $b ? $deudas[$clave]['neto'] -= $t['total'] : $deudas[$clave]['neto'] += $t['total'];
  }

  return $deudas;
}

function crearGasto(PDO $db, int $grupoId, int $pagadorId, string $descripcion, float $importe, array $participantes): void {
  $importePorPersona = round($importe / count($participantes), 2);
  $stmt = $db->prepare("INSERT INTO movimientos (grupo_id, usuario_id, tipo, importe, descripcion) VALUES (?, ?, 'gasto', ?, ?)");
  $stmt->execute([$grupoId, $pagadorId, $importe, $descripcion]);
  $movimientoId = $db->lastInsertId();
  $stmt = $db->prepare("INSERT INTO participantes (movimiento_id, usuario_id, importe) VALUES (?, ?, ?)");
  foreach ($participantes as $participanteId) {
    $stmt->execute([$movimientoId, (int)$participanteId, $importePorPersona]);
  }
}

function borrarGasto(PDO $db, int $movimientoId, int $grupoId, string $rol, int $usuarioId): void {
  $stmt = $db->prepare("SELECT usuario_id FROM movimientos WHERE id = ? AND grupo_id = ?");
  $stmt->execute([$movimientoId, $grupoId]);
  $mov = $stmt->fetch();
  if ($mov && ($rol === 'admin' || $mov['usuario_id'] === $usuarioId)) {
    $stmt = $db->prepare("DELETE FROM movimientos WHERE id = ?");
    $stmt->execute([$movimientoId]);
  }
}

function crearTransferencia(PDO $db, int $grupoId, int $pagadorId, int $receptorId, float $importe): void {
  $stmt = $db->prepare("INSERT INTO movimientos (grupo_id, usuario_id, tipo, importe, descripcion, destinatario_id) VALUES (?, ?, 'transferencia', ?, 'Transferencia', ?)");
  $stmt->execute([$grupoId, $pagadorId, $importe, $receptorId]);
}