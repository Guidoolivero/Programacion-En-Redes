// Prueba la entrada de Apache con archivos ficticios, sin ejecutar PHP ni usar la base.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn } = require('node:child_process');

test('La carpeta y el HTML pasan por la entrada PHP de sesión', { timeout: 10000 }, async t => {
  const dir = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'ej12-apache-')));
  const ruta = '/ejerciciosPHP/12-sesion/appModulo1/';
  const app = path.join(dir, 'www', ruta);
  fs.mkdirSync(app, { recursive: true });
  fs.writeFileSync(path.join(app, 'index.php'), 'ENTRADA_PHP');
  fs.writeFileSync(path.join(app, 'index.html'), 'PLANTILLA');
  const reglas = path.join(__dirname, '../ejerciciosPHP/12-sesion/appModulo1/.htaccess');
  if (fs.existsSync(reglas)) fs.copyFileSync(reglas, path.join(app, '.htaccess'));
  const origen = path.dirname(reglas);
  const htmlEntrada = fs.readFileSync(path.join(origen, 'index.php'), 'utf8');
  const recursos = [...htmlEntrada.matchAll(/(?:href|src)="(templates\/[^"?]+)/g)].map(m => m[1]);
  assert.deepEqual(recursos, ['templates/estilo.css', 'templates/abm.js']);
  fs.mkdirSync(path.join(app, 'templates'));
  for (const recurso of recursos) {
    assert(fs.existsSync(path.join(origen, recurso)), recurso + ': el archivo debe existir');
    fs.writeFileSync(path.join(app, recurso), 'RECURSO ' + recurso);
  }
  const socket = net.createServer();
  await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  const modulos = process.env.APACHE_MODULES || '/usr/libexec/apache2';
  const cargas = ['mpm_prefork', 'unixd', 'authz_core', 'dir', 'rewrite'].map(n => `LoadModule ${n}_module "${modulos}/mod_${n}.so"`).join('\n');
  fs.writeFileSync(path.join(dir, 'httpd.conf'), `${cargas}
ServerRoot "${dir}"
PidFile "${dir}/httpd.pid"
ErrorLog "${dir}/error.log"
ServerName localhost
Listen 127.0.0.1:${port}
DocumentRoot "${dir}/www"
<Directory "${dir}/www">
  AllowOverride All
  Options FollowSymLinks
  Require all granted
</Directory>
`);
  const servidor = spawn(process.env.APACHE_PATH || '/usr/sbin/httpd', ['-f', path.join(dir, 'httpd.conf'), '-DFOREGROUND'], { detached: true });
  t.after(async () => {
    if (servidor.exitCode === null) {
      servidor.kill('SIGTERM');
      await new Promise(resolve => servidor.once('exit', resolve));
    }
    fs.rmSync(dir, { recursive: true, force: true });
  });
  let respuesta;
  for (let i = 0; i < 50; i++) {
    try { respuesta = await fetch(`http://127.0.0.1:${port}${ruta}`); break; }
    catch { await new Promise(resolve => setTimeout(resolve, 50)); }
  }
  assert(respuesta, fs.existsSync(path.join(dir, 'error.log')) ? fs.readFileSync(path.join(dir, 'error.log'), 'utf8') : 'Apache no inició');
  assert.equal(await respuesta.text(), 'ENTRADA_PHP');
  for (const recurso of recursos) {
    const archivo = await fetch(`http://127.0.0.1:${port}${ruta}${recurso}`);
    assert.equal(archivo.status, 200);
    assert.equal(await archivo.text(), 'RECURSO ' + recurso);
  }
  const html = await fetch(`http://127.0.0.1:${port}${ruta}index.html`, { redirect: 'manual' });
  assert.equal(html.status, 302);
  assert.equal(new URL(html.headers.get('location')).pathname, ruta + 'index.php');
});
