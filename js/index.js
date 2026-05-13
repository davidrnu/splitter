function mostrarMensaje(texto, tipo = 'ok') {
  const msg = document.createElement('div');
  msg.textContent = texto;
  msg.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded shadow text-sm font-medium ${tipo === 'ok' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
  document.body.appendChild(msg);
  setTimeout(() => msg.remove(), 3000);
}

document.getElementById('btn-nuevo').addEventListener('click', () => {
  document.getElementById('form-nuevo-grupo').classList.toggle('hidden');
});

document.getElementById('btn-cancelar').addEventListener('click', () => {
  document.getElementById('form-nuevo-grupo').classList.add('hidden');
});

document.getElementById('btn-crear').addEventListener('click', async () => {
  const nombre      = document.getElementById('input-nombre').value.trim();
  const descripcion = document.getElementById('input-desc').value.trim();
  if (!nombre) return;

  const datos = new FormData();
  datos.append('accion', 'crear');
  datos.append('nombre', nombre);
  datos.append('descripcion', descripcion);

  const res      = await fetch('api/grupos.php', { method: 'POST', body: datos });
  const resultado = await res.json();

  if (resultado.ok) {
    const lista = document.getElementById('lista-grupos');
    lista.innerHTML += `
      <li>
        <a href="grupo.php?id=${resultado.id}" class="block bg-white rounded border border-gray-200 px-4 py-3 hover:bg-gray-50">
          <span class="font-medium">${resultado.nombre}</span>
          ${resultado.descripcion ? `<span class="text-gray-400 text-sm ml-2">${resultado.descripcion}</span>` : ''}
        </a>
      </li>`;
    document.getElementById('form-nuevo-grupo').classList.add('hidden');
    document.getElementById('input-nombre').value = '';
    document.getElementById('input-desc').value   = '';
    mostrarMensaje('Grupo creado correctamente');
  } else {
    mostrarMensaje('Error al crear el grupo', 'error');
  }
});
