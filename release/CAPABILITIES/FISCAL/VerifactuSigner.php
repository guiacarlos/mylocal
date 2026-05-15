<?php
namespace FISCAL;

class VerifactuSigner
{
    public function sign($xml, $certPath, $certPassword = '')
    {
        if (!file_exists($certPath)) {
            return ['success' => false, 'error' => 'Certificado no encontrado en: ' . $certPath];
        }

        $certContent = file_get_contents($certPath);
        $certs = [];
        $ext = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));

        if ($ext === 'pfx' || $ext === 'p12') {
            if (!openssl_pkcs12_read($certContent, $certs, $certPassword)) {
                return ['success' => false, 'error' => 'No se pudo leer el certificado PKCS#12. Verifique la contrasena.'];
            }
        } elseif ($ext === 'pem') {
            $certs['pkey'] = $certContent;
            $certs['cert'] = $certContent;
        } else {
            return ['success' => false, 'error' => 'Formato de certificado no soportado. Use PFX o PEM.'];
        }

        $privateKey = openssl_pkey_get_private($certs['pkey'], $certPassword);
        if (!$privateKey) {
            return ['success' => false, 'error' => 'No se pudo extraer la clave privada del certificado.'];
        }

        $signature = '';
        if (!openssl_sign($xml, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return ['success' => false, 'error' => 'Error al firmar el XML.'];
        }

        $signatureBase64 = base64_encode($signature);
        $certBase64 = base64_encode($certs['cert'] ?? '');

        $signedXml = str_replace(
            '</sii:SuministroLRFacturasEmitidas>',
            '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
            . '<ds:SignatureValue>' . $signatureBase64 . '</ds:SignatureValue>'
            . '<ds:KeyInfo><ds:X509Data><ds:X509Certificate>' . $certBase64 . '</ds:X509Certificate></ds:X509Data></ds:KeyInfo>'
            . '</ds:Signature>'
            . '</sii:SuministroLRFacturasEmitidas>',
            $xml
        );

        return ['success' => true, 'data' => ['signed_xml' => $signedXml]];
    }
}
