<?php

require_once('utils.php');
$token = $_GET["token"] ?? "";
$error = "";
$exito = "";
$tokenValido = false;

if ($token !== "") {
  $db = conectarDB();
  $stmt = $db->prepare("
  SELECT tr.id, tr.usuario_id
  FROM tokens_reset tr
  WHERE tr.token = ? AND tr.usado = 0 AND tr.expira_en > NOW()
  LIMIT 1
  ");
  $stmt->execute([$token]);
  $reset = $stmt->fetch();

  if ($reset) {
    $tokenValido = true;
  }
}
  
if ($_SERVER["REQUEST_METHOD"] === "POST" && $tokenValido) {
  $password = $_POST["password"] ?? "";
  $password2 = $_POST["password2"] ?? "";

  if ($password !== $password2) {
    $error = "Las contraseñas no coinciden.";
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $reset['usuario_id']]);

    $stmt = $db->prepare("UPDATE tokens_reset SET usado = 1 WHERE id = ?");
    $stmt->execute([$reset['id']]);

    $exito = "Contraseña cambiada. Ya puedes iniciar sesión.";
    $tokenValido = false;
  }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Splitter - Nueva contraseña</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white border border-gray-200 rounded-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-semibold mb-6">Nueva contraseña</h1>

    <?php if ($exito): ?>
      <p class="text-green-600 text-sm mb-4"><?= htmlspecialchars($exito) ?></p>
      <a href="login.php" class="text-sm hover:text-gray-900">Ir al login</a>

    <?php elseif (!$tokenValido): ?>
      <p class="text-red-500 text-sm mb-4">El enlace no es válido o ha expirado.</p>
      <a href="reset.php" class="text-sm hover:text-gray-900">Solicitar uno nuevo</a>

    <?php else: ?>
      <?php if ($error): ?>
        <p class="text-red-500 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="text-sm text-gray-600">Nueva contraseña</label>
          <input type="password" name="password" required
            class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
        </div>
        <div>
          <label class="text-sm text-gray-600">Repetir contraseña</label>
          <input type="password" name="password2" required
            class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
        </div>
        <button class="w-full bg-gray-900 text-white py-2 rounded text-sm hover:bg-gray-700">Cambiar contraseña</button>
      </form>
    <?php endif; ?>
  </div>
</body>

</html>