<?php
// models/RenglonesModel.php - Modelo para la persistencia y consultas de datos

require_once __DIR__ . '/Database.php';

class RenglonesModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function getUnidades() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Database::logError('Unidades: ' . $e->getMessage());
            return [];
        }
    }

    public function getRenglones($filtros = [], $orden = 'nroFactura') {
        if (!$this->pdo) return ['renglones' => [], 'cantidad' => 0];

        $camposPermitidos = [
            'nroFactura', 'codArticulo', 'unidadMedida',
            'descripcion', 'fechaFactura', 'cantidad',
            'precioUnitario', 'importeRenglon'
        ];
        if (!in_array($orden, $camposPermitidos)) {
            $orden = 'nroFactura';
        }

        try {
            // Detectar nombres reales de columnas en MySQL
            $cols = $this->pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
            $colNro   = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : ($cols[0] ?? 'nroFactura'));
            $colFecha = in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : ($cols[1] ?? 'fechaFactura')));
            $colCod   = in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : ($cols[2] ?? 'codArticulo'));
            $colDesc  = in_array('descripcion', $cols) ? 'descripcion' : ($cols[3] ?? 'descripcion');
            $colUm    = in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : ($cols[4] ?? 'unidadMedida')));
            $colCant  = in_array('cantidad', $cols) ? 'cantidad' : ($cols[5] ?? 'cantidad');
            $colPrec  = in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : ($cols[6] ?? 'precioUnitario'));
            $colImp   = in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : ($cols[7] ?? 'importeRenglon')));

            $mapaOrden = [
                'nroFactura'     => $colNro,
                'codArticulo'    => $colCod,
                'unidadMedida'   => $colUm,
                'descripcion'    => $colDesc,
                'fechaFactura'   => $colFecha,
                'cantidad'       => $colCant,
                'precioUnitario' => $colPrec,
                'importeRenglon' => $colImp,
            ];
            $colOrdenSql = $mapaOrden[$orden] ?? $colNro;

            $sql = "SELECT * FROM renglones_factura WHERE 1=1";
            $params = [];

            $fNro   = trim($filtros['f_nroFactura'] ?? '');
            $fCod   = trim($filtros['f_codArticulo'] ?? '');
            $fUm    = trim($filtros['f_unidadMedida'] ?? '');
            $fDesc  = trim($filtros['f_descripcion'] ?? '');
            $fFecha = trim($filtros['f_fechaFactura'] ?? '');

            if ($fNro !== '')   { $sql .= " AND `$colNro` LIKE ?";   $params[] = "%$fNro%"; }
            if ($fCod !== '')   { $sql .= " AND `$colCod` LIKE ?";   $params[] = "%$fCod%"; }
            if ($fUm !== '')    { $sql .= " AND (`$colUm` = ? OR `$colUm` LIKE ?)"; $params[] = $fUm; $params[] = "%$fUm%"; }
            if ($fDesc !== '')  { $sql .= " AND `$colDesc` LIKE ?";  $params[] = "%$fDesc%"; }
            if ($fFecha !== '') { $sql .= " AND `$colFecha` = ?";    $params[] = $fFecha; }

            $sql .= " ORDER BY `$colOrdenSql`";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $filasRaw = $stmt->fetchAll();

            $filas = array_map(function($r) use ($colNro, $colCod, $colUm, $colDesc, $colFecha, $colCant, $colPrec, $colImp) {
                $cant = $r[$colCant] ?? '';
                $prec = $r[$colPrec] ?? '';
                $imp  = $r[$colImp] ?? (($cant !== '' && $prec !== '') ? number_format((float)$cant * (float)$prec, 2, '.', '') : '');
                return [
                    'nroFactura'     => $r[$colNro] ?? '',
                    'codArticulo'    => $r[$colCod] ?? '',
                    'unidadMedida'   => $r[$colUm] ?? '',
                    'descripcion'    => $r[$colDesc] ?? '',
                    'fechaFactura'   => $r[$colFecha] ?? '',
                    'cantidad'       => $cant,
                    'precioUnitario' => $prec,
                    'importeRenglon' => $imp,
                ];
            }, $filasRaw);

            return ['renglones' => $filas, 'cantidad' => count($filas)];
        } catch (PDOException $e) {
            Database::logError('Renglones: ' . $e->getMessage());
            return ['error' => $e->getMessage(), 'renglones' => [], 'cantidad' => 0];
        }
    }

    public function insertarRenglon($datos, &$traza = '') {
        if (!$this->pdo) {
            $traza = "Error: Sin conexión con la base de datos";
            return false;
        }
        $traza .= "Conexion exitosa<br>";

        try {
            $nro     = $datos['nroFactura'] ?? '';
            $fecha   = $datos['fechaFactura'] ?? '';
            $cod     = $datos['codArticulo'] ?? '';
            $desc    = $datos['descripcion'] ?? '';
            $um      = $datos['unidadMedida'] ?? '';
            $cant    = (float)($datos['cantidad'] ?? 0);
            $precio  = (float)($datos['precioUnitario'] ?? 0);
            $importe = $cant * $precio;

            $sql = "INSERT INTO renglones_factura (nroFactura, fechaFactura, codArticulo, descripcion, unidadMedida, cantidad, precioUnitario, importeRenglon) VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "Preparacion exitosa<br>";

            $stmt->execute([$nro, $fecha, $cod, $desc, $um, $cant, $precio, $importe]);
            $traza .= "Ejecucion exitosa<br>";
            $traza .= "Alta de renglón $nro / $cod correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Alta: ' . $e->getMessage());
            $traza .= "Error: " . $e->getMessage();
            return false;
        }
    }

    public function actualizarRenglon($datos, &$traza = '') {
        if (!$this->pdo) {
            $traza = "Error: Sin conexión con la base de datos";
            return false;
        }
        $traza .= "Conexion exitosa<br>";

        try {
            $nro     = $datos['nroFactura'] ?? '';
            $fecha   = $datos['fechaFactura'] ?? '';
            $cod     = $datos['codArticulo'] ?? '';
            $desc    = $datos['descripcion'] ?? '';
            $um      = $datos['unidadMedida'] ?? '';
            $cant    = (float)($datos['cantidad'] ?? 0);
            $precio  = (float)($datos['precioUnitario'] ?? 0);
            $importe = $cant * $precio;

            $sql = "UPDATE renglones_factura SET fechaFactura=?, descripcion=?, unidadMedida=?, cantidad=?, precioUnitario=?, importeRenglon=? WHERE nroFactura=? AND codArticulo=?";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "Preparacion exitosa<br>";

            $stmt->execute([$fecha, $desc, $um, $cant, $precio, $importe, $nro, $cod]);
            $traza .= "Ejecucion exitosa<br>";
            $traza .= "Modificacion de renglón $nro / $cod correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Modi: ' . $e->getMessage());
            $traza .= "Error: " . $e->getMessage();
            return false;
        }
    }

    public function eliminarRenglon($nroFactura, $codArticulo, &$traza = '') {
        if (!$this->pdo) {
            $traza = "Error: Sin conexión con la base de datos";
            return false;
        }
        $traza .= "Conexion exitosa<br>";

        try {
            $sql = "DELETE FROM renglones_factura WHERE nroFactura=? AND codArticulo=?";
            $stmt = $this->pdo->prepare($sql);
            $traza .= "Preparacion exitosa<br>";

            $stmt->execute([$nroFactura, $codArticulo]);
            $traza .= "Ejecucion exitosa<br>";
            $traza .= "Baja de renglón $nroFactura / $codArticulo correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Baja: ' . $e->getMessage());
            $traza .= "Error: " . $e->getMessage();
            return false;
        }
    }
}
