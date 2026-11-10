// Requiere @php-wasm/node y @php-wasm/universal; PHP_WASM_MODULES permite usarlos desde /tmp.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL, fileURLToPath } from 'node:url';

const modulos = process.env.PHP_WASM_MODULES;
const cargar = nombre => import(modulos ? pathToFileURL(path.join(modulos, nombre, 'index.js')).href : nombre);
const { PHP } = await cargar('@php-wasm/universal');
const { loadNodeRuntime } = await cargar('@php-wasm/node');
const php = new PHP(await loadNodeRuntime('8.2', { emscriptenOptions: { processId: 1 } }));
const raiz = fileURLToPath(new URL('../ejerciciosPHP/12-sesion/', import.meta.url));
php.mkdir('/ej12');
php.mkdir('/ej12/appModulo1');
php.mkdir('/ej12/appModulo1/templates');

const rutas = [
    ['index.php', './formularioDeLogin.html'],
    ['destruirsesion.php', './formularioDeLogin.html'],
    ['descargarErrores.php', './formularioDeLogin.html'],
    ['seedUsuario.php', './formularioDeLogin.html'],
    ['appModulo1/index.php', '../index.php'],
    ['appModulo1/alta.php', '../index.php'],
    ['appModulo1/modi.php', '../index.php'],
    ['appModulo1/baja.php', '../index.php'],
    ['appModulo1/salidaJsonRenglones.php', '../index.php'],
    ['appModulo1/salidaJsonUnidades.php', '../index.php'],
    ['appModulo1/traeBinario.php', '../index.php'],
    ['appModulo1/destruirsesion.php', '../index.php'],
    ['appModulo1/descargarErrores.php', '../index.php'],
    ['appModulo1/templates/abm_template.php', '../index.php'],
];
for (const archivo of ['manejoSesion.inc', 'ingresoAlSistema.php', ...rutas.map(([ruta]) => ruta)]) {
    php.writeFile('/ej12/' + archivo, fs.readFileSync(path.join(raiz, archivo)));
}
// No se copian configuraciones ni credenciales reales al entorno de pruebas.
php.writeFile('/ej12/datosConexionBase.php', '<?php');

let fallos = 0;
for (const [archivo, destino] of rutas) {
    const respuesta = await php.run({ code: `<?php chdir('/ej12/${path.dirname(archivo)}'); include '${path.basename(archivo)}';` });
    try {
        assert.equal(respuesta.httpStatusCode, 302, archivo + ': debe rechazar el acceso sin sesión');
        assert.deepEqual(respuesta.headers.location, [destino], archivo + ': debe redirigir al ingreso');
        assert.equal(respuesta.text, '', archivo + ': no debe emitir contenido privado');
    } catch (error) {
        console.error(error.message);
        fallos++;
    }
}
console.log(`Protección de sesión: ${rutas.length - fallos}/${rutas.length} entradas correctas`);

let caso = 0;
async function autenticado(archivo, extra = '') {
    return php.run({ code: `<?php
        session_id('prueba${++caso}'); session_start();
        $_SESSION = ['identificativo'=>'sesion-valida', 'login'=>'Guido <prueba>', 'ContadorDeSesion'=>7];
        ${extra}
        chdir('/ej12/${path.dirname(archivo)}'); include '${path.basename(archivo)}';` });
}
for (const archivo of ['index.php', 'ingresoAlSistema.php', 'appModulo1/templates/abm_template.php']) {
    const respuesta = await autenticado(archivo);
    assert.equal(respuesta.httpStatusCode, 200, archivo + ': debe permitir el acceso autenticado');
    assert(respuesta.text.includes('Guido &lt;prueba&gt;'), archivo + ': debe mostrar el usuario escapado');
    assert(respuesta.text.includes('7'), archivo + ': debe conservar el contador en el reload');
}
php.writeFile('/ej12/errores.log', '# comentario\nError uno\nError uno\nError dos\n');
php.writeFile('/ej12/appModulo1/errores.log', 'Error uno\nError dos\n');
for (const archivo of ['descargarErrores.php', 'appModulo1/descargarErrores.php']) {
    const respuesta = await autenticado(archivo);
    assert.equal(respuesta.httpStatusCode, 200);
    assert.equal(respuesta.text, 'Error uno\nError dos\n', archivo + ': debe descargar los mensajes sin repetir');
}
for (const [archivo, destino] of [['destruirsesion.php', './formularioDeLogin.html'], ['appModulo1/destruirsesion.php', '../formularioDeLogin.html']]) {
    const respuesta = await autenticado(archivo, "register_shutdown_function(function() { echo json_encode(['datos'=>$_SESSION, 'estado'=>session_status()]); });");
    assert.deepEqual(respuesta.headers.location, [destino]);
    assert.deepEqual(JSON.parse(respuesta.text), { datos: [], estado: 1 }, archivo + ': debe vaciar y destruir la sesión');
}
console.log('Sesión autenticada: menú, reload, plantilla, errores y cierre correctos');
php.exit();
process.exit(fallos ? 1 : 0);
