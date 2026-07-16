<?php
/*
 * 0x-proxy.php — Proxy para la API de 0x.
 *
 * ¿Qué hace? El frontend (swap.html) llama AQUÍ en lugar de llamar directo a
 * 0x. Este archivo le añade tu API key y reenvía la petición a 0x. Así la key
 * vive solo en el servidor y NUNCA viaja al navegador.
 *
 * Dónde va: en tu hosting (Hostinger), dentro de una carpeta, p. ej.
 *   /public_html/api/0x-proxy.php
 * y se llama como  https://tudominio.com/api/0x-proxy.php?path=...&sellToken=...
 */

// ─────────────────────────────────────────────────────────────
// 1. Cargar la configuración (la API key vive en config.php, aparte)
// ─────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Falta config.php. Copia config.example.php a config.php y pon tu API key de 0x.'
    ]);
    exit;
}
require $configPath; // define ZEROX_API_KEY y ALLOWED_ORIGIN

// ─────────────────────────────────────────────────────────────
// 2. CORS — permitir que tu frontend llame a este proxy
// ─────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Respuesta al "preflight" del navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 3. Solo se permite GET (0x usa GET para price/quote)
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo se permite GET.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 4. Allowlist: solo dejamos pasar los endpoints que usamos.
//    (Evita que este proxy se convierta en una puerta abierta.)
// ─────────────────────────────────────────────────────────────
$allowedPaths = [
    '/swap/allowance-holder/price',
    '/swap/allowance-holder/quote',
];
$path = isset($_GET['path']) ? $_GET['path'] : '';
if (!in_array($path, $allowedPaths, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint no permitido.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 5. Reconstruir los parámetros (todos menos 'path') y armar la URL
// ─────────────────────────────────────────────────────────────
$params = $_GET;
unset($params['path']);
$url = 'https://api.0x.org' . $path . '?' . http_build_query($params);

// ─────────────────────────────────────────────────────────────
// 6. Llamar a 0x con la key (cURL viene incluido en Hostinger)
// ─────────────────────────────────────────────────────────────
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    '0x-api-key: ' . ZEROX_API_KEY,
    '0x-version: v2',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo contactar a 0x: ' . $curlErr]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 7. Devolver al frontend la respuesta de 0x, tal cual
// ─────────────────────────────────────────────────────────────
http_response_code($status ?: 200);
echo $response;
