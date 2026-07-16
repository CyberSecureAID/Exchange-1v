# Plataforma de trading no-custodial

> Documento vivo. Es la brújula del proyecto: qué construimos, por qué, y las
> decisiones que ya están tomadas. Si alguna vez se pierde el rumbo, se vuelve
> aquí.

---

## La idea en una frase

Una interfaz de trading limpia y en español, montada **sobre mercados
descentralizados (DEX) que ya existen**, donde el usuario opera con su propia
wallet y su propio dinero. Nosotros aportamos la experiencia, las herramientas y
el criterio. Ganamos por comisión, **sin custodiar fondos ni respaldar
liquidez**.

## Para quién

Personas que **no tienen acceso estable** a los exchanges tradicionales: cuentas
que se cierran de un día para otro, regiones bloqueadas, verificaciones que
caducan. Nuestra ventaja no es ser los más baratos: es **existir para quien no
tiene otra puerta**.

---

## El principio que no se negocia

**No somos el banco. Somos la mejor puerta.**

- **No custodiamos.** El dinero vive en la wallet del usuario, nunca en nuestro
  servidor. Cada operación la firma él.
- **No reinventamos.** El código que mueve dinero no se escribe desde cero: se
  ensamblan piezas ya auditadas, con miles de millones pasando por ellas. Encima
  de eso construimos la experiencia.

Si esta línea se respeta, el proyecto es viable y mucho más seguro. Si se cruza
(por ejemplo, simulando apalancamiento que por detrás cubrimos nosotros), nos
convertimos en la contraparte y en la casa — y ahí vuelve la liquidez que
queremos evitar y el riesgo legal serio.

---

## Por dónde viaja el dinero

```
  Wallet del usuario  ──▸  Nuestra plataforma  ──▸  DEX / Agregador
   (su dinero,              (arma la orden,          (ejecuta el
    su firma)                no toca el dinero)        intercambio real)
```

El usuario firma cada operación. El fondo va directo de su wallet al mercado.
Nosotros orquestamos, pero el dinero no nos atraviesa. **Por eso no somos
custodios.**

---

## Las tres piezas del producto

1. **Conexión de wallet.** Botón «Conectar wallet»: leemos dirección y saldo. No
   guardamos claves.
2. **Ejecutor de intercambios.** La interfaz arma la transacción contra el
   mercado y el usuario la firma. No escribimos contrato propio: usamos un
   agregador que ya enruta entre DEX y busca el mejor precio.
3. **Nuestro valor agregado** (aquí está el negocio):
   - **Órdenes límite** — un servicio que vigila precios y ejecuta al cumplirse
     la condición.
   - **Panel de ganancias** — rendimiento desde el punto de entrada, historial,
     gráficas.
   - **Interfaz limpia en español**, pensada para nuestro público.

---

## Stack afianzado (qué usamos ya hecho)

| Pieza | Para qué |
|---|---|
| **wagmi + RainbowKit** (o Web3Modal) | Conexión de wallets |
| **0x / 1inch API** | El corazón de los swaps: mejor ruta entre DEX + transacción lista para firmar |
| **1inch Limit Order Protocol** | Órdenes límite ya auditadas |
| **0x `affiliateFee`** | Nuestra comisión, cobrada sola en cada swap |
| **Hostinger + base de datos** | Registro de usuarios, puntos de entrada, historial, vigilancia de órdenes límite |

> Nota de arranque: para la Fase 01 se puede empezar con **JS + una librería
> web3 desde CDN** (sin herramientas de build) para que sea fácil de alojar y
> probar. Si el proyecto crece, se migra a un entorno con bundler (Vite/React)
> para usar wagmi/RainbowKit con comodidad. Esta decisión se confirma al llegar
> a esa fase.

---

## Lo que construimos vs. lo que NO

**Construimos:**
- La interfaz limpia, en español
- El panel de ganancias desde el punto de entrada
- El servicio que vigila las órdenes límite
- El registro de usuarios e historial
- La experiencia y el criterio (lo que nos hace distintos)

**No construimos:**
- Un exchange con libro de órdenes propio
- Liquidez propia que respalde operaciones
- Un smart contract de swaps desde cero
- Un sistema de custodia de fondos
- El enrutamiento entre DEX (lo dan los agregadores)

---

## Cómo ganamos

Una **comisión pequeña sobre cada operación** que pasa por la interfaz. El
agregador permite añadir nuestra comisión a cada swap: se cobra sola, en la
misma transacción, y llega a nuestra wallet. Sin mensualidades, sin custodiar,
sin perseguir a nadie. El ingreso crece con el volumen, no con lo que
arriesgamos.

### Decisión tomada: comisión = **0,05 %** por operación

- Es **la mitad** del estándar de Binance (~0,1 %). Competitivo de entrada.
- Para el usuario es casi invisible (en $1.000 = $0,50), pero sostiene el
  negocio por volumen.
- Bajar más (p. ej. 0,01 %) no lo nota el usuario, pero multiplica por 5 el
  volumen necesario para el mismo ingreso, y quedaría por debajo del propio gas
  de la red. **Por eso 0,05 % es el punto elegido.**
- Idea futura: **descuentos por volumen** (quien mueve mucho paga menos), en vez
  de regalar la tarifa mínima a todos desde el día uno.

### La palanca más importante: la red

El **gas de la red lo paga el usuario aparte** y puede ser mayor que nuestra
comisión. Por eso operamos sobre **redes baratas** (Base, BNB Chain, Arbitrum,
Polygon), no sobre Ethereum. Esto es lo que de verdad nos hace competitivos para
nuestro público.

---

## Límites honestos (tener siempre presente)

- **No hay apalancamiento sintético.** Un DEX solo hace intercambios al contado:
  no existen cortos ni apalancamiento nativos. No los simulamos, porque hacerlo
  nos convertiría en la contraparte y necesitaríamos liquidez.
- **No custodiar es lo que nos protege.** Al no tocar el dinero ni ser
  contraparte, la categoría de riesgo baja mucho. Baja, no desaparece.
- **Consulta legal real antes de lanzar al público.** Según país y según
  implementación, puede requerir registro o licencia. Se valida con un
  profesional antes de crecer, no después.

---

## Fases de construcción

- **Fase 01 · Base** — Conectar wallet y leer saldos. Interfaz esqueleto. Aún no
  se opera; solo se demuestra la conexión no-custodial.
- **Fase 02 · Intercambio** — Integrar el agregador (0x/1inch) y ejecutar el
  primer swap real firmado por el usuario.
- **Fase 03 · Comisión** — Activar el 0,05 % en el flujo del agregador.
- **Fase 04 · Diferenciación** — Órdenes límite y panel de ganancias.
- **Fase 05 · Antes de abrir** — Validación legal, pruebas de seguridad y fase
  cerrada con usuarios reales.

---

## Estado del repositorio

| Fase | Estado |
|---|---|
| Documentación / brújula (este README) | ✅ Hecho |
| Fase 01 · Base | ⏳ Siguiente |
| Fase 02–05 | ⬜ Pendiente |

### Archivos del repo

- `README.md` — este documento (la brújula).

*Se irá ampliando archivo por archivo, poco a poco.*

---

## Reglas de trabajo

- **Un archivo a la vez.** Sin prisa. Cada pieza se entiende antes de pasar a la
  siguiente.
- **Nada de reinventar lo que ya está auditado.** El dinero lo mueven piezas
  probadas.
- **Cada decisión importante se anota aquí**, para no repetir conversaciones ni
  perder el rumbo.
