-- schema.sql — Estructura de la base de datos del panel de ganancias.
--
-- CÓMO USARLO en Hostinger:
--   1. En hPanel, crea una base de datos MySQL y un usuario (anota host,
--      nombre, usuario y contraseña: van en config.php).
--   2. Abre phpMyAdmin, elige tu base de datos y pega/importa este archivo.
--
-- Guarda las posiciones que el usuario registra en el panel, asociadas a su
-- dirección de wallet, para poder verlas desde cualquier dispositivo.

CREATE TABLE IF NOT EXISTS positions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    address     VARCHAR(42)     NOT NULL,           -- wallet del usuario (en minúsculas)
    symbol      VARCHAR(20)     NOT NULL,           -- p. ej. ETH, USDC
    amount      DECIMAL(38,18)  NOT NULL,           -- cantidad del token (hasta 18 decimales)
    cost        DECIMAL(20,2)   NOT NULL,           -- cuánto pagó en total (USD)
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_address (address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
