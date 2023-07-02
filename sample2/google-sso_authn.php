<?php session_start();
require_once './vendor/autoload.php';
require_once './php-internal.php';
$database['useraccesslog']=new internalDB(dirname(__FILE__).'/'.'database_useraccess.db');
if ( mb_strtolower($_SERVER['REQUEST_METHOD']) != 'post' ) {
	http_response_code(405); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		NULL,/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP405]Method NOT allowed.('.dechex(__LINE__).')');
}
if ( !isset($_POST) || !is_array($_POST) ) {
	http_response_code(400); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		NULL,/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}
if ( !isset($_POST['client_id']) || strlen(trim($_POST['client_id']))==0 ) {
	http_response_code(400); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		NULL,/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}
if ( !isset($_POST['credential']) || strlen(trim($_POST['credential']))==0 ) {
	http_response_code(400); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		NULL,/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}

define('CLIENT_ID', $_POST['client_id']);
define('CLIENT_TOKEN', $_POST['credential']);

$_SESSION = [];

$client = new Google_Client(['client_id' => CLIENT_ID]); 
$payload = $client->verifyIdToken(CLIENT_TOKEN);
if ($payload) {
    $userid = $payload['sub'];
    $_SESSION = [];
    $_SESSION['user']['google']['client_id']      = CLIENT_ID;
    $_SESSION['user']['google']['client_token']   = CLIENT_TOKEN;
    $_SESSION['user']['google']['userid']         = $payload['sub'];
    $_SESSION['user']['google']['email']          = $payload['email'];
    $_SESSION['user']['google']['name']           = $payload['name'];
    $_SESSION['user']['google']['icon']           = $payload['picture'];
    $_SESSION['user']['google']['session']['iat'] = $payload['iat'];
    $_SESSION['user']['google']['session']['exp'] = $payload['exp'];
    $_SESSION['user']['local']['privilege']['eqvol']['level'] = 1;
    $_SESSION['user']['local']['privilege']['eqvol']['name']  = 'Authorized user';
}
echo json_encode([
	'client_id'  => CLIENT_ID,
	'credential' => CLIENT_TOKEN,
	'email'      => $_SESSION['user']['google']['email'],
	'icon'       => $_SESSION['user']['google']['icon'],
	'name'       => $_SESSION['user']['google']['name'],
	'session'    => $_SESSION['user']['google']['session'],
	'eqvol'      => [ 'userlevel' => $_SESSION['user']['local']['privilege']['eqvol']['level'] ],
]);
