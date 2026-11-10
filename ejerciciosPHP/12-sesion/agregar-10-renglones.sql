-- Seleccionar primero la base del ejercicio en phpMyAdmin y abrir la pestaña SQL.
-- Agrega las facturas 1005 a 1014 con artículos ART-04 a ART-13, sin PDF.
-- Ejecutar una sola vez: los pares factura + artículo deben ser únicos.
-- Detecta si MySQL calcula el importe; en ese caso no intenta escribirlo.
-- Referencia: https://mariadb.com/docs/server/reference/system-tables/information-schema/information-schema-tables/information-schema-columns-table

SET @ej12_importe_generado = EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'renglones_factura'
      AND COLUMN_NAME = 'importe_renglon'
      AND COALESCE(GENERATION_EXPRESSION, '') <> ''
);

SET @ej12_columna_importe = IF(@ej12_importe_generado, '', ', importe_renglon');
SET @ej12_calculo_importe = IF(@ej12_importe_generado, '', ', nuevos.cantidad * nuevos.precio_unitario');

SET @ej12_sql = CONCAT(
    'INSERT INTO renglones_factura (nro_factura, fecha_factura, cod_articulo, descripcion, cod_um, cantidad, precio_unitario',
    @ej12_columna_importe,
    ') SELECT nuevos.nro_factura, nuevos.fecha_factura, nuevos.cod_articulo, nuevos.descripcion, nuevos.cod_um, nuevos.cantidad, nuevos.precio_unitario',
    @ej12_calculo_importe,
    ' FROM (',
    'SELECT 1005 AS nro_factura, ''2026-11-10'' AS fecha_factura, ''ART-04'' AS cod_articulo, ''Cable de red Cat 6'' AS descripcion, ''UN'' AS cod_um, 10 AS cantidad, 1500.00 AS precio_unitario',
    ' UNION ALL SELECT 1006, ''2026-11-10'', ''ART-05'', ''Conector RJ45 Cat 6'', ''UN'', 50, 250.00',
    ' UNION ALL SELECT 1007, ''2026-11-10'', ''ART-06'', ''Patch cord de 1 metro'', ''UN'', 15, 2200.00',
    ' UNION ALL SELECT 1008, ''2026-11-10'', ''ART-07'', ''Switch de 8 puertos'', ''UN'', 3, 25000.00',
    ' UNION ALL SELECT 1009, ''2026-11-10'', ''ART-08'', ''Router WiFi'', ''UN'', 2, 45000.00',
    ' UNION ALL SELECT 1010, ''2026-11-10'', ''ART-09'', ''Placa de red USB'', ''UN'', 5, 12000.00',
    ' UNION ALL SELECT 1011, ''2026-11-10'', ''ART-10'', ''Canaleta de 2 metros'', ''UN'', 20, 1800.00',
    ' UNION ALL SELECT 1012, ''2026-11-10'', ''ART-11'', ''Ficha de pared RJ45'', ''UN'', 12, 3500.00',
    ' UNION ALL SELECT 1013, ''2026-11-10'', ''ART-12'', ''Patch panel de 24 puertos'', ''UN'', 1, 60000.00',
    ' UNION ALL SELECT 1014, ''2026-11-10'', ''ART-13'', ''Tester de cable de red'', ''UN'', 2, 18000.00',
    ') AS nuevos'
);

PREPARE insertar_renglones_ej12 FROM @ej12_sql;
EXECUTE insertar_renglones_ej12;
DEALLOCATE PREPARE insertar_renglones_ej12;
