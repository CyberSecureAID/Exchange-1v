<?php
/*
 * 1inch-orders.php — Proxy para las ÓRDENES LÍMITE de 1inch.
 *
 * QUÉ HACE (seguro):
 *   - Esconde tu API key de 1inch.
 *   - Transmite a 1inch una orden que YA VIENE FIRMADA por el usuario.
 *   - Lista las órdenes de una dirección y consulta el estado de una orden.
 *
 * QUÉ NO HACE (a propósito):
 *   - No construye ni firma la orden. Eso lo hará el frontend con el SDK oficial
 *     de 1inch (la pieza siguiente, cuando el proyecto tenga entorno de build).
 *     Aquí solo movemos datos ya firmados y consultamos estados: por eso es
 *     seguro y no puede "romper" una firma.
 *
 * NO custodia nada: la orden la firma el usuario; 1inch mantiene el libro de
 * órdenes; los resolvers de 1inch la ejecutan cuando el precio se cumple.
 *
 * OPERACIONES:
 *   POST  ?op=submit&chain=8453          (cuerpo = la orden firmada en JSON)
 *   GET   ?op=list&chain=8453&address=0x…
 *   GET   ?op=status&chain=8453&hash=0x…
 */

// ─────────────────────────────────────────────────────────────
// 1. Configuración (las keys viven en config.php, aparte)
// ─────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Falta config.php. Copia config.example.php a config.php y pon tus API keys.']);
    exit;
}
require $configPath; // ZEROX_API_KEY, ONEINCH_API_KEY, ALLOWED_ORIGIN

// ─────────────────────────────────────────────────────────────
// 2. CORS
// ─────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─────────────────────────────────────────────────────────────
// 3. Parámetros comunes
// ─────────────────────────────────────────────────────────────
$op    = isset($_GET['op'])    ? $_GET['op']    : '';
$chain = isset($_GET['chain']) ? $_GET['chain'] : '';
if (!preg_match('/^[0-9]+$/', $chain)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro "chain" (id de red) inválido.']);
    exit;
}
$base = 'https://api.1inch.dev/orderbook/v4.0/' . $chain;

// ─────────────────────────────────────────────────────────────
// Helper: llamar a 1inch con la key (cURL)
// ─────────────────────────────────────────────────────────────
function call_1inch($url, $method, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        'Authorization: Bearer ' . ONEINCH_API_KEY,
        'Accept: application/json',
    ];
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);
    return [$resp, $status, $err];
}

// ─────────────────────────────────────────────────────────────
// 4. Enrutado por operación (allowlist)
// ─────────────────────────────────────────────────────────────
$resp = null; $status = 0; $err = '';

if ($op === 'submit') {
    // Transmitir una orden YA FIRMADA (llega en el cuerpo POST como JSON)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'La operación "submit" requiere POST.']);
        exit;
    }
    $body = file_get_contents('php://input');
    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el cuerpo con la orden firmada.']);
        exit;
    }
    // Comprobación mínima: que sea JSON válido
    if (json_decode($body) === null) {
        http_response_code(400);
        echo json_encode(['error' => 'El cuerpo no es JSON válido.']);
        exit;
    }
    list($resp, $status, $err) = call_1inch($base, 'POST', $body);

} elseif ($op === 'list') {
    // Órdenes de una dirección (el "maker")
    $address = isset($_GET['address']) ? $_GET['address'] : '';
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro "address" inválido.']);
        exit;
    }
    list($resp, $status, $err) = call_1inch($base . '/address/' . $address, 'GET');

} elseif ($op === 'status') {
    // Estado de una orden por su hash
    $hash = isset($_GET['hash']) ? $_GET['hash'] : '';
    if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $hash)) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro "hash" inválido.']);
        exit;
    }
    list($resp, $status, $err) = call_1inch($base . '/order/' . $hash, 'GET');

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Operación no permitida. Usa op=submit | list | status.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 5. Devolver la respuesta de 1inch tal cual
// ─────────────────────────────────────────────────────────────
if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo contactar a 1inch: ' . $err]);
    exit;
}
http_response_code($status ?: 200);
echo $resp;
