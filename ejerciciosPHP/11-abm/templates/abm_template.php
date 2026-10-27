<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>ABM — Renglones de factura (Patrón MVT)</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>
  <h1>Renglones de factura de ventas</h1>
  <p>Alumno: Guido Olivero — Tabla 11 — ABM (Patrón MVT)</p>
  <div class="controles">
    <label>Orden: <input type="text" id="orden" value="nroFactura" readonly></label>
    <button type="button" id="btnCargar">Cargar datos</button>
    <button type="button" id="btnVaciar">Vaciar datos</button>
    <button type="button" id="btnLimpiar">Limpiar filtros</button>
    <button type="button" id="btnAlta">Alta registro</button>
  </div>
  <div class="tabla-wrap">
    <table>
      <thead>
        <tr class="titulos">
          <th data-campo="nroFactura">Nro Factura</th>
          <th data-campo="codArticulo">Cod. artículo</th>
          <th data-campo="unidadMedida">UM</th>
          <th data-campo="descripcion">Descripción</th>
          <th data-campo="fechaFactura">Fecha</th>
          <th data-campo="cantidad">Cantidad</th>
          <th data-campo="precioUnitario">Precio</th>
          <th data-campo="importeRenglon">Importe</th>
          <th>Modis</th>
          <th>Bajas</th>
        </tr>
        <tr class="filtros">
          <td><input type="text" id="f_nroFactura"></td>
          <td><input type="text" id="f_codArticulo"></td>
          <td><select id="f_unidadMedida"><option value="">— todas —</option></select></td>
          <td><input type="text" id="f_descripcion"></td>
          <td><input type="date" id="f_fechaFactura"></td>
          <td></td><td></td><td></td><td></td><td></td>
        </tr>
      </thead>
      <tbody id="cuerpo"><tr><td colspan="10">Sin datos. Pulsa “Cargar datos”.</td></tr></tbody>
      <tfoot><tr><td colspan="10" id="pie">Nro de registros: 0</td></tr></tfoot>
    </table>
  </div>
  <footer><p>Renglones de factura de ventas — Guido Olivero</p></footer>

  <div class="modal" id="modalForm">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarModal()">&times;</span>
      <h3 id="tituloModal">Formulario</h3>
      <form id="formABM">
        <label>Nro Factura: <input type="text" name="nroFactura" id="m_nro" required></label>
        <label>Fecha: <input type="date" name="fechaFactura" id="m_fecha" required></label>
        <label>Cod. artículo: <input type="text" name="codArticulo" id="m_cod" required></label>
        <label>Descripción: <input type="text" name="descripcion" id="m_desc" required></label>
        <label>Unidad de medida: <select name="unidadMedida" id="m_um" required></select></label>
        <label>Cantidad: <input type="number" name="cantidad" id="m_cant" step="0.01" required></label>
        <label>Precio unitario: <input type="number" name="precioUnitario" id="m_precio" step="0.01" required></label>
        <button type="submit" id="btnEnviar">Enviar</button>
      </form>
      <div class="respuesta" id="respuesta"></div>
    </div>
  </div>

  <script>
    let modo = 'alta';
    window.addEventListener('DOMContentLoaded', cargarUnidades);

    async function cargarUnidades() {
      try {
        const resp = await fetch('salidaJsonUnidades.php');
        const texto = await resp.text();
        alert(texto);
        const data = JSON.parse(texto);
        const selFiltro = document.getElementById('f_unidadMedida');
        const selModal = document.getElementById('m_um');
        (data.unidades || []).forEach(u => {
          const val = u.cod_um || u.codigo || u.codUm || u.id || u.descripcion || '';
          const nom = u.descripcion || u.descripcionUm || u.nombre || val;
          const opt1 = document.createElement('option'); opt1.value = val; opt1.textContent = nom; selFiltro.appendChild(opt1);
          const opt2 = document.createElement('option'); opt2.value = val; opt2.textContent = nom; selModal.appendChild(opt2);
        });
      } catch (e) { alert('Error unidades: ' + e); }
    }

    document.querySelectorAll('th[data-campo]').forEach(th => th.addEventListener('click', () => document.getElementById('orden').value = th.dataset.campo));
    document.getElementById('btnCargar').onclick = cargarDatos;
    document.getElementById('btnVaciar').onclick = () => { document.getElementById('cuerpo').innerHTML = '<tr><td colspan="10">Sin datos.</td></tr>'; document.getElementById('pie').textContent = 'Nro de registros: 0'; };
    document.getElementById('btnLimpiar').onclick = () => { ['f_nroFactura','f_codArticulo','f_descripcion','f_fechaFactura'].forEach(id => document.getElementById(id).value = ''); document.getElementById('f_unidadMedida').value = ''; };
    document.getElementById('btnAlta').onclick = () => abrirModal('alta');

    async function cargarDatos() {
      const params = new URLSearchParams();
      params.append('orden', document.getElementById('orden').value);
      params.append('f_nroFactura', document.getElementById('f_nroFactura').value);
      params.append('f_codArticulo', document.getElementById('f_codArticulo').value);
      params.append('f_unidadMedida', document.getElementById('f_unidadMedida').value);
      params.append('f_descripcion', document.getElementById('f_descripcion').value);
      params.append('f_fechaFactura', document.getElementById('f_fechaFactura').value);
      alert('Lo que sale del navegador en el body:\n' + params.toString());
      document.getElementById('cuerpo').innerHTML = '<tr><td colspan="10">Esperando respuesta…</td></tr>';
      try {
        const resp = await fetch('salidaJsonRenglones.php', { method: 'POST', body: params });
        const texto = await resp.text();
        alert(texto);
        const data = JSON.parse(texto);
        pintar(data.renglones || []);
        document.getElementById('pie').textContent = 'Nro de registros: ' + (data.cantidad || 0);
      } catch (e) { alert('Error: ' + e); }
    }

    function pintar(filas) {
      const tbody = document.getElementById('cuerpo');
      if (!filas.length) { tbody.innerHTML = '<tr><td colspan="10">Sin resultados.</td></tr>'; return; }
      tbody.innerHTML = filas.map(r => `<tr>
        <td>${r.nroFactura??''}</td>
        <td>${r.codArticulo??''}</td>
        <td>${r.unidadMedida??''}</td>
        <td>${r.descripcion??''}</td>
        <td>${r.fechaFactura??''}</td>
        <td>${r.cantidad??''}</td>
        <td>${r.precioUnitario??''}</td>
        <td>${r.importeRenglon??''}</td>
        <td><button type="button" onclick='abrirModi(${JSON.stringify(r)})'>Modi</button></td>
        <td><button type="button" onclick='borrar("${r.nroFactura}","${r.codArticulo}")'>Borrar</button></td>
      </tr>`).join('');
    }

    function abrirModal(m) {
      modo = m;
      document.getElementById('tituloModal').textContent = m === 'alta' ? 'Formulario de alta' : 'Formulario de modificación';
      document.getElementById('btnEnviar').textContent = m === 'alta' ? 'Enviar Alta' : 'Enviar Modi';
      document.getElementById('respuesta').innerHTML = '';
      if (m === 'alta') {
        document.getElementById('formABM').reset();
        document.getElementById('m_nro').readOnly = false;
        document.getElementById('m_cod').readOnly = false;
      }
      document.getElementById('modalForm').classList.add('activo');
    }

    function abrirModi(r) {
      abrirModal('modi');
      document.getElementById('m_nro').value = r.nroFactura || '';
      document.getElementById('m_nro').readOnly = true;
      document.getElementById('m_fecha').value = (r.fechaFactura || '').substring(0,10);
      document.getElementById('m_cod').value = r.codArticulo || '';
      document.getElementById('m_cod').readOnly = true;
      document.getElementById('m_desc').value = r.descripcion || '';
      document.getElementById('m_um').value = r.unidadMedida || '';
      document.getElementById('m_cant').value = r.cantidad || '';
      document.getElementById('m_precio').value = r.precioUnitario || '';
    }

    function cerrarModal() {
      document.getElementById('modalForm').classList.remove('activo');
    }

    document.getElementById('formABM').addEventListener('submit', async function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      const url = modo === 'alta' ? 'alta.php' : 'modi.php';
      const resp = await fetch(url, { method: 'POST', body: fd });
      const texto = await resp.text();
      document.getElementById('respuesta').innerHTML = texto;
      alert(texto);
      cargarDatos();
    });

    async function borrar(nro, cod) {
      if (!confirm('¿Borrar renglón ' + nro + ' / ' + cod + '?')) return;
      const fd = new FormData();
      fd.append('nroFactura', nro);
      fd.append('codArticulo', cod);
      const resp = await fetch('baja.php', { method: 'POST', body: fd });
      const texto = await resp.text();
      alert(texto);
      cargarDatos();
    }
  </script>
</body>
</html>
