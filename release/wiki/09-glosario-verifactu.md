---
slug: glosario-verifactu
seccion: Glosario Hostelero
titulo: Verifactu y TicketBAI explicados
orden: 410
---

# Verifactu y TicketBAI explicados

Dos normativas distintas que persiguen lo mismo: que cada ticket que
emites llegue a Hacienda y no se pueda manipular despues.

## Que es Verifactu

Sistema de la Agencia Tributaria espanola (AEAT) que obliga a los
sistemas informaticos de facturacion a:

- Generar registros de facturacion encadenados (cada ticket lleva el
  hash del anterior).
- Enviarlos en tiempo real (o casi) a los servidores de la AEAT.
- Imprimir un QR de verificacion en cada ticket.

**Quien debe cumplir**: empresas y autonomos que emitan facturas.
**Cuando**: en general, a partir de 2026.
**Multa por incumplir**: hasta 50.000 EUR por ejercicio fiscal.

## Que es TicketBAI

Equivalente al Verifactu pero para el Pais Vasco. Tiene tres versiones,
una por cada Diputacion Foral:

- TicketBAI Alava
- TicketBAI Bizkaia
- TicketBAI Gipuzkoa

Cada Diputacion exige firma digital con certificado del contribuyente
y envio de XML al instante.

## Como lo cumple MyLocal

- **Genera el ticket** con todos los campos exigidos.
- **Calcula el hash** y lo encadena con el anterior.
- **Envia automaticamente** a AEAT (Verifactu) o a la Diputacion
  correspondiente (TicketBAI).
- **Imprime el QR** de verificacion en el ticket fisico o digital.
- **Reintenta** si el servidor de Hacienda no esta disponible (sin
  perder ningun ticket).

Todo esto sin que el hostelero tenga que hacer nada distinto a lo
habitual: simplemente cobrar.

## Necesito hardware especial?

No. MyLocal funciona en cualquier tablet, movil o ordenador con
navegador. La caja registradora y la impresora son opcionales.
