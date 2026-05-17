<?php
namespace Legal;

/**
 * LegalGenerator — genera legales personalizados por local (RGPD / LSSI).
 *
 * Colección AxiDB: local_legales
 * IDs: <localId>_privacidad, <localId>_aviso, <localId>_cookies
 *
 * Los 3 documentos se generan al registrar el local y se pueden
 * regenerar desde los ajustes si cambian los datos del negocio.
 */
class LegalGenerator
{
    public const COLLECTION = 'local_legales';
    public const DOCS       = ['privacidad', 'aviso', 'cookies'];

    /**
     * Genera y persiste los 3 documentos para un local.
     * Llama a data_put() — requiere que lib.php esté cargado.
     */
    public static function generateForLocal(
        string $localId,
        string $nombre,
        string $email,
        string $slug,
        string $direccion = '',
        string $telefono  = ''
    ): void {
        $vars = [
            '{{nombre}}'    => $nombre ?: ucfirst($slug),
            '{{email}}'     => $email,
            '{{slug}}'      => $slug,
            '{{direccion}}' => $direccion ?: 'España',
            '{{telefono}}'  => $telefono ?: $email,
            '{{fecha}}'     => date('d/m/Y'),
            '{{anio}}'      => date('Y'),
        ];

        foreach (self::DOCS as $doc) {
            $content = strtr(self::template($doc), $vars);
            if (function_exists('data_put')) {
                data_put(self::COLLECTION, $localId . '_' . $doc, [
                    'id'         => $localId . '_' . $doc,
                    'local_id'   => $localId,
                    'slug_doc'   => $doc,
                    'titulo'     => self::titulo($doc),
                    'contenido'  => $content,
                    'updated_at' => date('c'),
                ], true);
            }
        }
    }

    /** Lee un documento legal del local. Devuelve null si no existe. */
    public static function get(string $localId, string $doc): ?array
    {
        if (!in_array($doc, self::DOCS, true)) return null;
        return function_exists('data_get') ? data_get(self::COLLECTION, $localId . '_' . $doc) : null;
    }

    /** Lista los 3 documentos del local (sin el contenido completo). */
    public static function list(string $localId): array
    {
        $out = [];
        foreach (self::DOCS as $doc) {
            $r = self::get($localId, $doc);
            if ($r) $out[] = ['slug_doc' => $doc, 'titulo' => $r['titulo'], 'updated_at' => $r['updated_at']];
        }
        return $out;
    }

    private static function titulo(string $doc): string
    {
        return match ($doc) {
            'privacidad' => 'Política de Privacidad',
            'aviso'      => 'Aviso Legal',
            'cookies'    => 'Política de Cookies',
            default      => ucfirst($doc),
        };
    }

    private static function template(string $doc): string
    {
        return match ($doc) {
            'privacidad' => self::tplPrivacidad(),
            'aviso'      => self::tplAviso(),
            'cookies'    => self::tplCookies(),
            default      => '',
        };
    }

    private static function tplPrivacidad(): string
    {
        return <<<MD
# Política de Privacidad

**Última actualización:** {{fecha}}

## Responsable del tratamiento

- **Titular:** {{nombre}}
- **Dirección:** {{direccion}}
- **Teléfono:** {{telefono}}
- **Email:** {{email}}

## 1. Datos que recopilamos

{{nombre}} recopila únicamente los datos necesarios para prestar el servicio de carta digital:

- **Clientes del local:** datos de navegación técnicos (IP, dispositivo) para el funcionamiento de la carta. No se almacenan datos personales sin consentimiento expreso.
- **Reseñas:** si el cliente deja una valoración, se almacena el nombre (opcional) y el comentario. No se recogen datos de contacto.
- **Reservas:** si el local acepta reservas, se almacenan nombre, teléfono y fecha de la reserva exclusivamente para su gestión.

## 2. Finalidad y base legal

| Dato | Finalidad | Base legal |
|------|-----------|------------|
| IP y datos técnicos | Seguridad y disponibilidad del servicio | Interés legítimo |
| Nombre y comentario en reseña | Mostrar valoraciones del local | Consentimiento |
| Datos de reserva | Gestión de la reserva | Ejecución de contrato |

## 3. Conservación

Los datos técnicos se conservan 12 meses. Las reseñas, mientras el local esté activo o hasta que el cliente solicite su eliminación contactando en **{{email}}** o **{{telefono}}**.

## 4. Derechos

Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición y portabilidad contactando en **{{email}}**. También puedes reclamar ante la Agencia Española de Protección de Datos (www.aepd.es).

## 5. No se realizan transferencias internacionales

Los datos se alojan en servidores dentro de la Unión Europea. No se realizan transferencias a terceros países.

## 6. Encargado de tratamiento

La carta digital está gestionada mediante la plataforma MyLocal (GESTASAI TECNOLOGY SL, CIF E23950967), que actúa como encargado del tratamiento conforme al artículo 28 del RGPD.
MD;
    }

    private static function tplAviso(): string
    {
        return <<<MD
# Aviso Legal

**Última actualización:** {{fecha}}

En cumplimiento de la Ley 34/2002, de Servicios de la Sociedad de la Información y Comercio Electrónico (LSSICE):

## 1. Datos del titular

- **Titular:** {{nombre}}
- **Dirección:** {{direccion}}
- **Teléfono:** {{telefono}}
- **Email:** {{email}}

## 2. Objeto

El presente aviso legal regula el uso de la carta digital pública del establecimiento **{{nombre}}**, accesible en el dominio {{slug}}.mylocal.es.

El acceso a esta carta es libre y gratuito. La información contenida (platos, precios, alérgenos) es responsabilidad exclusiva de **{{nombre}}**.

## 3. Propiedad intelectual

Las fotografías, descripciones y contenidos publicados en esta carta son propiedad de **{{nombre}}** o cuentan con las correspondientes licencias de uso.

## 4. Responsabilidad

**{{nombre}}** no se responsabiliza de errores u omisiones en los contenidos, ni de los posibles daños derivados del uso de la información publicada. Los precios y disponibilidad pueden variar sin previo aviso.

## 5. Plataforma tecnológica

La carta digital está desarrollada sobre la plataforma MyLocal, operada por GESTASAI TECNOLOGY SL (CIF E23950967). El responsable de los contenidos publicados (platos, precios, alérgenos, imágenes) es exclusivamente **{{nombre}}**.

## 6. Legislación aplicable

Este aviso se rige por la legislación española. Para cualquier controversia, las partes se someten a los juzgados y tribunales correspondientes a la localidad de **{{nombre}}**.
MD;
    }

    private static function tplCookies(): string
    {
        return <<<MD
# Política de Cookies

**Última actualización:** {{fecha}}

## ¿Qué son las cookies?

Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un sitio web.

## Cookies que utilizamos

La carta digital de **{{nombre}}** utiliza únicamente almacenamiento técnico estrictamente necesario:

| Tipo | Finalidad | Duración |
|------|-----------|----------|
| sessionStorage (mylocal_session) | Mantener el estado de la carta durante la visita | Sesión del navegador |
| localStorage (mylocal_cookie_consent) | Recordar tu preferencia sobre cookies | 1 año |

No utilizamos cookies de seguimiento, publicidad ni análisis de terceros. No se instalan cookies de WordPress ni de ninguna otra plataforma ajena.

## Cómo gestionar el almacenamiento

Puedes limpiar los datos almacenados en cualquier momento desde la configuración de tu navegador (Herramientas → Privacidad → Borrar datos de navegación).

## Contacto

Para cualquier consulta, contacta con **{{nombre}}** en **{{email}}** o en el teléfono **{{telefono}}**.

© {{anio}} {{nombre}}
MD;
    }
}
