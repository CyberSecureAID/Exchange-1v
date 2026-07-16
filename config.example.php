<?php
/*
 * config.example.php — PLANTILLA de configuración.
 *
 * CÓMO USARLO:
 *   1. Copia este archivo y renómbralo a  config.php
 *   2. Pon tus valores reales abajo.
 *   3. NO subas config.php a un repositorio público (ya está en .gitignore).
 *      La API key es secreta: si se filtra, alguien puede gastar tu cuota.
 */

// Tu API key de 0x (se saca gratis en el panel de developers de 0x).
define('ZEROX_API_KEY', 'PON_AQUI_TU_API_KEY_DE_0X');

// Tu API key de 1inch (se saca gratis en el portal de developers de 1inch).
// La usan las órdenes límite (backend/1inch-orders.php).
define('ONEINCH_API_KEY', 'PON_AQUI_TU_API_KEY_DE_1INCH');

// ── Base de datos MySQL (para el panel de ganancias) ──
// Estos datos te los da Hostinger al crear la base de datos en hPanel.
define('DB_HOST', 'localhost');
define('DB_NAME', 'PON_AQUI_EL_NOMBRE_DE_LA_BD');
define('DB_USER', 'PON_AQUI_EL_USUARIO');
define('DB_PASS', 'PON_AQUI_LA_CONTRASENA');

// Origen permitido para llamar al proxy (tu frontend).
//   - En desarrollo puedes dejar '*' (cualquiera).
//   - En producción, ponlo a tu dominio exacto, p. ej.:
//       define('ALLOWED_ORIGIN', 'https://tudominio.com');
define('ALLOWED_ORIGIN', '*');
