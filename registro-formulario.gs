/**
 * Registro del formulario de rehabilitacionliteraria.com en una hoja de cálculo.
 * Pegar en Extensiones > Apps Script de la hoja, implementar como "Aplicación web"
 * (Ejecutar como: yo / Quién tiene acceso: cualquier persona) y copiar la URL /exec.
 */
var HOJA = 'Registros';
var ENCABEZADOS = ['Fecha', 'Nombre', 'Correo', 'Origen'];

function doPost(e) {
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    var d = JSON.parse((e && e.postData && e.postData.contents) || '{}');

    // Campo trampa: si un bot lo llena, se ignora sin guardar.
    if (d.web) return salida({ ok: true });

    var nombre = limpiar(d.nombre, 120);
    var correo = limpiar(d.correo, 160);
    if (!nombre || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo)) {
      return salida({ ok: false, error: 'datos_invalidos' });
    }

    var libro = SpreadsheetApp.getActiveSpreadsheet();
    var hoja = libro.getSheetByName(HOJA);
    if (!hoja) {
      hoja = libro.insertSheet(HOJA);
      hoja.appendRow(ENCABEZADOS);
      hoja.setFrozenRows(1);
    }

    // Evita correos repetidos.
    var correos = hoja.getRange(2, 3, Math.max(hoja.getLastRow() - 1, 1), 1).getValues().flat();
    if (correos.map(function (c) { return String(c).toLowerCase(); }).indexOf(correo.toLowerCase()) !== -1) {
      return salida({ ok: true, repetido: true });
    }

    hoja.appendRow([new Date(), nombre, correo, limpiar(d.origen, 80)]);
    return salida({ ok: true });
  } catch (err) {
    return salida({ ok: false, error: 'error_interno' });
  } finally {
    lock.releaseLock();
  }
}

// Recorta, quita saltos de línea y neutraliza fórmulas (=, +, -, @) al abrir la hoja.
function limpiar(valor, max) {
  var s = String(valor == null ? '' : valor).replace(/[\r\n\t]+/g, ' ').trim().slice(0, max);
  return /^[=+\-@]/.test(s) ? "'" + s : s;
}

function salida(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
