# readaptacionliteraria.com (sitio de pruebas)

Sitio estático de una sola página. No necesita build.

- `index.html`: todo el sitio (estilos, script e ilustraciones incrustadas).
- `vercel.json`: sin indexación mientras sea sitio de pruebas.

## Antes de pasar a producción
1. Quitar `noindex` de `index.html` y de `vercel.json`.
2. Pegar la URL de Apps Script en `var ENDPOINT = ''` (dentro del `<script>` de `index.html`).
3. Revisar el aviso de privacidad con quien lo firme.
4. Confirmar dominio (el libro imprime readaptacionliteraria.com).

## Formulario → Google Sheets (cuenta de servicio)

El formulario envía a `/api/registro` (función de Vercel, `api/registro.js`), que agrega una fila a la pestaña `Registros` de la hoja "FORMULARIO readaptacionliteraria.com".

Variables de entorno en Vercel (Settings → Environment Variables), para Production, Preview y Development:

- `GOOGLE_SERVICE_ACCOUNT_EMAIL`: correo de la cuenta de servicio.
- `GOOGLE_PRIVATE_KEY`: el campo `private_key` del JSON (con los `\n` tal cual).
- `SHEET_ID` (opcional): por omisión usa la hoja del proyecto.

La hoja debe estar compartida como editora con el correo de la cuenta de servicio. Tras agregar o cambiar variables hay que volver a desplegar.
