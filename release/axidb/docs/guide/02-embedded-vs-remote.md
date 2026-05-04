# Embebido vs Remoto — Cuando usar cada transport

**Estado**: Fase 1.6 ✅. Este documento explica los dos modos de operacion del SDK.

---

## Resumen en una tabla

| Criterio | Embedded (`new Client()`) | HTTP (`new Client('http://...')`) |
| :-- | :-- | :-- |
| Latencia por operacion | ~0.1-1ms | ~5-50ms (LAN) |
| Red | ninguna | HTTP/HTTPS |
| Autenticacion | heredada del proceso PHP | Bearer token / cookie sesion |
| Comparte proceso con la app | si | no |
| Concurrencia | del proceso PHP | del servidor HTTP (Apache/nginx) |
| Se usa desde otra maquina | no | si |
| Hosting compartido barato | si | si (como API del propio hosting) |
| Recomendado para | apps mono-proceso, portafolio, CMS pequeno | TPV multi-dispositivo, micro-servicios, dashboards que leen varios AxiDB |

---

## Cuando elegir Embedded

- Tu app PHP es un monolito (una web, una CLI, un cron).
- Vives en el mismo directorio que AxiDB y no tienes razones para exponer el motor por red.
- Te importa la latencia: sub-milisegundo por op vs decenas por HTTP.
- No necesitas gestionar tokens/cookies — el proceso PHP tiene acceso directo al storage.

**Ejemplo**: app de notas personal, portafolio de un desarrollador, blog pequeno.

---

## Cuando elegir HTTP

- Tu AxiDB sirve datos a multiples apps o dispositivos (TPV en caja y tablets de camareros hablando con el mismo motor).
- Quieres separar la app frontend (sitio publico) del motor de datos (detras de firewall).
- Necesitas que un AxiDB central sea consumido por sidecars (agentes IA, reportes, sincronizacion).
- Quieres dar credenciales a terceros sin darles acceso al filesystem.

**Ejemplo**: Socola (TPV multi-dispositivo + web publica consumen el mismo motor).

---

## Migracion entre modos

Cambiar de embedded a HTTP (o viceversa) no requiere tocar la logica de negocio. Solo el constructor:

```php
// Antes
$db = new Client();

// Despues
$db = new Client('http://api.mi-host.com/axidb/api/axi.php', 'token-bearer-aqui');

// El resto del codigo — $db->collection(...)->where(...)->get() — no cambia.
```

Esto es intencional: el SDK define un contrato `Transport` y ambas impls lo cumplen. Si mananana necesitas un transport custom (p.ej. gRPC, WebSockets), lo implementas una vez y lo inyectas: `new Client($miTransport)`.

---

## Autenticacion en modo HTTP

Tres formas:

```php
// 1. Sin auth (API pública + modo anonimo)
new Client('http://host/axidb/api/axi.php');

// 2. Bearer token explicito
new Client('http://host/axidb/api/axi.php', 'abc123...');

// 3. URL scheme axi:// con credenciales embebidas
//    (el password se extrae como bearer token)
new Client('axi://user:abc123@host/ns');
```

Para obtener el token: ejecutar `auth.login` contra el servidor y guardar `data.token`.

---

## Ver tambien

- [01-quickstart.md](01-quickstart.md) — primer contacto.
- [../standard/wire-protocol.md](../standard/wire-protocol.md) — spec HTTP formal.
- [../standard/op-model.md](../standard/op-model.md) — el contrato Op es independiente del transport.
