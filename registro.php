<?php
require_once __DIR__ . '/utils.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['usuario_id'])) {
  header("location: login.php");
  exit;
}

$error = '';
$exito = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = trim($_POST["nombre"] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($nombre === '' || $email === '' || $password === '') {
    $error = "Rellena todos los campos.";
  } else {
    $res = registrarUsuario($nombre, $email, $password);
    if ($res === true) {
      $exito = 'Cuenta creada correctamente. <a href="login.php">Inicia sesion</a>';
    } else {
      $error = $res;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Splitter - Registro</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white border border-gray-200 rounded-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-semibold mb-6">Crear cuenta</h1>

    <?php if ($error !== ''): ?>
      <p class="text-red-500 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($exito !== ''): ?>
      <p class="text-green-600 text-sm mb-4"><?= $exito ?></p>
    <?php endif; ?>

    <form method="POST" action="registro.php" class="space-y-4">
      <div>
        <label class="text-sm text-gray-600">Nombre</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
          class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-gray-600">Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-gray-600">Contraseña</label>
        <input type="password" name="password" required
          class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
      </div>
      <button class="w-full bg-gray-900 text-white py-2 rounded text-sm hover:bg-gray-700">Registrarse</button>
    </form>

    <p class="mt-4 text-sm text-gray-500">¿Ya tienes cuenta? <a href="login.php" class="hover:text-gray-900">Inicia sesión</a></p>
  </div>
</body>

</html>