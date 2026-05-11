<?php
require_once __DIR__ . '/utils.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['usuario_id'])) {
  header('Location: index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Por favor, rellena todos los campos.';
  } else {
    $usuario = loginUsuario($email, $password);
    if ($usuario) {
      $_SESSION['usuario_id']     = $usuario['id'];
      $_SESSION['usuario_nombre'] = $usuario['nombre'];
      header('Location: index.php');
      exit;
    } else {
      $error = 'Email o contraseña incorrectos.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Splitter</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white border border-gray-200 rounded-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-semibold mb-6">Iniciar sesión</h1>

    <?php if ($error !== ''): ?>
      <p class="text-red-500 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
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
      <button class="w-full bg-gray-900 text-white py-2 rounded text-sm hover:bg-gray-700">Entrar</button>
    </form>

    <div class="mt-4 text-sm text-gray-500 space-y-1">
      <p><a href="reset.php" class="hover:text-gray-900">¿Olvidaste tu contraseña?</a></p>
      <p>¿No tienes cuenta? <a href="registro.php" class="hover:text-gray-900">Regístrate</a></p>
    </div>
  </div>
</body>

</html>