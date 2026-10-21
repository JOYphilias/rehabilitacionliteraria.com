# rehabilitacionliteraria.com (sitio de pruebas)

Sitio estático de una sola página. No necesita build.

- `index.html`: todo el sitio (estilos, script e ilustraciones incrustadas).
- `vercel.json`: sin indexación mientras sea sitio de pruebas.

## Antes de pasar a producción
1. Quitar `noindex` de `index.html` y de `vercel.json`.
2. Pegar la URL de Apps Script en `var ENDPOINT = ''` (dentro del `<script>` de `index.html`).
3. Revisar el aviso de privacidad con quien lo firme.
4. Confirmar dominio (el libro imprime readaptacionliteraria.com).
