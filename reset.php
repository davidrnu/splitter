<?php
require_once('utils.php');
require_once('vendor/autoload.php');

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  if ($email === "") {
    $error = "Introduce el email.";
  } else {
    $db = conectarDB();

    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
      $token = bin2hex(random_bytes(32));
      $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

      $stmt = $db->prepare("INSERT INTO tokens_reset (usuario_id, token, expira_en) VALUES (?, ?, ?)");
      $stmt->execute([$usuario['id'], $token, $expira]);

      $dominio = $_ENV["DOMAIN"];
      $resend = Resend::client($_ENV['RESEND_API_KEY']);
      $enlace = "$dominio/resetPassword.php?token=$token";

      $resend->emails->send([
        'from' => 'Splitter <splitter@davidrnu.me>',
        'to' => [$email],
        'subject' => 'Recupera tu contraseña - Splitter',
        'html' => "
          <h2>Recuperar contraseña</h2>
          <p>Has solicitado cambiar tu contraseña. Haz clic en el siguiente enlace:</p>
          <p><a href='$enlace'>Cambiar mi contraseña</a></p>
          <p>Este enlace expira en 1 hora.</p>
          <p>Si no has sido tú, ignora este email.</p>
        "

      ]);
    }

    $mensaje = "Si el email está registrado, recibirás un enlace de recuperación.";
  }
}

?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Splitter - Restablecer contraseña</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white border border-gray-200 rounded-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-semibold mb-6">Restablecer contraseña</h1>

    <?php if ($mensaje): ?>
      <p class="text-green-600 text-sm mb-4"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
      <p class="text-red-500 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="text-sm text-gray-600">Tu email</label>
        <input type="email" name="email" required
          class="mt-1 w-full border border-gray-200 rounded px-3 py-2 text-sm">
      </div>
      <button class="w-full bg-gray-900 text-white py-2 rounded text-sm hover:bg-gray-700">Enviar enlace</button>
    </form>

    <p class="mt-4 text-sm text-gray-500"><a href="login.php" class="hover:text-gray-900">Volver al login</a></p>
  </div>
</body>

</html>