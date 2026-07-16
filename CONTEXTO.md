# CONTEXTO — Empieza aquí

> **Si estás leyendo esto, probablemente eres un asistente (o una persona) que
> abre el `.zip` de este proyecto sin recordar las conversaciones donde se
> construyó.** Este documento te pone al día de TODO: qué es, por qué, qué está
> hecho, qué falta y cómo seguir. Léelo entero antes de tocar nada. El `README.md`
> es la documentación técnica del repo; este archivo es el mapa completo y el
> "estado mental" del proyecto.

---

## 0. Resumen en tres frases

Se está construyendo una **plataforma de trading de criptomonedas no-custodial**:
una interfaz limpia, en español, montada sobre mercados descentralizados (DEX)
que ya existen. El usuario opera con **su propia wallet y su propio dinero**;
la plataforma solo orquesta y cobra una **comisión del 0,05%**, sin custodiar
fondos ni respaldar liquidez. Está pensada para **gente sin acceso estable a los
exchanges tradicionales** (cuentas que se cierran, regiones bloqueadas).

---

## 1. Quién es el dueño del proyecto (el usuario)

- Es **trader**, tiene una **academia de trading**, y desarrolla webs para
  empresas. Habla **español** (respóndele siempre en español).
- **Reglas de trabajo que pide** (respétalas):
  - **Un archivo a la vez.** Sin prisa. Cada pieza se entiende antes de seguir.
  - **Entregar solo los archivos que cambian**, no el repo entero.
  - **Respuestas concisas**, sin disertaciones largas.
  - **Verificar el código** antes de entregar (sintaxis, render).
  - Es honesto y quiere honestidad de vuelta: si algo tiene un riesgo o un
    límite, hay que decírselo claro, no maquillarlo.
- **No tiene presupuesto ahora.** Todo se desarrolla **gratis**; pagar hosting
  queda para el final, cuando el producto esté listo y con clientes.

---

## 2. El objetivo y para quién

- **Para quién:** personas que **no pueden mantenerse en exchanges** normales
  (ejemplo recurrente: cubanos y regiones cuyas cuentas se cierran aunque estén
  verificadas). La ventaja no es ser el más barato, es **existir para quien no
  tiene otra puerta**.
- **El negocio:** comisión por volumen (0,05% por operación). Nada de custodiar
  dinero ni respaldar con liquidez propia (el dueño no puede ni quiere hacerlo).

---

## 3. El principio innegociable

**No somos el banco. Somos la mejor puerta.**

1. **No custodiar.** El dinero vive en la wallet del usuario, nunca en nuestro
   servidor. Cada operación la firma él.
2. **No reinventar.** El código que mueve dinero no se escribe desde cero: se
   ensamblan piezas ya auditadas (0x, 1inch). Encima ponemos la experiencia.

Si esta línea se cruza (por ejemplo, simular apalancamiento que por detrás
cubrimos nosotros), nos volvemos la contraparte y la casa → vuelve la necesidad
de liquidez y aparece riesgo legal serio. **Por eso NO hay apalancamiento
sintético:** un DEX solo hace spot; no lo simulamos.

---

## 4. Cómo funciona (arquitectura y flujo del dinero)

```
  Wallet del usuario  ──▸  Nuestra plataforma  ──▸  DEX / Agregador
   (su dinero,              (arma la orden,          (ejecuta el
    su firma)                no toca el dinero)        intercambio real)
```

- **Swaps al contado:** vía el agregador **0x** (enruta el mejor precio entre
  DEX; devuelve la transacción lista para que el usuario firme).
- **Órdenes límite:** vía **1inch Limit Order Protocol**. El usuario **firma** la
  orden off-chain (no mueve fondos); los **resolvers de 1inch la ejecutan** cuando
  el precio se cumple. No necesitamos un vigilante propio 24/7 ni custodia.
- **Nuestra comisión:** se inyecta en el swap de 0x (parámetro de fee) y llega
  sola a nuestra wallet en cada operación.

---

## 5. Decisiones ya tomadas (con su razón, para no rediscutirlas)

- **Comisión = 0,05%** (5 puntos básicos). Es la mitad del estándar de Binance
  (~0,1%). Se descartó 0,01% porque multiplica por 5 el volumen necesario para el
  mismo ingreso y quedaría por debajo del gas. Idea futura: descuentos por
  volumen.
- **Redes baratas** (Base, BNB Chain, Arbitrum, Polygon), NO Ethereum. El gas lo
  paga el usuario aparte y en Ethereum es carísimo; la red barata es la palanca
  real de competitividad.
- **Sin apalancamiento sintético** (ver sección 3).
- **Backend en PHP + MySQL**, pensado para **hosting compartido** (no VPS). Con
  1inch ejecutando las órdenes, no hace falta un proceso corriendo 24/7, así que
  el hosting compartido basta.
- **SSL/HTTPS obligatorio:** las wallets web3 no se conectan sin HTTPS. Cualquier
  hosting elegido DEBE dar SSL.
- **Frontend sin "build"** (HTML + librerías desde CDN) para poder subirlo a
  GitHub Pages y probar fácil. Única excepción: el SDK de 1inch para órdenes
  límite se carga como módulo ESM desde CDN (funciona, pero si diera problemas,
  el plan B es montar un entorno con bundler tipo Vite).

---

## 6. Estado actual — qué está HECHO

Todo el núcleo está construido. Inventario de archivos del repo:

**Frontend (gratis, va en GitHub Pages):**
- `index.html` — **Fase 01**. Conectar wallet y leer saldo. Detecta la red y
  marca si es "barata". No-custodial (solo lectura).
- `swap.html` — **Fase 02+03**. Primer swap real vía 0x, con la **comisión 0,05%**
  ya integrada. El usuario firma; hay `approve` automático si el token lo requiere.
- `panel.html` — **Fase 04**. Panel de ganancias desde el punto de entrada:
  registra posiciones, trae precios (CoinGecko, sin key), calcula PnL, resumen y
  gráfica. Hoy guarda en el navegador (localStorage) como puente.
- `limit.html` — **Fase 04**. Crea y **firma** órdenes límite con el SDK de 1inch;
  las envía al backend. **NO probado en red real** (ver sección 8).

**Backend (PHP + MySQL, listo para cuando haya hosting):**
- `backend/0x-proxy.php` — esconde la API key de 0x; el frontend llama aquí.
- `backend/1inch-orders.php` — órdenes límite: `submit` (transmitir orden ya
  firmada), `list`, `status`. No construye ni firma (seguro). No necesita BD.
- `backend/panel-db.php` — guarda las posiciones del panel en MySQL (`list`,
  `add`, `delete`). Usa sentencias preparadas (anti-inyección SQL).
- `backend/schema.sql` — la tabla `positions` para importar en phpMyAdmin.
- `backend/config.example.php` — plantilla: API keys (0x, 1inch) y datos de la BD.
  Se copia a `config.php`, que **no se sube** (está en `.gitignore`).

**Otros:**
- `README.md` — documentación técnica del repo (la "brújula").
- `.gitignore` — evita subir `config.php` con las keys.

---

## 7. Qué FALTA (los próximos pasos)

1. **Conectar el frontend a los proxys** (cuando haya hosting): en `swap.html`
   cambiar `API_BASE` a la URL del proxy y quitar el campo de API key; en
   `panel.html` pasar de localStorage a llamar a `panel-db.php`.
2. **Autenticación por firma** en `panel-db.php`: hoy guarda/lista/borra por
   dirección **sin verificar** que el usuario controla esa wallet. Antes de
   producción hay que pedir una firma. (Las posiciones no son fondos, pero es
   necesario para que nadie lea/borre las de otro.)
3. **Probar las órdenes límite en una red de test**, con montos mínimos, antes de
   usarlas con dinero real. Es la única pieza sin probar.
4. **Fase 05 — antes de abrir al público:** pruebas de seguridad, **validación
   legal** (según país/región puede requerir licencia; consultar a un profesional
   real, no improvisar), y una fase cerrada con usuarios reales.
5. **Contratar hosting** solo al final (o usar uno gratis para arrancar — sección
   9).

---

## 8. Aviso sobre las órdenes límite (`limit.html`)

Es la pieza **más avanzada y la única no probada contra la red real**. Firmar la
orden NO mueve fondos (es una firma off-chain), así que el riesgo de la firma es
bajo; lo que hay que revisar bien es que **precio y cantidades** estén correctos.
**Regla:** probar primero en **testnet** con **montos mínimos**. Necesita el
backend desplegado y una **API key de 1inch**. La dirección del contrato para el
`approve` la da el propio SDK (no está escrita a mano, así que ese riesgo está
cubierto).

---

## 9. Hosting GRATIS para empezar (PHP + MySQL) — para los primeros clientes

Idea del dueño: arrancar gratis con los primeros 10–100 clientes y, cuando
generen ingresos, migrar a algo de pago (Hostinger). Opciones reales en 2026
(verifica siempre, los planes cambian):

**Recomendadas (PHP 8.x + MySQL + SSL gratis, sin tarjeta):**
- **InfinityFree** — PHP 8.3, hasta 400 bases MySQL (cap ~50 MB c/u), SSL gratis
  (Let's Encrypt), sin anuncios. Límite ~30.000 visitas/día, **sin SSH ni cron**,
  sin correo saliente. Respaldado por iFastNet desde 2013. Buena primera opción.
- **Byet.host** — misma infraestructura (iFastNet), más ancho de banda. Es el
  "plan B" natural si InfinityFree te limita.
- **GoogieHost** — PHP, hasta 2 bases MySQL, ~1 GB disco, integración Cloudflare.
- **cpanelfree** — PHP 8.x con cPanel completo, MySQL, SSL gratis.

**Evitar:**
- **AwardSpace** — su plan gratis sirve **PHP 7** (fin de vida, inseguro) y el SSL
  es de pago. No para un proyecto nuevo que necesita HTTPS.
- **000webhost** — **cerrado** por Hostinger desde octubre de 2023. No existe.

**⚠ Punto crítico a verificar en cualquier host gratis:** nuestros proxys
(`0x-proxy.php`, `1inch-orders.php`) hacen **llamadas salientes con cURL** a
`api.0x.org` y `api.1inch.dev`. Algunos hosts gratis **bloquean o limitan** las
conexiones salientes. **Antes de casarte con uno, sube un `0x-proxy.php` de
prueba y confirma que cURL saliente funciona.** Si no funciona, ese proxy no
sirve ahí.

### Alternativas si no quieres depender de PHP+MySQL gratis

- **La mayoría del proyecto NO necesita base de datos.** Los swaps (0x) y las
  órdenes límite (1inch guarda su propio libro de órdenes) **no usan BD**. La BD
  solo la necesita el **panel de ganancias**. Es decir: podrías desplegar los
  proxys y las órdenes límite, y dejar el panel en localStorage por ahora — y
  tendrías casi todo funcionando sin base de datos.
- **Si quieres BD sin MySQL propio:** servicios con capa gratis como **Supabase**
  o **Neon** (PostgreSQL) o **Firebase** (Firestore). Cambiarían el `panel-db.php`
  por su API, pero quitan la dependencia del MySQL del host.
- **Para los proxys sin PHP:** podrían reescribirse como funciones serverless
  (Cloudflare Workers en JS, o Vercel/Netlify en Node) con capa gratis. Solo si
  el PHP gratis diera problemas de cURL saliente.

---

## 10. Pendientes de seguridad (antes de abrir al público)

- **Autenticación por firma** en el panel (sección 7, punto 2).
- **Restringir CORS** en los proxys al dominio real (hoy permiten `*` para
  desarrollo).
- **Verificar todas las direcciones de token** en el explorador de cada red antes
  de operar con cantidades reales. Las de los archivos son de referencia.
- **Nunca subir `config.php`** (las keys). Ya está en `.gitignore`.
- **Validación legal** antes de operar para el público.

---

## 11. Cómo retomar (para el asistente futuro)

1. Lee este archivo y el `README.md`.
2. Pregunta al dueño en qué punto quiere seguir. Lo más probable es una de:
   conectar frontend↔backend, probar límite en testnet, montar hosting (gratis o
   de pago), o añadir la autenticación por firma.
3. Mantén las reglas: **español, un archivo a la vez, conciso, honesto, verifica
   el código, entrega solo lo que cambia.**
4. Cada decisión o avance importante, anótalo en el `README.md` para que la
   brújula siga al día.

---

*Documento vivo. Refleja el estado del proyecto en el momento de guardarlo en el
repositorio (nombre del repo: **Exchange-U1V**). Si algo cambió, actualízalo.*
