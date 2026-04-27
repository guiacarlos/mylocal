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
**Commit al terminar:** `feat: estructura base mylocal`
**Push:** https://github.com/guiacarlos/mylocal

- [ ] Crear repositorio mylocal en GitHub
- [ ] Clonar synaxiscore como punto de partida
- [ ] Eliminar modulos que no pertenecen al producto
      - CRM general
      - RRHH
      - Proyectos y tareas genericas
      - Academy y formacion
      - E-commerce generico
      - Blog y CMS
      - Demos y modulos de prueba sin uso
- [ ] Definir estructura de directorios (axia / socola / agentes / shared)
- [ ] Verificar que AxiDB arranca de forma aislada
- [ ] Crear README.md con descripcion del producto
- [ ] Commit y push

---

### FASE 1 — Nivel 1: Carta Digital QR (MVP vendible)

**Objetivo:** producto listo para vender contra Bakarta, NordQR y BuenaCarta.
**Precio de lanzamiento:** 29€/mes, sin permanencia.
**Commit al terminar:** `feat: nivel-1 carta digital qr completa`

#### 1.1 AxiDB — esquema de carta
- [ ] Modelo: local, sala, categoria, producto
- [ ] Modelo: imagen de producto (url + alt)
- [ ] Modelo: disponibilidad y precio por franja horaria
- [ ] API interna de lectura de carta (sin autenticacion de cliente)
- [ ] Maximo 1 archivo por modelo, maximo 250 lineas

#### 1.2 Gestion de carta (panel hostelero)
- [ ] Autenticacion de hostelero (email + contrasena)
- [ ] CRUD de categorias
- [ ] CRUD de productos (nombre, descripcion, precio, foto, disponible)
- [ ] Reordenacion de categorias y productos
- [ ] Actualizacion en tiempo real (sin recargar pagina del cliente)
- [ ] Panel sin emojis, texto claro, una accion por pantalla

#### 1.3 Carta QR publica (vista del cliente)
- [ ] URL unica por local: mylocal.app/[slug-local]
- [ ] Web-app: funciona en movil sin descarga
- [ ] Diseno limpio: foto, nombre, descripcion, precio, alergenos
- [ ] Filtro por categoria
- [ ] Multiidioma: ES, EN, FR, DE (selector visible)
- [ ] Tiempo de carga: menos de 2 segundos en 4G
- [ ] Sin registro del cliente para ver la carta

#### 1.4 Generacion de QR
- [ ] QR unico por mesa y por local
- [ ] Descarga en PDF listo para imprimir
- [ ] QR de carta general y QR por mesa

#### 1.5 Onboarding
- [ ] Alta de local en menos de 10 pasos
- [ ] Importacion de carta desde PDF o CSV (opcional)
- [ ] Guia de primer uso integrada en el panel
- [ ] Soporte WhatsApp configurado desde el primer cliente

#### 1.6 Infraestructura
- [ ] Dominio mylocal.app configurado
- [ ] SSL activo
- [ ] Despliegue reproducible (sin dependencia de entorno local)

**Criterio de salida del Nivel 1:** 5 clientes piloto usando la carta QR en produccion.
**Commit y push al terminar.**

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
