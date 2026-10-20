<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado — Orden y Filtros (Renglones de factura)</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../index.php">Volver al menú</a>
  <h1>Renglones de factura de ventas</h1>
  <p>Alumno: Guido Olivero — Tabla 11</p>
  <div class="controles">
    <label>Orden: <input type="text" id="orden" value="nroFactura" readonly></label>
    <button type="button" id="btnCargar">Cargar datos</button>
    <button type="button" id="btnVaciar">Vaciar datos</button>
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
        </tr>
        <tr class="filtros">
          <td><input type="text" id="f_nroFactura" placeholder="Nro"></td>
          <td><input type="text" id="f_codArticulo" placeholder="Cód."></td>
          <td><select id="f_unidadMedida"><option value="">— todas —</option></select></td>
          <td><input type="text" id="f_descripcion" placeholder="Descripción"></td>
          <td><input type="date" id="f_fechaFactura"></td>
          <td></td><td></td><td></td>
        </tr>
      </thead>
      <tbody id="cuerpo"><tr><td colspan="8">Sin datos. Pulsa “Cargar datos”.</td></tr></tbody>
      <tfoot><tr><td colspan="8" id="pie">Nro de registros: 0</td></tr></tfoot>
    </table>
  </div>
  <footer><p>Renglones de factura de ventas — Guido Olivero</p></footer>
  <script>
    window.addEventListener('DOMContentLoaded', cargarUnidades);
    async function cargarUnidades() {
      try {
        const resp = await fetch('salidaJsonUnidades.php');
        const texto = await resp.text();
        alert(texto);
        const data = JSON.parse(texto);
        const select = document.getElementById('f_unidadMedida');
        (data.unidades || []).forEach(u => {
          const vals = Object.values(u);
          const val = u.codigo || u.codUm || u.cod_um || u.unidadMedida || u.unidad_medida || u.id || vals[0] || '';
          const nom = u.descripcion || u.descripcionUm || u.descripcion_um || u.nombre || vals[1] || val;
          const opt = document.createElement('option');
          opt.value = val; opt.textContent = nom;
          select.appendChild(opt);
        });
      } catch (e) { alert('Error cargando unidades: ' + e); }
    }
    document.querySelectorAll('th[data-campo]').forEach(th => {
      th.addEventListener('click', () => { document.getElementById('orden').value = th.dataset.campo; });
    });
    document.getElementById('btnCargar').addEventListener('click', cargarDatos);
    document.getElementById('btnVaciar').addEventListener('click', vaciar);
    async function cargarDatos() {
      const params = new URLSearchParams();
      params.append('orden', document.getElementById('orden').value);
      params.append('f_nroFactura', document.getElementById('f_nroFactura').value);
      params.append('f_codArticulo', document.getElementById('f_codArticulo').value);
      params.append('f_unidadMedida', document.getElementById('f_unidadMedida').value);
      params.append('f_descripcion', document.getElementById('f_descripcion').value);
      params.append('f_fechaFactura', document.getElementById('f_fechaFactura').value);
      alert('Lo que sale del navegador en el body:\n' + params.toString());
      document.getElementById('cuerpo').innerHTML = '<tr><td colspan="8">Esperando respuesta…</td></tr>';
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
      if (!filas.length) { tbody.innerHTML = '<tr><td colspan="8">Sin resultados.</td></tr>'; return; }
      tbody.innerHTML = filas.map(r => {
        const nro  = r.nroFactura ?? r.nro_factura ?? '';
        const cod  = r.codArticulo ?? r.cod_articulo ?? '';
        const um   = r.unidadMedida ?? r.unidad_medida ?? r.codUm ?? r.cod_um ?? r.um ?? r.id_unidad ?? '';
        const desc = r.descripcion ?? '';
        const fec  = r.fechaFactura ?? r.fecha_factura ?? r.fecha ?? '';
        const cant = r.cantidad ?? '';
        const prec = r.precioUnitario ?? r.precio_unitario ?? r.precio ?? '';
        const imp  = r.importeRenglon ?? r.importe_renglon ?? r.importe ?? '';
        return `<tr><td>${nro}</td><td>${cod}</td><td>${um}</td><td>${desc}</td><td>${fec}</td><td>${cant}</td><td>${prec}</td><td>${imp}</td></tr>`;
      }).join('');
    }
    function vaciar() {
      document.getElementById('cuerpo').innerHTML = '<tr><td colspan="8">Sin datos.</td></tr>';
      document.getElementById('pie').textContent = 'Nro de registros: 0';
    }
  </script>
</body>
</html>
