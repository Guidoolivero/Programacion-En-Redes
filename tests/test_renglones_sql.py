"""Comprueba el INSERT generado con ambas variantes del importe, solo en memoria."""
import re
import sqlite3
import unittest
from pathlib import Path

SQL = Path(__file__).resolve().parents[1] / 'ejerciciosPHP/12-sesion/agregar-10-renglones.sql'


class RenglonesEjemploTest(unittest.TestCase):
    def test_agrega_diez_renglones_con_importes_correctos(self):
        script = SQL.read_text()
        cuerpo = re.search(r'SET @ej12_sql = CONCAT\(([\s\S]*?)\);', script).group(1)
        partes = re.findall(r"'(?:''|[^'])*'|@ej12_\w+", cuerpo)
        for generado in [False, True]:
            with self.subTest(importe_generado=generado):
                variables = {
                    '@ej12_columna_importe': '' if generado else ', importe_renglon',
                    '@ej12_calculo_importe': '' if generado else ', nuevos.cantidad * nuevos.precio_unitario',
                }
                consulta = ''.join(variables[p] if p.startswith('@') else p[1:-1].replace("''", "'") for p in partes)
                importe = 'REAL GENERATED ALWAYS AS (cantidad * precio_unitario) STORED' if generado else 'REAL NOT NULL'
                with sqlite3.connect(':memory:') as db:
                    db.execute(f'''CREATE TABLE renglones_factura (
                        id INTEGER PRIMARY KEY, nro_factura INTEGER, fecha_factura TEXT,
                        cod_articulo TEXT, descripcion TEXT, cod_um TEXT,
                        cantidad REAL, precio_unitario REAL, importe_renglon {importe}, documentoPdf BLOB,
                        UNIQUE(nro_factura, cod_articulo)
                    )''')
                    db.execute(consulta)
                    filas = db.execute('SELECT nro_factura, cod_articulo, cantidad, precio_unitario, importe_renglon, documentoPdf FROM renglones_factura ORDER BY nro_factura').fetchall()
                    self.assertEqual(len(filas), 10)
                    self.assertEqual([f[0] for f in filas], list(range(1005, 1015)))
                    self.assertEqual([f[1] for f in filas], [f'ART-{i:02}' for i in range(4, 14)])
                    for _, _, cantidad, precio, total, pdf in filas:
                        self.assertAlmostEqual(total, cantidad * precio)
                        self.assertIsNone(pdf)


if __name__ == '__main__':
    unittest.main()
