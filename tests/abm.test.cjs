// Ejecutar con Playwright disponible: node --test tests/abm.test.cjs
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { expect } = require('playwright/test');

const root = path.join(__dirname, '../ejerciciosPHP/12-sesion/appModulo1');
let browser;
before(async () => {
  browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
});
after(async () => { if (browser) await browser.close(); });

async function abrirApp(t) {
  const page = await browser.newPage();
  t.after(() => page.close());
  const estado = {
    peticiones: [], alertas: [], errores: [], confirmar: true, pdf: 'JVBERi0xLjQK', fallo: '',
    filas: [
      { nroFactura: '1001', codArticulo: 'ART-02', unidadMedida: 'UN', descripcion: '<b>Conector RJ45</b>', fechaFactura: '2026-10-15', cantidad: '50.00', precioUnitario: '0.40', importeRenglon: '20.00' },
      { nroFactura: '1002', codArticulo: 'ART-02', unidadMedida: 'KG', descripcion: 'COBRE', fechaFactura: '2026-10-16', cantidad: '2.00', precioUnitario: '100.00', importeRenglon: '200.00' }
    ]
  };
  page.on('pageerror', error => estado.errores.push(error.message));
  page.on('dialog', async dialog => {
    estado.alertas.push(dialog.message());
    if (dialog.type() === 'confirm' && !estado.confirmar) await dialog.dismiss();
    else await dialog.accept();
  });
  await page.route('https://abm.test/**', async route => {
    const request = route.request();
    const url = new URL(request.url());
    const ruta = url.pathname.substring(1);
    const nombre = path.posix.basename(ruta);
    if (!ruta) {
      const html = fs.readFileSync(path.join(root, 'index.php'), 'utf8').replace(/^<\?php[\s\S]*?\?>\s*/, '');
      return route.fulfill({ contentType: 'text/html', body: html });
    }
    if (['templates/abm.js', 'templates/estilo.css'].includes(ruta)) {
      return route.fulfill({ contentType: nombre.endsWith('.js') ? 'text/javascript' : 'text/css', body: fs.readFileSync(path.join(root, ruta), 'utf8') });
    }
    if (nombre === 'destruirsesion.php') return route.fulfill({ body: 'Sesión cerrada' });
    if (!ruta.startsWith('views/') || !fs.existsSync(path.join(root, ruta))) return route.abort();
    if (nombre === 'datosSesion.php') return route.fulfill({ json: { login: 'admin <prueba>', contador: 29, identificativo: 'sesion-prueba' } });
    const body = request.postData() || '';
    const datos = new URLSearchParams(body);
    estado.peticiones.push({ nombre, metodo: request.method(), body, tipo: request.resourceType(), query: url.searchParams });
    if (nombre === estado.fallo) return route.abort();
    if (nombre === 'salidaJsonUnidades.php') {
      return route.fulfill({ json: { unidades: [{ cod_um: 'UN', descripcion: 'Unidad' }, { cod_um: 'KG', descripcion: 'Kilogramo' }] } });
    }
    if (nombre === 'salidaJsonRenglones.php') {
      const descripcion = datos.get('f_descripcion');
      if (descripcion === 'json-invalido') return route.fulfill({ body: 'Respuesta no JSON' });
      if (descripcion === 'lento') {
        await new Promise(resolve => setTimeout(resolve, 300));
        return route.fulfill({ json: { renglones: [{ ...estado.filas[0], descripcion: 'Respuesta vieja' }], cantidad: 1 } });
      }
      const filtros = ['nroFactura', 'codArticulo', 'unidadMedida', 'descripcion', 'fechaFactura'];
      const filas = estado.filas.filter(fila => filtros.every(campo => !datos.get('f_' + campo) || fila[campo].includes(datos.get('f_' + campo))));
      return route.fulfill({ json: { renglones: filas, cantidad: filas.length } });
    }
    if (nombre === 'traeBinario.php') return route.fulfill({ json: { documentoPdf: estado.pdf } });
    if (nombre === 'baja.php') estado.filas = estado.filas.filter(fila => fila.nroFactura !== '1001');
    return route.fulfill({ contentType: 'text/html', body: '<p>Operación procesada</p>' });
  });
  await page.goto('https://abm.test/');
  await expect(page.locator('#f_unidadMedida option')).toHaveCount(3);
  await expect.poll(() => estado.peticiones.filter(p => p.nombre === 'salidaJsonRenglones.php').length).toBeGreaterThan(0);
  return { page, estado };
}

async function cargar(page) {
  await page.locator('#btnCargar').click();
  await expect(page.locator('#tbDatos tr')).toHaveCount(2);
}

test('La sesión se muestra como texto desde la respuesta PHP', async t => {
  const { page, estado } = await abrirApp(t);
  await expect(page.locator('#usuarioSesion')).toHaveText('admin <prueba>');
  await expect(page.locator('#contadorSesion')).toHaveText('29');
  await expect(page.locator('#identificativoSesion')).toHaveText('sesion-prueba');
  await expect(page.locator('#usuarioSesion prueba')).toHaveCount(0);
  assert.deepEqual(estado.errores, []);
});

test('Inicio, carga, filtros, orden, limpieza y vaciado conservan sus resultados', async t => {
  const { page, estado } = await abrirApp(t);
  await expect(page.locator('#tbDatos')).toContainText('Sin datos.');
  await expect(page.locator('#btEnvioFormAlta')).toBeDisabled();
  await cargar(page);
  await expect(page.locator('#tbDatos tr').first().locator('td')).toHaveCount(11);
  await expect(page.locator('#tbDatos tr').first()).toContainText('<b>Conector RJ45</b>');
  await expect(page.locator('#tbDatos b')).toHaveCount(0);
  await expect(page.locator('#pie')).toHaveText('Nro de registros: 2');
  await page.locator('#f_codArticulo').fill('ART-02');
  await page.locator('#f_nroFactura').fill('1002');
  await page.locator('#f_descripcion').fill('COBRE');
  await page.locator('#f_unidadMedida').selectOption('KG');
  await page.locator('#f_fechaFactura').fill('2026-10-16');
  await expect(page.locator('#tbDatos tr')).toHaveCount(1);
  await expect(page.locator('#tbDatos')).toContainText('COBRE');
  await page.locator('th[data-campo="cantidad"]').click();
  await expect(page.locator('#orden')).toHaveValue('cantidad');
  await expect.poll(() => estado.peticiones.some(p => p.body.includes('orden=cantidad') && p.body.includes('f_fechaFactura=2026-10-16'))).toBe(true);
  await page.locator('#btnLimpiar').click();
  await expect(page.locator('#tbDatos tr')).toHaveCount(2);
  for (const id of ['f_nroFactura', 'f_codArticulo', 'f_descripcion', 'f_unidadMedida', 'f_fechaFactura']) await expect(page.locator('#' + id)).toHaveValue('');
  await page.locator('#btnVaciar').click();
  await expect(page.locator('#pie')).toHaveText('Nro de registros: 0');
  await expect(page.locator('#tbDatos')).toHaveText('Sin datos.');
  assert.deepEqual(estado.errores, []);
});

test('Alta muestra ejemplos y restaura el prefijo ART- al abrir el formulario', async t => {
  const { page, estado } = await abrirApp(t);
  await page.locator('#btnAlta').click();
  await expect(page.locator('#formArticulosEntCodArtAlta')).toHaveValue('ART-');
  const ejemplos = { Nro: 'Ej: 1005', CodArt: 'Ej: ART-04', Descripcion: 'Ej: Cable de red Cat 6', Cantidad: 'Ej: 10', Precio: 'Ej: 1500.00' };
  for (const [campo, ejemplo] of Object.entries(ejemplos)) {
    await expect(page.locator('#formArticulosEnt' + campo + 'Alta')).toHaveAttribute('placeholder', ejemplo);
  }
  await expect(page.locator('#btEnvioFormAlta')).toBeDisabled();
  await page.locator('#formArticulosEntCodArtAlta').fill('ART-99');
  await page.locator('#ventanaModalFormularioAlta .cerrar').click();
  await page.locator('#btnAlta').click();
  await expect(page.locator('#formArticulosEntCodArtAlta')).toHaveValue('ART-');
  assert.deepEqual(estado.errores, []);
});

test('Alta valida la clave compuesta, admite otra factura y envía el PDF con FormData', async t => {
  const { page, estado } = await abrirApp(t);
  await page.locator('#btnAlta').click();
  await expect(page.locator('#ventanaModalFormularioAlta')).toBeVisible();
  assert.equal(await page.locator('#formArticulosEntFechaAlta').inputValue(), await page.evaluate(() => new Date().toISOString().substring(0, 10)));
  for (const [campo, valor] of Object.entries({ Nro: '1001', CodArt: ' art-02 ', Descripcion: 'Nuevo artículo', Cantidad: '2', Precio: '3' })) await page.locator('#formArticulosEnt' + campo + 'Alta').fill(valor);
  await page.locator('#formArticulosEntUmAlta').selectOption('UN');
  await expect(page.locator('#msgErrorCodArtAlta')).toBeVisible();
  await expect(page.locator('#btEnvioFormAlta')).toBeDisabled();
  await page.locator('#formArticulosEntNroAlta').fill('1009');
  await expect(page.locator('#msgErrorCodArtAlta')).toBeHidden();
  await expect(page.locator('#btEnvioFormAlta')).toBeEnabled();
  await page.locator('#formArticulosEntDocumentoPdfAlta').setInputFiles({ name: 'factura.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\nPrueba') });
  await page.locator('#btEnvioFormAlta').click();
  await expect(page.locator('#ventanaModalRespuesta')).toBeVisible();
  await expect(page.locator('#ventanaModalFormularioAlta')).toBeHidden();
  await expect(page.locator('#tbDatos tr')).toHaveCount(2);
  const alta = estado.peticiones.find(p => p.nombre === 'alta.php');
  assert.equal(alta.metodo, 'POST');
  for (const texto of ['name="nroFactura"', '1009', 'name="codArticulo"', 'art-02', 'factura.pdf', '%PDF-1.4']) assert(alta.body.includes(texto), texto);
  await page.locator('#pieModalRespuesta button').click();
  await expect(page.locator('#ventanaModalRespuesta')).toBeHidden();
  assert.deepEqual(estado.errores, []);
});

test('Modificar completa la ficha, valida campos y envía los datos originales de la clave', async t => {
  const { page, estado } = await abrirApp(t);
  await cargar(page);
  await page.locator('.btCeldaModi').first().click();
  await expect(page.locator('#formArticulosEntNroModi')).toHaveValue('1001');
  await expect(page.locator('#formArticulosEntCodArtModi')).toHaveValue('ART-02');
  await expect(page.locator('#formArticulosEntUmModi')).toHaveValue('UN');
  await expect(page.locator('#formArticulosEntFechaModi')).toHaveValue('2026-10-15');
  await page.locator('#formArticulosEntDescripcionModi').fill('');
  await expect(page.locator('#btEnvioFormModi')).toBeDisabled();
  await page.locator('#formArticulosEntDescripcionModi').fill('Modificado');
  await page.locator('#btEnvioFormModi').click();
  await expect(page.locator('#ventanaModalFormularioModi')).toBeHidden();
  await expect(page.locator('#contenidoModalRespuesta')).toHaveText('<p>Operación procesada</p>');
  await expect(page.locator('#contenidoModalRespuesta p')).toHaveCount(0);
  const modi = estado.peticiones.find(p => p.nombre === 'modi.php');
  for (const texto of ['1001', 'ART-02', 'Modificado', 'name="documentoPdf"']) assert(modi.body.includes(texto), texto);
  assert.deepEqual(estado.errores, []);
});

test('Baja requiere confirmación, envía la clave compuesta y actualiza la tabla', async t => {
  const { page, estado } = await abrirApp(t);
  await cargar(page);
  estado.confirmar = false;
  await page.locator('.btCeldaBaja').first().click();
  assert(!estado.peticiones.some(p => p.nombre === 'baja.php'));
  estado.confirmar = true;
  await page.locator('.btCeldaBaja').first().click();
  await expect(page.locator('#tbDatos tr')).toHaveCount(1);
  const baja = estado.peticiones.find(p => p.nombre === 'baja.php');
  assert(baja.body.includes('1001') && baja.body.includes('ART-02'));
  await expect(page.locator('#contenidoModalRespuesta')).toHaveText('<p>Operación procesada</p>');
  assert.deepEqual(estado.errores, []);
});

test('PDF abre el iframe, cierra y avisa cuando no hay documento', async t => {
  const { page, estado } = await abrirApp(t);
  await cargar(page);
  await page.locator('.btCeldaPdf').first().click();
  await expect(page.locator('#ventanaModalPdf')).toBeVisible();
  await expect(page.locator('#iframePdf')).toHaveAttribute('src', 'data:application/pdf;base64,' + estado.pdf);
  const pdf = estado.peticiones.find(p => p.nombre === 'traeBinario.php');
  assert.equal(pdf.query.get('nroFactura'), '1001');
  assert.equal(pdf.query.get('codArticulo'), 'ART-02');
  await page.locator('#ventanaModalPdf .cerrar').click();
  await expect(page.locator('#iframePdf')).toHaveAttribute('src', '');
  await expect(page.locator('#ventanaModalPdf')).toBeHidden();
  estado.pdf = null;
  await page.locator('.btCeldaPdf').first().click();
  await expect.poll(() => estado.alertas.some(texto => texto.includes('No hay documento PDF'))).toBe(true);
  assert.deepEqual(estado.errores, []);
});

test('El filtro espera al escribir e ignora respuestas de búsquedas anteriores', async t => {
  const { page, estado } = await abrirApp(t);
  const antes = estado.peticiones.length;
  await page.locator('#f_descripcion').fill('lento');
  assert.equal(estado.peticiones.length, antes);
  await page.locator('#f_descripcion').press('Enter');
  await expect.poll(() => estado.peticiones.some(p => p.body.includes('f_descripcion=lento'))).toBe(true);
  await page.locator('#f_descripcion').fill('COBRE');
  await page.locator('#f_descripcion').press('Enter');
  await expect(page.locator('#tbDatos')).toContainText('COBRE');
  await page.waitForTimeout(400);
  await expect(page.locator('#tbDatos')).not.toContainText('Respuesta vieja');
  assert.deepEqual(estado.errores, []);
});

test('Enter ejecuta una sola búsqueda sin repetirla después de los 300 ms', async t => {
  const { page, estado } = await abrirApp(t);
  await page.locator('#f_descripcion').fill('COBRE');
  await page.locator('#f_descripcion').press('Enter');
  await expect(page.locator('#tbDatos')).toContainText('COBRE');
  await page.waitForTimeout(400);
  const busquedas = estado.peticiones.filter(p => p.nombre === 'salidaJsonRenglones.php' && new URLSearchParams(p.body).get('f_descripcion') === 'COBRE');
  assert.equal(busquedas.length, 1);
  assert.deepEqual(estado.errores, []);
});

test('Elegir una unidad ejecuta una sola búsqueda', async t => {
  const { page, estado } = await abrirApp(t);
  await page.locator('#f_unidadMedida').selectOption('KG');
  await expect(page.locator('#tbDatos')).toContainText('COBRE');
  const busquedas = estado.peticiones.filter(p => p.nombre === 'salidaJsonRenglones.php' && new URLSearchParams(p.body).get('f_unidadMedida') === 'KG');
  assert.equal(busquedas.length, 1);
  assert.deepEqual(estado.errores, []);
});

test('Errores de JSON y de red se notifican sin excepciones sin controlar', async t => {
  const { page, estado } = await abrirApp(t);
  await page.locator('#f_descripcion').fill('json-invalido');
  await page.locator('#btnCargar').click();
  await expect.poll(() => estado.alertas.some(texto => texto.startsWith('Error:'))).toBe(true);
  estado.fallo = 'salidaJsonRenglones.php';
  const antes = estado.alertas.length;
  await page.locator('#btnCargar').click();
  await expect.poll(() => estado.alertas.slice(antes).some(texto => texto.startsWith('Error:'))).toBe(true);
  assert.deepEqual(estado.errores, []);
});

test('Los fallos de alta, modificación, baja y PDF muestran un error sin cerrar los formularios', async t => {
  const { page, estado } = await abrirApp(t);
  await cargar(page);
  estado.fallo = 'traeBinario.php';
  await page.locator('.btCeldaPdf').first().click();
  await expect.poll(() => estado.alertas.some(texto => texto.startsWith('Error al obtener PDF:'))).toBe(true);
  await expect(page.locator('#ventanaModalPdf')).toBeHidden();
  estado.fallo = 'baja.php';
  await page.locator('.btCeldaBaja').first().click();
  await expect.poll(() => estado.alertas.some(texto => texto.startsWith('Error en baja:'))).toBe(true);
  await expect(page.locator('#tbDatos tr')).toHaveCount(2);
  estado.fallo = 'modi.php';
  await page.locator('.btCeldaModi').first().click();
  await page.locator('#btEnvioFormModi').click();
  await expect.poll(() => estado.alertas.some(texto => texto.startsWith('Error en modi:'))).toBe(true);
  await expect(page.locator('#ventanaModalFormularioModi')).toBeVisible();
  await page.locator('#ventanaModalFormularioModi .cerrar').click();
  estado.fallo = 'alta.php';
  await page.locator('#btnAlta').click();
  for (const [campo, valor] of Object.entries({ Nro: '1009', CodArt: 'ART-99', Descripcion: 'Prueba', Cantidad: '2', Precio: '3' })) await page.locator('#formArticulosEnt' + campo + 'Alta').fill(valor);
  await page.locator('#formArticulosEntUmAlta').selectOption('UN');
  await page.locator('#btEnvioFormAlta').click();
  await expect.poll(() => estado.alertas.some(texto => texto.startsWith('Error en alta:'))).toBe(true);
  await expect(page.locator('#ventanaModalFormularioAlta')).toBeVisible();
  assert.deepEqual(estado.errores, []);
});

test('Cerrar sesión conserva la navegación al PHP correspondiente', async t => {
  const { page } = await abrirApp(t);
  await page.locator('#btCierraSesion').click();
  await expect(page).toHaveURL('https://abm.test/destruirsesion.php');
});

test('La aplicación usa fetch sin cargar jQuery', async t => {
  const { page, estado } = await abrirApp(t);
  assert.equal(await page.evaluate(() => typeof window.jQuery), 'undefined');
  assert(estado.peticiones.every(p => p.tipo === 'fetch'));
  assert.deepEqual(estado.errores, []);
});
