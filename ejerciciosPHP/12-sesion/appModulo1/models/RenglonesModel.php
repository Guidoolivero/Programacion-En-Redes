<?php
// models/RenglonesModel.php - Modelo para la persistencia y consultas de datos (Patrón MVT)

require_once __DIR__ . '/Database.php';

class RenglonesModel {
    private $pdo; // Guarda la conexión del modelo con MySQL.
    private $importeCalculado = null; // Recuerda si MySQL calcula el importe automáticamente.

    // Al crear el modelo, obtiene su conexión con MySQL.
    public function __construct() {
        $this->pdo = Database::getConnection(); // Pide la conexión a la clase Database.
    }

    // Devuelve la conexión que ya usa este modelo.
    public function getPdo() {
        return $this->pdo; // Entrega la conexión al código que la solicite.
    }

    // Solo consulta la característica que puede variar en tu tabla.
    private function importeEsCalculado() {
        if ($this->importeCalculado === null) {
            $columna = $this->pdo->query("SHOW COLUMNS FROM renglones_factura LIKE 'importe_renglon'")->fetch(PDO::FETCH_ASSOC);
            $this->importeCalculado = strpos(strtolower($columna['Extra'] ?? ''), 'generated') !== false;
        }
        return $this->importeCalculado;
    }

    /**
     * Construye mapas de conversión entre id numérico, código ('UN', 'MT') y descripción
     * leyendo la tabla unidades_medida para mostrar siempre el código.
     */
    private function obtenerInfoUnidades() {
        $mapaMostrar = []; // Cualquier valor en BD -> código para mostrar/seleccionar en frontend
        $filas = $this->getUnidades(); // Lee la tabla auxiliar de unidades.

        foreach ($filas as $u) {
            $vals    = array_values($u); // Permite consultar los valores también por posición.
            // Intenta reconocer el identificador numérico de la unidad.
            $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
            // Intenta reconocer el código de unidad, por ejemplo UN.
            $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
            // Intenta reconocer la descripción visible de la unidad.
            $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? $codVal);
            // Para mostrar la unidad, prefiere el código a la descripción.
            $preferido = ($codVal !== '') ? (string)$codVal : (string)$descVal;

            if ($idVal !== null) $mapaMostrar[(string)$idVal] = $preferido;
            if ($codVal !== '')  $mapaMostrar[(string)$codVal] = (string)$codVal;
            if ($descVal !== '') $mapaMostrar[(string)$descVal] = $preferido;
        }

        return ['filas' => $filas, 'mapaMostrar' => $mapaMostrar];
    }

    /**
     * Verifica si la combinación (nroFactura + codArticulo) ya existe en renglones_factura.
     * Permite repetir el mismo codArticulo en distintas facturas y varios artículos en una misma factura.
     */
    public function existeRenglon($nroFactura, $codArticulo) {
        if (!$this->pdo) return false;
        $nroLimpio = trim((string)$nroFactura); // Normaliza el número antes de buscar duplicados.
        $codLimpio = trim((string)$codArticulo); // Normaliza el artículo antes de buscar duplicados.
        if ($nroLimpio === '' || $codLimpio === '') return false; // Normaliza el número antes de buscar duplicados.

        try {
            // Cuenta coincidencias de la clave factura + artículo.
            $sql = "SELECT COUNT(*) FROM renglones_factura WHERE LOWER(TRIM(CAST(`nro_factura` AS CHAR))) = LOWER(:nro) AND LOWER(TRIM(CAST(`cod_articulo` AS CHAR))) = LOWER(:cod)";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $stmt->bindValue(':nro', $nroLimpio); // Asocia el número de factura al parámetro de la consulta.
            $stmt->bindValue(':cod', $codLimpio); // Asocia el código de artículo al parámetro de la consulta.
            $stmt->execute(); // Ejecuta el SELECT que comprueba si la clave está repetida.
            return ((int)$stmt->fetchColumn()) > 0; // Devuelve true si la combinación ya existe.
        } catch (PDOException $e) {
            Database::logError('Validar renglón: ' . $e->getMessage());
            return false;
        }
    }

    // SELECT de las opciones del selector de unidades.
    public function getUnidades() {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM unidades_medida"); // query ejecuta directamente el SELECT de unidades.
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Database::logError('Unidades: ' . $e->getMessage());
            return [];
        }
    }

    // SELECT del listado: aplica filtros y orden antes de devolver las filas.
    public function getRenglones($filtros = [], $orden = 'nroFactura') {
        if (!$this->pdo) return ['renglones' => [], 'cantidad' => 0];

        $mapaOrden = [ // Campo del navegador => columna real de MySQL.
            'nroFactura' => 'nro_factura', 'codArticulo' => 'cod_articulo',
            'unidadMedida' => 'cod_um', 'descripcion' => 'descripcion',
            'fechaFactura' => 'fecha_factura', 'cantidad' => 'cantidad',
            'precioUnitario' => 'precio_unitario', 'importeRenglon' => 'importe_renglon',
        ];
        if (!in_array($orden, array_keys($mapaOrden), true)) { // Un orden desconocido usa el valor inicial.
            $orden = 'nroFactura';
        }

        try {
            $infoUm = $this->obtenerInfoUnidades();
            $mapaUmTexto = $infoUm['mapaMostrar'];

            $colOrden = $mapaOrden[$orden];

            $condiciones = []; // Acumula las condiciones que formarán el WHERE.
            $params = []; // Guarda los valores que se vincularán a los parámetros SQL.

            $fNro   = trim((string)($filtros['f_nroFactura'] ?? '')); // Lee el filtro de factura y quita espacios externos.
            // Lee el filtro de artículo y quita espacios externos.
            $fCod   = trim((string)($filtros['f_codArticulo'] ?? ''));
            $fUm    = trim((string)($filtros['f_unidadMedida'] ?? '')); // Lee la unidad elegida en el filtro.
            $fDesc  = trim((string)($filtros['f_descripcion'] ?? '')); // Lee el texto de búsqueda de la descripción.
            $fFecha = trim((string)($filtros['f_fechaFactura'] ?? '')); // Lee la fecha usada como filtro.

            if ($fNro !== '') {
                $condiciones[] = "LOWER(CAST(`nro_factura` AS CHAR)) LIKE LOWER(:f_nro)";
                $params[':f_nro'] = '%' . $fNro . '%'; // Los signos % permiten buscar una coincidencia parcial.
            }
            if ($fCod !== '') {
                $condiciones[] = "LOWER(CAST(`cod_articulo` AS CHAR)) LIKE LOWER(:f_cod)";
                $params[':f_cod'] = '%' . $fCod . '%'; // Busca el texto del código dentro del valor de la columna.
            }
            if ($fUm !== '') {
                $equivalencias = [strtolower($fUm)]; // Permite filtrar una unidad por ID, código o descripción.
                foreach ($infoUm['filas'] as $u) {
                    $vals    = array_values($u); // Permite consultar los valores también por posición.
                    // Intenta reconocer el identificador numérico de la unidad.
                    $idVal   = $u['id'] ?? $u['id_unidad'] ?? $u['id_um'] ?? null;
                    // Intenta reconocer el código de unidad, por ejemplo UN.
                    $codVal  = $u['cod_um'] ?? $u['codigo'] ?? $u['codUm'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
                    // Intenta reconocer la descripción visible de la unidad.
                    $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? '');
                    if (
                        ($idVal !== null && strcasecmp((string)$idVal, $fUm) === 0) ||
                        ($codVal !== '' && strcasecmp((string)$codVal, $fUm) === 0) ||
                        ($descVal !== '' && strcasecmp((string)$descVal, $fUm) === 0)
                    ) {
                        if ($idVal !== null) $equivalencias[] = strtolower(trim((string)$idVal));
                        if ($codVal !== '')  $equivalencias[] = strtolower(trim((string)$codVal));
                        if ($descVal !== '') $equivalencias[] = strtolower(trim((string)$descVal));
                    }
                }
                // Quita equivalencias vacías o repetidas antes de armar el IN.
                $equivalencias = array_values(array_unique(array_filter($equivalencias, function($v) {
                    return $v !== '';
                })));
                $phUm = [];
                foreach ($equivalencias as $idx => $eqVal) {
                    $key = ":f_um_$idx"; // Crea un parámetro SQL distinto para cada equivalencia.
                    $phUm[] = $key;
                    $params[$key] = $eqVal;
                }
                $condiciones[] = "LOWER(TRIM(CAST(`cod_um` AS CHAR))) IN (" . implode(', ', $phUm) . ")";
            }
            if ($fDesc !== '') {
                $condiciones[] = "LOWER(CAST(`descripcion` AS CHAR)) LIKE LOWER(:f_desc)";
                $params[':f_desc'] = '%' . $fDesc . '%'; // Busca la descripción aunque el texto esté en el medio.
            }
            if ($fFecha !== '') {
                $condiciones[] = "CAST(`fecha_factura` AS CHAR) LIKE :f_fecha";
                $params[':f_fecha'] = $fFecha . '%'; // El prefijo de fecha permite comparar también fechas con hora.
            }

            // AS conserva los nombres que recibe abm.js.
            $sql = "SELECT nro_factura AS nroFactura, fecha_factura AS fechaFactura,
                cod_articulo AS codArticulo, descripcion, cod_um AS unidadMedida,
                cantidad, precio_unitario AS precioUnitario, importe_renglon AS importeRenglon
                FROM renglones_factura";
            if (!empty($condiciones)) {
                $sql .= " WHERE " . implode(' AND ', $condiciones); // Une con AND todos los filtros que se completaron.
            }
            $sql .= " ORDER BY `$colOrden` ASC"; // El nombre proviene del mapa permitido, no del usuario.

            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $stmt->execute($params); // SELECT: ejecuta la búsqueda con los valores de los filtros.
            $filas = $stmt->fetchAll(); // Recupera todos los registros encontrados.

            // Normalizar código de Unidad de Medida para visualización y edición consistente
            foreach ($filas as &$fila) {
                $rawUm = (string)($fila['unidadMedida'] ?? '');
                if ($rawUm !== '' && isset($mapaUmTexto[$rawUm])) {
                    $fila['unidadMedida'] = $mapaUmTexto[$rawUm];
                }
            }
            unset($fila);

            return [
                'renglones' => $filas,
                'cantidad'  => count($filas), // La cantidad se calcula sobre las filas que realmente devolvió la consulta.
            ];
        } catch (PDOException $e) {
            Database::logError('Renglones: ' . $e->getMessage());
            return ['renglones' => [], 'cantidad' => 0];
        }
    }

    // ALTA: valida la clave, inserta el registro y guarda el PDF opcional.
    public function insertarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = trim((string)($datos['nroFactura'] ?? ''));
        $fecha   = trim((string)($datos['fechaFactura'] ?? ''));
        $cod     = trim((string)($datos['codArticulo'] ?? ''));
        $desc    = trim((string)($datos['descripcion'] ?? ''));
        $um      = trim((string)($datos['unidadMedida'] ?? ''));
        $cant    = (float)($datos['cantidad'] ?? 0); // Convierte la cantidad recibida a un número decimal.
        $precio  = (float)($datos['precioUnitario'] ?? 0); // Convierte el precio recibido a un número decimal.
        $importe = $cant * $precio; // Calcula el importe del renglón en el servidor.

        // Comienza el mensaje que se mostrará en el navegador.
        $traza .= "Respuesta del servidor al alta. Entradas recibidas en el req http:\n";
        $traza .= "nroFactura: " . $nro . "\n";
        $traza .= "codArticulo: " . $cod . "\n";
        $traza .= "fechaFactura: " . $fecha . "\n";
        $traza .= "unidadMedida: " . $um . "\n";
        $traza .= "descripcion: " . $desc . "\n";
        $traza .= "cantidad: " . $cant . "\n";
        $traza .= "precioUnitario: " . $precio . "\n";
        $traza .= "importeRenglon: " . $importe . "\n";

        try {
            // Se permite repetir codArticulo en distintas facturas, pero no duplicar la clave compuesta (nroFactura + codArticulo)
            // El servidor vuelve a rechazar una clave repetida aunque falle el control JS.
            if ($this->existeRenglon($nro, $cod)) {
                $msgDup = "En la factura '$nro' ya está cargado el artículo '$cod'. (Podés repetir '$cod' en otra factura distinta o modificar ese renglón).";
                Database::logError('Alta rechazada (renglón duplicado en misma factura): ' . $msgDup);
                $traza .= "\nERROR DE VALIDACIÓN: " . $msgDup;
                return false;
            }

            // Columnas y parámetros fijos: son los nombres reales de tu tabla.
            $columnas = 'nro_factura, fecha_factura, cod_articulo, descripcion, cod_um, cantidad, precio_unitario';
            $valores = ':nroFactura, :fechaFactura, :codArticulo, :descripcion, :unidadMedida, :cantidad, :precioUnitario';
            $binds = [
                ':nroFactura' => $nro, ':fechaFactura' => $fecha, ':codArticulo' => $cod,
                ':descripcion' => $desc, ':unidadMedida' => $um, ':cantidad' => $cant,
                ':precioUnitario' => $precio,
            ];
            // Una columna generada no se incluye en el INSERT.
            if (!$this->importeEsCalculado()) {
                $columnas .= ', importe_renglon';
                $valores .= ', :importeRenglon';
                $binds[':importeRenglon'] = $importe;
            }
            $sql = "INSERT INTO renglones_factura ($columnas) VALUES ($valores)";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute(); // INSERT: ejecuta el alta del registro.
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nAlta de renglón $nro / $cod correcta";

            $this->guardarPdf($nro, $cod, $archivoPdf, $traza, 'Error guardando PDF alta: ');

            return true;
        } catch (PDOException $e) {
            Database::logError('Alta: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // MODIFICAR: actualiza los campos de una fila y su PDF opcional.
    public function actualizarRenglon($datos, &$traza = '', $archivoPdf = null) {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        $nro     = trim((string)($datos['nroFactura'] ?? ''));
        $fecha   = trim((string)($datos['fechaFactura'] ?? ''));
        $cod     = trim((string)($datos['codArticulo'] ?? ''));
        $desc    = trim((string)($datos['descripcion'] ?? ''));
        $um      = trim((string)($datos['unidadMedida'] ?? ''));
        $cant    = (float)($datos['cantidad'] ?? 0); // Convierte la cantidad recibida a un número decimal.
        $precio  = (float)($datos['precioUnitario'] ?? 0); // Convierte el precio recibido a un número decimal.
        $importe = $cant * $precio; // Calcula el importe del renglón en el servidor.

        $traza .= "Parte Modificacion simple de datos\n";
        $traza .= "nroFactura: $nro / codArticulo: $cod\n";

        try {
            $cambios = 'fecha_factura=:fechaFactura, descripcion=:descripcion,
                cod_um=:unidadMedida, cantidad=:cantidad, precio_unitario=:precioUnitario';
            $binds = [
                ':fechaFactura' => $fecha, ':descripcion' => $desc, ':unidadMedida' => $um,
                ':cantidad' => $cant, ':precioUnitario' => $precio,
                ':nroFactura' => $nro, ':codArticulo' => $cod,
            ];
            // Si MySQL calcula el importe, solo se actualizan cantidad y precio.
            if (!$this->importeEsCalculado()) {
                $cambios .= ', importe_renglon=:importeRenglon';
                $binds[':importeRenglon'] = $importe;
            }
            // La factura y el artículo identifican la fila que se modifica.
            $sql = "UPDATE renglones_factura SET $cambios WHERE nro_factura=:nroFactura AND cod_articulo=:codArticulo";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "\nPreparacion exitosa!";

            foreach ($binds as $ph => $val) {
                $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
                //$binds (El arreglo de datos)
                //$ph (Placeholder / Marcador): Contiene la clave, por ejemplo ':id'.
                //$val (Value / Valor): Contiene el dato real a insertar, por ejemplo 15.
            }
            $traza .= "\nBinding exitoso!";

            $stmt->execute(); // UPDATE: ejecuta los cambios en los campos.
            $traza .= "\nEjecucion exitosa!";
            $traza .= "\nModificacion de renglón $nro / $cod correcta";

            $this->guardarPdf($nro, $cod, $archivoPdf, $traza, 'Error actualizando PDF modi: ');

            return true;
        } catch (PDOException $e) {
            Database::logError('Modi: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // Alta y modificación usan el mismo UPDATE para guardar el PDF binario.
    private function guardarPdf($nro, $cod, $archivoPdf, &$traza, $mensajeError) {
        if (!$archivoPdf || !isset($archivoPdf['tmp_name']) || empty($archivoPdf['name'])) return;
        try {
            $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
            $stmt = $this->pdo->prepare("UPDATE renglones_factura SET documentoPdf=:doc WHERE nro_factura=:nro AND cod_articulo=:cod");
            $stmt->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB); // LOB indica contenido binario.
            $stmt->bindParam(':nro', $nro);
            $stmt->bindParam(':cod', $cod);
            $stmt->execute();
            $traza .= "\nDocumento PDF actualizado correctamente.";
        } catch (Exception $e) {
            Database::logError($mensajeError . $e->getMessage());
        }
    }

    // BORRAR: elimina la fila indicada por factura + artículo.
    public function eliminarRenglon($nroFactura, $codArticulo, &$traza = '') {
        if (!$this->pdo) {
            $traza = "No se pudo conectar a la base de datos.";
            return false;
        }

        try {
            // DELETE: borra solo el renglón que coincide con los dos datos de la clave.
            $sql = "DELETE FROM renglones_factura WHERE `nro_factura`=:nroFactura AND `cod_articulo`=:codArticulo";
            $stmt = $this->pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
            $traza .= "Preparacion exitosa!\n";

            $stmt->bindParam(':nroFactura', $nroFactura);
            $stmt->bindParam(':codArticulo', $codArticulo);
            $traza .= "Binding exitoso!\n";

            $stmt->execute(); // DELETE: ejecuta el borrado definitivo de la fila.
            $traza .= "Ejecucion exitosa!\n";
            $traza .= "Baja de renglón $nroFactura / $codArticulo correcta";
            return true;
        } catch (PDOException $e) {
            Database::logError('Baja: ' . $e->getMessage());
            $traza .= "\n" . $e->getMessage();
            return false;
        }
    }

    // VISTA PREVIA: lee el binario y lo convierte a Base64 para el JSON.
    public function getPdfBase64($nroFactura, $codArticulo) {
        if (!$this->pdo) return null;
        try {
            // SELECT PDF: busca únicamente el binario del renglón elegido.
            $stmt = $this->pdo->prepare("SELECT `documentoPdf` FROM renglones_factura WHERE `nro_factura` = :nro AND `cod_articulo` = :cod");
            $stmt->bindParam(':nro', $nroFactura);
            $stmt->bindParam(':cod', $codArticulo);
            $stmt->execute(); // Ejecuta el SELECT del archivo PDF.
            $fila = $stmt->fetch(); // Obtiene una sola fila de la respuesta SQL.
            if ($fila && !empty($fila['documentoPdf'])) {
                return base64_encode($fila['documentoPdf']); // Convierte el binario a texto Base64 para poder enviarlo en JSON.
            }
            return null;
        } catch (PDOException $e) {
            Database::logError('traeBinario: ' . $e->getMessage());
            return null;
        }
    }
}
