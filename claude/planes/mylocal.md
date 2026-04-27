# Plan MyLocal — Producto de Hosteleria

**Proyecto:** mylocal
**Repositorio:** https://github.com/guiacarlos/mylocal
**Fecha inicio:** 2026-04-27
**Estado actual:** Fase 0 completada. Iniciando Fase 1.

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

**Estado: En curso**
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

- [ ] gateway.php: eliminar logica ACIDE/PROJECTS, simplificar para mylocal
      Sin referencias a active_project. Maximo 120 lineas.
- [ ] QREngine.php: desacoplar restaurant_organizer en generate_qr_list
      Leer restaurant_zones desde STORAGE directamente (fallback ya existe).
- [ ] QREngine.php: proteger create_revolut_payment y check_revolut_payment
      Retornar error controlado "Modulo de pago no disponible" hasta Fase 2.
- [ ] Crear js/mylocal-service.js: reemplaza acideService
      Contrato: mylocal.call(action, data) → Promise {success, data, error}
      Endpoint: /gateway.php. Maximo 60 lineas.
- [ ] socola-carta.js linea 9: cambiar EP a /gateway.php

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

- [ ] Crear CAPABILITIES/CARTA/models/LocalModel.php
- [ ] Crear CAPABILITIES/CARTA/models/CategoriaModel.php
- [ ] Crear CAPABILITIES/CARTA/models/ProductoCartaModel.php
- [ ] Crear CAPABILITIES/CARTA/models/MesaModel.php
- [ ] Crear CAPABILITIES/CARTA/CartaPublicaApi.php
- [ ] Crear CAPABILITIES/CARTA/CartaAdminApi.php
- [ ] Verificar que ningun archivo supera 250 lineas

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

- [ ] Crear CAPABILITIES/CARTA/admin/CartaAdmin.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/CategoriaForm.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/ProductoCartaForm.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/AlergensSelector.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/MesasAdmin.jsx

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

- [ ] Adaptar carta.html: titulo dinamico, sin hardcoded
- [ ] Corregir EP en socola-carta.js
- [ ] Selector de idioma en carta publica (ES/EN/FR/DE)
- [ ] Renderizado multiidioma desde campos _i18n
- [ ] Mostrar alergenos en ficha de producto
- [ ] Carga verificada en menos de 2 segundos en 4G

---

#### 1.4 Generacion de QR

QREngine genera URLs. Faltan imagenes QR y PDF para imprimir.

**QrImageGenerator.php**: URL → imagen PNG base64. Max 80 lineas.
**QrPdfExport.php**: genera PDF de etiquetas por zona. Max 100 lineas.
  Formato: nombre de zona y numero de mesa visibles bajo el QR.
**QRAdmin.jsx**: ya existe. Anadir boton "Descargar PNG" y "PDF todas".

- [ ] Crear CAPABILITIES/QR/QrImageGenerator.php
- [ ] Crear CAPABILITIES/QR/QrPdfExport.php
- [ ] Adaptar QRAdmin.jsx: descarga PNG por mesa y PDF por zona
- [ ] QR de carta general (sin mesa) y QR por mesa

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

- [ ] Crear CAPABILITIES/CARTA/admin/OnboardingWizard.jsx
- [ ] Alta completa medida con usuario real en menos de 30 minutos
- [ ] Enlace WhatsApp de soporte visible en paso 10

---

#### 1.6 Infraestructura y despliegue

- [ ] Crear INSTALL.md: pasos para desplegar en Apache/LiteSpeed
- [ ] Crear config.example.json: plantilla sin credenciales
- [ ] Verificar .htaccess en Apache y LiteSpeed
- [ ] Redireccion http a https en .htaccess
- [ ] Despliegue reproducible: zip + subir + configurar = funciona

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

**Estado: Pendiente — inicia tras criterio de salida Nivel 1**
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

- [ ] Crear CAPABILITIES/PAYMENT/models/SesionMesaModel.php
- [ ] Crear CAPABILITIES/PAYMENT/models/LineaPedidoModel.php
- [ ] Crear CAPABILITIES/PAYMENT/models/PagoModel.php
- [ ] Crear CAPABILITIES/PAYMENT/models/TakeRateRegistroModel.php
- [ ] Verificar que ningun archivo supera 250 lineas

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

- [ ] Crear CAPABILITIES/PAYMENT/PaymentEngine.php
- [ ] Crear CAPABILITIES/PAYMENT/drivers/BizumDriver.php
- [ ] Crear CAPABILITIES/PAYMENT/drivers/CashDriver.php
- [ ] Crear CAPABILITIES/PAYMENT/drivers/StripeDriver.php
- [ ] Crear CAPABILITIES/PAYMENT/TakeRateManager.php
- [ ] Crear CAPABILITIES/PAYMENT/TicketEngine.php

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

- [ ] Ampliar socola-carta.js: estado de items en carrito
- [ ] Pantalla de pago con metodos habilitados por local
- [ ] Flujo Bizum: enlace con importe precargado
- [ ] Flujo tarjeta: integracion con PaymentEngine
- [ ] Ticket digital en pantalla tras pago completado
- [ ] Flujo "pedir la cuenta" visible solo cuando hay items en carrito

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

- [ ] Ampliar TPVPos.jsx: indicadores de estado por mesa
- [ ] Panel de solicitudes con sonido de notificacion
- [ ] Total del dia visible en barra superior
- [ ] Flujo de cobro completo: PaymentEngine + clear_table + ticket

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

- [ ] Crear CAPABILITIES/PAYMENT/UpsellEngine.php
- [ ] Crear CAPABILITIES/TPV/admin/UpsellAdmin.jsx
- [ ] Integrar evaluate_upsell en socola-carta.js al anadir producto

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

- [ ] Crear CAPABILITIES/PAYMENT/admin/PaymentSettingsPanel.jsx
- [ ] Integracion en TPVAdmin.jsx: pestana Pagos
- [ ] Take rate del mes visible en panel

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

**Estado: Pendiente — obligatorio antes de campana comercial masiva**
**Commit al terminar:** feat: cumplimiento-fiscal verifactu ticketbai

#### 3.1 Verifactu

El TicketEngine de Fase 2 genera el ticket. Verifactu añade firma electronica
y envio automatico a la AEAT al cerrar cada sesion de mesa.

Campos obligatorios Verifactu: NIF emisor, numero de factura, fecha, importe,
IVA desglosado, hash encadenado con la factura anterior.

**VerifactuSigner.php**
  Firma el registro de facturacion con hash SHA-256 encadenado.
  Max 120 lineas.

**VerifactuSender.php**
  Envia el registro a la AEAT via API REST (endpoint oficial AEAT).
  Reintenta en caso de fallo. Registra estado en STORAGE.
  Max 100 lineas.

**VerifactuQueue.php**
  Cola de facturas pendientes de envio.
  Procesa en background tras cada cierre de mesa.
  Max 80 lineas.

- [ ] Crear CAPABILITIES/FISCAL/VerifactuSigner.php
- [ ] Crear CAPABILITIES/FISCAL/VerifactuSender.php
- [ ] Crear CAPABILITIES/FISCAL/VerifactuQueue.php
- [ ] Integracion con TicketEngine: firmar al generar ticket
- [ ] Integracion con PaymentEngine: encolar al confirmar pago
- [ ] Test con entorno de pruebas AEAT antes de produccion

#### 3.2 TicketBAI

Especifico para Pais Vasco y Navarra. Activable por local segun provincia.

**TicketBAIEngine.php**
  Mismo concepto que Verifactu pero con el esquema TicketBAI.
  Usa certificado digital del local (almacenado en STORAGE/.vault/).
  Max 150 lineas.

- [ ] Crear CAPABILITIES/FISCAL/TicketBAIEngine.php
- [ ] Activable por configuracion de local (campo provincia)

#### 3.3 Factura simplificada al cliente

- [ ] Generacion de factura simplificada desde TicketEngine
- [ ] Envio por email si el cliente lo solicita (campo email opcional en pago)
- [ ] QR en ticket para descargar la factura en PDF

**Criterio de salida:** certificacion Verifactu validada en entorno real AEAT.

---

### FASE 4 — Nivel 3: TPV completo

**Estado: Pendiente**
**Precio:** 149€/mes + take rate.
**Commit al terminar:** feat: nivel-3 tpv completo

TPVPos.jsx ya es una base muy solida. Esta fase lo convierte en producto completo.

#### 4.1 TPV tactil (barra y sala)

TPVPos.jsx ya tiene vista de catalogo y vista de mesas.
Lo que falta: interfaz optimizada para barra (sin plano, solo catalogo rapido).

**BarraView.jsx**
  Vista de venta rapida: catalogo en grid grande, carrito lateral.
  Sin plano de mesas. Para cafeteria y barra de bar.
  Max 200 lineas.

- [ ] Crear CAPABILITIES/TPV/pos/BarraView.jsx
- [ ] Modo barra activable desde configuracion del local
- [ ] Compatible con tablet, movil y PC sin hardware nuevo

#### 4.2 Comandero digital (camarero en movil)

**ComanderoApp.jsx**
  App PWA para el camarero. Muestra mesas asignadas.
  Permite tomar comanda y enviarla a cocina.
  Recibe notificacion cuando plato esta listo.
  Max 200 lineas.

- [ ] Crear CAPABILITIES/TPV/pos/ComanderoApp.jsx
- [ ] PWA instalable en movil del camarero (sin App Store)
- [ ] Notificacion push cuando plato listo en cocina

#### 4.3 KDS — Pantalla de cocina

**KitchenDisplay.jsx**
  Pantalla de cocina: lista de items en orden de llegada.
  Columnas por ronda o por mesa.
  Boton "Listo" por item: dispara notificacion al camarero.
  Max 150 lineas.

- [ ] Crear CAPABILITIES/TPV/pos/KitchenDisplay.jsx
- [ ] Flujo: QR/TPV → cocina (KDS) → camarero (notificacion) → servido

#### 4.4 Multi-local

- [ ] Un usuario admin con varios locales
- [ ] Selector de local al iniciar sesion
- [ ] Estadisticas comparativas entre locales en TPVAdmin

#### 4.5 Analitica de negocio

TPVAdmin ya tiene analitica basica. Ampliar con:

- [ ] Ticket medio diario, semanal, mensual
- [ ] Productos mas y menos vendidos con margen estimado
- [ ] Franjas horarias de mayor ocupacion
- [ ] Rotacion de mesas (tiempo medio ocupacion)
- [ ] Informe exportable en CSV

**Criterio de salida:** 50 locales usando TPV completo.

---

### FASE 5 — Agentes IA de decision

**Estado: Pendiente**
**No se vende como IA. Se vende como resultado en euros y horas.**
**Commit al terminar:** feat: agentes-ia decision-negocio

CAPABILITIES/AGENTE_RESTAURANTE/ ya existe como base.
CAPABILITIES/GEMINI/ ya existe como conector.

#### 5.1 Agente upselling inteligente

Evoluciona las reglas simples de Fase 2 con aprendizaje real del historial.

- [ ] Analiza combinaciones de productos con mayor ticket en el local
- [ ] Sugerencias basadas en patrones del propio local (no genericas)
- [ ] Metrica: incremento de ticket medio en % medible

#### 5.2 Agente ingenieria de menu

- [ ] Identifica que platos generan mas margen bruto
- [ ] Sugiere reordenacion de carta para maximizar venta
- [ ] Detecta platos con bajo pedido y alto coste de preparacion

#### 5.3 Agente alertas operativas

- [ ] Mesa sin atender mas de X minutos (configurable)
- [ ] Pedido en cocina sin confirmar mas de X minutos
- [ ] Patron de baja demanda detectado en franja horaria

#### 5.4 Interfaz conversacional

- [ ] Consultas en lenguaje natural desde el panel del hostelero
- [ ] Ejemplos: "cuanto vendimos el sabado", "que plato vendo menos"
- [ ] Respuesta en texto plano, sin graficas innecesarias

**Criterio de salida:** 3 metricas de negocio mejorables demostradas con datos reales.

---

### FASE 6 — Escala y canal

**Estado: Pendiente**
**Commit al terminar:** feat: escala canal-distribucion

- [ ] API publica documentada para integraciones de terceros
- [ ] Integracion con Glovo, Uber Eats y Just Eat (agregador de pedidos)
- [ ] Programa de canal: acuerdo con 1 distribuidora de bebidas o asociacion hostelera
- [ ] Portal de onboarding para partners
- [ ] SLA de soporte definido y documentado

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

---

## Ventana temporal

La ola Verifactu 2026-2028 genera clientes con urgencia regulatoria.
Un cliente con urgencia tiene un CAC 3-4 veces menor que uno convencido.
Esta ventana se cierra.

Incompleto y en el mercado gana a completo y tarde.

Objetivo: Nivel 1 en produccion antes de Q2 2026.
Objetivo: Nivel 2 con Verifactu antes de Q3 2026.
Objetivo: Nivel 3 antes de Q1 2027.
