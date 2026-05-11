<?php

$usuario = $_SESSION["usuario_nombre"];

?>

<script src="https://cdn.tailwindcss.com"></script>

<header class="bg-white border-b border-gray-200 px-6 h-14 flex items-center justify-between">
  <span class="font-bold text-lg">Splitter</span>
  <nav class="flex items-center gap-4 text-sm text-gray-600">
    <span>Hola, <?= htmlspecialchars($usuario) ?></span>
    <a href="/" class="hover:text-gray-900">Inicio</a>
    <a href="logout.php" class="hover:text-gray-900">Cerrar sesión</a>
  </nav>
</header>