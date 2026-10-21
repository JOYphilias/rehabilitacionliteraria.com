<?php
/**
 * Registro del formulario en Google Sheets con una cuenta de servicio (GoDaddy / cPanel, PHP 7.4+).
 *
 * La clave de la cuenta de servicio (el archivo JSON de Google) se sube UNA vez FUERA de public_html,
 * a la carpeta de inicio del hosting, con el nombre: readaptacion-google.json
 * Ejemplo: /home/USUARIO/readaptacion-google.json   (public_html queda en /home/USUARIO/public_html)
 */
const SHEET_ID = '1HRA0kYh20bCoJH60ZlR9EWsko_CMT_72uiYMSR5qgo4';
const HOJA = 'Registros';
const ENCABEZADOS = ['Fecha', 'Nombre', 'Correo', 'Origen'];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

function limpiar($valor, $max) {
    $s = trim(preg_replace('/[\r\n\t]+/u', ' ', (string)$valor));
    $s = mb_substr($s, 0, $max, 'UTF-8');
    return preg_match('/^[=+\-@]/', $s) ? "'" . $s : $s;
}

function b64url($datos) {
    return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
}

function http($metodo, $url, $cabeceras, $cuerpo = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $cabeceras,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($cuerpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
    $r = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($r === false || $codigo >= 400) throw new Exception('HTTP ' . $codigo . ' en ' . $url . ' ' . substr((string)$r, 0, 200));
    return json_decode($r, true);
}

function token($clave) {
    $ahora = time();
    $cabecera = b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $datos = b64url(json_encode([
        'iss' => $clave['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $ahora,
        'exp' => $ahora + 3000,
    ]));
    $firma = '';
    if (!openssl_sign($cabecera . '.' . $datos, $firma, $clave['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new Exception('No se pudo firmar con la clave.');
    }
    $r = http('POST', 'https://oauth2.googleapis.com/token',
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $cabecera . '.' . $datos . '.' . b64url($firma),
        ]));
    return $r['access_token'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, ['ok' => false, 'error' => 'metodo']);
}

// Solo se acepta desde el mismo sitio.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $host = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    $miHost = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
    if ($host !== $miHost) responder(403, ['ok' => false, 'error' => 'origen']);
}

$crudo = file_get_contents('php://input', false, null, 0, 4096);
$d = json_decode($crudo, true);
if (!is_array($d)) responder(400, ['ok' => false, 'error' => 'datos_invalidos']);

// Campo trampa: si un bot lo llena, se responde bien pero no se guarda.
if (!empty($d['web'])) responder(200, ['ok' => true]);

$nombre = limpiar($d['nombre'] ?? '', 120);
$correo = limpiar($d['correo'] ?? '', 160);
if ($nombre === '' || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $correo)) {
    responder(400, ['ok' => false, 'error' => 'datos_invalidos']);
}

try {
    $ruta = dirname($_SERVER['DOCUMENT_ROOT']) . '/readaptacion-google.json';
    if (!is_readable($ruta)) throw new Exception('No se encuentra la clave de Google en ' . $ruta);
    $clave = json_decode(file_get_contents($ruta), true);
    if (empty($clave['client_email']) || empty($clave['private_key'])) throw new Exception('La clave de Google está incompleta.');

    $t = token($clave);
    $auth = ['Authorization: Bearer ' . $t, 'Content-Type: application/json'];
    $base = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEET_ID;

    // Crea la pestaña "Registros" con encabezados si no existe.
    $meta = http('GET', $base . '?fields=sheets.properties.title', $auth);
    $existe = false;
    foreach (($meta['sheets'] ?? []) as $s) if (($s['properties']['title'] ?? '') === HOJA) $existe = true;
    if (!$existe) {
        http('POST', $base . ':batchUpdate', $auth, json_encode(['requests' => [
            ['addSheet' => ['properties' => ['title' => HOJA, 'gridProperties' => ['frozenRowCount' => 1]]]],
        ]]));
        http('PUT', $base . '/values/' . rawurlencode(HOJA . '!A1:D1') . '?valueInputOption=RAW', $auth,
            json_encode(['values' => [ENCABEZADOS]]));
    }

    // Evita correos repetidos.
    $previos = http('GET', $base . '/values/' . rawurlencode(HOJA . '!C2:C'), $auth);
    foreach (($previos['values'] ?? []) as $fila) {
        if (mb_strtolower((string)($fila[0] ?? '')) === mb_strtolower($correo)) {
            responder(200, ['ok' => true, 'repetido' => true]);
        }
    }

    http('POST', $base . '/values/' . rawurlencode(HOJA . '!A:D') . ':append?valueInputOption=RAW&insertDataOption=INSERT_ROWS',
        $auth, json_encode(['values' => [[gmdate('c'), $nombre, $correo, limpiar($_SERVER['HTTP_HOST'] ?? '', 80)]]]));

    responder(200, ['ok' => true]);
} catch (Exception $e) {
    error_log('registro: ' . $e->getMessage());
    responder(500, ['ok' => false, 'error' => 'error_interno']);
}
