<?php
// Ejecutar: php tests/renglones-model.test.php. Usa SQLite en memoria, nunca Hostinger.

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
copy($origen . '/RenglonesModel.php', $temporal . '/appModulo1/models/RenglonesModel.php');
$entradas = ['alta.php', 'modi.php', 'baja.php', 'salidaJsonRenglones.php', 'salidaJsonUnidades.php', 'traeBinario.php'];
foreach ($entradas as $entrada) copy($origen . '/../' . $entrada, $temporal . '/appModulo1/' . $entrada);
copy($origen . '/../../manejoSesion.inc', $temporal . '/manejoSesion.inc');
require $temporal . '/appModulo1/models/RenglonesModel.php';
session_start();
$_SESSION['identificativo'] = 'sesion-prueba';
function respuestaPhp($archivo, $post = [], $get = []) {
    $_POST = $post;
    $_GET = $get;
    $_FILES = [];
    ob_start();
    include $archivo;
    return ob_get_clean();
}

$resultados = [];
foreach ([false, true] as $generado) {
    $pdo = new BasePrueba($generado);
    Database::$pdo = $pdo;
    Database::$errores = [];
    $modelo = new RenglonesModel();
    comprobar($modelo->getPdo() === $pdo, 'El modelo debe reutilizar la conexión.');
    comprobar(count($modelo->getUnidades()) === 2, 'Deben devolverse las unidades.');
    comprobar($modelo->getRenglones()['cantidad'] === 0, 'El listado vacío debe tener cantidad cero.');

    $datos = [
        'nroFactura' => '1001', 'fechaFactura' => '2026-11-10', 'codArticulo' => 'ART-01',
        'descripcion' => 'Cable de red', 'unidadMedida' => 'UN', 'cantidad' => 3.5,
        'precioUnitario' => 7,
    ];
    $pdf = $temporal . '/prueba.pdf';
    file_put_contents($pdf, "%PDF-1.4\nDocumento de prueba");
    $archivo = ['tmp_name' => $pdf, 'name' => 'prueba.pdf'];
    $traza = '';
    comprobar($modelo->insertarRenglon($datos, $traza, $archivo), 'Debe dar de alta con PDF.');
    comprobar(str_contains($traza, 'Alta de renglón 1001 / ART-01 correcta'), 'Debe conservar la respuesta del alta.');
    comprobar($modelo->getPdfBase64('1001', 'ART-01') === base64_encode(file_get_contents($pdf)), 'El PDF guardado debe poder verse.');
    comprobar($modelo->getPdfBase64('9999', 'ART-01') === null, 'Un registro ausente no tiene PDF.');
    comprobar($modelo->existeRenglon(' 1001 ', ' art-01 '), 'Debe reconocer duplicados con espacios o distinta caja.');
    $traza = '';
    comprobar(!$modelo->insertarRenglon($datos, $traza), 'Debe rechazar el par factura + artículo repetido.');

    $datos['nroFactura'] = '1002';
    $datos['descripcion'] = 'Cobre';
    $datos['unidadMedida'] = 'KG';
    $traza = '';
    comprobar($modelo->insertarRenglon($datos, $traza), 'El mismo artículo puede estar en otra factura.');
    comprobar($modelo->getPdfBase64('1002', 'ART-01') === null, 'El alta sin archivo no debe guardar PDF.');
    $listado = $modelo->getRenglones([], 'cantidad');
    comprobar($listado['cantidad'] === 2, 'Deben listarse ambos registros.');
    comprobar((float)$listado['renglones'][0]['importeRenglon'] === 24.5, 'El importe debe ser cantidad por precio.');

    $filtros = [
        'f_nroFactura' => '1002', 'f_codArticulo' => 'art-01', 'f_unidadMedida' => 'KG',
        'f_descripcion' => 'obr', 'f_fechaFactura' => '2026-11-10',
    ];
    comprobar($modelo->getRenglones($filtros)['cantidad'] === 1, 'Deben combinarse los cinco filtros.');
    comprobar($modelo->getRenglones(['f_unidadMedida' => 'Kilogramo'])['cantidad'] === 1, 'Debe conservarse la búsqueda por descripción de unidad.');
    comprobar($modelo->getRenglones(['f_descripcion' => "' OR 1=1 --"])['cantidad'] === 0, 'Los filtros deben enviarse como parámetros.');
    comprobar($modelo->getRenglones([], 'campo_inexistente')['cantidad'] === 2, 'Un orden desconocido debe usar el permitido por defecto.');

    $datos['descripcion'] = "Conector 'especial'";
    $datos['cantidad'] = 2;
    $datos['precioUnitario'] = 9.25;
    $datos['unidadMedida'] = 'UN';
    $traza = '';
    comprobar($modelo->actualizarRenglon($datos, $traza), 'Debe modificar los campos sin requerir un PDF.');
    $fila = $modelo->getRenglones(['f_nroFactura' => '1002'])['renglones'][0];
    comprobar($fila['descripcion'] === "Conector 'especial'", 'La descripción debe guardarse literalmente.');
    comprobar((float)$fila['importeRenglon'] === 18.5, 'La modificación debe actualizar el importe.');
    $traza = '';
    comprobar($modelo->actualizarRenglon($datos, $traza, $archivo), 'Debe adjuntar un PDF al modificar.');
    comprobar($modelo->getPdfBase64('1002', 'ART-01') === base64_encode(file_get_contents($pdf)), 'Debe recuperarse el PDF adjuntado al modificar.');

    $traza = '';
    comprobar($modelo->eliminarRenglon('1001', 'ART-01', $traza), 'Debe borrar el registro indicado.');
    comprobar(!$modelo->existeRenglon('1001', 'ART-01'), 'La fila borrada no debe seguir existiendo.');
    comprobar($modelo->getRenglones()['cantidad'] === 1, 'El borrado debe respetar la otra factura.');
    comprobar($pdo->consultasEstructura === 1, 'La estructura se consulta una sola vez por modelo.');

    // Comprueba las vistas PHP reales que recibe fetch, sin la clase intermediaria.
    $app = $temporal . '/appModulo1/';
    $respuesta = json_decode(respuestaPhp($app . 'traeBinario.php', [], ['nroFactura'=>'1002', 'codArticulo'=>'ART-01']), true, 512, JSON_THROW_ON_ERROR);
    comprobar($respuesta === ['documentoPdf'=>base64_encode(file_get_contents($pdf))], 'La vista debe conservar el JSON del PDF.');
    comprobar(json_decode(respuestaPhp($app . 'traeBinario.php', [], ['nroFactura'=>'9999', 'codArticulo'=>'ART-01']), true) === ['documentoPdf'=>null], 'Un PDF ausente debe responder null.');
    $respuesta = json_decode(respuestaPhp($app . 'salidaJsonRenglones.php'), true, 512, JSON_THROW_ON_ERROR);
    comprobar($respuesta['cantidad'] === 1 && $respuesta['renglones'][0]['nroFactura'] == 1002, 'La vista debe conservar la lista JSON.');
    $respuesta = json_decode(respuestaPhp($app . 'salidaJsonUnidades.php'), true, 512, JSON_THROW_ON_ERROR);
    comprobar(count($respuesta['unidades']) === 2, 'La vista debe conservar las unidades.');
    $datos['nroFactura'] = '1003';
    comprobar(str_contains(respuestaPhp($app . 'alta.php', $datos), 'Alta de renglón 1003 / ART-01 correcta'), 'alta.php debe llamar al modelo.');
    $datos['descripcion'] = 'Modificado desde la vista';
    comprobar(str_contains(respuestaPhp($app . 'modi.php', $datos), 'Modificacion de renglón 1003 / ART-01 correcta'), 'modi.php debe llamar al modelo.');
    comprobar($modelo->getRenglones(['f_nroFactura'=>'1003'])['renglones'][0]['descripcion'] === $datos['descripcion'], 'La vista debe guardar la modificación.');
    comprobar(str_contains(respuestaPhp($app . 'baja.php', ['nroFactura'=>'1003', 'codArticulo'=>'ART-01']), 'Baja de renglón 1003 / ART-01 correcta'), 'baja.php debe llamar al modelo.');
    comprobar(!$modelo->existeRenglon('1003', 'ART-01'), 'La vista debe eliminar la fila.');

    $pdo->exec('DROP TABLE renglones_factura');
    comprobar($modelo->getRenglones()['cantidad'] === 0, 'El listado debe conservar su respuesta ante un error SQL.');
    comprobar(!$modelo->actualizarRenglon($datos, $traza), 'La modificación debe informar un error SQL.');
    comprobar(!$modelo->eliminarRenglon('1002', 'ART-01', $traza), 'La baja debe informar un error SQL.');
    comprobar($modelo->getPdfBase64('1002', 'ART-01') === null, 'La consulta de PDF debe controlar un error SQL.');
    comprobar(count(Database::$errores) >= 4, 'Los errores SQL deben registrarse.');
    $resultados[] = 'OK: ABM, filtros, PDF, JSON y errores; importe ' . ($generado ? 'generado' : 'normal');
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporal, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $archivo) {
    $archivo->isDir() ? rmdir($archivo->getPathname()) : unlink($archivo->getPathname());
}
rmdir($temporal);
echo implode(PHP_EOL, $resultados) . PHP_EOL;
