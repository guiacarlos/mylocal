<?php
namespace FISCAL;

class TicketBAISigner
{
    public function sign($xml, $certPath, $certPassword = '')
    {
        if (!file_exists($certPath)) {
            return ['success' => false, 'error' => 'Certificado no encontrado'];
        }

        $certContent = file_get_contents($certPath);
        $certs = [];
        $ext = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));

        if ($ext === 'pfx' || $ext === 'p12') {
            if (!openssl_pkcs12_read($certContent, $certs, $certPassword)) {
                return ['success' => false, 'error' => 'No se pudo leer el certificado PKCS#12'];
            }
        } elseif ($ext === 'pem') {
            $certs['pkey'] = $certContent;
            $certs['cert'] = $certContent;
        } else {
            return ['success' => false, 'error' => 'Formato de certificado no soportado'];
        }

        $privateKey = openssl_pkey_get_private($certs['pkey'], $certPassword);
        if (!$privateKey) {
            return ['success' => false, 'error' => 'No se pudo extraer la clave privada'];
        }

        $canonicalXml = $xml;
        $digestValue = base64_encode(hash('sha256', $canonicalXml, true));

        $signedInfo = '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
            . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>'
            . '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'
            . '<ds:Reference><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
            . '<ds:DigestValue>' . $digestValue . '</ds:DigestValue></ds:Reference>'
            . '</ds:SignedInfo>';

        $signature = '';
        openssl_sign($signedInfo, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureValue = base64_encode($signature);
        $certBase64 = base64_encode($certs['cert'] ?? '');

        $dsSignature = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
            . $signedInfo
            . '<ds:SignatureValue>' . $signatureValue . '</ds:SignatureValue>'
            . '<ds:KeyInfo><ds:X509Data><ds:X509Certificate>' . $certBase64 . '</ds:X509Certificate></ds:X509Data></ds:KeyInfo>'
            . '</ds:Signature>';

        $signedXml = str_replace('</T:TicketBai>', $dsSignature . '</T:TicketBai>', $xml);

        return ['success' => true, 'data' => ['signed_xml' => $signedXml]];
    }
}
