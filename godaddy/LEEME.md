# Publicar en GoDaddy (hosting con cPanel)

Requiere un plan de **Hosting web con cPanel** (no el constructor "Websites + Marketing") con PHP 7.4 o superior.

## 1. Subir el sitio
En cPanel > Administrador de archivos, entra a `public_html` del dominio readaptacionliteraria.com y sube estos tres elementos:

- `index.html`
- `.htaccess` (si no lo ves, activa "Mostrar archivos ocultos")
- carpeta `api/` con `registro.php`

## 2. Subir la clave de Google (una sola vez, fuera de public_html)
Sube el archivo JSON de la cuenta de servicio a la **carpeta de inicio** (la que contiene a `public_html`, no dentro de ella) con el nombre exacto:

    readaptacion-google.json

Así la clave no queda accesible desde internet.

## 3. Permisos en Google
- Comparte la hoja "FORMULARIO rehabilitacionliteraria.com" como **Editor** con el correo de la cuenta de servicio (campo `client_email` del JSON).
- Activa la API de Google Sheets en el proyecto de Google Cloud de esa cuenta.

## 4. Probar
Abre https://readaptacionliteraria.com, llena el formulario con un correo de prueba y confirma que aparece una fila en la pestaña `Registros`. Borra esa fila después.
Si falla, el motivo queda en el registro de errores de PHP (cPanel > Errores).

## 5. Al publicar de verdad
Mientras el sitio está en revisión lleva `noindex`. Para que Google lo indexe, borra la línea `X-Robots-Tag` de `.htaccess` y la línea `<meta name="robots" ...>` de `index.html`.
