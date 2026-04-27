# Plan MyLocal — Producto de Hosteleria

**Proyecto:** mylocal
**Repositorio:** https://github.com/guiacarlos/mylocal
**Fecha inicio:** 2026-04-27
**Estado actual:** Fase 0 — Limpieza y creacion de repo

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
- El checklist de cada fase se actualiza al completar cada tarea
- Al terminar cada fase: commit descriptivo + push a github
- No se pasa a la siguiente fase sin cerrar la anterior

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

Tres modulos principales. Cada uno es independiente y desplegable por separado.

```
mylocal/
  axia/          motor de datos AxiDB
  socola/        TPV y experiencia de restauracion
  agentes/       agentes IA
  shared/        utilidades compartidas (max 1 responsabilidad por archivo)
  claude/
    planes/      este documento y planes de fase
```

---

## Competencia y niveles de mercado

El desarrollo sigue los niveles de competencia como guia de avance.
Cada nivel es un producto completo que ya puede venderse.

### Nivel 1 — Carta Digital y QR

Competidores directos:
- NordQR: carta QR, freemium, ~15€/mes
- Bakarta: digitalizacion rapida, plan gratuito
- BuenaCarta: multilingue 11 idiomas, bajo coste
- Horecarta: sustitucion de cartas fisicas
- Meknu: notificaciones de pedido al mesero

Lo que ofrecen: visualizacion de carta.
Lo que no ofrecen: pedidos, pagos, fiscal.

Para superar este nivel hay que entregar:
- Carta QR como web-app (sin descarga)
- Actualizacion de carta en tiempo real
- Fotos de calidad por plato
- Multi-idioma
- Panel de gestion simple
- Onboarding en menos de 30 minutos
- Sin permanencia

### Nivel 2 — Pedido y Pago desde Mesa (Order & Pay)

Competidores:
- Honei: lider en grupos de restauracion, cierre automatico de mesa en TPV
- MONEI Pay: pago QR, acepta Bizum y tarjeta, 0,45% por transaccion

Lo que ofrecen: carta + pedido + pago + cierre de mesa.
Lo que no ofrecen: TPV completo, fiscal, IA.

Para superar este nivel hay que entregar ademas:
- Pedido desde mesa via QR
- Pago QR (Bizum + tarjeta)
- Cierre automatico de mesa
- Take rate competitivo (por debajo de Honei)
- Sugerencias de upselling en el momento del pedido

### Nivel 3 — Ecosistema Integral TPV

Competidores:
- Last.app: TPV modular, 1.800 locales, 250 integraciones, ~87€/mes + take rate
- Qamarero: todo en uno, TPV tactil, comandero digital, IA menu, Verifactu

Lo que ofrecen: TPV completo + delivery + stock + reservas + analitica + fiscal.
Lo que no ofrecen: soberania de datos, sin hardware propietario, IA real de negocio.

Para superar este nivel hay que entregar ademas:
- TPV tactil completo (sala + barra + terraza)
- Comandero digital para camareros
- KDS cocina (Kitchen Display System)
- Verifactu y TicketBAI certificados
- Analitica de negocio real (ticket medio, rotacion, mermas)
- Agentes IA de decision (no solo traduccion)
- Multi-local desde un solo panel
- Compatible con hardware existente (tablet, movil, PC)

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

**Objetivo:** repo mylocal limpio, con solo lo que se va a construir.
**Commit:** `feat: estructura base mylocal` — 2026-04-27 — hash 4e93029
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
- [x] Commit y push — 753 archivos

---

### FASE 1 — Nivel 1: Carta Digital QR (MVP vendible)

**Objetivo:** producto listo para vender contra Bakarta, NordQR y BuenaCarta.
**Precio de lanzamiento:** 29€/mes, sin permanencia.
**Commit al terminar:** `feat: nivel-1 carta digital qr completa`

---

#### Analisis del codigo existente (hallazgos criticos)

Antes de construir hay que entender lo que ya existe y lo que esta roto.

**Lo que ya existe y es util:**

`CAPABILITIES/QR/QREngine.php` — motor PHP completo con:
  - generate_qr_list: genera URLs QR por zona y mesa desde restaurant_zones
  - get_table_order: obtiene pedido activo de una mesa (ID real o slug)
  - process_external_order: recibe pedido del cliente QR y lo inyecta en TPV
  - update_table_cart: actualiza comanda con soft-delete de items cancelados
  - clear_table: libera mesa al cobrar
  - handle_table_request: cliente llama al camarero o pide la cuenta
  - get_table_requests / acknowledge_request: gestion de solicitudes de mesa
  - create_revolut_payment / check_revolut_payment: integracion Revolut (ROTA)

`CAPABILITIES/PRODUCTS/admin/ProductsAdmin.jsx` — panel React con CRUD completo de
  productos, inventario e historial. Tiene campos de e-commerce (SKU, stock)
  que no son relevantes para carta de restaurante.

`CAPABILITIES/PRODUCTS/admin/components/ProductForm.jsx` — formulario de producto
  con IVA espanol correcto (21%, 10%, 4%, 0%). Util, pero mezcla campos
  de tienda (SKU, stock inicial) con campos de carta.

`CAPABILITIES/QR/admin/QRAdmin.jsx` — panel React que lista QRs por mesa
  y permite imprimir. Genera URLs pero no genera imagenes QR reales.

`js/socola-carta.js` — frontend JavaScript de la carta publica: carga productos,
  navegacion por categorias, carrito, pedidos desde mesa, config de pagos.
  Funcional y bien estructurado.

`carta.html` y `carta-tpv.html` — paginas estaticas de la carta publica y TPV.

**Dependencias rotas que hay que corregir antes de cualquier otra cosa:**

ROTO 1 — QREngine.php lineas 699-706 y 728:
  Referencias a CAPABILITIES/STORE/settings/RevolutGateway.php.
  STORE fue eliminado. Esto causa error fatal al intentar cobrar.
  Solucion: extraer pago a modulo propio PaymentEngine.php o desactivar
  provisionalmente con retorno de error controlado hasta Fase 2.

ROTO 2 — QREngine.php linea 341-347:
  generate_qr_list depende de $this->services['restaurant_organizer'].
  RESTAURANT_ORGANIZER fue eliminado.
  Solucion: leer restaurant_zones directamente desde STORAGE sin depender
  del servicio eliminado. El fallback ya existe en el propio codigo (lineas 57-61).

ROTO 3 — ProductsAdmin.jsx, QRAdmin.jsx y todos los JSX del dashboard:
  Importan de @/acide/acideService (el sistema ACIDE eliminado).
  Solucion: crear js/mylocal-service.js propio que reemplaza acideService.
  Mismo contrato de API, distinto endpoint apuntando a gateway.php.

ROTO 4 — socola-carta.js linea 9:
  var EP = '/acide/index.php' — endpoint hardcodeado al sistema ACIDE.
  Solucion: cambiar a /gateway.php o a una constante configurable.

ROTO 5 — gateway.php:
  Referencia a PROJECTS/ y a active_project.json del sistema ACIDE.
  Solucion: simplificar gateway.php para mylocal sin proyectos multiples.

ROTO 6 — ProductForm.jsx:
  Campos SKU, stock inicial e historial de inventario son de e-commerce.
  Para carta de restaurante necesitamos: alergenos, disponibilidad, categoria.
  Solucion: crear CartaProductForm.jsx especifico para hosteleria.

---

#### 1.0 Correcciones previas (bloqueantes, deben ir primero)

Estos archivos estan rotos y bloquean todo lo demas. Se corrigen antes de
construir nada nuevo.

- [ ] gateway.php — eliminar logica ACIDE/PROJECTS, simplificar para mylocal
      Entrada: REQUEST_URI. Salida: routear a CORE o CAPABILITIES segun accion.
      Sin referencias a active_project, sin tunel ACIDE.
      Archivo resultante: maximo 120 lineas.

- [ ] QREngine.php — desacoplar de restaurant_organizer y de RevolutGateway
      generate_qr_list: leer restaurant_zones desde STORAGE directamente
      (el fallback ya existe, solo hay que hacerlo el camino principal).
      create_revolut_payment y check_revolut_payment: retornar error controlado
      con mensaje "Modulo de pago no disponible en este plan" hasta Fase 2.
      No borrar los metodos, solo protegerlos con require_once condicional.

- [ ] Crear js/mylocal-service.js — reemplaza acideService para los JSX
      Mismo contrato: mylocal.call(action, data) devuelve Promise con {success, data, error}.
      Endpoint: /gateway.php en lugar de /acide/index.php.
      Maximo 60 lineas.

- [ ] socola-carta.js linea 9 — cambiar EP a /gateway.php

---

#### 1.1 AxiDB — modelos de carta para hosteleria

Los modelos actuales son genericos (e-commerce). Hay que definir los
modelos especificos de carta de restaurante en AxiDB.

Cada modelo es un archivo PHP independiente. Maximo 250 lineas por archivo.

**Modelo: Local**
  Campos: id, slug (unico, URL-safe), nombre, descripcion_corta,
  logo_url, idioma_defecto, idiomas_activos[], timezone, activo.
  Archivo: CAPABILITIES/CARTA/models/LocalModel.php

**Modelo: Categoria de carta**
  Campos: id, local_id, nombre, nombre_i18n{es,en,fr,de},
  icono_texto (maximo 2 chars), orden, disponible, created_at.
  Sin emojis en datos, solo texto plano.
  Archivo: CAPABILITIES/CARTA/models/CategoriaModel.php

**Modelo: Producto de carta**
  Campos: id, local_id, categoria_id, nombre, nombre_i18n{es,en,fr,de},
  descripcion, descripcion_i18n{es,en,fr,de}, precio, precio_por_franja[],
  imagen_url, alergenos[] (lista de IDs del catalogo oficial EU),
  iva_tipo (reducido_10/superreducido_4/general_21/exento),
  disponible, orden, created_at, updated_at.
  Archivo: CAPABILITIES/CARTA/models/ProductoCartaModel.php

**Modelo: Mesa**
  Campos: id, local_id, zona_nombre, numero, capacidad, qr_url, activa.
  Archivo: CAPABILITIES/CARTA/models/MesaModel.php

**API publica de carta (sin autenticacion)**
  GET /carta/{slug-local} — devuelve carta completa del local
  GET /carta/{slug-local}/{zona}-{numero} — carta con contexto de mesa
  Archivo: CAPABILITIES/CARTA/CartaPublicaApi.php

- [ ] Crear CAPABILITIES/CARTA/ como nuevo modulo de carta hostelera
- [ ] Crear LocalModel.php — modelo de local
- [ ] Crear CategoriaModel.php — modelo de categoria
- [ ] Crear ProductoCartaModel.php — modelo de producto de carta
- [ ] Crear MesaModel.php — modelo de mesa
- [ ] Crear CartaPublicaApi.php — endpoint publico sin autenticacion
- [ ] Crear CartaAdminApi.php — endpoint autenticado para gestion
- [ ] Verificar que cada archivo esta por debajo de 250 lineas

---

#### 1.2 Gestion de carta (panel hostelero)

Usar ProductsAdmin.jsx como base pero crear version hostelera especifica.
No modificar ProductsAdmin.jsx original (mantener compatibilidad).
Crear componentes nuevos en CAPABILITIES/CARTA/admin/.

**CartaAdmin.jsx** — componente raiz del panel de carta
  Pestanas: Categorias | Productos | Mesas | Vista previa
  Maximo 200 lineas.

**CategoriaForm.jsx** — formulario de categoria
  Campos: nombre (ES requerido, EN/FR/DE opcionales), icono_texto, orden, disponible.
  Sin MediaPicker (las categorias no tienen imagen, solo texto).
  Maximo 80 lineas.

**ProductoCartaForm.jsx** — formulario de producto de carta
  Campos: nombre (multidioma), descripcion (multidioma), precio,
  categoria (selector), imagen (MediaPicker existente), alergenos
  (checkboxes con los 14 alergenos obligatorios EU), iva_tipo,
  disponible, precio_desayuno / precio_almuerzo / precio_cena (opcionales).
  No hay SKU, no hay stock, no hay historial de inventario.
  Maximo 150 lineas.

**AlergensSelector.jsx** — selector de los 14 alergenos EU
  Gluten, crustaceos, huevos, pescado, cacahuetes, soja, lacteos,
  frutos_cascara, apio, mostaza, sesamo, sulfitos, altramuces, moluscos.
  Checkboxes con texto, sin iconos. Maximo 60 lineas.

**MesasAdmin.jsx** — gestion de mesas y zonas
  Tabla: zona, numero, capacidad, estado, QR (boton ver/descargar).
  Formulario: crear/editar zona y mesa. Maximo 150 lineas.

- [ ] Crear CAPABILITIES/CARTA/admin/CartaAdmin.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/CategoriaForm.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/ProductoCartaForm.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/AlergensSelector.jsx
- [ ] Crear CAPABILITIES/CARTA/admin/MesasAdmin.jsx
- [ ] Panel sin emojis, texto claro, una accion por pantalla
- [ ] Autenticacion via CORE/auth (ya existe y funciona)

---

#### 1.3 Carta QR publica (vista del cliente)

La base es carta.html + socola-carta.js. Hay que adaptarlos y completarlos.

**carta.html** — pagina publica de la carta
  Titulo dinamico desde API (no hardcodeado "Socola").
  Meta tags con nombre del local y descripcion real.
  Schema.org: Restaurant + Menu.
  Sin referencias a ACIDE ni a sinaxiscore en el HTML.

**socola-carta.js** — frontend de la carta
  Correccion del endpoint (ya en 1.0).
  Anadir selector de idioma: boton ES/EN/FR/DE, guarda en localStorage.
  Las traducciones vienen del API (campos _i18n del modelo).
  Filtro por categoria con scroll suave.
  Vista de producto con alergenos (iconos de texto, no emojis).
  Tiempo de carga objetivo: menos de 2 segundos en 4G.
  Maximo 250 lineas — si se supera, dividir en modulos.

**Multiidioma**
  El cliente selecciona idioma una vez, se guarda en localStorage.
  El API devuelve siempre todos los idiomas.
  El JS muestra el campo correcto segun idioma activo.
  Idiomas Nivel 1: ES (requerido), EN, FR, DE (opcionales).
  Sin libreria i18n externa — logica simple en el propio JS.

- [ ] Adaptar carta.html: titulo dinamico, sin hardcoded "Socola"
- [ ] Corregir EP en socola-carta.js (ver 1.0)
- [ ] Añadir selector de idioma en carta publica
- [ ] Implementar renderizado multiidioma desde campos _i18n
- [ ] Mostrar alergenos en ficha de producto (texto, sin emojis)
- [ ] Verificar carga en menos de 2 segundos (medir con DevTools)
- [ ] Sin registro del cliente para ver la carta

---

#### 1.4 Generacion de QR

QREngine.php ya genera las URLs. Lo que falta es generar las imagenes QR
y el PDF para imprimir.

**QrImageGenerator.php** — genera imagen QR como PNG/SVG
  Usar libreria PHP QR Code (sin dependencias externas si es posible).
  Entrada: URL. Salida: imagen base64 o archivo temporal.
  Maximo 80 lineas.

**QrPdfExport.php** — genera PDF de etiquetas para imprimir
  Una pagina por zona con todas sus mesas.
  Formato: 4x4 por pagina o A5 individual.
  Nombre de zona y numero de mesa visibles bajo el QR.
  Maximo 100 lineas.

**QRAdmin.jsx** — ya existe, anadir boton de descarga de imagen
  Boton "Descargar PNG" por QR individual.
  Boton "Imprimir todas" que llama a QrPdfExport.
  No crear nuevo componente, modificar el existente.

- [ ] Crear CAPABILITIES/QR/QrImageGenerator.php
- [ ] Crear CAPABILITIES/QR/QrPdfExport.php
- [ ] Adaptar QRAdmin.jsx: boton de descarga PNG por mesa
- [ ] QR de carta general (sin mesa) y QR por mesa
- [ ] PDF listo para imprimir con nombre de zona y numero visible

---

#### 1.5 Onboarding

El hostelero debe poder dar de alta su local y tener la carta funcionando
en menos de 30 minutos desde cero.

**Flujo de alta (10 pasos max):**
  1. Registro: nombre, email, contrasena, nombre del local
  2. Slug del local (auto-generado, editable)
  3. Logo del local (opcional, saltar disponible)
  4. Crear primera categoria
  5. Crear primer producto
  6. Vista previa de la carta en movil
  7. Configurar mesas (zona + numero)
  8. Descargar QR de primera mesa
  9. Escanear QR de prueba
  10. Activar y compartir enlace

**OnboardingWizard.jsx** — wizard de alta
  Un paso por pantalla. Progreso visible (1 de 10).
  Sin modal, ocupa toda la pantalla en movil.
  Maximo 200 lineas — dividir en pasos si se supera.

- [ ] Crear CAPABILITIES/CARTA/admin/OnboardingWizard.jsx
- [ ] Alta completa en menos de 30 minutos (medir con usuario real)
- [ ] Cada paso tiene un solo objetivo claro
- [ ] Soporte WhatsApp enlace directo desde el panel (no boton flotante)

---

#### 1.6 Infraestructura y despliegue

- [ ] Crear INSTALL.md: pasos para desplegar en hosting compartido Apache
- [ ] Crear config.example.json: plantilla de configuracion sin credenciales
- [ ] Verificar .htaccess funciona en Apache y LiteSpeed
- [ ] Verificar SSL (redireccion http → https en .htaccess)
- [ ] Despliegue reproducible: un zip + subir + configurar = funciona
- [ ] Dominio mylocal.app (o subdominio de prueba) configurado

---

**Criterio de salida del Nivel 1:**
  5 clientes piloto con carta QR funcionando en produccion.
  El hostelero puede actualizar su carta sin ayuda.
  Tiempo de carga de carta < 2 segundos en 4G.
  QR descargable e imprimible.

**Commit y push al terminar cada subfase.**
Mensajes de commit:
  fix: corregir dependencias rotas gateway y QREngine
  feat: modelos de carta hostelera en AxiDB
  feat: panel de gestion de carta hostelero
  feat: carta publica multiidioma
  feat: generacion QR con imagen y PDF
  feat: wizard onboarding alta de local

---

### FASE 2 — Nivel 2: Pedido y Pago desde Mesa

**Objetivo:** superar a Honei y MONEI Pay. Producto con take rate activo.
**Precio:** 79€/mes + take rate segun volumen.
**Commit al terminar:** `feat: nivel-2 pedido y pago desde mesa`

#### 2.1 AxiDB — esquema de pedidos
- [ ] Modelo: sesion de mesa (apertura, cierre, estado)
- [ ] Modelo: linea de pedido (producto, cantidad, modificaciones, estado)
- [ ] Modelo: pedido agrupado por mesa y por ronda
- [ ] Estados: pendiente, confirmado, en cocina, servido, cobrado

#### 2.2 Pedido desde mesa
- [ ] Cliente escanea QR de mesa y ve carta
- [ ] Seleccion de productos con cantidad y notas
- [ ] Envio de pedido sin registro del cliente
- [ ] Confirmacion visual inmediata al cliente
- [ ] El pedido llega al panel del hostelero en tiempo real
- [ ] Camarero confirma o rechaza desde el panel

#### 2.3 Pago QR
- [ ] Integracion Bizum
- [ ] Integracion tarjeta (pasarela compatible)
- [ ] El cliente paga desde su movil al terminar
- [ ] Ticket digital enviado por pantalla (sin papel)
- [ ] Cierre automatico de la sesion de mesa al pagar
- [ ] Take rate configurado por plan

#### 2.4 Panel de operaciones
- [ ] Vista de mesas en tiempo real (libre, ocupada, pendiente de cobro)
- [ ] Historial de pedidos del dia
- [ ] Resumen de caja al cierre

#### 2.5 Sugerencias de upselling
- [ ] En el momento del pedido: sugerencia de bebida, postre o complemento
- [ ] Reglas configurables por el hostelero (si pide X, sugerir Y)
- [ ] Sin IA en esta fase: reglas simples basadas en categoria

**Criterio de salida del Nivel 2:** take rate activo en al menos 20 locales.
**Commit y push al terminar.**

---

### FASE 3 — Cumplimiento fiscal (Verifactu / TicketBAI)

**Objetivo:** lock-in fiscal. El cliente no puede cambiar de proveedor sin gestoria.
**Este modulo es obligatorio antes de cualquier campana comercial masiva.**
**Commit al terminar:** `feat: cumplimiento-fiscal verifactu ticketbai`

#### 3.1 Verifactu
- [ ] Registro de facturas en formato exigido por AEAT
- [ ] Envio automatico a Hacienda al cerrar cada mesa
- [ ] Certificado de cumplimiento activo
- [ ] Sin intervencion manual del hostelero

#### 3.2 TicketBAI
- [ ] Modulo especifico para Pais Vasco y Navarra
- [ ] Activable por local segun comunidad autonoma

#### 3.3 Facturacion al cliente
- [ ] Generacion de factura simplificada por mesa
- [ ] Envio por email o QR al cliente si lo solicita

**Criterio de salida:** certificacion Verifactu activa y validada.
**Commit y push al terminar.**

---

### FASE 4 — Nivel 3: TPV completo

**Objetivo:** competir con Last.app y Qamarero. Ecosistema integral.
**Precio:** 149€/mes + take rate.
**Commit al terminar:** `feat: nivel-3 tpv completo`

#### 4.1 TPV tactil
- [ ] Interfaz de caja para barra y sala
- [ ] Compatible con tablet, movil y PC existente
- [ ] Sin hardware propietario obligatorio
- [ ] Impresion de ticket (impresora termica estandar)

#### 4.2 Comandero digital
- [ ] App para camarero en movil propio
- [ ] Envio de comanda a cocina desde la mesa
- [ ] Estados de plato visibles para el camarero

#### 4.3 KDS — Pantalla de cocina
- [ ] Pantalla de cocina con pedidos en orden de llegada
- [ ] Confirmacion de plato listo desde cocina
- [ ] Alerta al camarero cuando el plato esta listo

#### 4.4 Multi-local
- [ ] Un usuario administrador con varios locales
- [ ] Cambio de local sin cerrar sesion
- [ ] Estadisticas comparativas entre locales

#### 4.5 Analitica de negocio
- [ ] Ticket medio diario, semanal, mensual
- [ ] Productos mas vendidos y menos vendidos
- [ ] Franjas horarias de mayor ocupacion
- [ ] Rotacion de mesas

**Criterio de salida:** 50 locales usando TPV completo.
**Commit y push al terminar.**

---

### FASE 5 — Agentes IA de decision

**Objetivo:** diferenciacion real frente a toda la competencia.
**No se vende como IA. Se vende como resultado en euros y horas.**
**Commit al terminar:** `feat: agentes-ia decision-negocio`

#### 5.1 Agente de upselling inteligente
- [ ] Sugerencias basadas en historial real del local
- [ ] Aprende que combinaciones aumentan el ticket medio
- [ ] Resultado medible: incremento de ticket en %

#### 5.2 Agente de ingenieria de menu
- [ ] Identifica que platos generan mas margen
- [ ] Sugiere reordenacion de carta para maximizar venta
- [ ] Detecta platos que se piden poco y cuestan mucho

#### 5.3 Agente de alertas operativas
- [ ] Mesa sin atender mas de X minutos
- [ ] Pedido en cocina sin confirmar mas de X minutos
- [ ] Patron de baja demanda en franja horaria

#### 5.4 Interfaz conversacional para el hostelero
- [ ] Consultas en lenguaje natural desde el panel
- [ ] Ejemplos: "cuanto vendimos el sabado", "que plato vendo menos"
- [ ] Respuesta en texto plano, sin graficas innecesarias

**Criterio de salida:** 3 metricas de negocio mejorables demostradas con datos reales.
**Commit y push al terminar.**

---

### FASE 6 — Escala y canal

**Objetivo:** escalar sin perder calidad de producto ni soporte.
**Commit al terminar:** `feat: escala canal-distribucion`

- [ ] API publica documentada para integraciones de terceros
- [ ] Integracion con Glovo, Uber Eats y Just Eat (agregador de pedidos)
- [ ] Programa de canal: acuerdo con 1 distribuidora de bebidas o asociacion hostelera
- [ ] Portal de onboarding para partners
- [ ] SLA de soporte definido y documentado

---

## Reglas de ejecucion

1. No se pasa a la siguiente fase sin cerrar la anterior y hacer commit + push
2. Ningun archivo supera 250 lineas
3. Cada archivo tiene una sola responsabilidad
4. Verifactu es obligatorio antes de campana comercial masiva
5. El claim de venta nunca menciona tecnologia, solo resultados en euros y horas
6. Soporte WhatsApp activo desde el primer cliente
7. Sin permanencias en el contrato
8. AxiDB es la unica fuente de verdad
9. 5 clientes piloto reales antes de escalar marketing
10. Al terminar cada tarea: marcar el checkbox en este documento

---

## Ventana temporal

La ola Verifactu 2026-2028 genera clientes con urgencia regulatoria.
Un cliente con urgencia tiene un CAC 3-4 veces menor que un cliente convencido.
Esta ventana se cierra.

Incompleto y en el mercado gana a completo y tarde.

Objetivo: Nivel 1 en produccion antes de Q2 2026.
Objetivo: Nivel 2 con Verifactu antes de Q3 2026.
Objetivo: Nivel 3 antes de Q1 2027.
