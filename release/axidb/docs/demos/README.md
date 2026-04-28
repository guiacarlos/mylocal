# Demos — guiones reproducibles paso a paso

Estos no son videos pre-renderizados: son **guiones de terminal** que cualquiera puede pegar y ver el mismo resultado en su maquina. Sustituyen a los GIFs/videos del plan original v1.0.

Razon: un GIF queda obsoleto en cuanto cambia un timestamp; un guion reproducible siempre se mantiene corriente porque ejecuta contra el codigo actual del repo.

## Como usar

```bash
# Cada *.sh es ejecutable con bash. Asume que estas en la raiz del repo
# que contiene la carpeta axidb/.
bash axidb/docs/demos/01-notas-from-zero.sh
```

Cada demo limpia su estado al terminar (carpeta tmp aparte).

## Demos disponibles

1. **[01-notas-from-zero.sh](01-notas-from-zero.sh)** — App Notas desde cero (3 min):
   crea coleccion, inserta, busca, edita, borra. **Cubre Fase 4.**
2. **[02-axisql-console.sh](02-axisql-console.sh)** — Consola REPL ejecutando
   AxiSQL contra Socola/AxiDB. **Cubre Fase 2 + Fase 6.**
3. **[03-agent-real-data.sh](03-agent-real-data.sh)** — Agente que consulta
   tus datos en lenguaje natural y devuelve respuesta correcta usando
   `Op\Count`. **Cubre Fase 6 con NoopLlm offline.**

## Para grabar un GIF/video real

Si quieres un asset visual (sigue siendo nice-to-have aunque no critico):

```bash
# Con asciinema (Linux/Mac):
asciinema rec --title "AxiDB Notas" demo.cast
bash axidb/docs/demos/01-notas-from-zero.sh
# Ctrl-D al terminar
asciinema upload demo.cast   # opcional, sube a asciinema.org

# Con vhs (https://github.com/charmbracelet/vhs):
vhs axidb/docs/demos/01-notas-from-zero.tape
# genera demo.gif
```

Los `.tape` no se incluyen en el repo porque el guion `.sh` ya basta para
reproducir el resultado y los assets binarios contaminan el diff.
