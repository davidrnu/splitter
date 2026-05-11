<?php

require_once('utils.php');
comprobarSesion();

$id = $_SESSION['usuario_id'];

$db = conectarDB();
$stmt = $db->prepare("
  SELECT g.id, g.nombre, g.descripcion, m.rol
  FROM grupos g
  JOIN miembros m ON m.grupo_id = g.id
  WHERE m.usuario_id = ?
  ORDER BY g.creado_en DESC
");
$stmt->execute([$id]);
$grupos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Splitter</title>
</head>

<body class="bg-gray-50">
  <?php require('header.php'); ?>

  <div class="max-w-xl mx-auto py-8 px-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-xl font-semibold">Tus grupos</h1>
      <button id="btn-nuevo" class="text-sm bg-gray-900 text-white px-3 py-1 rounded hover:bg-gray-700">+ Nuevo</button>
    </div>

    <div id="form-nuevo-grupo" class="hidden bg-white border border-gray-200 rounded p-4 mb-4 space-y-3 text-sm">
      <input id="input-nombre" type="text" placeholder="Nombre del grupo" class="w-full border border-gray-200 rounded px-3 py-2">
      <textarea id="input-desc" placeholder="Descripción (opcional)" class="w-full border border-gray-200 rounded px-3 py-2"></textarea>
      <div class="flex gap-2">
        <button id="btn-crear" class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-700">Crear</button>
        <button id="btn-cancelar" class="text-gray-500 hover:text-gray-700">Cancelar</button>
      </div>
    </div>

    <ul id="lista-grupos" class="space-y-2">
      <?php if (empty($grupos)): ?>
        <p class="text-gray-500 text-sm">No tienes ningún grupo todavía.</p>
      <?php else: ?>
        <?php foreach ($grupos as $grupo): ?>
          <li>
            <a href="grupo.php?id=<?= $grupo['id'] ?>" class="block bg-white rounded border border-gray-200 px-4 py-3 hover:bg-gray-50">
              <span class="font-medium"><?= htmlspecialchars($grupo['nombre']) ?></span>
              <?php if ($grupo['descripcion']): ?>
                <span class="text-gray-400 text-sm ml-2"><?= htmlspecialchars($grupo['descripcion']) ?></span>
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif ?>
    </ul>
  </div>

  <script src="js/index.js"></script>
</body>

</html>
