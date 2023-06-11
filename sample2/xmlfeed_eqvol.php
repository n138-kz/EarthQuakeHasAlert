<?php session_start();
require_once './vendor/autoload.php';
function xml2json($data){
	$data=simplexml_load_string($data);
	$data=json_encode($data, JSON_INVALID_UTF8_IGNORE );
	return $data;
}
function loadCache($cache_name = ''){
	if ( !is_readable($cache_name) ) {
		error_log('Feed cache load failed: ' . $cache_name);
	}
	$cache_mtime = filemtime($cache_name);
	$cache_result = FALSE;
	$cache_expire = 300;
	if ( $cache_mtime!==FALSE && (time()-$cache_mtime)<$cache_expire ) {
		$cache_result = json_decode(file_get_contents($cache_name), TRUE);
	}
	return $cache_result;
}
function saveCache($cache_name = '', $data=[]){
	if ( is_array($data) ) { $data = json_encode($data, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE); }
	$cache_result = file_put_contents($cache_name, $data, LOCK_EX);
	if ( $cache_result === FALSE ) {
		error_log('Feed cache save failed: ' . $cache_name);
	}

	saveStore(
		dirname(__FILE__) . '/' . 'xmlfeed_eqvol_log.json',
		json_encode( $data, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE )
	);
}
function saveStore($cache_name = '', $data=[]){
	if ( is_array($data) ) { $data = json_encode($data, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE); }
	$data=json_decode($data, TRUE);

	$store=file_get_contents($cache_name);
	$store=json_decode($store, TRUE);

	$dat1=[];
	if(!isset($data['entry']) || !is_array($data['entry'])){
		error_log('Object(key=entry) is not accesable.');
		return FALSE;
	}
	/* EventID をIDとして管理できるようにする */
	foreach($data['entry'] as $key => $val){
		$dat1[$val['detail']['Head']['EventID']]=$val;
	}

	/* すでに項目がある場合は上書きして保存 */
	foreach($data['entry'] as $key => $val){
		$store[$key]=$val;
	}

	/* jsonにして保存 */
	$store=json_encode($store, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE);
	$cache_result = file_put_contents($cache_name, $store, LOCK_EX);
	if ( $cache_result === FALSE ) {
		error_log('Store cache save failed: ' . $cache_name);
	}

	return $cache_result !== FALSE;
}
function loadSystemSecret($secret_keyfile = 'secret.txt'){
	if (!is_readable($secret_keyfile)) {
		return FALSE;
	}
	$secret_keyfile = file_get_contents($secret_keyfile);
	$secret_keyfile = json_decode($secret_keyfile, TRUE);
	return $secret_keyfile;
}
function calcFeedAccessVol($database='database_feedaccess.db'){
	$database=new internalDB(dirname(__FILE__).'/'.$database);
	$data=$database->select();
	
	$sum=0;
	$grep_time=[
		(int)(new DateTime)->modify('first day of')->setTime(0,0,0)->format('U'),
		(int)(new DateTime)->modify('first day of next month')->setTime(0,0,0)->format('U'),
	];
	foreach( $data as $key => $val ){
		if ( $val[0] < $grep_time[0] ) { continue; }
		if ( $val[0] > $grep_time[1] ) { continue; }
		$sum+=$val[2];
	}
	$sum=[
		$sum * ( 10 **  0 ), /* byte */
		$sum * ( 10 ** -3 ), /* Kilo-byte */
		$sum * ( 10 ** -6 ), /* Mega-byte */
		$sum * ( 10 ** -9 ), /* Giga-byte */
	];	

	return $sum;
}
require_once './php-internal.php';
$database['useraccesslog']=new internalDB(dirname(__FILE__).'/'.'database_useraccess.db');
if ( mb_strtolower($_SERVER['REQUEST_METHOD']) != 'get' ) {
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
if ( !isset($_GET['ts']) || ( time() - $_GET['ts'] ) > 300 ) {
	http_response_code(400); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		$_GET['ts'],/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}
if ( !isset($_GET['id']) || strlen(trim($_GET['id']))==0 ) {
	http_response_code(400); 
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		$_GET['ts'],/* Client ts */
		-1,/* Google reCAPTCHA v3 result *//* -1:not set */
	]);
	error_log('Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}
if ( gethostbyaddr($_SERVER['REMOTE_ADDR']) !== 'localhost' ) {
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		$_GET['ts'],/* Client ts */
		NULL,/* Google reCAPTCHA v3 result */
	]);
}
$secret = loadSystemSecret();
require_once('./lib/Discode_push_class.php');
$discord = new discord();
$discord->endpoint = $secret['external']['discord'][0]['endpoint'];
require_once './lib/Google_reCAPTCHA_v3.php';
$google = new google();
$google->setKey_private('6LfCHdcUAAAAAE6CABzkcDthyMEt8CTKM4yzkvKZ');
$google->setKey_public($_GET['id']);
$google->setip_remotehost($_SERVER['REMOTE_ADDR']);
$google_res = $google->exec_curl();
$google_res['mesg'] = $google->get_resultMesg($google_res);
if ($google_res['success'] != TRUE || $google_res['score'] < 0.3) {
}

$feedaccessvol = calcFeedAccessVol('database_feedaccess.db');
if (FALSE) {
} elseif ($feedaccessvol[3] >= 10.0) {
	/* Warning: feed access limit reached!! ( = 100%) */
} elseif ($feedaccessvol[3] >=  9.9) {
	/* Warning: feed access limit reached!! ( =  99%) */
} elseif ($feedaccessvol[3] >=  9.5) {
	/* Warning: feed access limit reached!! ( =  95%) */
} elseif ($feedaccessvol[3] >=  9.0) {
	/* Warning: feed access limit reached!! ( =  90%) */
} elseif ($feedaccessvol[3] >=  7.5) {
	/* Warning: feed access limit reached!! ( =  75%) */
} elseif ($feedaccessvol[3] >=  5.0) {
	/* Warning: feed access limit reached!! ( =  50%) */
}

$data_recv='';
$data_recv_length=0;
$data=loadCache(dirname(__FILE__) . '/' . 'xmlfeed_eqvol.json');
if (!$data) {
	$data='https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml';
	$data_recv=file_get_contents($data);
	$data_recv_length+=strlen($data_recv);
	$data=xml2json($data_recv);

	/* 必要なものだけ抽出 */
	$search='震源・震度に関する情報';
	$data=json_decode($data, TRUE);
	foreach($data['entry'] as $key => $val){
		if( $val['title'] != $search ) {
			unset( $data['entry'][$key] );
		}
	}

	/* 添字番号採番 */
	$data['entry']=array_values($data['entry']);

	/* 詳細情報取得 */
	foreach($data['entry'] as $key => $val){
		$data_recv=file_get_contents($data['entry'][$key]['id']);
		$data_recv_length+=strlen($data_recv);
		$data['entry'][$key]['detail'] = json_decode( xml2json( $data_recv ), TRUE);

		/* 各地の震度の項目が不定形だったので定形に変更 */
		if( isset( $val['detail']['Body']['Intensity']['Observation']['Pref']['Area'] ) ) {
			/* $data['entry'][$key] --> $val */
			$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][] = $val['detail']['Body']['Intensity']['Observation']['Pref']['Area'];
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['City'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['Code'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['Name'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['MaxInt'] );
		}
	}

	$discord->setValue('content', json_encode([
		time(),
		date('Y/m/d H:i:s T'),
		['received packets(byte)', $data_recv_length]
	]));$discord->exec_curl();

	$database['feedaccesslog']=new internalDB(dirname(__FILE__).'/'.'database_feedaccess.db');
	$discord->setValue('content', json_encode([
		time(),
		date('Y/m/d H:i:s T'),
		$database['feedaccesslog']->insert([
			time(),
			date('Y/m/d H:i:s T'),
			$data_recv_length,
		]),
	]));$discord->exec_curl();

	saveCache(
		dirname(__FILE__) . '/' . 'xmlfeed_eqvol.json',
		json_encode( $data, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE )
	);
}

if ( gethostbyaddr($_SERVER['REMOTE_ADDR']) !== 'localhost' ) {
	$params = [
		'files' => new CURLFile(
			dirname(__FILE__) . '/' . 'xmlfeed_eqvol.json',
			'application/json',
			'xmlfeed_eqvol_'.date('YmdHis T').'.json'
		)
	];
	$curl_req = curl_init($secret['external']['discord'][0]['endpoint']);
	curl_setopt($curl_req,CURLOPT_POST,           TRUE);
	curl_setopt($curl_req,CURLOPT_POSTFIELDS,     $params);
	curl_setopt($curl_req,CURLOPT_SSL_VERIFYPEER, FALSE); // オレオレ証明書対策
	curl_setopt($curl_req,CURLOPT_SSL_VERIFYHOST, FALSE); //
	curl_setopt($curl_req,CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl_req,CURLOPT_COOKIEJAR,      'cookie');
	curl_setopt($curl_req,CURLOPT_COOKIEFILE,     'tmp');
	curl_setopt($curl_req,CURLOPT_FOLLOWLOCATION, TRUE); // Locationヘッダを追跡

	$curl_res=curl_exec($curl_req);
	$curl_res=json_decode($curl_res, TRUE);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
echo json_encode( $data );
