# CLAUDE.md — MyLocal

Guia de trabajo para Claude Code en este proyecto.

---

## Que es este proyecto

Plataforma SaaS de hosteleria espanola. TPV + carta QR + agentes IA.
Motor de datos: AxiDB (file-based, sin SQL externo).
Plan completo: claude/planes/mylocal.md

---

## Reglas de construccion

- Cada archivo tiene una sola responsabilidad
- Maximo 250 lineas de codigo por archivo
- Sin comentarios que expliquen el que; solo el por que cuando no es obvio
- Sin emojis en codigo, interfaces ni documentacion
- Construccion atomica: una cosa a la vez, completa y funcional
- No se pasa a la siguiente fase sin cerrar la anterior

---

## Estructura de modulos

```
axidb/             motor de datos — no modificar sin entender el protocolo
CORE/              framework base — auth, config, gestion de archivos
CAPABILITIES/QR/   generacion de QR
CAPABILITIES/TPV/  punto de venta
CAPABILITIES/AGENTE_RESTAURANTE/  agente IA
CAPABILITIES/PRODUCTS/  carta y productos
CAPABILITIES/GEMINI/  conector IA
STORAGE/           datos en tiempo real — ignorados por git
MEDIA/             imagenes de productos
dashboard/         panel de hostelero
```

---

## Protocolo de datos

Toda lectura y escritura de datos pasa por AxiDB.
No se accede directamente a STORAGE sin pasar por la capa AxiDB.
No se usa SQL externo.

---

## Flujo de trabajo

1. Implementar tarea
2. Verificar que el archivo no supera 250 lineas
3. Actualizar checklist en claude/planes/mylocal.md
4. Commit con mensaje descriptivo de la fase
5. Push a github.com/guiacarlos/mylocal

---

## Lo que NO se hace

- No se crean modulos fuera del plan actual
- No se instala hardware propietario en el codigo
- No se sube STORAGE, vault ni config con credenciales
- No se añaden features de fases futuras antes de cerrar la fase actual
- No se usan frameworks JS pesados sin justificacion clara

---

## Credenciales y secretos

Los archivos en STORAGE/.vault/, vault/ y CORE/config.json contienen credenciales.
Estan en .gitignore. No modificar esa politica.
