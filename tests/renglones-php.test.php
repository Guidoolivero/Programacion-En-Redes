<?php
// Ejecutar: php tests/renglones-php.test.php. Usa SQLite en memoria, nunca Hostinger.

// MySQL y SQLite comparten las consultas del ABM; solo se adapta SHOW COLUMNS.
class BasePrueba extends PDO {
    public $consultasEstructura = 0;
    private $importeGenerado;

    public function __construct($importeGenerado) {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->importeGenerado = $importeGenerado;
        $importe = $importeGenerado
            ? 'REAL GENERATED ALWAYS AS (cantidad * precio_unitario) STORED'
            : 'REAL NOT NULL';
        $this->exec("CREATE TABLE renglones_factura (
            id INTEGER PRIMARY KEY, nro_factura INTEGER, fecha_factura TEXT,
            cod_articulo TEXT, descripcion TEXT, cod_um TEXT, cantidad REAL,
            precio_unitario REAL, importe_renglon $importe, documentoPdf BLOB,
            UNIQUE(nro_factura, cod_articulo))");
        $this->exec("CREATE TABLE unidades_medida (id INTEGER PRIMARY KEY, cod_um TEXT, descripcion TEXT)");
        $this->exec("INSERT INTO unidades_medida VALUES (1, 'UN', 'Unidad'), (2, 'KG', 'Kilogramo')");
    }

    public function query(string $sql, ?int $fetchMode = null, mixed ...$args): PDOStatement|false {
        if (str_starts_with($sql, 'SHOW COLUMNS')) {
            $this->consultasEstructura++;
            $columnas = [
                ['id', 'int', 'auto_increment'], ['nro_factura', 'int', ''],
                ['fecha_factura', 'date', ''], ['cod_articulo', 'varchar(20)', ''],
                ['descripcion', 'varchar(200)', ''], ['cod_um', 'varchar(10)', ''],
                ['cantidad', 'decimal(10,2)', ''], ['precio_unitario', 'decimal(10,2)', ''],
                ['importe_renglon', 'decimal(12,2)', $this->importeGenerado ? 'STORED GENERATED' : ''],
                ['documentoPdf', 'longblob', ''],
            ];
            $filas = [];
            foreach ($columnas as [$nombre, $tipo, $extra]) {
                if (str_contains($sql, 'importe_renglon') && $nombre !== 'importe_renglon') continue;
                $filas[] = 'SELECT ' . $this->quote($nombre) . ' AS Field, '
                    . $this->quote($tipo) . ' AS Type, ' . $this->quote($extra) . ' AS Extra';
            }
            $sql = implode(' UNION ALL ', $filas);
        }
        return parent::query($sql, $fetchMode, ...$args);
    }
}

function comprobar($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
}

// Copias aisladas y una conexión de prueba, sin copiar credenciales ni escribir logs reales.
$origen = __DIR__ . '/../ejerciciosPHP/12-sesion/appModulo1/models';
$temporal = sys_get_temp_dir() . '/ej12-model-' . uniqid();
mkdir($temporal . '/appModulo1/models', 0777, true);
file_put_contents($temporal . '/appModulo1/models/Database.php', '<?php
class Database {
    public static $pdo;
    public static $errores = [];
    public static function getConnection() { return self::$pdo; }
    public static function logError($mensaje) { self::$errores[] = $mensaje; }
}');
$entradas = ['alta.php', 'modi.php', 'baja.php', 'salidaJsonRenglones.php', 'salidaJsonUnidades.php', 'traeBinario.php'];
foreach ($entradas as $entrada) copy($origen . '/../' . $entrada, $temporal . '/appModulo1/' . $entrada);
copy($origen . '/../../manejoSesion.inc', $temporal . '/manejoSesion.inc');
require $temporal . '/appModulo1/models/Database.php';
session_start();
$_SESSION['identificativo'] = 'sesion-prueba';
function respuestaPhp($archivo, $post = [], $get = [], $files = []) {
    $_POST = $post;
    $_GET = $get;
    $_FILES = $files;
    ob_start();
    include $archivo;
    return ob_get_clean();
}

$resultados = [];
foreach ([false, true] as $generado) {
    Database::$pdo = new BasePrueba($generado);
    Database::$errores = [];
    $app = $temporal . '/appModulo1/';
    $pdf = $temporal . '/prueba.pdf';
    file_put_contents($pdf, "%PDF-1.4\nDocumento de prueba");
    $datos = ['nroFactura'=>'1001', 'fechaFactura'=>'2026-11-10', 'codArticulo'=>'ART-01',
        'descripcion'=>'Cable de red', 'unidadMedida'=>'UN', 'cantidad'=>3.5, 'precioUnitario'=>7];
    $archivo = ['documentoPdf'=>['tmp_name'=>$pdf, 'name'=>'prueba.pdf']];
    $json = function($nombre, $post = [], $get = []) use ($app) {
        return json_decode(respuestaPhp($app . $nombre, $post, $get), true, 512, JSON_THROW_ON_ERROR);
    };
    comprobar($json('salidaJsonRenglones.php')['cantidad'] === 0, 'El listado inicial debe estar vacío.');
    comprobar(count($json('salidaJsonUnidades.php')['unidades']) === 2, 'Debe listar las unidades.');
    comprobar(str_contains(respuestaPhp($app . 'alta.php', $datos, [], $archivo), 'Alta de renglón 1001 / ART-01 correcta'), 'Debe dar de alta con PDF.');
    comprobar($json('traeBinario.php', [], ['nroFactura'=>'1001','codArticulo'=>'ART-01'])['documentoPdf'] === base64_encode(file_get_contents($pdf)), 'Debe recuperar el PDF del alta.');
    $repetido = $datos;
    $repetido['codArticulo'] = ' art-01 ';
    comprobar(str_contains(respuestaPhp($app . 'alta.php', $repetido), 'ERROR DE VALIDACIÓN'), 'Debe rechazar duplicados normalizados.');
    $datos['nroFactura'] = '1002'; $datos['descripcion'] = 'Cobre'; $datos['unidadMedida'] = 'KG';
    comprobar(str_contains(respuestaPhp($app . 'alta.php', $datos), 'Alta de renglón 1002 / ART-01 correcta'), 'Debe permitir el mismo artículo en otra factura.');
    $filas = $json('salidaJsonRenglones.php');
    comprobar($filas['cantidad'] === 2, 'Debe listar ambas facturas.');
    comprobar((float)$filas['renglones'][0]['importeRenglon'] === 24.5, 'El importe debe ser cantidad por precio.');
    $filtros = ['f_nroFactura'=>'1002','f_codArticulo'=>'art-01','f_unidadMedida'=>'KG','f_descripcion'=>'obr','f_fechaFactura'=>'2026-11-10'];
    comprobar($json('salidaJsonRenglones.php', $filtros)['cantidad'] === 1, 'Debe combinar los cinco filtros.');
    comprobar($json('salidaJsonRenglones.php', ['f_unidadMedida'=>'Kilogramo'])['cantidad'] === 1, 'Debe conservar las equivalencias de unidad.');
    comprobar($json('salidaJsonRenglones.php', ['f_descripcion'=>"' OR 1=1 --"])['cantidad'] === 0, 'Debe parametrizar la búsqueda.');
    comprobar($json('salidaJsonRenglones.php', ['orden'=>'columna_invalida'])['cantidad'] === 2, 'Debe controlar un orden desconocido.');
    $datos['cantidad'] = 2; $datos['precioUnitario'] = 9.25; $datos['descripcion'] = "Conector 'especial'";
    comprobar(str_contains(respuestaPhp($app . 'modi.php', $datos), 'Modificacion de renglón 1002 / ART-01 correcta'), 'Debe modificar la fila.');
    $fila = $json('salidaJsonRenglones.php', ['f_nroFactura'=>'1002'])['renglones'][0];
    comprobar($fila['descripcion'] === $datos['descripcion'] && (float)$fila['importeRenglon'] === 18.5, 'Debe actualizar descripción e importe.');
    respuestaPhp($app . 'modi.php', $datos, [], $archivo);
    comprobar($json('traeBinario.php', [], ['nroFactura'=>'1002','codArticulo'=>'ART-01'])['documentoPdf'] === base64_encode(file_get_contents($pdf)), 'Debe adjuntar PDF al modificar.');
    comprobar($json('traeBinario.php', [], ['nroFactura'=>'9999','codArticulo'=>'ART-01']) === ['documentoPdf'=>null], 'Un registro ausente no tiene PDF.');
    comprobar(str_contains(respuestaPhp($app . 'baja.php', ['nroFactura'=>'1001','codArticulo'=>'ART-01']), 'Baja de renglón 1001 / ART-01 correcta'), 'Debe borrar la fila indicada.');
    comprobar($json('salidaJsonRenglones.php')['cantidad'] === 1, 'Debe respetar la otra factura.');
    Database::$pdo->exec('DROP TABLE renglones_factura');
    comprobar($json('salidaJsonRenglones.php')['cantidad'] === 0, 'Debe controlar errores al listar.');
    comprobar(!str_contains(respuestaPhp($app . 'modi.php', $datos), 'Modificacion de renglón 1002 / ART-01 correcta'), 'Debe informar el fallo al modificar.');
    comprobar(!str_contains(respuestaPhp($app . 'baja.php', ['nroFactura'=>'1002','codArticulo'=>'ART-01']), 'Baja de renglón 1002 / ART-01 correcta'), 'Debe informar el fallo al borrar.');
    comprobar($json('traeBinario.php', [], ['nroFactura'=>'1002','codArticulo'=>'ART-01']) === ['documentoPdf'=>null], 'Debe controlar errores al leer PDF.');
    comprobar(count(Database::$errores) >= 4, 'Debe registrar los errores SQL.');
    $resultados[] = 'OK: vistas PHP, filtros, duplicados, PDF y errores; importe ' . ($generado ? 'generado' : 'normal');
}
Database::$pdo = null;
comprobar($json('salidaJsonRenglones.php')['cantidad'] === 0, 'Sin conexión debe devolver el listado vacío.');
comprobar($json('salidaJsonUnidades.php') === ['unidades'=>[]], 'Sin conexión debe devolver unidades vacías.');
comprobar($json('traeBinario.php') === ['documentoPdf'=>null], 'Sin conexión no debe devolver PDF.');
comprobar(respuestaPhp($app . 'alta.php', $datos) === 'No se pudo conectar a la base de datos.', 'El alta debe informar la falta de conexión.');
comprobar(respuestaPhp($app . 'modi.php', $datos) === 'No se pudo conectar a la base de datos.', 'La modificación debe informar la falta de conexión.');
comprobar(respuestaPhp($app . 'baja.php', $datos) === 'No se pudo conectar a la base de datos.', 'La baja debe informar la falta de conexión.');
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporal, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $archivo) {
    $archivo->isDir() ? rmdir($archivo->getPathname()) : unlink($archivo->getPathname());
}
rmdir($temporal);
echo implode(PHP_EOL, $resultados) . PHP_EOL;
