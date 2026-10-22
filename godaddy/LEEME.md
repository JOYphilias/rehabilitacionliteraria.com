# Publicar en GoDaddy (Hosting administrado para WordPress, PHP 8.2)

Este plan trae un WordPress instalado. Subimos el sitio por SFTP a la carpeta raíz del sitio.
**No subas ni reemplaces el `.htaccess`**: el de WordPress ya existe y lo necesita.

## 0. PHP
Panel de GoDaddy > Configuración de hosting > Versión de PHP > Cambiar: elige 8.2.

## 1. Diagnóstico (antes de subir el sitio)
Sube solo `diagnostico.php` a la carpeta raíz y ábrelo en `https://readaptacionliteraria.com/diagnostico.php`.
Todo debe decir OK. **Bórralo** al terminar.

## 2. Subir el sitio
Por SFTP, a la carpeta raíz del sitio (donde están `wp-content` y `wp-config.php`):

- `index.html`
- carpeta `api/` con `registro.php` y `registro-config.ejemplo.php`

Abre el dominio. Si ves este sitio, funcionó. Si ves el WordPress de siempre, avísame (hay otra salida).

## 3. Clave de Google
En `api/`, copia `registro-config.ejemplo.php` como `registro-config.php` y pega ahí `client_email` y `private_key` del JSON de la cuenta de servicio.
Ese archivo solo devuelve datos: abierto en el navegador no muestra nada. No lo publiques en ningún repositorio.

## 4. Permisos en Google
- Comparte la hoja "FORMULARIO rehabilitacionliteraria.com" como **Editor** con el `client_email`.
- Activa la API de Google Sheets en el proyecto de Google Cloud de esa cuenta.

## 5. Probar
Llena el formulario con un correo de prueba y confirma que aparece una fila en la pestaña `Registros`. Borra esa fila después.

## 6. Al publicar de verdad
Mientras está en revisión el sitio lleva `<meta name="robots" content="noindex, nofollow">` en `index.html`. Para que Google lo indexe, borra esa línea.
