/**
 * Registro del formulario en Google Sheets mediante una cuenta de servicio.
 *
 * Variables de entorno (Vercel > Settings > Environment Variables):
 *   GOOGLE_SERVICE_ACCOUNT_EMAIL  correo de la cuenta de servicio
 *   GOOGLE_PRIVATE_KEY            private_key del JSON de la cuenta de servicio
 *   SHEET_ID                      (opcional) ID de la hoja; por omisión, la hoja del proyecto
 */
const { JWT } = require('google-auth-library');

const SHEET_ID = process.env.SHEET_ID || '1HRA0kYh20bCoJH60ZlR9EWsko_CMT_72uiYMSR5qgo4';
const HOJA = 'Registros';
const ENCABEZADOS = ['Fecha', 'Nombre', 'Correo', 'Origen'];
const BASE = 'https://sheets.googleapis.com/v4/spreadsheets/' + SHEET_ID;

function limpiar(valor, max) {
  const s = String(valor == null ? '' : valor).replace(/[\r\n\t]+/g, ' ').trim().slice(0, max);
  return /^[=+\-@]/.test(s) ? "'" + s : s;
}

function cliente() {
  const email = process.env.GOOGLE_SERVICE_ACCOUNT_EMAIL;
  const key = (process.env.GOOGLE_PRIVATE_KEY || '').replace(/\\n/g, '\n');
  if (!email || !key) throw new Error('Faltan las variables de entorno de Google.');
  return new JWT({ email, key, scopes: ['https://www.googleapis.com/auth/spreadsheets'] });
}

async function asegurarHoja(c) {
  const meta = await c.request({ url: BASE + '?fields=sheets.properties.title' });
  const existe = (meta.data.sheets || []).some((s) => s.properties.title === HOJA);
  if (existe) return;
  await c.request({
    url: BASE + ':batchUpdate', method: 'POST',
    data: { requests: [{ addSheet: { properties: { title: HOJA, gridProperties: { frozenRowCount: 1 } } } }] },
  });
  await c.request({
    url: BASE + '/values/' + encodeURIComponent(HOJA + '!A1:D1') + '?valueInputOption=RAW',
    method: 'PUT', data: { values: [ENCABEZADOS] },
  });
}

module.exports = async (req, res) => {
  res.setHeader('Cache-Control', 'no-store');
  if (req.method !== 'POST') { res.setHeader('Allow', 'POST'); return res.status(405).json({ ok: false, error: 'metodo' }); }

  // Solo se acepta desde el mismo sitio.
  const origen = req.headers.origin;
  if (origen) {
    let host = '';
    try { host = new URL(origen).host; } catch (e) { /* origen inválido */ }
    if (host !== req.headers.host) return res.status(403).json({ ok: false, error: 'origen' });
  }

  let d = req.body;
  if (typeof d === 'string') { try { d = JSON.parse(d); } catch (e) { d = null; } }
  if (!d || typeof d !== 'object') return res.status(400).json({ ok: false, error: 'datos_invalidos' });

  // Campo trampa: si un bot lo llena, se responde bien pero no se guarda.
  if (d.web) return res.status(200).json({ ok: true });

  const nombre = limpiar(d.nombre, 120);
  const correo = limpiar(d.correo, 160);
  if (!nombre || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo)) {
    return res.status(400).json({ ok: false, error: 'datos_invalidos' });
  }

  try {
    const c = cliente();
    await asegurarHoja(c);

    const rango = encodeURIComponent(HOJA + '!C2:C');
    const previos = await c.request({ url: BASE + '/values/' + rango });
    const correos = (previos.data.values || []).map((f) => String(f[0] || '').toLowerCase());
    if (correos.includes(correo.toLowerCase())) return res.status(200).json({ ok: true, repetido: true });

    await c.request({
      url: BASE + '/values/' + encodeURIComponent(HOJA + '!A:D') + ':append?valueInputOption=RAW&insertDataOption=INSERT_ROWS',
      method: 'POST',
      data: { values: [[new Date().toISOString(), nombre, correo, limpiar(req.headers.host, 80)]] },
    });
    return res.status(200).json({ ok: true });
  } catch (err) {
    console.error('registro: ' + (err && err.message ? err.message : err));
    return res.status(500).json({ ok: false, error: 'error_interno' });
  }
};
