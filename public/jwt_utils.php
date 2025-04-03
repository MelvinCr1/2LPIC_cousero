<?php
// === Clé secrète à garder privée ===
const JWT_SECRET = 'Vwh7bPEuXAn2fQZ';

// === Créer un JWT avec un payload simple (ex: id étudiant) ===
function generate_jwt(array $payload, $expireInSeconds = 3600) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['exp'] = time() + $expireInSeconds;

    $base64UrlHeader = base64url_encode(json_encode($header));
    $base64UrlPayload = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", JWT_SECRET, true);
    $base64UrlSignature = base64url_encode($signature);

    return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
}

// === Vérifie si un token JWT est valide ou expiré ===
function is_jwt_valid($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    list($headerB64, $payloadB64, $signatureB64) = $parts;
    $signatureCheck = base64url_encode(hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true));

    if (!hash_equals($signatureCheck, $signatureB64)) return false;

    $payload = json_decode(base64url_decode($payloadB64), true);
    if (!$payload || !isset($payload['exp'])) return false;

    return ($payload['exp'] >= time());
}

// === Décoder le contenu (payload) du JWT sous forme de tableau ===
function get_payload_from_jwt($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;

    $payloadB64 = $parts[1];
    return json_decode(base64url_decode($payloadB64), true);
}

// === Helpers pour base64urle ===
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
?>