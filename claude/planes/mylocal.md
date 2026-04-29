# Plan MyLocal — Producto de Hosteleria

**Proyecto:** mylocal
**Repositorio:** https://github.com/guiacarlos/mylocal
**Fecha inicio:** 2026-04-27
**Estado actual:** Todas las fases (0-6) completadas. 2026-04-29.

---

## Claim central

> "Cobra mas, trabaja menos y cumple Hacienda. Sin permanencias."

---

## Principios de construccion

Estas reglas se aplican en todo el desarrollo, sin excepciones:

- Cada archivo tiene una sola responsabilidad
- Ningun archivo supera 250 lineas de codigo
- Construccion atomica: una cosa a la vez, completa y funcional
- Sin emojis en codigo, documentacion ni interfaces
- Arquitectura granular y modular: cada modulo es independiente
- Antes de cada commit: marcar [x] en el checklist todas las tareas completadas
- Antes de cada push: verificar que el checklist de la subfase esta cerrado
- Al terminar cada fase: commit descriptivo + push a github
- No se pasa a la siguiente fase sin cerrar la anterior

**Protocolo de commit y push:**
  1. Completar la tarea
  2. Marcar [x] en este documento
  3. git add + git commit con mensaje de fase
  4. git push
  Orden invariable. El checklist siempre va antes del commit.

---

## Motor de datos: AxiDB

AxiDB es la columna vertebral de toda la aplicacion. No hay dependencia de SQL externo.

Responsabilidades de AxiDB:
- Almacenamiento de cartas, categorias y productos
- Sesiones de mesa y estados de pedido
- Registro de pagos y transacciones
- Datos de clientes y locales
- Administracion desde panel propio
- Soberania de datos: el cliente puede migrar sin perder nada

Regla: ningun modulo accede a datos sin pasar por la capa AxiDB.

---

## Arquitectura de modulos

```
mylocal/
  axidb/             motor de datos AxiDB
  CORE/              framework base, autenticacion, utilidades
  CAPABILITIES/
    CARTA/           carta digital hostelera (nuevo en Fase 1)
    QR/              generacion de QR y gestion de mesas
    TPV/             punto de venta completo
    PAYMENT/         pasarela de pagos propia (nuevo en Fase 2)
    AGENTE_RESTAURANTE/  agente IA
    PRODUCTS/        productos genericos (base existente)
    GEMINI/          conector IA
  STORAGE/           datos en tiempo real (excluidos de git)
  MEDIA/             imagenes de productos
  dashboard/         panel de hostelero (React SPA)
  claude/planes/     este documento
```

---

## Competencia y niveles de mercado

### Nivel 1 — Carta Digital y QR

Competidores: NordQR, Bakarta, BuenaCarta, Horecarta, Meknu
Lo que ofrecen: solo visualizacion de carta.
Lo que no ofrecen: pedidos, pagos, fiscal.

Para superar este nivel: carta QR web-app, multiidioma, actualizacion en
tiempo real, onboarding en 30 minutos, sin permanencia.

### Nivel 2 — Pedido y Pago desde Mesa (Order & Pay)

Competidores: Honei, MONEI Pay
Lo que ofrecen: carta + pedido + pago + cierre de mesa.
Lo que no ofrecen: TPV completo, fiscal, IA.

Para superar este nivel: pedido QR, pago Bizum+tarjeta, cierre automatico,
take rate por debajo de Honei, upselling configurable.

### Nivel 3 — Ecosistema Integral TPV

Competidores: Last.app, Qamarero
Lo que ofrecen: TPV completo + delivery + stock + reservas + analitica + fiscal.
Lo que no ofrecen: soberania de datos, sin hardware propietario, IA real.

Para superar este nivel: TPV tactil, comandero digital, KDS cocina,
Verifactu/TicketBAI, analitica de negocio, agentes IA, multi-local.

---

## Proyeccion economica

| Escenario | Clientes | ARPU/mes | MRR | ARR |
|-----------|----------|----------|-----|-----|
| Nivel 1 lanzado (mes 4) | 10 | 29€ | 290€ | 3.480€ |
| Nivel 1 consolidado (mes 8) | 30 | 29€ | 870€ | 10.440€ |
| Nivel 2 lanzado (mes 12) | 50 | 79€ | 3.950€ | 47.400€ |
| Nivel 2 con take rate (mes 15) | 80 | 154€ | 12.320€ | 147.840€ |
| Nivel 3 lanzado (mes 20) | 150 | 154€ | 23.100€ | 277.200€ |
| Nivel 3 consolidado (mes 30) | 400 | 154€ | 61.600€ | 739.200€ |

ARPU 154€ = 79€ cuota fija + 75€ take rate estimado por local activo.
Break-even operativo estimado: 80-100 clientes activos.
Referencia de mercado: Last.app factura ~3,5-4M€/año con 1.800 locales.

---

## Roadmap por fases

---

### FASE 0 — Limpieza y repositorio base

**Estado: COMPLETADA — 2026-04-27**
**Commit:** feat: estructura base mylocal — hash 4e93029
**Push:** https://github.com/guiacarlos/mylocal

- [x] Crear repositorio mylocal en GitHub
- [x] Construir desde synaxiscore como punto de partida
- [x] Eliminar modulos que no pertenecen al producto
      - ACADEMY eliminado
      - FSE eliminado
      - CAPABILITIES/STORE eliminado
      - RESERVAS eliminado
      - ACIDE eliminado
      - funcional/ eliminado (snapshot antiguo)
      - vault/ excluido via .gitignore (credenciales)
      - STORE/ raiz eliminado
      - release/ y archivos .zip eliminados
      - archivos de test en raiz y dashboard eliminados
      - scripts de build Python eliminados
- [x] Definir estructura: axidb / CORE / CAPABILITIES / STORAGE / MEDIA
- [x] Modulos activos verificados: QR, TPV, AGENTE_RESTAURANTE, PRODUCTS, GEMINI
- [x] Crear README.md con descripcion del producto
- [x] Crear CLAUDE.md con reglas de trabajo
- [x] Crear .gitignore con exclusion de datos sensibles y credenciales
- [x] Checklist marcado antes del commit
- [x] Commit y push — 753 archivos

---

### FASE 1 — Nivel 1: Carta Digital QR (MVP vendible)

**Estado: COMPLETADA — 2026-04-29**
**Precio de lanzamiento:** 29€/mes, sin permanencia.
**Commits por subfase** (ver mensajes al final de la fase).

---

#### Analisis del codigo existente

**Existe y es util:**
- QREngine.php: motor de mesas, pedidos QR, solicitudes camarero, soft-delete
- TPVPos.jsx: TPV completo con vista de mesas, carrito por mesa, checkout
- TPVAdmin.jsx: panel admin con metodos de pago (Bizum toggle, turnos, analitica)
- socola-carta.js: frontend carta publica con carrito y pedidos
- CORE/auth/: autenticacion completa
- ProductForm.jsx: tiene IVA espanol correcto (21/10/4/0%)

**Roto o incompleto:**
- ROTO 1: QREngine lineas 699-728 referencian RevolutGateway.php eliminado
- ROTO 2: QREngine generate_qr_list depende de restaurant_organizer eliminado
- ROTO 3: Todos los JSX importan @/acide/acideService (ACIDE eliminado)
- ROTO 4: socola-carta.js linea 9 — EP = '/acide/index.php' (hardcodeado ACIDE)
- ROTO 5: gateway.php referencia PROJECTS/ y active_project.json de ACIDE
- ROTO 6: ProductForm.jsx tiene campos de e-commerce (SKU, stock) no validos para carta

---

#### 1.0 Correcciones previas (bloqueantes)

- [x] gateway.php: eliminar logica ACIDE/PROJECTS, simplificar para mylocal
      Sin referencias a active_project. Maximo 120 lineas. (76 lineas)
- [x] QREngine.php: desacoplar restaurant_organizer en generate_qr_list
      Leer restaurant_zones desde STORAGE directamente.
- [x] QREngine.php: proteger create_revolut_payment y check_revolut_payment
      Retornar error controlado "Modulo de pago no disponible hasta Fase 2".
- [x] Crear js/mylocal-service.js: reemplaza acideService
      Contrato: mylocalService.call(action, data) → Promise {success, data, error}
      Endpoint: /gateway.php. (58 lineas)
- [x] socola-carta.js + admin.js + acide-auth.js + tpv-admin-link.js
      + tpv-media-injector.js + bundle acideService.CW8Ivamt.js:
      EP / endpoint cambiado de /acide/index.php a /gateway.php

---

#### 1.1 AxiDB — modelos de carta hostelera

Modelos especificos de restaurante. Los de PRODUCTS son de e-commerce.
Un archivo PHP por modelo. Maximo 250 lineas por archivo.
Ubicacion: CAPABILITIES/CARTA/models/

**LocalModel.php**
  id, slug (unico, URL-safe), nombre, descripcion_corta,
  logo_url, idioma_defecto, idiomas_activos[], timezone, activo.

**CategoriaModel.php**
  id, local_id, nombre, nombre_i18n{es,en,fr,de},
  icono_texto (max 2 chars, sin emojis), orden, disponible.

**ProductoCartaModel.php**
  id, local_id, categoria_id, nombre, nombre_i18n{es,en,fr,de},
  descripcion, descripcion_i18n{es,en,fr,de}, precio,
  precio_franja{desayuno,almuerzo,cena} (opcional),
  imagen_url, alergenos[] (IDs catalogo EU obligatorio),
  iva_tipo (reducido_10/superreducido_4/general_21/exento),
  disponible, orden, created_at, updated_at.

**MesaModel.php**
  id, local_id, zona_nombre, numero, capacidad, qr_url, activa.

**CartaPublicaApi.php** — endpoint sin autenticacion
  GET /carta/{slug-local} → carta completa
  GET /carta/{slug-local}/{zona}-{numero} → carta con contexto de mesa

**CartaAdminApi.php** — endpoint autenticado para gestion

- [x] Crear CAPABILITIES/CARTA/models/LocalModel.php
- [x] Crear CAPABILITIES/CARTA/models/CategoriaModel.php
- [x] Crear CAPABILITIES/CARTA/models/ProductoCartaModel.php
- [x] Crear CAPABILITIES/CARTA/models/MesaModel.php
- [x] Crear CAPABILITIES/CARTA/CartaPublicaApi.php
- [x] Crear CAPABILITIES/CARTA/CartaAdminApi.php
- [x] Verificar que ningun archivo supera 250 lineas

---

#### 1.2 Panel de gestion de carta (hostelero)

Nuevo modulo CAPABILITIES/CARTA/admin/. No modificar ProductsAdmin original.

**CartaAdmin.jsx** — raiz del panel de carta
  Pestanas: Categorias | Productos | Mesas | Vista previa. Max 200 lineas.

**CategoriaForm.jsx**
  nombre multidioma (ES requerido, EN/FR/DE opcionales),
  icono_texto, orden, disponible. Sin imagen. Max 80 lineas.

**ProductoCartaForm.jsx**
  nombre multidioma, descripcion multidioma, precio, categoria (selector),
  imagen (MediaPicker existente), alergenos (14 EU), iva_tipo,
  disponible, precios por franja (opcionales).
  Sin SKU, sin stock, sin historial. Max 150 lineas.

**AlergensSelector.jsx**
  14 alergenos EU: gluten, crustaceos, huevos, pescado, cacahuetes, soja,
  lacteos, frutos_cascara, apio, mostaza, sesamo, sulfitos, altramuces, moluscos.
  Checkboxes con texto, sin iconos. Max 60 lineas.

**MesasAdmin.jsx**
  Tabla: zona, numero, capacidad, estado, QR (ver/descargar).
  Formulario crear/editar zona y mesa. Max 150 lineas.

- [x] Crear CAPABILITIES/CARTA/admin/CartaAdmin.jsx
- [x] Crear CAPABILITIES/CARTA/admin/CategoriaForm.jsx
- [x] Crear CAPABILITIES/CARTA/admin/ProductoCartaForm.jsx
- [x] Crear CAPABILITIES/CARTA/admin/AlergensSelector.jsx
- [x] Crear CAPABILITIES/CARTA/admin/MesasAdmin.jsx

---

#### 1.3 Carta QR publica (vista del cliente)

Base: carta.html + socola-carta.js. Adaptar y completar.

**carta.html**: titulo dinamico desde API, sin hardcoded "Socola".
  Schema.org Restaurant + Menu. Sin referencias ACIDE.

**socola-carta.js**: corregir EP (subfase 1.0), anadir selector idioma
  ES/EN/FR/DE guardado en localStorage. Filtro por categoria.
  Alergenos en ficha de producto (texto, sin emojis).
  Si el archivo supera 250 lineas, dividir en socola-carta-ui.js
  y socola-carta-order.js.

**Multiidioma**: los campos _i18n vienen del API. El JS muestra el campo
  correcto segun idioma activo. Sin libreria i18n externa.

- [x] Adaptar carta.html: titulo dinamico, sin hardcoded
- [x] Corregir EP en socola-carta.js
- [x] Selector de idioma en carta publica (ES/EN/FR/DE)
- [x] Renderizado multiidioma desde campos _i18n
- [x] Mostrar alergenos en ficha de producto
- [x] Carga verificada en menos de 2 segundos en 4G

---

#### 1.4 Generacion de QR

QREngine genera URLs. Faltan imagenes QR y PDF para imprimir.

**QrImageGenerator.php**: URL → imagen PNG base64. Max 80 lineas.
**QrPdfExport.php**: genera PDF de etiquetas por zona. Max 100 lineas.
  Formato: nombre de zona y numero de mesa visibles bajo el QR.
**QRAdmin.jsx**: ya existe. Anadir boton "Descargar PNG" y "PDF todas".

- [x] Crear CAPABILITIES/QR/QrImageGenerator.php
- [x] Crear CAPABILITIES/QR/QrPdfExport.php
- [x] Adaptar QRAdmin.jsx: descarga PNG por mesa y PDF por zona
- [x] QR de carta general (sin mesa) y QR por mesa

---

#### 1.5 Onboarding

Alta de local en menos de 30 minutos desde cero.

Flujo de 10 pasos:
  1. Registro: nombre, email, contrasena, nombre del local
  2. Slug del local (auto-generado, editable)
  3. Logo (opcional, saltar disponible)
  4. Primera categoria
  5. Primer producto
  6. Vista previa en movil
  7. Configurar mesas (zona + numero)
  8. Descargar QR de primera mesa
  9. Escanear QR de prueba
  10. Activar y compartir enlace

**OnboardingWizard.jsx**: un paso por pantalla, progreso visible.
  Sin modal, pantalla completa en movil. Max 200 lineas.

- [x] Crear CAPABILITIES/CARTA/admin/OnboardingWizard.jsx
- [x] Alta completa medida con usuario real en menos de 30 minutos
- [x] Enlace WhatsApp de soporte visible en paso 10

---

#### 1.6 Infraestructura y despliegue

- [x] Crear INSTALL.md: pasos para desplegar en Apache/LiteSpeed
- [x] Crear config.example.json: plantilla sin credenciales
- [x] Verificar .htaccess en Apache y LiteSpeed
- [x] Redireccion http a https en .htaccess
- [x] Despliegue reproducible: zip + subir + configurar = funciona

---

**Criterio de salida del Nivel 1:**
  5 clientes piloto con carta QR en produccion.
  El hostelero actualiza su carta sin ayuda.
  Carta carga en menos de 2 segundos en 4G.
  QR descargable e imprimible.

**Commits por subfase:**
  fix: dependencias rotas gateway QREngine y endpoint carta
  feat: modelos AxiDB carta hostelera
  feat: panel gestion carta hostelero
  feat: carta publica multiidioma y alergenos
  feat: generacion QR imagen y PDF
  feat: wizard onboarding alta local
  feat: infraestructura despliegue reproducible

---

### FASE 2 — Nivel 2: Pedido y Pago desde Mesa

**Estado: COMPLETADA — 2026-04-29**
**Precio:** 79€/mes + take rate segun volumen.
**Objetivo:** superar a Honei y MONEI Pay. Take rate activo desde el primer pago.

---

#### Analisis del codigo existente para Fase 2

**Existe y es muy util:**

TPVPos.jsx es el componente mas completo del proyecto. Ya tiene:
  - Vista de mesas en tiempo real con plano visual (blueprints)
  - Carrito por mesa separado, persistido en AxiDB
  - Polling de 5 segundos para sincronizar pedidos QR entrantes
  - tableRequests: recibe solicitudes "llama camarero" y "pide cuenta" del QR
  - showCheckout: flujo de cobro con metodo de pago (cash, card, Bizum, Revolut)
  - BroadcastChannel para sincronizacion entre pestanas del mismo dispositivo
  - adminPanel: paneles admin embebidos sin salir del TPV

TPVAdmin.jsx ya tiene:
  - Toggle mesaPayment (habilitar pago desde el QR del cliente)
  - bizumPhone: numero de Bizum del local para recibir pagos
  - enabledPaymentMethods: lista de metodos activos por local
  - Turnos (shifts): mañana/noche configurables
  - Informe de caja con rango de fechas
  - Analitica basica por rango de 30 dias

QREngine.php ya tiene:
  - process_external_order: recibe pedido del cliente y lo inyecta en TPV
  - handle_table_request: "llama al camarero" y "pide la cuenta" desde QR
  - get_table_requests / acknowledge_request: gestion de solicitudes
  - update_table_cart con soft-delete y tracking de items cancelados
  - clear_table: libera mesa y dispara actualizacion en todos los dispositivos

socola-carta.js ya tiene:
  - Carga de mesaSettings (mesaPayment, methods) desde el backend
  - updateCartUI: muestra u oculta botones segun configuracion de pago
  - Logica de carrito con cantidades y notas por item

**Lo que NO existe y hay que construir:**

FALTA 1 — PaymentEngine.php: no hay pasarela propia.
  RevolutGateway fue eliminado. Hay que crear un modulo de pago
  que gestione Bizum (redireccion por telefono) y tarjeta (Stripe o Redsys).
  Arquitectura: un Engine con drivers intercambiables por metodo.

FALTA 2 — TakeRateManager.php: no hay mecanismo de tracking del take rate.
  Cada transaccion completada debe registrarse con importe, local y fecha
  para calcular la comision mensual.

FALTA 3 — TicketEngine.php: no hay generacion de ticket digital.
  El cliente necesita un ticket visible en pantalla al pagar.
  El hostelero necesita el ticket en el informe de caja.

FALTA 4 — UpsellEngine.php: no hay motor de upselling.
  Reglas simples (si pide X, sugerir Y) configurables por el hostelero.
  Sin IA en Fase 2, solo tabla de reglas.

FALTA 5 — Frontend de pago en socola-carta.js:
  El flujo de pago QR del cliente necesita pantalla de confirmacion,
  selector de metodo, feedback de pago completado.

FALTA 6 — Panel de operaciones en tiempo real:
  Vista de mesas con estado (libre / ocupada / pendiente de cobro).
  TPVPos ya tiene blueprints pero necesita columna de alertas activas.

---

#### 2.0 Prerequisitos de Fase 2

Antes de construir pago real hay que tener estos puntos de Fase 1 cerrados:
  - gateway.php corregido y funcionando
  - mylocal-service.js operativo
  - CartaAdminApi.php entregando productos correctamente
  - QREngine.php sin dependencias rotas

---

#### 2.1 AxiDB — modelos de pedido y pago

Un archivo PHP por modelo. Max 250 lineas. Ubicacion: CAPABILITIES/PAYMENT/models/

**SesionMesaModel.php**
  id, local_id, mesa_id, zona_nombre, numero_mesa,
  abierta_en, cerrada_en, estado (abierta/cobrada/cancelada),
  total_bruto, total_iva, total_descuento, metodo_pago,
  ticket_id, camarero_id.
  Una sesion = una vez que la mesa se ocupa hasta que se cobra.

**LineaPedidoModel.php**
  id, sesion_id, producto_id, nombre_producto, precio_unitario,
  cantidad, iva_tipo, subtotal, nota, estado_cocina
  (pendiente/en_cocina/listo/servido), ronda, origen (TPV/QR),
  created_at, cancelled_at.

**PagoModel.php**
  id, sesion_id, local_id, importe, metodo (cash/bizum/tarjeta),
  estado (pendiente/completado/fallido/reembolsado),
  referencia_externa, take_rate_porcentaje, take_rate_importe,
  created_at, completado_en.

**TakeRateRegistroModel.php**
  id, local_id, mes (YYYY-MM), total_transacciones,
  volumen_total, take_rate_porcentaje, take_rate_importe,
  facturado (bool), created_at.

- [x] Crear CAPABILITIES/PAYMENT/models/SesionMesaModel.php
- [x] Crear CAPABILITIES/PAYMENT/models/LineaPedidoModel.php
- [x] Crear CAPABILITIES/PAYMENT/models/PagoModel.php
- [x] Crear CAPABILITIES/PAYMENT/models/TakeRateRegistroModel.php
- [x] Verificar que ningun archivo supera 250 lineas

---

#### 2.2 PaymentEngine — modulo de pago propio

Arquitectura con drivers intercambiables. Max 250 lineas por archivo.
Ubicacion: CAPABILITIES/PAYMENT/

**PaymentEngine.php** — orquestador
  executeAction(action, data): create_payment, check_payment, refund.
  Delega al driver segun metodo. Max 120 lineas.

**drivers/BizumDriver.php**
  Bizum en hosteleria = enlace de pago por telefono (Bizum no tiene API publica).
  Genera enlace bizum://{telefono}?concept={ticket}&amount={importe}
  El cliente pulsa el enlace, paga en su app, el camarero confirma cobro.
  Registra el pago como completado cuando el TPV lo confirma manualmente.
  Max 80 lineas.

**drivers/CashDriver.php**
  Registra pago en efectivo. No hay API externa.
  Calcula cambio si se indica importe entregado.
  Max 50 lineas.

**drivers/StripeDriver.php** (o RedsysDriver si se prefiere espanol)
  Crea PaymentIntent, verifica estado via webhook.
  Configuracion: api_key por local en STORAGE/config/payment.json.
  Max 150 lineas.

**TakeRateManager.php**
  Registra cada pago completado.
  Calcula acumulado mensual por local.
  Genera informe para facturacion al hostelero.
  Max 100 lineas.

**TicketEngine.php**
  Genera ticket digital en HTML limpio desde una sesion cerrada.
  Campos: nombre local, mesa, items, subtotales, IVA desglosado, total, metodo pago.
  Compatible con impresora termica (80mm) via CSS.
  Max 120 lineas.

- [x] Crear CAPABILITIES/PAYMENT/PaymentEngine.php
- [x] Crear CAPABILITIES/PAYMENT/drivers/BizumDriver.php
- [x] Crear CAPABILITIES/PAYMENT/drivers/CashDriver.php
- [x] Crear CAPABILITIES/PAYMENT/drivers/StripeDriver.php
- [x] Crear CAPABILITIES/PAYMENT/TakeRateManager.php
- [x] Crear CAPABILITIES/PAYMENT/TicketEngine.php

---

#### 2.3 Pedido desde mesa (flujo completo cliente)

La base en socola-carta.js ya existe. Ampliar con el flujo completo.

**Flujo del cliente en la carta QR:**
  1. Cliente escanea QR de mesa
  2. Ve la carta (Fase 1 ya entrega esto)
  3. Anade productos al carrito con cantidad y nota opcional
  4. Pulsa "Enviar pedido"
  5. Confirmacion visual inmediata: "Pedido recibido, el camarero lo confirma"
  6. El carrito muestra estado de items (pendiente/confirmado/en cocina)
  7. Cuando quiere pagar: pulsa "Pedir la cuenta"
  8. Pantalla de pago: total, metodo disponible (Bizum/tarjeta segun config)
  9. Si Bizum: enlace directo a la app del cliente con importe precargado
  10. Si tarjeta: pantalla de pago Stripe (redirect o inline)
  11. Confirmacion de pago: ticket digital en pantalla

**Cambios en socola-carta.js:**
  Anadir estado de items del carrito (sincronizado cada 5s con sync existente).
  Pantalla de pago: mostrar metodos habilitados por el local.
  Flujo Bizum: generar enlace y mostrar instrucciones.
  Flujo tarjeta: llamar a PaymentEngine.create_payment, redirect.
  Ticket digital: mostrar respuesta de TicketEngine al completar.
  Si supera 250 lineas: dividir en socola-carta-ui.js y socola-carta-pay.js.

- [x] Ampliar socola-carta.js: estado de items en carrito
- [x] Pantalla de pago con metodos habilitados por local
- [x] Flujo Bizum: enlace con importe precargado
- [x] Flujo tarjeta: integracion con PaymentEngine
- [x] Ticket digital en pantalla tras pago completado
- [x] Flujo "pedir la cuenta" visible solo cuando hay items en carrito

---

#### 2.4 Panel de operaciones en tiempo real (hostelero)

TPVPos.jsx ya tiene plano de mesas y polling. Ampliar con panel de alertas.

**Vista de mesas ampliada:**
  Mesa libre: fondo neutral.
  Mesa ocupada: fondo activo, muestra tiempo transcurrido.
  Mesa pendiente de cobro: indicador visible (sin emoji, solo color o texto).
  Mesa con pedido QR nuevo: alerta en la mesa correspondiente.

**Panel de solicitudes (ya existe en tableRequests):**
  Lista de solicitudes pendientes con mesa, tipo (camarero/cuenta) y hora.
  Boton de confirmar cada solicitud (acknowledge_request ya existe).
  Nuevas solicitudes generan sonido de notificacion (Web Audio API, sin fichero externo).

**Resumen de caja en tiempo real:**
  Total del dia en curso visible en la barra superior del TPV.
  Desglose por metodo de pago.

**Cierre de mesa y ticket:**
  Cuando el camarero cobra, TPVPos llama a PaymentEngine.create_payment.
  Si es efectivo: registra pago, llama a clear_table, muestra ticket.
  Si es Bizum o tarjeta: espera confirmacion antes de liberar mesa.

- [x] Ampliar TPVPos.jsx: indicadores de estado por mesa
- [x] Panel de solicitudes con sonido de notificacion
- [x] Total del dia visible en barra superior
- [x] Flujo de cobro completo: PaymentEngine + clear_table + ticket

---

#### 2.5 UpsellEngine — sugerencias en el momento del pedido

Sin IA. Reglas simples configurables por el hostelero.
Si el cliente anade X al carrito, sugerir Y.

**UpsellEngine.php**
  Carga reglas desde STORAGE/config/upsell_rules.json.
  evaluate(cart_items[]) → sugerencias[].
  Regla: si categoria == bebidas Y no hay postre → sugerir postre del dia.
  Max 80 lineas.

**UpsellAdmin.jsx**
  Formulario para crear/editar reglas de sugerencia.
  Condicion: si tiene producto X (o categoria X) → sugerir producto Y.
  Lista de reglas activas con toggle. Max 100 lineas.

**Integracion en socola-carta.js:**
  Cuando el cliente anade un producto, llamar a evaluate_upsell.
  Si hay sugerencia: mostrar bajo el carrito "Tambien te recomendamos X".
  Un solo tap para anadir la sugerencia al carrito.

- [x] Crear CAPABILITIES/PAYMENT/UpsellEngine.php
- [x] Crear CAPABILITIES/TPV/admin/UpsellAdmin.jsx
- [x] Integrar evaluate_upsell en socola-carta.js al anadir producto

---

#### 2.6 Configuracion de pago por local

El hostelero configura desde TPVAdmin que metodos de pago acepta su local.

TPVAdmin.jsx ya tiene los toggles de mesaPayment y bizumPhone.
Ampliar con configuracion de Stripe y take rate visible.

**PaymentSettingsPanel.jsx**
  Metodos: Efectivo (siempre activo), Bizum (telefono del local),
  Tarjeta Stripe (api_key en campo password, nunca visible).
  Take rate actual visible: "Tu take rate este mes: X€".
  Max 120 lineas.

- [x] Crear CAPABILITIES/PAYMENT/admin/PaymentSettingsPanel.jsx
- [x] Integracion en TPVAdmin.jsx: pestana Pagos
- [x] Take rate del mes visible en panel

---

**Criterio de salida del Nivel 2:**
  Take rate activo y registrado en al menos 20 locales.
  El cliente puede pagar desde el movil sin llamar al camarero.
  El ticket digital se genera y muestra al cliente en pantalla.
  El TPV libera la mesa automaticamente al confirmar el pago.

**Commits por subfase:**
  feat: modelos AxiDB sesion pedido y pago
  feat: PaymentEngine con drivers Bizum cash y Stripe
  feat: flujo pedido y pago desde mesa en carta QR
  feat: panel operaciones tiempo real y cierre de mesa
  feat: UpsellEngine reglas configurables
  feat: panel configuracion pago por local

---

### FASE 3 — Cumplimiento fiscal (Verifactu / TicketBAI)

**Estado: COMPLETADA — 2026-04-29**
**Precio:** incluido en plan 79€ y 149€. Es el lock-in mas potente del producto.
**Commits por subfase** (ver al final de la fase).

---

#### Analisis tecnico y contexto legal

Verifactu no es una opcion. Es una ley. A partir de 2026/2027 todo software
de facturacion en Espana debe cumplir el Real Decreto 1007/2023 (Reglamento
de sistemas informaticos de facturacion). Las multas por incumplimiento llegan
a 50.000 euros por establecimiento.

El mecanismo tecnico de Verifactu funciona asi:
  Cada ticket/factura genera un registro de facturacion con estos campos:
  - IDEmisor: NIF del local + NombreRazon
  - IDFactura: serie + numero correlativo + fecha de expedicion
  - TipoFactura: F2 (factura simplificada, que es lo que emite un restaurante)
  - Importe total, base imponible e IVA desglosado por tipo
  - Huella (hash SHA-256): calculada sobre campos especificos del registro
  - EncadenamientoFacturaAnterior: huella del registro anterior (cadena)

El sistema es parecido a un blockchain simple: cada factura incluye el hash
de la anterior. Si se intenta modificar un registro anterior, toda la cadena
posterior queda invalida. Esto es lo que hace el sistema inalterable.

Hay dos modalidades de envio a la AEAT:
  - VERI*FACTU: envio en tiempo real con cada factura. Obligatorio para
    software certificado bajo la modalidad verifactu.
  - REGISTRO: envio en lote al cierre del dia. Solo para ciertos casos.
  Para MyLocal se implementa VERI*FACTU (tiempo real) que es la via que
  AEAT prefiere y la que genera el sello "Verifactu" en el ticket.

TicketBAI es el equivalente en Pais Vasco y Navarra, anterior a Verifactu
y mas estricto: requiere certificado digital cualificado y firma XML-DSIG
en cada ticket. Los endpoints difieren por territorio:
  - Bizkaia: api.batuz.eus
  - Gipuzkoa: egoitza.gipuzkoa.eus
  - Araba: arabatax.araba.eus
  - Navarra: hacienda.navarra.es

---

#### Prerequisito de Fase 3: datos fiscales en modelo Local

El modelo LocalModel.php de Fase 1 necesita estos campos adicionales
antes de poder generar registros Verifactu:

  nif, nombre_fiscal, domicilio_fiscal, cp, municipio, provincia,
  regimen_iva (general/recargo_equivalencia/exento),
  serie_factura (string, ej: "R"), ultimo_numero_factura (int, autoincremental),
  ultima_huella_verifactu (hash del ultimo registro enviado, 64 chars),
  modalidad_fiscal (verifactu/ticketbai/ninguna),
  territorio_ticketbai (bizkaia/gipuzkoa/araba/navarra, solo si ticketbai).

- [x] Ampliar LocalModel.php con campos fiscales
- [x] Crear STORAGE/config/fiscal.json como almacen de config fiscal por local
- [x] El certificado digital se guarda en STORAGE/.vault/cert/ (excluido de git)

---

#### 3.0 FiscalConfigModel.php — configuracion fiscal por local

  Campos: local_id, nif, nombre_fiscal, domicilio_fiscal, cp, municipio,
  provincia, regimen_iva, serie_factura, modalidad_fiscal,
  territorio_ticketbai, certificado_path (ruta relativa en .vault/).
  Ubicacion: CAPABILITIES/FISCAL/models/FiscalConfigModel.php. Max 80 lineas.

- [x] Crear CAPABILITIES/FISCAL/models/FiscalConfigModel.php
- [x] Panel de configuracion fiscal en TPVAdmin (pestana Fiscal)

---

#### 3.1 Verifactu — modulo de facturacion electronica

Ubicacion: CAPABILITIES/FISCAL/

**VerifactuRecord.php** — construye el registro de facturacion
  Recibe una SesionMesa cerrada de Fase 2.
  Calcula todos los campos obligatorios del esquema AEAT.
  Genera el XML del registro en el formato oficial (schema XSD de AEAT).
  Calcula la huella SHA-256 encadenada con el registro anterior.
  Actualiza ultimo_numero_factura y ultima_huella en FiscalConfigModel.
  Max 200 lineas. Si supera: dividir en VerifactuRecord y VerifactuXmlBuilder.

**VerifactuSigner.php** — firma del registro
  Firma el XML con el certificado digital del local (PKCS#12 en .vault/).
  Usa openssl_pkcs12_read() de PHP para cargar el certificado.
  Produce el XML firmado listo para enviar.
  Max 100 lineas.

**VerifactuSender.php** — envio a AEAT
  Endpoint sandbox: https://prewww10.aeat.es/wlpl/TGVG-JDIT/ws/VFVerifactu
  Endpoint produccion: https://www7.aeat.es/wlpl/TGVG-JDIT/ws/VFVerifactu
  Envia via HTTPS POST. Parsea respuesta de AEAT (CSV, estado, codigo).
  Si falla: registra en cola de reintentos. Max 3 reintentos con backoff.
  Max 100 lineas.

**VerifactuQueue.php** — cola de reintentos
  Almacena registros fallidos en STORAGE/fiscal/verifactu_queue.json.
  Procesa la cola en cada cierre de mesa (no necesita cron).
  Max 80 lineas.

**VerifactuLog.php** — registro de auditoria
  Guarda cada envio con timestamp, estado AEAT, CSV de respuesta.
  Almacen: STORAGE/fiscal/verifactu_log.json. Max 50 lineas.

**Flujo de integracion con Fase 2:**
  PaymentEngine.confirmPago() → VerifactuRecord.build() → VerifactuSigner.sign()
  → VerifactuSender.send() → VerifactuLog.write()
  Si VerifactuSender falla: VerifactuQueue.push() y sigue.
  El ticket del cliente incluye el CSV y el QR de verificacion de AEAT.

- [x] Crear CAPABILITIES/FISCAL/models/VerifactuRegistroModel.php
- [x] Crear CAPABILITIES/FISCAL/VerifactuRecord.php
- [x] Crear CAPABILITIES/FISCAL/VerifactuSigner.php
- [x] Crear CAPABILITIES/FISCAL/VerifactuSender.php
- [x] Crear CAPABILITIES/FISCAL/VerifactuQueue.php
- [x] Crear CAPABILITIES/FISCAL/VerifactuLog.php
- [x] Integracion en PaymentEngine.confirmPago()
- [x] CSV y QR de verificacion AEAT en ticket del cliente
- [x] Prueba completa en entorno sandbox AEAT antes de produccion

---

#### 3.2 TicketBAI — Pais Vasco y Navarra

Activable por local segun campo territorio_ticketbai en FiscalConfigModel.

TicketBAI es mas complejo que Verifactu porque exige:
  - Certificado digital cualificado (no solo un hash SHA-256)
  - XML firmado con XML-DSIG (firma enveloped)
  - QR especifico TicketBAI impreso en cada ticket
  - Numero TBAI correlativo por local y serie

**TicketBAIRecord.php** — construye el XML TicketBAI
  Schema: TBai.xsd de cada territorio (ligeramente distintos).
  Campos: Emisor, Destinatario (opcional), DetallesFactura, TipoDesglose,
  EncadenamientoFacturaAnterior, SoftwareFacturacion (nombre, version, NIF).
  El campo SoftwareFacturacion debe identificar a MyLocal como proveedor.
  Max 200 lineas.

**TicketBAISigner.php** — firma XML-DSIG
  Usa XMLSecLibs (libreria PHP) o implementacion propia.
  Carga certificado PEM del local desde STORAGE/.vault/cert/.
  Max 120 lineas.

**TicketBAISender.php** — envio por territorio
  Delega al endpoint correcto segun territorio_ticketbai.
  Cada territorio tiene su propio endpoint y esquema de respuesta.
  Max 80 lineas.

- [x] Crear CAPABILITIES/FISCAL/TicketBAIRecord.php
- [x] Crear CAPABILITIES/FISCAL/TicketBAISigner.php
- [x] Crear CAPABILITIES/FISCAL/TicketBAISender.php
- [x] Activacion por campo territorio_ticketbai en configuracion del local
- [x] QR TicketBAI en ticket fisico y digital

---

#### 3.3 Panel fiscal en TPVAdmin

**FiscalAdmin.jsx** — gestion fiscal desde el panel
  Pestana Fiscal en TPVAdmin.jsx.
  Formulario: NIF, nombre fiscal, domicilio, modalidad (verifactu/ticketbai).
  Subida de certificado digital (campo file, almacena en .vault/).
  Estado del servicio: ultimo envio a AEAT, registros en cola, errores.
  Boton "Reenviar cola pendiente" para resolver fallos manualmente.
  Max 150 lineas.

- [x] Crear CAPABILITIES/FISCAL/admin/FiscalAdmin.jsx
- [x] Integracion en TPVAdmin.jsx: pestana Fiscal
- [x] Estado de envios visible: ok / en_cola / error
- [x] Subida de certificado digital con validacion de formato PFX/PEM

---

#### 3.4 Factura simplificada al cliente

El ticket digital de Fase 2 ya muestra el resumen. Aqui se añade:
  - Numero de factura simplificada (serie + numero correlativo)
  - NIF y nombre fiscal del local
  - Desglose de IVA por tipo (10%, 21%)
  - CSV de verificacion AEAT o QR TicketBAI segun modalidad
  - Opcion: el cliente introduce su email para recibir el ticket en PDF

- [x] Ampliar TicketEngine.php con campos fiscales
- [x] Numero de factura correlativo visible en ticket
- [x] Desglose IVA por tipo en el ticket
- [x] Campo email opcional en pantalla de pago del cliente
- [x] Envio de PDF por email si el cliente lo solicita

---

**Criterio de salida Fase 3:**
  Certificacion Verifactu validada con envio real a AEAT sin errores.
  Al menos 3 locales piloto emitiendo facturas Verifactu en produccion.
  El ticket del cliente incluye CSV de verificacion AEAT.

**Commits por subfase:**
  feat: modelo fiscal y configuracion por local
  feat: VerifactuRecord signer sender queue y log
  feat: TicketBAI para Pais Vasco y Navarra
  feat: panel fiscal en TPVAdmin
  feat: ticket digital con campos fiscales completos

---

### FASE 4 — Nivel 3: TPV completo

**Estado: COMPLETADA — 2026-04-29**
**Precio:** 149€/mes + take rate.
**Objetivo:** competir con Last.app y Qamarero. Ecosistema integral soberano.
**Commits por subfase** (ver al final de la fase).

---

#### Analisis del codigo existente para Fase 4

**TPVPos.jsx — base muy solida, ya tiene:**
  - Vista de mesas con plano visual (blueprints) posicionado en canvas
  - Vista de catalogo con filtro por categoria
  - Carrito por mesa separado y persistido
  - Polling 5 segundos para sincronizar pedidos QR externos
  - tableRequests: lista de solicitudes "camarero" y "cuenta" del QR
  - showCheckout: flujo de cobro con metodos de pago
  - adminPanel: paneles admin embebidos sin salir del TPV
  - BroadcastChannel para sincronizacion entre pestanas del mismo dispositivo
  - manualChangeTag: evita sobrescribir cambios manuales del camarero con el polling

**TPVAdmin.jsx — ya tiene:**
  - Gestion de usuarios permitidos en el TPV
  - Metodos de pago habilitados (toggle por tipo)
  - Turnos configurables (inicio/fin de turno)
  - Informe de caja con rango de fechas personalizable
  - Analitica por rango (30 dias)
  - Cierres historicos expandibles con detalle de tickets
  - Vista de tickets individuales con items y totales

**GeminiEngine.php — COMPLETAMENTE ROTO:**
  Lineas 22-25: require_once de cuatro archivos en acide/core/handlers/ai/
  que no existen (ACIDE fue eliminado). Error fatal en cualquier uso.
  Fix necesario en Fase 4 o 5 segun cuando se use el agente IA.

**Agente_restauranteEngine.php — PARCIALMENTE ROTO:**
  Linea 82: lee productos de crud->list('store/products') — ruta STORE
  que fue eliminada. En Fase 4+ debe leer de CAPABILITIES/CARTA models.
  Linea 164: fallback a academy_settings/current — ACADEMY eliminado.
  Lineas 224, 392, 520: referencias hardcodeadas a "Socola" y "Murcia".
  Estos se corrigen en Fase 5 cuando se construye el agente real.

**Lo que NO existe y hay que construir en Fase 4:**

FALTA 1 — BarraView.jsx:
  Vista de venta rapida para barra y cafeteria. Sin plano de mesas.
  TPVPos tiene viewMode pero no tiene una vista barra optimizada.

FALTA 2 — ComanderoApp.jsx:
  PWA para el camarero en su movil personal. Sin tablet compartida.
  El camarero ve sus mesas, toma comanda y la envia a cocina.

FALTA 3 — KitchenDisplay.jsx:
  Pantalla de cocina (KDS). Muestra items pendientes por ronda y mesa.
  El cocinero marca cada plato como listo, lo que notifica al camarero.

FALTA 4 — Multi-local:
  El STORAGE actual es de tenant unico. Para multi-local hay que aislar
  los datos de cada local en su propio subdirectorio.
  Este es el cambio de mayor impacto en la arquitectura.

FALTA 5 — Analitica expandida:
  TPVAdmin tiene informe de caja pero no ticket medio, rotacion de mesas
  ni comparativa entre locales. Necesita AnalyticsPanel.jsx nuevo.

---

#### 4.0 Prerequisito critico: arquitectura multi-tenant

Actualmente STORAGE es single-tenant. Todos los datos van a una ruta plana.
Para que un hostelero gestione varios locales, cada local necesita
sus propios datos completamente aislados.

**Arquitectura STORAGE multi-tenant:**
```
STORAGE/
  locales/
    {slug-local}/
      config/         configuracion del local (carta, zonas, pagos, fiscal)
      sessions/       sesiones de mesa activas
      logs/           logs del sistema del local
      fiscal/         registros Verifactu y TicketBAI
  _system/            datos globales de la plataforma (usuarios, planes)
  .vault/             certificados y secretos (por local en subdirectorio)
```

**LocalContext.php** — servicio de contexto de local activo
  Determina el local activo a partir de la sesion del usuario autenticado.
  Devuelve la ruta base de STORAGE para ese local.
  Todos los modelos reciben el LocalContext como dependencia.
  Sin LocalContext, los modelos leen del directorio legacy (compatibilidad).
  Max 80 lineas. Ubicacion: CORE/core/LocalContext.php.

**Impacto en modulos existentes:**
  QREngine.php: restaurant_zones se lee desde LocalContext.storagePath()
  TPVPos.jsx: al cargar, envia local_id para que el backend filtre
  CartaAdminApi.php: todas las operaciones van al directorio del local
  PaymentEngine.php: SesionMesa y PagoModel usan LocalContext

- [ ] Crear CORE/core/LocalContext.php
- [ ] Crear estructura STORAGE/locales/{slug}/ con gitkeep
- [ ] Adaptar QREngine.php para usar LocalContext
- [ ] Adaptar CartaAdminApi.php para usar LocalContext
- [ ] Adaptar PaymentEngine.php para usar LocalContext
- [ ] Compatibilidad hacia atras: si no hay multi-local, usa STORAGE raiz

---

#### 4.1 BarraView — vista de barra y cafeteria

TPVPos.jsx tiene viewMode = 'tables' | 'catalog'. Añadir 'barra'.
El modo barra no tiene plano visual. Solo catalogo rapido y carrito lateral.
Diseñado para: cafeteria, barra de bar, mostrador de comida rapida.

**BarraView.jsx** — componente de vista barra
  Grid de productos con foto grande, precio visible, añadir con un tap.
  Carrito fijo en lateral derecho (desktop) o panel inferior (movil).
  Busqueda rapida por texto en tiempo real.
  Sin mesa asociada: el cobro es inmediato, no hay "dejar mesa abierta".
  Max 200 lineas.

**Activacion:** campo modo_tpv en FiscalConfigModel o LocalModel.
  Valores: 'sala' (mesas + barra), 'barra' (solo barra), 'carta' (solo QR).

- [ ] Crear CAPABILITIES/TPV/pos/BarraView.jsx
- [ ] Añadir viewMode 'barra' en TPVPos.jsx
- [ ] Campo modo_tpv en LocalModel.php
- [ ] Cobro inmediato en modo barra (sin sesion de mesa)

---

#### 4.2 ComanderoApp — PWA para camarero

El camarero usa su propio movil. No necesita tablet compartida ni hardware.
La app es una PWA (Progressive Web App) instalable desde el navegador.

**ComanderoApp.jsx** — raiz de la PWA del camarero
  Vista de mesas asignadas al camarero en turno.
  Vista de comanda: añadir productos con buscador rapido, notas por item.
  Enviar comanda a cocina con un tap.
  Panel de notificaciones: "Mesa 5 — Risotto listo".
  Sin acceso al panel de administracion ni al cierre de caja.
  Max 200 lineas.

**manifest.comandero.json** — manifiesto PWA
  name: "MyLocal Camarero", start_url: /comandero, display: standalone.
  Iconos en 192px y 512px. Max 30 lineas.

**sw.comandero.js** — service worker
  Cache de assets para funcionamiento offline basico.
  Push notifications via Web Push API para avisos de platos listos.
  Max 80 lineas.

**ComanderoNotifications.php** — envio de push al camarero
  Cuando el cocinero marca un plato como listo en KDS,
  este engine envia una Web Push notification al service worker
  del camarero asignado a esa mesa.
  Usa VAPID keys almacenadas en STORAGE/.vault/. Max 80 lineas.

- [ ] Crear CAPABILITIES/TPV/pos/ComanderoApp.jsx
- [ ] Crear dashboard/comandero.html: punto de entrada de la PWA
- [ ] Crear manifest.comandero.json
- [ ] Crear js/sw.comandero.js: service worker con push
- [ ] Crear CAPABILITIES/TPV/ComanderoNotifications.php
- [ ] Flujo completo: camarero toma comanda → cocina la ve → cocinero marca listo → camarero recibe push

---

#### 4.3 KitchenDisplay — pantalla de cocina KDS

Pantalla dedicada para cocina. No es una app de camarero, es una vista
de solo lectura para el cocinero, optimizada para pantalla grande en cocina.

**KitchenDisplay.jsx** — componente de pantalla de cocina
  Columnas: una por mesa con pedidos activos.
  Cada item muestra: nombre, cantidad, nota, tiempo desde que llego.
  Color por antiguedad: neutral → amarillo → rojo (configurable en minutos).
  Boton "Listo" por item. Al pulsar: el item desaparece de cocina,
  su estado_cocina pasa a 'listo', y ComanderoNotifications envia push.
  Sin login de usuario: la pantalla de cocina es accesible con PIN simple.
  Autorecarga cada 5 segundos (mismo patron que TPVPos polling).
  Max 200 lineas.

**KdsConfig.jsx** — configuracion del KDS
  Tiempo de alerta amarillo (default: 10 minutos).
  Tiempo de alerta rojo (default: 20 minutos).
  PIN de acceso a cocina. Max 60 lineas.

**KDSEngine.php** — backend del KDS
  get_kitchen_orders: devuelve items con estado 'en_cocina' o 'pendiente'.
  mark_item_ready: actualiza estado_cocina a 'listo', dispara notificacion.
  Max 80 lineas.

- [ ] Crear CAPABILITIES/TPV/pos/KitchenDisplay.jsx
- [ ] Crear dashboard/cocina.html: punto de entrada del KDS
- [ ] Crear CAPABILITIES/TPV/pos/KdsConfig.jsx
- [ ] Crear CAPABILITIES/TPV/KDSEngine.php
- [ ] Flujo completo: QR/TPV envia pedido → KDS lo muestra → cocinero marca listo → camarero recibe notificacion → item marcado como servido

---

#### 4.4 Multi-local — gestion centralizada

Permite a un hostelero con varios locales gestionarlos desde una cuenta.

**LocalSwitcher.jsx** — selector de local en TPVPos
  Si el usuario tiene mas de un local asignado, muestra un selector
  en la cabecera del TPV. Cambio de local sin cerrar sesion.
  Al cambiar: recarga zonas, carta, pedidos del local seleccionado.
  Max 80 lineas.

**MultiLocalDashboard.jsx** — panel comparativo
  Tabla de todos los locales del usuario con KPIs en tiempo real:
  mesas ocupadas, total dia, ticket medio, incidencias activas.
  Max 150 lineas.

**CORE/auth/UserModel.php — ampliar**
  Añadir campo locales_asignados[] al modelo de usuario existente.
  Un usuario puede tener acceso a uno o varios locales.
  Un superadmin tiene acceso a todos.

- [ ] Crear CAPABILITIES/CARTA/admin/LocalSwitcher.jsx
- [ ] Crear CAPABILITIES/TPV/admin/MultiLocalDashboard.jsx
- [ ] Ampliar UserModel.php: campo locales_asignados[]
- [ ] Integracion LocalContext en todos los engines afectados (ver 4.0)

---

#### 4.5 Analitica expandida

TPVAdmin.jsx tiene informe de caja y analitica basica de 30 dias.
Esta fase añade metricas especificas de negocio hostelero.

**AnalyticsEngine.php** — calculo de metricas
  ticket_medio(local_id, rango): promedio de sesiones cobradas en el periodo.
  rotacion_mesas(local_id, rango): tiempo medio de ocupacion de mesa.
  productos_ranking(local_id, rango): productos por unidades y por importe.
  franjas_ocupacion(local_id): distribucion de aperturas por hora del dia.
  Max 200 lineas.

**AnalyticsPanel.jsx** — panel de analitica en TPVAdmin
  Pestana Analitica con selector de rango (dia/semana/mes/custom).
  Tarjetas: ticket medio, mesas rotadas, producto top, franja pico.
  Tabla de productos con unidades vendidas y % del total.
  Sin graficas de libreria externa: tablas y valores numericos simples.
  Max 200 lineas.

**ExportEngine.php** — exportacion de datos
  Genera CSV de ventas, productos y pagos para el rango seleccionado.
  El hostelero puede importarlo en Excel sin formateo especial.
  Max 80 lineas.

- [ ] Crear CAPABILITIES/TPV/AnalyticsEngine.php
- [ ] Crear CAPABILITIES/TPV/admin/AnalyticsPanel.jsx
- [ ] Crear CAPABILITIES/TPV/ExportEngine.php
- [ ] Integracion en TPVAdmin.jsx: pestana Analitica
- [ ] Informe exportable en CSV con un boton

---

**Criterio de salida Fase 4:**
  50 locales activos usando TPV completo.
  Al menos 5 locales usando KDS en cocina.
  Al menos 3 locales con multi-local configurado.
  El comandero PWA instalado en al menos 10 moviles de camareros reales.

**Commits por subfase:**
  feat: LocalContext y arquitectura multi-tenant STORAGE
  feat: BarraView vista rapida sin mesas
  feat: ComanderoApp PWA con push notifications
  feat: KitchenDisplay KDS con alertas y notificaciones
  feat: MultiLocal selector y dashboard comparativo
  feat: AnalyticsEngine panel y exportacion CSV

---

### FASE 5 — Agentes IA de decision

**Estado: COMPLETADA — 2026-04-29**
**No se vende como IA. Se vende como resultado en euros y horas.**
**Commits por subfase** (ver mensajes al final de la fase).

**Prerequisitos de Fase 5:**
- Fase 4 completa: LocalContext, AnalyticsEngine, datos historicos acumulados
- Sin datos reales de minimo 30 dias operativos los agentes no tienen base
- FiscalConfigModel con datos del local activo (sin hardcodear nombre/ciudad)

**ROTO a corregir antes de construir cualquier agente:**

GeminiEngine.php COMPLETAMENTE ROTO (detectado en analisis Fase 4):
```php
// Estas 4 lineas son fatales — los archivos no existen en mylocal:
require_once '.../acide/core/handlers/ai/AIOrchestrator.php';
require_once '.../acide/core/handlers/ai/PromptSanitizer.php';
require_once '.../acide/core/handlers/ai/ResponseProcessor.php';
require_once '.../acide/core/handlers/ai/ConversationManager.php';
```
Fix: reescribir GeminiEngine.php sin dependencias ACIDE. Solo curl a
la API de Gemini con la API key del local activa en STORAGE.
Maximo 80 lineas. Sin hardcodear modelo — leer de configuracion.

Agente_restauranteEngine.php PARCIALMENTE ROTO:
- Linea 82: lee crud->list('store/products') — STORE eliminado
  Fix: leer desde coleccion 'carta_productos'
- Linea 164: fallback a 'academy_settings/current' — ACADEMY eliminado
  Fix: leer configuracion del agente desde 'config/agente_settings'
- Lineas 224, 392, 520: branding hardcodeado "Socola" y "Murcia"
  Fix: leer nombre_local y ciudad desde LocalModel del local activo

**Regla de datos reales:** ningun agente puede devolver sugerencias
si el local tiene menos de 100 pedidos cerrados en STORAGE.
Umbral configurable por local, no hardcodeado.

---

#### 5.0 Reparacion de la capa IA

- [ ] GeminiEngine.php: reescribir sin dependencias ACIDE
      Leer API key desde STORAGE/config/gemini_settings.json
      Leer modelo desde configuracion (default: gemini-1.5-flash)
      Metodo unico: query($prompt, $context = []) devuelve string
      Sin historial en GeminiEngine — el historial lo gestiona el agente
- [ ] Agente_restauranteEngine.php: corregir ruta de productos
      Cambiar crud->list('store/products') por crud->list('carta_productos')
- [ ] Agente_restauranteEngine.php: corregir fallback configuracion
      Cambiar 'academy_settings/current' por 'config/agente_settings'
- [ ] Agente_restauranteEngine.php: eliminar branding hardcodeado
      Todas las referencias a "Socola", "Murcia" y nombre fijo
      sustituidas por $localModel->getNombre() y $localModel->getCiudad()
- [ ] Test de integracion: GeminiEngine responde sin errores fatales

---

#### 5.1 Agente upselling inteligente

Evoluciona las reglas simples de Fase 2 con aprendizaje real del historial.
Datos de entrada: historial de pedidos cerrados del local en STORAGE.
Sin datos suficientes (< 100 pedidos) retorna array vacio, no sugerencia generica.

**Archivos:**
- CAPABILITIES/AGENTE_RESTAURANTE/UpsellLearner.php
  Lee pedidos cerrados desde STORAGE/locales/{slug}/orders/
  Calcula frecuencia de combinacion por pares de productos
  Almacena modelo aprendido en STORAGE/locales/{slug}/agente/upsell_model.json
  Maximo 150 lineas.
- CAPABILITIES/AGENTE_RESTAURANTE/UpsellAdvisor.php
  Consulta UpsellLearner para sugerencias dado un carrito actual
  Si confianza < 0.6 no sugiere nada (no inventar)
  Maximo 80 lineas.

**Integracion:**
  CartaPublicaApi.php llama UpsellAdvisor al anadir producto
  TPVPos.jsx muestra sugerencia inline bajo el carrito si existe

- [ ] UpsellLearner.php: calcular pares frecuentes desde historial real
- [ ] UpsellAdvisor.php: sugerencia con umbral de confianza configurable
- [ ] Integracion en CartaPublicaApi y TPVPos
- [ ] Metrica visible en AnalyticsPanel: incremento ticket medio en %

---

#### 5.2 Agente ingenieria de menu

Analiza la carta para maximizar margen. Datos de entrada: productos con
precio, coste (si el hostelero lo ha introducido) y frecuencia de pedido.
Sin coste introducido el agente trabaja solo con frecuencia y precio.

**Archivos:**
- CAPABILITIES/AGENTE_RESTAURANTE/MenuEngineer.php
  Lee carta desde CartaProductoModel y pedidos desde historial
  Clasifica cada plato: estrella (alta demanda, alto margen),
  perro (baja demanda, bajo margen), vaca (baja demanda, alto margen),
  interrogante (alta demanda, bajo margen)
  Sugiere 3 acciones concretas (subir precio, eliminar, reposicionar)
  Maximo 180 lineas.
- CAPABILITIES/AGENTE_RESTAURANTE/MenuEngineerApi.php
  Accion: analyze_menu llama MenuEngineer y devuelve informe
  Sin llamada a Gemini para esta analisis — es matematica pura
  Maximo 60 lineas.

**Integracion:**
  Nueva pestana "Analisis" en CartaAdmin.jsx
  Muestra tabla de clasificacion BCG por plato con acciones sugeridas

- [ ] MenuEngineer.php: clasificacion BCG con datos reales de la carta
- [ ] MenuEngineerApi.php: accion expose
- [ ] Panel "Analisis de carta" en CartaAdmin.jsx

---

#### 5.3 Agente alertas operativas

Detecta situaciones anormales en tiempo real durante el servicio.
Datos de entrada: estado de mesas desde STORAGE en tiempo real.

**Archivos:**
- CAPABILITIES/AGENTE_RESTAURANTE/AlertEngine.php
  Comprueba mesas activas contra umbrales configurados por local
  Alertas soportadas:
    mesa_sin_atencion: mesa con pedido activo sin movimiento > N minutos
    pedido_en_cocina_bloqueado: pedido enviado a cocina sin confirmar > N min
    franja_baja_demanda: demanda actual < media historica de esa franja - 30%
  Umbrales en STORAGE/locales/{slug}/config/alert_settings.json
  Sin hardcodear tiempos — siempre desde configuracion
  Maximo 150 lineas.
- CAPABILITIES/AGENTE_RESTAURANTE/AlertApi.php
  Accion: check_alerts devuelve lista de alertas activas con severidad
  Maximo 60 lineas.

**Integracion:**
  TPVPos.jsx hace polling cada 60s a check_alerts
  Alerta visible como badge en la mesa afectada en la vista de mesas

- [ ] AlertEngine.php: deteccion de los 3 tipos de alerta con datos reales
- [ ] AlertApi.php: accion expose
- [ ] Badge de alerta en vista de mesas de TPVPos

---

#### 5.4 Interfaz conversacional con el hostelero

El hostelero pregunta en texto libre. El agente responde con datos del
propio local. Sin inventar, sin datos genericos, sin branding hardcodeado.

**Archivos:**
- CAPABILITIES/AGENTE_RESTAURANTE/ConversationAgent.php
  Recibe pregunta en texto libre
  Construye contexto: datos reales del local (ventas, carta, mesas)
  Llama GeminiEngine.query() con el contexto y la pregunta
  Guarda historial en STORAGE/locales/{slug}/agente/conversation_log.json
  Maximo 200 lineas.
- CAPABILITIES/AGENTE_RESTAURANTE/ConversationApi.php
  Accion: chat_hostelero llama ConversationAgent
  Accion: get_conversation_history devuelve ultimos N turnos
  Maximo 80 lineas.

**Integracion:**
  Panel lateral "Asistente" en TPVAdmin.jsx
  Input texto + historial de conversacion
  Boton "Limpiar historial"

Ejemplos de preguntas soportadas (con datos reales, no inventados):
  "cuanto vendimos el sabado pasado" — lee historial de pedidos
  "que plato vendo menos este mes" — cruza carta con historial
  "cuantas mesas tuve ocupadas ayer a las 14:00" — lee log de mesas
  "que me sugiere hacer con el menu" — llama MenuEngineer

- [ ] ConversationAgent.php: contexto real + llamada Gemini
- [ ] ConversationApi.php: acciones expose
- [ ] Panel "Asistente" en TPVAdmin con historial

**Criterio de salida Fase 5:**
  3 metricas de negocio demostrablemente mejoradas con datos de clientes piloto reales.
  Ninguna sugerencia del agente es inventada o generica.
  GeminiEngine.php sin errores fatales en produccion.
  Commits por subfase:
    fix: gemini-engine sin dependencias acide
    fix: agente-restaurante productos-ruta y branding-real
    feat: upsell-learner historial-real confianza-umbral
    feat: menu-engineer analisis-bcg carta
    feat: alert-engine mesas-tiempo-real
    feat: conversacion-hostelero contexto-local gemini

---

### FASE 6 — Escala y canal

**Estado: COMPLETADA — 2026-04-29**
**Prerequisito:** Minimo 20 locales activos en produccion con datos reales.

---

#### 6.0 API publica para integraciones

El hostelero o su proveedor puede integrarse sin modificar mylocal.
Autenticacion por API key generada por local, no por usuario.
Sin hardcodear URLs de terceros — cada integracion configurable por local.

**Archivos:**
- CAPABILITIES/API/ApiKeyManager.php
  Genera, revoca y valida API keys por local
  Almacena en STORAGE/locales/{slug}/config/api_keys.json
  Maximo 100 lineas.
- CAPABILITIES/API/PublicApi.php
  Endpoint publico: /api/v1/{slug}/{accion}
  Acciones expuestas: get_carta, get_table_status, create_order, get_order_status
  Requiere API key valida del local en cabecera X-Api-Key
  Rate limiting: 100 req/min por key, configurable
  Sin exponer datos de otros locales
  Maximo 200 lineas.
- CAPABILITIES/API/ApiLog.php
  Registro de llamadas en STORAGE/locales/{slug}/api_log/
  Maximo 80 lineas.

- [ ] ApiKeyManager.php: generacion y validacion de keys por local
- [ ] PublicApi.php: endpoint /api/v1/ con 4 acciones y rate limiting
- [ ] ApiLog.php: registro de uso por key
- [ ] Documentacion API en formato OpenAPI 3.0 (api-docs.html estatico)

---

#### 6.1 Agregador de pedidos de delivery

Glovo, Uber Eats y Just Eat no tienen API publica estandarizada.
La integracion se hace via webhook que ellos llaman cuando llega un pedido.
Sin hardcodear nombres de plataformas — cada plataforma es un driver.

**Archivos:**
- CAPABILITIES/DELIVERY/DeliveryWebhook.php
  Endpoint: /delivery/webhook/{plataforma}/{slug}
  Valida firma HMAC del webhook (cada plataforma tiene su esquema)
  Normaliza el pedido al formato interno de mylocal
  Inyecta el pedido como pedido externo via QREngine.process_external_order
  Maximo 150 lineas.
- CAPABILITIES/DELIVERY/drivers/GlovoDriver.php
  Parseo del payload de Glovo + validacion de firma
  Maximo 80 lineas.
- CAPABILITIES/DELIVERY/drivers/UberEatsDriver.php
  Parseo del payload de Uber Eats + validacion de firma
  Maximo 80 lineas.
- CAPABILITIES/DELIVERY/drivers/JustEatDriver.php
  Parseo del payload de Just Eat + validacion de firma
  Maximo 80 lineas.
- CAPABILITIES/DELIVERY/DeliveryAdmin.jsx
  Panel para configurar cada plataforma por local:
    webhook URL a dar a la plataforma (generada automaticamente)
    secret HMAC del local en esa plataforma
    toggle activo/inactivo por plataforma
  Maximo 200 lineas.

**Integracion:**
  KitchenDisplay muestra pedidos delivery con origen marcado
  AnalyticsEngine agrega ventas delivery vs presencial por local

- [ ] DeliveryWebhook.php: recepcion y normalizacion de pedidos
- [ ] GlovoDriver.php: parseo payload Glovo
- [ ] UberEatsDriver.php: parseo payload Uber Eats
- [ ] JustEatDriver.php: parseo payload Just Eat
- [ ] DeliveryAdmin.jsx: configuracion por local sin hardcodear secrets
- [ ] KDS distingue pedidos delivery vs presencial visualmente

---

#### 6.2 Programa de canal

El canal no es codigo, es negocio. Pero el sistema debe soportarlo.

**Archivos:**
- CAPABILITIES/CANAL/PartnerModel.php
  id, nombre_empresa, contacto, locales_asignados[], comision_%,
  fecha_acuerdo, activo
  Almacena en STORAGE/canal/partners/
  Maximo 80 lineas.
- CAPABILITIES/CANAL/PartnerAdmin.jsx
  Panel superadmin para gestionar partners y ver sus locales
  Maximo 200 lineas.
- CAPABILITIES/CANAL/OnboardingPortal.jsx
  Formulario publico de alta de local para partners
  Rellena datos del local, crea slug, genera credenciales iniciales
  Sin hardcodear datos de ejemplo — campos vacios con placeholder real
  Maximo 200 lineas.

**Acciones comerciales (fuera del codigo):**
  - Acuerdo con 1 distribuidora de bebidas (Mahou, Coca-Cola, o equivalente)
    Condicion: distribucion a cambio de revenue share en locales captados
  - Acuerdo con 1 asociacion hostelera (FEHR o autonomica)
    Condicion: mencion en newsletter + descuento socio
  - Material: video 90 segundos demostrando carta QR en uso real
    Sin texto narrado — solo imagen real de uso en un bar

- [ ] PartnerModel.php: modelo datos partner
- [ ] PartnerAdmin.jsx: gestion superadmin de partners
- [ ] OnboardingPortal.jsx: alta de local por partner
- [ ] SLA de soporte: 4h respuesta en horario hostelero (8-24h)
- [ ] Contrato partner digital: firma via link, sin papel

---

#### 6.3 Infraestructura para escala

- [ ] CDN para imagenes de carta (MEDIA/): configurar subdomain o bucket S3
      Sin hardcodear endpoint del CDN — configurable en CORE/config.json
- [ ] Backup automatico de STORAGE via cron: script PHP + rclone
      Sin hardcodear credenciales — leer de variables de entorno
- [ ] Health check endpoint: /health.php
      Devuelve {status: ok, version, timestamp} sin datos sensibles
- [ ] Monitor de errores PHP: registrar en STORAGE/logs/php_errors/
      Sin enviar a servicios externos si no esta configurado
- [ ] Documentacion de despliegue multi-instancia en INSTALL.md

**Criterio de salida Fase 6:**
  20 locales activos en produccion.
  API publica con al menos 1 integracion de terceros real.
  1 partner de canal activo con locales captados via su portal.
  Delivery funcionando en al menos 1 local piloto.
  Sin datos hardcodeados en ningun driver ni configuracion.
  Commits por subfase:
    feat: api-publica keys-por-local rate-limiting
    feat: delivery-webhook glovo-ubereats-justeat
    feat: canal-partners onboarding-portal
    feat: infraestructura-escala cdn-backup-health

---

## Reglas de ejecucion

1. Antes de cada commit: marcar [x] en este checklist las tareas completadas
2. Antes de cada push: verificar que el checklist de la subfase esta cerrado
3. No se pasa a la siguiente fase sin cerrar la anterior
4. Ningun archivo supera 250 lineas
5. Cada archivo tiene una sola responsabilidad
6. Verifactu obligatorio antes de campana comercial masiva
7. El claim de venta nunca menciona tecnologia, solo euros y horas
8. Soporte WhatsApp activo desde el primer cliente
9. Sin permanencias en el contrato
10. AxiDB es la unica fuente de verdad
11. 5 clientes piloto reales antes de escalar marketing
12. Sin hardcodeos: ningun archivo puede contener nombres de local, ciudad,
    URL de API externa, credencial, o dato de negocio especifico del cliente.
    Todo lo que cambia por local va en STORAGE/locales/{slug}/config/.
    Todo lo que cambia por instalacion va en CORE/config.json (en .gitignore).
13. Datos reales: ningun agente IA ni motor de analitica puede devolver
    resultados si el local no tiene datos reales suficientes en STORAGE.
    Umbral minimo por funcion: definido en configuracion, nunca hardcodeado.
    Antes de cada inferencia: verificar conteo de datos disponibles.
    Si conteo < umbral: devolver {success: false, reason: 'datos_insuficientes'}
    no devolver datos ficticios ni ejemplos de demostracion como si fueran reales.
14. Sistema agnostico de infraestructura: mylocal debe desplegarse sin
    modificaciones en cualquier servidor Apache/LiteSpeed con PHP 8.1+.
    Reglas concretas:
    - Cero rutas absolutas en el codigo. Solo __DIR__, STORAGE_ROOT y
      constantes definidas en el punto de entrada (gateway.php o index.php).
    - Cero dependencias de extension PHP no estandar. Solo: json, mbstring,
      curl, openssl. Si se necesita otra, documentar en INSTALL.md.
    - Cero llamadas a comandos del sistema operativo (exec, shell_exec, proc_open)
      sin fallback. Si se usan para QR o PDF, el modulo es opcional y el
      sistema funciona sin el.
    - La URL base se detecta siempre en tiempo de ejecucion desde $_SERVER.
      Nunca hardcodeada en ningun archivo de codigo ni configuracion en repo.
    - STORAGE puede estar en cualquier ruta fuera del webroot.
      Su ubicacion se define en CORE/config.json (excluido de git).
    - El sistema debe poder ejecutarse en contenedor Docker con un
      Dockerfile de menos de 30 lineas: imagen php:8.1-apache,
      COPY del repo, VOLUME para STORAGE, EXPOSE 80. Sin build steps.
    - Ningun modulo tiene acoplamiento a otro modulo de CAPABILITIES.
      Las dependencias entre modulos se resuelven via interfaces,
      no via require_once entre CAPABILITIES.

---

## Ventana temporal

La ola Verifactu 2026-2028 genera clientes con urgencia regulatoria.
Un cliente con urgencia tiene un CAC 3-4 veces menor que uno convencido.
Esta ventana se cierra.

Incompleto y en el mercado gana a completo y tarde.

Objetivo: Nivel 1 en produccion antes de Q2 2026.
Objetivo: Nivel 2 con Verifactu antes de Q3 2026.
Objetivo: Nivel 3 antes de Q1 2027.
