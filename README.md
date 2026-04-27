# MyLocal

Plataforma SaaS de gestion para hosteleria espanola.

**"Cobra mas, trabaja menos y cumple Hacienda. Sin permanencias."**

---

## Que es MyLocal

Software de punto de venta y carta digital para bares, restaurantes y cafeterias.
Construido sobre AxiDB como motor de datos y diseado para funcionar sin hardware propietario.

---

## Modulos activos

- **AxiDB** — motor de datos file-based, sin dependencia SQL externa
- **SOCOLA TPV** — carta QR, pedidos desde mesa, pago, cierre automatico
- **Agente Restaurante** — agente IA para decision de negocio
- **CORE** — framework base, autenticacion, gestion de medios

---

## Arquitectura

```
mylocal/
  axidb/             motor de datos AxiDB
  CORE/              framework base y autenticacion
  CAPABILITIES/
    QR/              generacion y gestion de codigos QR
    TPV/             punto de venta
    AGENTE_RESTAURANTE/  agente IA de restauracion
    PRODUCTS/        gestion de productos y carta
    GEMINI/          integracion IA
  STORAGE/           datos de la aplicacion (excluidos del repo)
  MEDIA/             imagenes de productos
  dashboard/         panel de administracion
  claude/planes/     plan de negocio y roadmap
```

---

## Principios de construccion

- Cada archivo tiene una sola responsabilidad
- Ningun archivo supera 250 lineas de codigo
- Sin emojis en codigo ni interfaces
- AxiDB es la unica fuente de verdad
- Sin hardware propietario obligatorio

---

## Niveles de producto

| Nivel | Descripcion | Estado |
|-------|-------------|--------|
| Nivel 1 | Carta digital QR | En construccion |
| Nivel 2 | Pedido y pago desde mesa | Pendiente |
| Nivel 3 | TPV completo con Verifactu | Pendiente |

---

## Requisitos

- PHP 8.1+
- Servidor web con soporte .htaccess (Apache / LiteSpeed)
- Sin base de datos SQL requerida

---

## Plan completo

Ver [claude/planes/mylocal.md](claude/planes/mylocal.md)
