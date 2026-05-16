# Plan de Ventas y Marketing — MyLocal

**Proyecto:** mylocal
**Documento hermano:** claude/planes/mylocal.md (plan de desarrollo)
**Fecha inicio:** 2026-04-29
**Estado actual:** Plan de desarrollo cerrado al 100%. Listos para ejecutar lanzamiento.

---

## Proposito de este documento

Este plan no describe codigo. Describe el producto que vendemos en cada fase,
la oferta comercial que lo acompana y el material de marketing necesario para
captar clientes. Cada fase de desarrollo tiene aqui su contraparte vendible.

Regla central: no se anuncia ni se promociona nada que no este construido y
funcionando. La fase de desarrollo se cierra antes de abrir la campana de
ventas correspondiente.

---

## Filosofia de lanzamiento

### Producto terminado por fase

No lanzamos beta. Lanzamos un nivel de servicio competitivo que resuelve un
problema real y genera ingresos desde el dia uno. Cuando la fase X esta
cerrada, el hostelero recibe un producto que ya gana por si solo, no una
promesa.

### IA progresiva como capa transversal

La IA no es una fase final. Es una capa silenciosa que se va revelando:

- Fase 1: IA invisible. Ayuda al hostelero a montar la carta. No la vendemos.
- Fase 2: IA visible. Sugiere ventas cruzadas al cliente. La nombramos.
- Fase 3: IA habladora. Explica los datos en lenguaje natural.
- Fase 4-5: IA decisora. Predice demanda, optimiza menu, agente de gestion.
- Fase 6: IA Conectada (A2A). Tu local habla con los asistentes personales de tus clientes.

Error a evitar: vender "IA para hosteleros" como gancho. Vendemos resultado
(mas dinero, menos trabajo, cero multas). La IA queda por debajo.

### Roadmap visible como arma de venta

Mostrar lo que viene NO es perjudicial si se hace bien. Genera confianza,
vende futuro y permite cobrar plan anual con anticipacion. Formato publico:

- Lo que ya tienes (lista marcada)
- Lo que llega en 30-60 dias (lista concreta con fecha)
- Vision MyLocal (1 frase, sin tecnicismos)

Lo que NO se hace: lista tecnica larga, fechas vagas, promesas sin commit.

---

## Estrategia de precios

### Estructura de tres planes

**DEMO (gancho de captacion)**

- 21 dias gratis
- Producto completo desbloqueado
- Sin tarjeta de credito (critico para conversion)
- Activacion automatica desde landing

**PRO mensual — 27€ + IVA / mes**

- Cancelable cualquier mes
- Incluye todo el nivel actual del producto
- Soporte estandar
- Para el 96% de locales independientes que prefieren flujo mensual

**PRO anual — 260€ + IVA / ano**

- Equivale a 21,67€/mes (descuento 20% sobre mensual)
- Bloqueo de precio mientras dure la suscripcion
- Acceso prioritario a las funcionalidades del roadmap
- Soporte prioritario via WhatsApp
- Pago unico por adelantado

Justificacion del anual: el descuento del 20% es el argumento de venta para
el cliente, pero para nosotros significa cobrar por adelantado y asegurar
retencion 12 meses. Es la palanca de caja del negocio.

### Conversion desde demo

- Dia 1: bienvenida + onboarding guiado
- Dia 7: informe IA "Asi ha ido tu primera semana"
- Dia 14: aviso "te quedan 7 dias" + comparativa mensual vs anual
- Dia 18: informe final IA "has servido X mesas, ahorrado Y horas"
- Dia 21: bloqueo suave del panel + boton "Activar plan anual con 20% off"

Meta de conversion demo -> pago: 25% en mensual, 12% directo a anual.

### Mensajes ancla (no se venden features, se vende resultado)

- "Recupera lo que pagas en un solo dia de servicio."
- "Aumenta cada mesa sin contratar mas personal."
- "Cumple con Hacienda sin comprar nada."

---

## Diseno publico del roadmap (pagina web)

### Bloque 1: Lo que ya tienes (con cualquier plan)

- Carta digital QR multiidioma
- Pedidos desde la mesa
- Pagos con Bizum y tarjeta
- TPV completo en sala y cocina
- Cumplimiento Verifactu y TicketBAI
- Analitica de ventas
- Asistente IA basico

### Bloque 2: Lo que llega en breve (30-60 dias)

- Integracion con plataformas de delivery (Glovo, Uber Eats, Just Eat)
- Programa de fidelizacion sin tarjetas fisicas
- Predictor de demanda con IA
- Modo cadena: panel multi-local consolidado
- Nivel 4: Agent-Ready Interface (Reserva automática vía Siri/Gemini)

### Bloque 3: Vision MyLocal

> Un consultor de negocio 24/7 que analiza tus datos para que ganes mas.

---

# FASES DE LANZAMIENTO

Cada fase contiene: diseno del producto terminado, oferta comercial,
estrategia de marketing, criterios de "listo para vender" y checklist
de tareas no tecnicas (las tecnicas viven en mylocal.md).

---

## FASE 1 — Carta Digital QR (Nivel 1)

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 1)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Captura masiva de clientes de NordQR, Bakarta y BuenaCarta mediante
simplicidad extrema y precio inferior. Construir base instalada.

### Diseno del producto terminado

**Promesa al hostelero:**

> Tu carta digital lista en menos de 30 minutos, sin ayuda, sin instalar nada.

**Interfaz publica (cliente final):**

- Web-app que carga en menos de 2 segundos
- Diseno limpio, foto del plato como protagonista
- Scroll vertical, categorias fijas arriba
- Selector de idioma visible

**Panel del hostelero:**

- Login en una pantalla
- Dashboard con tres metricas: visitas, escaneos, plato mas visto
- Menu lateral: Carta / Diseno / QR / Publicar / Reseñas / Ajustes
- Boton permanente "Ver como cliente" (preview en vivo)

**IA invisible (Copiloto de Carta):**

- Generador de descripciones de platos a partir de ingredientes
- Traduccion automatica contextual (ES/EN/FR/DE)
- Sugerencia de categorias segun tipo de negocio
- No se promociona como IA. Se presenta como "ayuda automatica".

**Local Vivo (Micro-Timeline & Reputacion):**
- Seccion "Timeline": el dueño publica fotos/videos cortos del dia.
- Seccion "Reseñas": clientes dejan estrellas y comentarios (SEO ready).
- Generacion automatica de textos legales (RGPD/LSSI) al registrarse.

### Flujo de onboarding (10 pasos guiados)

Este flujo es el corazon del producto en Fase 1. La metrica clave es el
porcentaje de usuarios que llegan al paso 10 y descargan el QR. Sin esto,
no hay activacion y no hay negocio.

**Paso 1 - Tipo de negocio:** Bar / Restaurante / Cafeteria / Otro.
Personaliza plantillas.

**Paso 2 - Identidad:** Nombre del local + logo opcional. Preview en vivo.

**Paso 3 - Idiomas:** ES por defecto. Toggle para EN/FR/DE.

**Paso 4 - Categorias:** Boton "Sugerir automaticamente" (IA) o anadir
manualmente. Ejemplos: Entrantes, Principales, Bebidas, Postres.

**Paso 5 - Platos:** Nombre, precio, foto, descripcion. Boton clave
"Generar descripcion" (IA) que rellena automaticamente.

**Paso 6 - Diseno visual:** 3 plantillas (Minimal / Elegante / Moderno).
Sin mas opciones para no bloquear al usuario.

**Paso 7 - Colores:** Color principal y de botones. Autogenerados desde
el logo si se subio.

**Paso 8 - Vista previa final:** Simulacion de movil con scroll real.
Boton "Ver como cliente" abre la carta publica.

**Paso 9 - QR:** Generar QR general o por mesa. Descarga en PNG y PDF.

**Paso 10 - Momento WOW:** Pantalla "Tu carta ya esta online" con enlace,
boton copiar, QR visible y CTA "Compartir con mi equipo".

### Microcopys clave

- "Anade tu primer plato (puedes cambiarlo luego)"
- "No te preocupes, puedes editar todo mas tarde"
- "Ya estas a un paso de tener tu carta online"
- "Tus clientes podran pedir esto hoy mismo"

### Oferta comercial

> Carta digital profesional. 21 dias gratis. Despues 27€/mes o 260€/ano.
> Sin permanencia. Sin tarjeta para empezar. Actualizaciones ilimitadas.

### Estrategia de marketing

**Canal principal:** Google Ads (busqueda)

- Keywords: "carta QR barata", "alternativa a NordQR", "carta digital
  bar", "menu QR restaurante", "carta QR sin permanencia"
- Landing dedicada por keyword

**Canal secundario:** SEO organico

- Blog: "Cuanto cuesta imprimir cartas en 2026", "Como hacer una carta QR
  en 30 minutos", "Multas por carta no actualizada"

**Canal de prueba social:**

- Capturas reales de cartas de clientes (con permiso)
- Contador en landing: "+X locales digitalizados"

### Argumento de venta principal

Ahorro economico tangible. Una imprenta cobra 80-150€ por reimpresion de
cartas. Un restaurante medio reimprime 4-6 veces al ano por cambios de
precios o platos de temporada. MyLocal anual cuesta menos que dos
reimpresiones. El resto es ganancia.

### Criterios "listo para vender"

- [ ] Landing publica con CTA "Empieza gratis 21 dias" funcionando
- [ ] Registro en menos de 30 segundos (email + password + nombre local)
- [ ] Onboarding 10 pasos completo y sin errores en movil y desktop
- [ ] Generacion y descarga de QR (PNG + PDF) funcional
- [ ] Carta publica carga en menos de 2s en 4G
- [ ] Multiidioma ES/EN/FR/DE operativo
- [ ] Generador de descripciones IA responde en menos de 5s
- [ ] Email de bienvenida automatico tras registro
- [ ] Email de aviso "quedan 7 dias" en demo
- [ ] Pasarela de cobro Stripe activa para 27€/mes y 260€/ano
- [ ] Politica de privacidad y aviso legal publicados
- [ ] WhatsApp de soporte operativo en horario comercial

### Checklist de lanzamiento Fase 1

**Producto:**

- [ ] Verificar que mylocal.md Fase 1 esta cerrada al 100%
- [ ] QA completo del onboarding en 5 dispositivos distintos
- [ ] Carga de landing y panel auditadas con Lighthouse (>90)
- [ ] Tiempos de respuesta de la IA medidos y aceptables

**Marketing:**

- [ ] Logo y manual de marca cerrados
- [ ] Landing principal publicada
- [ ] 3 landings dedicadas a keywords prioritarias
- [ ] Campana Google Ads configurada con presupuesto inicial 300€/mes
- [ ] 3 articulos SEO publicados
- [ ] Pagina de precios publica con los 3 planes
- [ ] Pagina de roadmap publica (formato Bloque 1/2/3)
- [ ] Pixel de seguimiento (Meta + Google) instalado

**Ventas:**

- [ ] Guion de demo en 5 minutos preparado
- [ ] FAQ publica con las 20 objeciones mas frecuentes
- [ ] Comparativa MyLocal vs NordQR vs Bakarta
- [ ] Caso de uso ejemplo con un restaurante real
- [ ] Email comercial frio para 50 contactos del sector

**Soporte:**

- [ ] Numero de WhatsApp Business activo
- [ ] Plantillas de respuesta para 10 incidencias frecuentes
- [ ] Manual de usuario en PDF descargable
- [ ] Video tutorial de 3 minutos del onboarding

---

## FASE 2 — Pedidos y Pagos desde la mesa (Nivel 2)

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 2)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Convertir la carta en punto de venta. Superar a Honei y MONEI Pay con
mejor precio y soberania de datos. Aumentar el ARPU del cliente Fase 1.

### Diseno del producto terminado

**Promesa al hostelero:**

> Tus clientes piden y pagan desde el movil. Tu personal cobra el doble
> de propinas y atiende mejor.

**Interfaz publica (cliente final):**

- Carrito de compra integrado en la carta
- Sugerencias de venta cruzada en el carrito (IA visible)
- Boton flotante "Pedir la cuenta"
- Pago Bizum en un toque, tarjeta en dos
- Confirmacion visual + ticket digital descargable

**Panel del hostelero:**

- Vista de pedidos entrantes en tiempo real
- Estado por mesa (libre / pidiendo / esperando / pagada)
- Configuracion de Bizum (numero) y Stripe (cuenta conectada)
- Reglas de upsell ("si pide hamburguesa -> sugerir patatas")

**IA visible (Maitre Digital):**

- Sugerencia de bebida/postre segun lo pedido
- Mensaje en interfaz: "Aumenta tu ticket medio automaticamente"
- Marcado automatico de los 14 alergenos UE
- Sin tecnicismos. La IA es "asistente".

### Oferta comercial

> Pasa al plan Pedidos y Pagos. 27€/mes igual. Comision 0,9% por pago QR.
> Sin contrato adicional. Tu plan actual incluye esto si lo activas.

Para clientes nuevos: incluido por defecto en el plan unico de 27€/mes.
La comision transaccional es la palanca de monetizacion sobre el ticket.

### Estrategia de marketing

**Canal principal:** Instagram y Facebook Ads

- Video corto (15s) de cliente real pagando con Bizum en 10 segundos
- Testimonios de hosteleros con cifras concretas

**Canal secundario:** Casos de exito en blog

- "Como el bar X aumento sus propinas un 150%"
- "Por que cerrar la mesa antes ahorra 3 horas al dia"

**Canal de retencion:**

- Notificacion push al hostelero: "La mesa 5 acepto tu sugerencia de
  postre. Has ganado 4,50€ extra hoy"

### Argumento de venta principal

Aumento de ingresos medible. Datos del sector:

- Ticket medio: +21% por upselling automatico
- Propinas: +150% por sugerencia digital amable
- Rotacion de mesas: +18% por cierre rapido

El plan se paga solo en la primera semana.

### Criterios "listo para vender"

- [ ] Pago Bizum funcional con confirmacion en menos de 5s
- [ ] Pago tarjeta Stripe funcional con 3DS
- [ ] Cierre automatico de mesa al confirmar pago
- [ ] Ticket digital generado y enviable por email/WhatsApp
- [ ] IA de upsell entrenada y configurada con reglas base
- [ ] Marcado de alergenos verificado para los 14 obligatorios UE
- [ ] Panel TPV recibe pedido en menos de 3s
- [ ] Comision 0,9% calculada y descontada correctamente

### Checklist de lanzamiento Fase 2

**Producto:**

- [ ] Verificar que mylocal.md Fase 2 esta cerrada al 100%
- [ ] Test de carga: 50 pedidos simultaneos sin degradacion
- [ ] QA del flujo Bizum en 3 entidades bancarias distintas
- [ ] QA del flujo Stripe con tarjetas de 3 paises

**Marketing:**

- [ ] 3 videos de caso real de cliente (15-30s)
- [ ] Landing dedicada "Cobra desde la mesa"
- [ ] Campana Meta Ads configurada (presupuesto inicial 500€/mes)
- [ ] Email a base de Fase 1 anunciando activacion

**Ventas:**

- [ ] Comparativa MyLocal vs Honei vs MONEI Pay
- [ ] Calculadora online "Cuanto puedes ganar al mes"
- [ ] Webinar de 30 minutos para clientes Fase 1

---

## FASE 3 — Cumplimiento Fiscal Verifactu y TicketBAI

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 3)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Lock-in total mediante seguridad juridica. El cliente no se va porque
MyLocal le garantiza no recibir multas de hasta 50.000€. Argumento de
venta mas potente del producto en 2026-2027.

### Diseno del producto terminado

**Promesa al hostelero:**

> Tu sistema de facturacion cumple la ley antifraude. Sin comprar
> hardware. Sin pagar a tu gestoria una integracion aparte.

**Interfaz del panel:**

- Sello visible "Software certificado Verifactu"
- Pie de tickets con QR de verificacion AEAT
- Estado de envio en tiempo real (enviado / pendiente / error)
- Reintento automatico ante caida de servidores AEAT
- Historico de facturas con consulta por NIF

**Para Pais Vasco:**

- Modulo TicketBAI activo segun provincia (Alava/Bizkaia/Gipuzkoa)
- XML firmado con certificado del cliente

**IA Analista (primera version):**

- Informe diario en lenguaje natural: "Hoy facturaste X. Comparado con
  ayer, has subido un Y%. Tu plato estrella fue Z."
- Sin graficos complejos. Solo lectura clara.

### Oferta comercial

> Plan Pro Fiscal. 27€/mes incluido. Sin coste adicional. Te certificamos
> el cumplimiento Verifactu y TicketBAI antes de la entrada en vigor.

Para clientes existentes: actualizacion gratuita. Es retencion pura.
Para clientes nuevos: argumento de cierre principal.

### Estrategia de marketing

**Canal principal:** Email marketing y gestorias

- Lista de gestorias en Espana (10.000+ contactos)
- Webinar mensual: "Verifactu 2026 explicado para hosteleros"

**Canal secundario:** SEO miedo

- Articulos: "Multas Verifactu 2026: lo que tu bar debe saber",
  "TicketBAI Pais Vasco: como cumplir sin comprar hardware"

**Canal de cierre:**

- Calculadora "Tu riesgo de multa": estima la sancion segun ticketing
  del local y muestra MyLocal como solucion.

### Argumento de venta principal

Miedo fundado. Multas reales documentadas. Comparativa de coste:

- MyLocal anual: 260€
- Multa Verifactu minima: 1.000-3.000€
- Multa Verifactu maxima: 50.000€

Adicional: el cliente no necesita comprar caja registradora ni TPV
homologado. Su tablet o movil actual sirve.

### Criterios "listo para vender"

- [ ] Certificacion Verifactu obtenida (mylocal.md Fase 3)
- [ ] Primer envio real a servidores AEAT con respuesta exitosa
- [ ] TicketBAI operativo en las 3 diputaciones
- [ ] Tickets con QR AEAT validados por la app oficial
- [ ] Manual fiscal para gestorias publicado
- [ ] Convenio firmado con al menos 1 asociacion de gestorias

### Checklist de lanzamiento Fase 3

**Producto:**

- [ ] Verificar mylocal.md Fase 3 cerrada
- [ ] Auditoria fiscal externa pasada
- [ ] Certificado tecnico publicado en panel publico

**Marketing:**

- [ ] Pagina dedicada "Software Verifactu"
- [ ] 5 articulos SEO publicados
- [ ] Calculadora de riesgo de multa online
- [ ] Webinar grabado y
- [ ]  disponible bajo demanda

**Ventas:**

- [ ] Plantilla de carta a gestorias
- [ ] Programa de afiliacion para gestorias (10% recurrente)
- [ ] Material de cierre: "Garantia juridica MyLocal"

---

## FASE 4 — TPV completo, KDS y multi-local (Nivel 3)

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 4)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Competir con Last.app y Qamarero en el segmento de grupos de restauracion
y locales medianos/grandes. Subir el ARPU. Ganar volumen transaccional.

### Diseno del producto terminado

**Promesa al hostelero:**

> Todo tu restaurante en tu bolsillo. De la cocina a la contabilidad,
> desde un solo panel.

**TPV de sala:**

- Mapa de mesas interactivo
- Comandero PWA para movil del camarero
- Division de cuenta y cambio de mesa
- Turnos y arqueo de caja

**Cocina (KDS):**

- Pantalla con tickets en columnas (entrantes / principales / postres)
- Tiempo de preparacion por plato
- Marcado "listo para servir" -> notifica al camarero

**Multi-local:**

- Panel maestro que consolida 1 a 50 locales
- Comparativas entre locales
- Permisos por rol y por ubicacion

**IA decisora (segunda version):**

- Recomendacion de stock segun previsiones de venta
- Identificacion de platos "perro" y "estrella" (matriz BCG)
- Sugerencia de horarios optimos de personal

### Oferta comercial

**Plan Pro Plus — 79€/mes + IVA por local**

- Todo lo anterior
- TPV sala + comandero + KDS + multi-local
- IA decisora
- Soporte 24/7 via WhatsApp

**Plan Cadena**

- A partir de 5 locales
- Precio negociado segun volumen
- Onboarding asistido in situ

### Estrategia de marketing

**Canal principal:** Visita comercial / puerta fria

- Equipo comercial en zonas de alta densidad hostelera (Madrid,
  Barcelona, Valencia, Bilbao, Sevilla)
- Demo en local con tablet propia

**Canal secundario:** LinkedIn y prensa sectorial

- Articulos en revistas: Hosteltur, Restauracion News
- Casos de exito de cadenas de 3-10 locales

**Canal de upgrade:**

- Email a clientes Pro: "Has crecido. Es hora de pasar a Pro Plus."

### Argumento de venta principal

Eficiencia operativa medible. Datos sectoriales:

- Eficacia del personal: +55%
- Ahorro de horas operativas: 3 horas/dia/local
- Reduccion de errores en comandas: -90%

Adicional clave vs competencia: soberania de datos. El cliente puede
exportar todo. No queda atrapado.

### Criterios "listo para vender"

- [ ] Sincronizacion comandero -> KDS en menos de 5s
- [ ] Mapa de mesas con drag and drop fluido
- [ ] Multi-local probado con 5 locales reales en paralelo
- [ ] Arqueo de caja cuadra al centimo en 100 pruebas

### Checklist de lanzamiento Fase 4

**Producto:**

- [ ] Verificar mylocal.md Fase 4 cerrada
- [ ] Pruebas de stress en local con 200 mesas/dia
- [ ] Backup automatico verificado

**Marketing:**

- [ ] Comparativa MyLocal vs Last.app vs Qamarero
- [ ] 5 casos de exito documentados con cifras
- [ ] Pagina dedicada "Para grupos de restauracion"

**Ventas:**

- [ ] Equipo comercial formado (al menos 2 personas)
- [ ] CRM operativo con pipeline de leads
- [ ] Material impreso para visita comercial
- [ ] Programa de partner para distribuidores locales

---

## FASE 5 — Agentes IA de decision

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 5)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Diferenciacion tecnologica absoluta. La IA como consultor de negocio
24/7. Argumento publicitario de prensa y pagina web. Atrae clientes
nuevos y blinda a los existentes.

### Diseno del producto terminado

**Promesa al hostelero:**

> Tu negocio te habla y te dice que hacer. No solo registramos datos,
> te decimos que hacer con ellos.

**Asistente conversacional:**

- Chat de lenguaje natural en panel
- Preguntas tipo: "Cual es mi plato con mas margen que casi no se pide?"
- Respuesta con dato + explicacion + accion sugerida

**Predictor de demanda:**

- Aviso 7 dias antes: "El proximo martes lloveria, hay evento local
  cerca, deberias comprar 20% menos de fresco y reforzar delivery"
- Cruza datos del local + meteo + agenda local + festividades

**Ingenieria de menu automatica:**

- Matriz BCG actualizada cada semana
- Sugerencia concreta: "Sube el precio del plato X 1,50€. Margen +18%."
- Aprobacion en un clic

### Oferta comercial

**Plan IA Premium — 99€/mes + IVA por local**

- Todo lo del Pro Plus
- Asistente IA conversacional
- Predictor de demanda
- Ingenieria de menu automatica
- Informes ejecutivos semanales

### Estrategia de marketing

**Canal principal:** LinkedIn y prensa sectorial

- Articulos de fondo en revistas economicas
- Demos en ferias del sector (HIP, Hostelco)

**Canal secundario:** Eventos propios

- Webinar mensual "MyLocal IA: la decision correcta cada semana"
- Showroom virtual con demo en directo

### Argumento de venta principal

Optimizacion de margen. Caso de uso real: un restaurante con ticket
medio 25€ y 1.500 tickets/mes (37.500€/mes) puede mejorar margen 3-5%
con sugerencias IA bien aplicadas. Eso son 1.100-1.875€/mes extra.
El plan se paga 11-19 veces.

### Criterios "listo para vender"

- [ ] Asistente responde en menos de 8s a preguntas en lenguaje natural
- [ ] Predictor entrenado con minimo 30 dias de datos del local
- [ ] Primera sugerencia de cambio de menu validada como util por
  el hostelero (encuesta interna)

### Checklist de lanzamiento Fase 5

**Producto:**

- [ ] Verificar mylocal.md Fase 5 cerrada
- [ ] Tests de calidad de respuesta IA (precision >85%)
- [ ] Salvaguarda contra alucinaciones documentada

**Marketing:**

- [ ] Video demo de 90s en pagina principal
- [ ] Articulo en al menos 1 revista sectorial
- [ ] 3 casos de exito con sugerencias IA aplicadas

**Ventas:**

- [ ] Material para presentacion ejecutiva (slides)
- [ ] Speech de 10 minutos para feria/evento
- [ ] Programa de prueba premium (15 dias) para Pro Plus existentes

---

## FASE 6 — Escala, delivery y canal de partners

**Estado desarrollo:** COMPLETADA (ver mylocal.md Fase 6)
**Estado ventas:** PENDIENTE LANZAMIENTO

### Objetivo comercial

Apertura de canales de venta indirectos y conexion con plataformas
de delivery. Ganar locales que ya viven del delivery y necesitan
unificar canales.

### Diseno del producto terminado

**Integracion delivery:**

- Conector con Glovo, Uber Eats y Just Eat
- Pedidos de los 3 canales en una sola pantalla
- Sincronizacion bidireccional de menu y stock

**Programa partners:**

- Portal para gestorias y consultoras
- Comision recurrente 15% mientras dure el cliente
- Material co-branded

**Soberania reforzada:**

- Exportacion total de datos en JSON y CSV
- Migracion asistida desde Last.app, Qamarero, Glop, Camarero

### Oferta comercial

Sin nuevo plan. Las funcionalidades se integran en los planes
existentes segun nivel:

- Pro: exportacion datos
- Pro Plus: delivery hub
- Premium IA: todo + onboarding asistido

### Estrategia de marketing

**Canal principal:** Programa de afiliacion

- Gestorias, asociaciones de hostelerias, consultoras
- Web propia para partners con material descargable

**Canal secundario:** Casos de migracion

- "Asi pasamos de Last.app a MyLocal en 48 horas"
- Comparativa de costes a 3 anos

### Criterios "listo para vender"

- [ ] Conectores delivery probados con cuenta real en cada plataforma
- [ ] Portal de partners publicado con login propio
- [ ] Migracion desde Last.app probada con 1 cliente real

### Checklist de lanzamiento Fase 6

- [ ] Verificar mylocal.md Fase 6 cerrada
- [ ] Acuerdos firmados con primeros 5 partners
- [ ] Documentacion publica de migracion desde competidores

---

## Resumen de ejecucion sincronizada

| Fase | Producto vendible                   | Argumento de venta          | Canal marketing principal |
| ---- | ----------------------------------- | --------------------------- | ------------------------- |
| 1    | Carta QR + Copiloto IA              | Ahorro de imprenta          | Google Ads (busqueda)     |
| 2    | Pedidos + Pagos + Maitre IA         | Mas ticket medio y propinas | Instagram/Facebook Ads    |
| 3    | Verifactu + TicketBAI + Analista IA | Cero multas                 | Email a gestorias         |
| 4    | TPV + KDS + Multi-local             | Eficiencia operativa        | Visita comercial          |
| 5    | Agente IA decisor                   | Optimizacion margen         | LinkedIn/prensa sectorial |
| 6    | Delivery + Partners + Protocolo A2A | Soberania e Interoperabilidad| Afiliacion y Alianzas IA  |

---

## Indicadores clave (KPI) por fase

### Fase 1

- Coste de adquisicion (CAC) objetivo: < 60€
- Conversion landing -> demo: > 8%
- Conversion demo -> pago: > 25%
- Activacion (descarga QR): > 70% de los registrados

### Fase 2

- ARPU medio: 27€ + 0,5% take rate
- Tickets QR/local/mes: > 100
- Aumento ticket medio observado: > 15%

### Fase 3

- Tasa de retencion 12 meses: > 85%
- Conversion plan mensual -> anual: > 30%

### Fase 4

- ARPU medio: 79€/local
- Locales por cuenta media: > 1,4

### Fase 5

- Adopcion del asistente IA: > 60% de Pro Plus
- Sugerencias IA aplicadas/mes/local: > 4

### Fase 6

- Partners activos: > 20 al cierre primer ano
- Locales via partner: > 30% del total

---

## Lo que NO se hace en marketing

- No se promete IA generica como argumento principal
- No se compara precio sin comparar funcionalidad
- No se ofrecen descuentos puntuales que erosionen el plan anual
- No se anuncia funcionalidad antes de cerrar la fase de desarrollo
- No se entra en guerras de precios con NordQR (somos otro nivel)
- No se promete migracion sin haberla probado con cliente real
- No se vende "todo" a un cliente que solo necesita Fase 1

---

## Protocolo de cierre de fase comercial

1. Verificar que la fase tecnica esta cerrada en mylocal.md
2. Marcar [x] en todos los checklist de "listo para vender" de la fase
3. Marcar [x] en todos los checklist de "lanzamiento" de la fase
4. Activar canal de marketing principal
5. Comunicar a base de clientes existente
6. Medir KPI de la fase durante 30 dias
7. Decidir continuidad, ajuste o pausa antes de abrir siguiente fase

Orden invariable. Sin atajos. Cada fase se demuestra en la calle antes de
abrir la siguiente.
