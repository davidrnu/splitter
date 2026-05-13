function mostrarMensaje(texto, tipo = 'ok') {
  const msg = document.createElement('div');
  msg.textContent = texto;
  msg.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded shadow text-sm font-medium ${tipo === 'ok' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
  document.body.appendChild(msg);
  setTimeout(() => msg.remove(), 3000);
}

// Recarga balances y gastos desde la API y actualiza el DOM
async function actualizarGrupo() {
  const res   = await fetch(`api/grupos.php?id=${grupoId}`);
  const datos = await res.json();
  if (!datos.ok) return;

  // Balances
  const balances = document.getElementById('seccion-balances');
  if (datos.deudas.length === 0) {
    balances.innerHTML = '<p class="text-sm text-gray-500">Todo en paz.</p>';
  } else {
    balances.innerHTML = '<ul class="text-sm space-y-1">' +
      datos.deudas.map(d => `
        <li>
          <span class="font-medium">${d.deudor}</span>
          le debe
          <span class="font-medium">${parseFloat(d.cantidad).toFixed(2)} €</span>
          a
          <span class="font-medium">${d.acreedor}</span>
        </li>`
      ).join('') +
    '</ul>';
  }

  // Gastos
  const gastos = document.getElementById('seccion-gastos');
  if (datos.gastos.length === 0) {
    gastos.innerHTML = '<p class="text-sm text-gray-500">No hay gastos todavía.</p>';
  } else {
    gastos.innerHTML = '<ul class="space-y-2">' +
      datos.gastos.map(g => `
        <li class="bg-white border border-gray-200 rounded px-4 py-3 text-sm flex justify-between items-start">
          <div>
            <span class="font-medium">${g.descripcion}</span>
            <span class="text-gray-500"> — ${parseFloat(g.importe).toFixed(2)} € · pagado por ${g.pagador}</span>
            ${g.participantes.length ? `<span class="text-gray-400"> (${g.participantes.join(', ')})</span>` : ''}
          </div>
          ${g.puede_borrar ? `<button class="btn-borrar-gasto text-red-400 hover:text-red-600 ml-4" data-id="${g.id}">Borrar</button>` : ''}
        </li>`
      ).join('') +
    '</ul>';
    registrarBotonesBorrar();
  }
}

// Registra el evento de borrar en cada botón de gasto
function registrarBotonesBorrar() {
  document.querySelectorAll('.btn-borrar-gasto').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('¿Borrar este gasto?')) return;

      const datos = new FormData();
      datos.append('accion', 'borrar');
      datos.append('grupo_id', grupoId);
      datos.append('movimiento_id', btn.dataset.id);

      const res = await fetch('api/gastos.php', { method: 'POST', body: datos });
      const resultado = await res.json();
      if (resultado.ok) { actualizarGrupo(); mostrarMensaje('Gasto eliminado'); }
      else mostrarMensaje('Error al eliminar el gasto', 'error');
    });
  });
}

// Añadir gasto
document.getElementById('form-gasto').addEventListener('submit', async (e) => {
  e.preventDefault();

  const datos = new FormData(e.target);
  datos.append('accion', 'crear');
  datos.append('grupo_id', grupoId);

  const res = await fetch('api/gastos.php', { method: 'POST', body: datos });
  const resultado = await res.json();
  if (resultado.ok) {
    e.target.reset();
    actualizarGrupo();
    mostrarMensaje('Gasto añadido correctamente');
  } else {
    mostrarMensaje('Error al añadir el gasto', 'error');
  }
});

// Registrar transferencia
document.getElementById('form-transferencia').addEventListener('submit', async (e) => {
  e.preventDefault();

  const datos = new FormData(e.target);
  datos.append('grupo_id', grupoId);

  const res = await fetch('api/transferencias.php', { method: 'POST', body: datos });
  const resultado = await res.json();
  if (resultado.ok) {
    e.target.reset();
    actualizarGrupo();
    mostrarMensaje('Transferencia registrada');
  } else {
    mostrarMensaje('Error al registrar la transferencia', 'error');
  }
});

// Generar invitación (solo admin)
const btnInvitacion = document.getElementById('btn-invitacion');
if (btnInvitacion) {
  btnInvitacion.addEventListener('click', async () => {
    const datos = new FormData();
    datos.append('grupo_id', grupoId);

    const res = await fetch('api/invitaciones.php', { method: 'POST', body: datos });
    const resultado = await res.json();
    if (resultado.ok) {
      const input = document.getElementById('enlace-invitacion');
      input.value = resultado.enlace;
      input.classList.remove('hidden');
      input.select();
      mostrarMensaje('Enlace de invitación generado');
    } else {
      mostrarMensaje('Error al generar la invitación', 'error');
    }
  });
}

// Borrar grupo (solo admin)
const btnBorrarGrupo = document.getElementById('btn-borrar-grupo');
if (btnBorrarGrupo) {
  btnBorrarGrupo.addEventListener('click', async () => {
    if (!confirm('¿Seguro que quieres borrar este grupo?')) return;

    const datos = new FormData();
    datos.append('accion', 'borrar');
    datos.append('grupo_id', grupoId);

    const res = await fetch('api/grupos.php', { method: 'POST', body: datos });
    const resultado = await res.json();
    if (resultado.ok) location.href = 'index.php';
  });
}

// Registrar botones de borrar que vienen del HTML inicial
registrarBotonesBorrar();
