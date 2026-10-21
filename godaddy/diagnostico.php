<?php
// Diagnóstico temporal. BÓRRALO del servidor en cuanto lo uses.
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
function ok($b) { return $b ? 'OK' : 'FALTA'; }

echo "PHP: " . PHP_VERSION . " (" . ok(version_compare(PHP_VERSION, '7.4', '>=')) . ", se requiere 7.4 o superior)\n";
echo "OpenSSL: " . ok(function_exists('openssl_sign')) . "\n";
echo "cURL: " . ok(function_exists('curl_init')) . "\n";
echo "mbstring: " . ok(function_exists('mb_substr')) . "\n";

$raiz = dirname($_SERVER['DOCUMENT_ROOT'] ?? '');
echo "Carpeta de inicio: " . $raiz . "\n";
echo "Clave de Google (readaptacion-google.json): " . (is_readable($raiz . '/readaptacion-google.json') ? 'ENCONTRADA' : 'no está (aún)') . "\n";

if (function_exists('curl_init')) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    echo "Conexión saliente a Google: " . ($codigo > 0 ? 'OK (respuesta HTTP ' . $codigo . ')' : 'FALLA: ' . $error) . "\n";
}
echo "\nSi todo dice OK, el formulario puede funcionar. Borra este archivo.\n";
