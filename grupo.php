<?php

require_once('utils.php');
comprobarSesion();

if (!isset($_GET["id"])) {
  header("location: index.php");
  exit;
}

$grupoId   = (int)$_GET['id'];
$usuarioId = $_SESSION["usuario_id"];

$db    = conectarDB();
$grupo = obtenerGrupo($db, $grupoId);

if (!$grupo) {
  header("location: index.php");
  exit;
}

$rol = obtenerRolEnGrupo($db, $grupoId, $usuarioId);

if ($rol === false) {
  header("location: index.php");
  exit;
}

$miembros                   = obtenerMiembros($db, $grupoId);
$movimientos                = obtenerMovimientos($db, $grupoId);
$participantesPorMovimiento = obtenerParticipantesPorMovimiento($db, $grupoId);
$deudas                     = calcularDeudas($db, $grupoId, $miembros);

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($grupo["nombre"]) ?></title>
</head>

<body class="bg-gray-50">
  <?php require("header.php") ?>

  <div class="max-w-xl mx-auto py-8 px-4 space-y-8">

    <div>
      <a href="/" class="text-sm text-gray-400 hover:text-gray-600">← Volver</a>
      <h1 class="text-xl font-semibold mt-1"><?= htmlspecialchars($grupo["nombre"]) ?></h1>
      <?php if ($grupo["descripcion"]): ?>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($grupo["descripcion"]) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <h2 class="font-medium mb-2">Miembros</h2>
      <ul class="text-sm text-gray-600 space-y-1">
        <?php foreach ($miembros as $m): ?>
          <li><?= htmlspecialchars($m['nombre']) ?> <span class="text-gray-400">(<?= $m['rol'] ?>)</span></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($rol === 'admin'): ?>
        <div class="mt-3 flex gap-2">
          <button id="btn-invitacion" class="text-sm bg-gray-900 text-white px-3 py-1 rounded hover:bg-gray-700">Generar invitación</button>
          <button id="btn-borrar-grupo" class="text-sm text-red-500 hover:text-red-700">Borrar grupo</button>
        </div>
        <input id="enlace-invitacion" class="hidden mt-2 w-full text-sm border border-gray-200 rounded px-2 py-1" type="text" readonly onclick="this.select()">
      <?php endif; ?>
    </div>

    <div>
      <h2 class="font-medium mb-2">Balances</h2>
      <div id="seccion-balances">
        <?php if (empty($deudas)): ?>
          <p class="text-sm text-gray-500">Todo en paz.</p>
        <?php else: ?>
          <ul class="text-sm space-y-1">
            <?php foreach ($deudas as $d):
              if (round($d['neto'], 2) == 0) continue;
              if ($d['neto'] > 0) {
                $deudor = $d['nombres'][$d['a']]; $acreedor = $d['nombres'][$d['b']]; $cantidad = $d['neto'];
              } else {
                $deudor = $d['nombres'][$d['b']]; $acreedor = $d['nombres'][$d['a']]; $cantidad = abs($d['neto']);
              }
            ?>
              <li><span class="font-medium"><?= htmlspecialchars($deudor) ?></span> le debe <span class="font-medium"><?= number_format($cantidad, 2) ?> €</span> a <span class="font-medium"><?= htmlspecialchars($acreedor) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h2 class="font-medium mb-2">Gastos</h2>
      <div id="seccion-gastos">
        <?php if (empty($movimientos)): ?>
          <p class="text-sm text-gray-500">No hay gastos todavía.</p>
        <?php else: ?>
          <ul class="space-y-2">
            <?php foreach ($movimientos as $mov): ?>
              <?php $participantes = $participantesPorMovimiento[$mov['id']] ?? []; ?>
              <li class="bg-white border border-gray-200 rounded px-4 py-3 text-sm flex justify-between items-start">
                <div>
                  <span class="font-medium"><?= htmlspecialchars($mov['descripcion']) ?></span>
                  <span class="text-gray-500"> — <?= number_format($mov['importe'], 2) ?> € · pagado por <?= htmlspecialchars($mov['pagador']) ?></span>
                  <?php if (!empty($participantes)): ?>
                    <span class="text-gray-400"> (<?= htmlspecialchars(implode(', ', $participantes)) ?>)</span>
                  <?php endif; ?>
                </div>
                <?php if ($rol === 'admin' || $mov['pagador_id'] === $usuarioId): ?>
                  <button class="btn-borrar-gasto text-red-400 hover:text-red-600 ml-4" data-id="<?= $mov['id'] ?>">Borrar</button>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h2 class="font-medium mb-2">Registrar transferencia</h2>
      <form id="form-transferencia" class="space-y-2 text-sm">
        <div class="flex gap-2">
          <select name="pagador_id" class="border border-gray-200 rounded px-2 py-1">
            <?php foreach ($miembros as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="self-center text-gray-400">→</span>
          <select name="receptor_id" class="border border-gray-200 rounded px-2 py-1">
            <?php foreach ($miembros as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="number" name="importe" step="0.01" min="0.01" placeholder="€" class="border border-gray-200 rounded px-2 py-1 w-20" required>
          <button type="submit" class="bg-gray-900 text-white px-3 py-1 rounded hover:bg-gray-700">Registrar</button>
        </div>
      </form>
    </div>

    <div>
      <h2 class="font-medium mb-2">Registrar gasto</h2>
      <form id="form-gasto" class="space-y-3 text-sm">
        <div>
          <input type="text" name="descripcion" placeholder="Descripción" required class="border border-gray-200 rounded px-2 py-1 w-full">
        </div>
        <div class="flex gap-2">
          <input type="number" name="importe" step="0.01" min="0.01" placeholder="Importe €" required class="border border-gray-200 rounded px-2 py-1 w-28">
          <select name="pagador_id" class="border border-gray-200 rounded px-2 py-1">
            <?php foreach ($miembros as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <p class="text-gray-500 mb-1">Entre quiénes:</p>
          <div class="flex gap-3 flex-wrap">
            <?php foreach ($miembros as $m): ?>
              <label class="flex items-center gap-1">
                <input type="checkbox" name="participantes[]" value="<?= $m['id'] ?>" checked>
                <?= htmlspecialchars($m['nombre']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="bg-gray-900 text-white px-3 py-1 rounded hover:bg-gray-700">Añadir gasto</button>
      </form>
    </div>

  </div>

  <script>
    const grupoId   = <?= $grupoId ?>;
    const usuarioId = <?= $usuarioId ?>;
    const rol       = <?= json_encode($rol) ?>;
  </script>
  <script src="js/grupo.js"></script>
</body>

</html>
