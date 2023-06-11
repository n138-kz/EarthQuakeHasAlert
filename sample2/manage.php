<?php session_start();
require_once './vendor/autoload.php';
require_once './php-internal.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['google']) || !isset($_SESSION['user']['google']['client_id']) ) {
	http_response_code(404);
    exit();
}
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['google']) || !isset($_SESSION['user']['google']['client_token']) ) {
	http_response_code(404);
    exit();
}

define('CLIENT_ID', $_SESSION['user']['google']['client_id']);
define('CLIENT_TOKEN', $_SESSION['user']['google']['client_token']);

try {
    $client = new Google_Client(['client_id' => CLIENT_ID]); 
    $payload = $client->verifyIdToken(CLIENT_TOKEN);
    if (!$payload) { throw $th; }
} catch (\Throwable $th) {
    http_response_code(401);
    die('Unauthorized');
}

?><!DOCTYPE html><html lang="ja">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>xmlfeed_eqvol.php</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=6LfCHdcUAAAAAOwkHsW_7W7MfoOrvoIw9CXdLRBA"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="./lib/grecaptcha.js" id="grecaptchajs"></script>
    <script src="./lib/isset.js"></script>
    <script>
        window.addEventListener('load', function() {
            console.log('page is fully loaded');
            getFeedXML_eqvol();
        });
        window.addEventListener('DOMContentLoaded', function() {
            init_pageloaded_eqvol();
            console.log('DOMContent loaded');
        });
        setInterval(() => {
            getFeedXML_eqvol();
        }, 1000*10);
    </script>
    <style>
    </style>
</head>
<body>
</body>
</html>
