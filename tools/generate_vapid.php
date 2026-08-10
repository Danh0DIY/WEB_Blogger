<?php
echo "=== WEB_Blogger VAPID Key Generator ===\n\n";
if (!function_exists('openssl_pkey_new')) {
    fwrite(STDERR, "Cần extension openssl.\n");
    exit(1);
}
$key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
if (!$key) { fwrite(STDERR, openssl_error_string() . "\n"); exit(1); }
openssl_pkey_export($key, $pemPrivate);
$details = openssl_pkey_get_details($key);
$pubPem = $details['key'];
function b64url($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
echo "Dán vào includes/config.php:\n\n";
echo "define('VAPID_SUBJECT', 'mailto:admin@your-domain.com');\n";
echo "define('VAPID_PUBLIC_KEY', '" . b64url($pubPem) . "');\n";
echo "define('VAPID_PRIVATE_KEY', '" . b64url($pemPrivate) . "');\n\n";
echo "--- PEM Public ---\n$pubPem\n";
echo "--- PEM Private ---\n$pemPrivate\n";
