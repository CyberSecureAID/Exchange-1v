<?php
/*
 * panel-db.php — Base de datos del panel de ganancias.
 *
 * Guarda las posiciones del usuario en MySQL, asociadas a su dirección de
 * wallet, para verlas desde cualquier dispositivo (hoy el panel las guarda solo
 * en el navegador). No toca fondos ni claves: solo datos que el usuario registra.
 *
 * OPERACIONES:
 *   GET   ?op=list&address=0x…            → posiciones de esa dirección
 *   POST  ?op=add     (body JSON: {address, symbol, amount, cost})
 *   POST  ?op=delete  (body JSON: {address, id})
 *
 * SEGURIDAD:
 *   - Usa SIEMPRE sentencias preparadas (PDO) → a prueba de inyección SQL.
 *   - PENDIENTE antes de producción: verificar que quien pide/modifica las
 *     posiciones de una dirección controla esa wallet (firma de un mensaje).
 *     Hoy no hay ese control; se añade antes de abrir al público. Anotado.
 */

// ─────────────────────────────────────────────────────────────
// 1. Configuración (credenciales de la base de datos en config.php)
// ─────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Falta config.php. Copia config.example.php a config.php y pon los datos de la base de datos.']);
    exit;
}
require $configPath; // DB_HOST, DB_NAME, DB_USER, DB_PASS, ALLOWED_ORIGIN

// ─────────────────────────────────────────────────────────────
// 2. CORS
// ─────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─────────────────────────────────────────────────────────────
// 3. Conexión a MySQL (PDO)
// ─────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No hay conexión con la base de datos.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function body_json() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true);
}
function valid_addr($a) {
    return is_string($a) && preg_match('/^0x[a-fA-F0-9]{40}$/', $a);
}

$op = isset($_GET['op']) ? $_GET['op'] : '';

// ─────────────────────────────────────────────────────────────
// 4. Operaciones (allowlist)
// ─────────────────────────────────────────────────────────────
if ($op === 'list') {
    $address = isset($_GET['address']) ? $_GET['address'] : '';
    if (!valid_addr($address)) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro "address" inválido.']);
        exit;
    }
    $stmt = $pdo->prepare(
        'SELECT id, symbol, amount, cost, created_at
         FROM positions WHERE address = ? ORDER BY created_at DESC'
    );
    $stmt->execute([strtolower($address)]);
    echo json_encode(['positions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} elseif ($op === 'add') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => '"add" requiere POST.']);
        exit;
    }
    $d = body_json();
    $address = ($d && isset($d['address'])) ? $d['address'] : '';
    $symbol  = ($d && isset($d['symbol']))  ? substr(trim($d['symbol']), 0, 20) : '';
    $amount  = ($d && isset($d['amount']))  ? $d['amount'] : null;
    $cost    = ($d && isset($d['cost']))    ? $d['cost']   : null;

    if (!valid_addr($address) || $symbol === '' ||
        !is_numeric($amount) || $amount <= 0 ||
        !is_numeric($cost)   || $cost < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos. Revisa address, symbol, amount y cost.']);
        exit;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO positions (address, symbol, amount, cost) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([strtolower($address), $symbol, $amount, $cost]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

} elseif ($op === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => '"delete" requiere POST.']);
        exit;
    }
    $d = body_json();
    $address = ($d && isset($d['address'])) ? $d['address'] : '';
    $id      = ($d && isset($d['id']))      ? $d['id']      : null;
    if (!valid_addr($address) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos para borrar.']);
        exit;
    }
    // Borra solo si la posición pertenece a esa dirección
    $stmt = $pdo->prepare('DELETE FROM positions WHERE id = ? AND address = ?');
    $stmt->execute([(int)$id, strtolower($address)]);
    echo json_encode(['ok' => true, 'deleted' => $stmt->rowCount()]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Operación no permitida. Usa op=list | add | delete.']);
    exit;
}
