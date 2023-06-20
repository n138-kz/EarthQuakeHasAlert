<?php session_start();
date_default_timezone_set('Asia/Tokyo');
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
	$cache_expire = 10;
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
	return FALSE;/* Temporary deactivate */
	if ( is_array($data) ) { $data = json_encode($data, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE); }
	$data=json_decode($data, TRUE);

	$store=file_get_contents($cache_name);
	$store=json_decode($store, TRUE);

	$dat1=[];
	if(!isset($data['entry']) || !is_array($data['entry'])){
		error_log('Object(key=entry) is not accessable.');
		return FALSE;
	}

	/* EventID をIDとして管理できるようにする */
	foreach($data['entry'] as $key => $val){
		$dat1[$val['detail']['Head']['EventID']]=$val;
	}

	/* すでに項目がある場合は上書きして保存 */
	foreach($dat1 as $key => $val){
		$store=array_merge($store, [$key=>$val]);
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
/* PHPMailer のクラスをグローバル名前空間（global namespace）にインポート */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
function sendEmail($params=['subject'=>null,'body'=>null]){
	/*
	 * Mail API を使用したメールの送受信
	 * https://cloud.google.com/appengine/docs/standard/php/mail/sending-receiving-with-mail-api?hl=ja
	 *
	 * is not set
	 *
	*/
	/*
	 *
	 * Gmail の SMTP サーバを使う例
	 * https://www.webdesignleaves.com/pr/php/php_phpmailer.php
	*/

	/* Composer のオートローダーの読み込み */
	require 'vendor/autoload.php';

	/* エラーメッセージ用日本語言語ファイルを読み込む場合 */
	require 'vendor/phpmailer/phpmailer/language/phpmailer.lang-ja.php';

	/* 言語、内部エンコーディングを指定 */
	mb_language("japanese");
	mb_internal_encoding("UTF-8");
	 
	/* インスタンスを生成（引数に true を指定して例外 Exception を有効に） */
	$mail = new PHPMailer(true);

	/* 日本語用設定 */
	$mail->CharSet = "iso-2022-jp";
	$mail->Encoding = "7bit";
	 
	/* エラーメッセージ用言語ファイルを使用する場合に指定 */
	$mail->setLanguage('ja', 'vendor/phpmailer/phpmailer/language/');
	 
	try {
	  /* サーバの設定 */
	  $mail->SMTPDebug = SMTP::DEBUG_SERVER; /* Debugの出力を有効に（テスト環境での検証用） */
	  $mail->isSMTP(); /* SMTP を使用 */
	  $mail->Host       = 'smtp.gmail.com'; /* Gmail SMTP サーバーを指定 */
	  $mail->SMTPAuth   = true; /* SMTP authentication を有効に */
	  $mail->Username   = 'gandy.lent.38@gmail.com'; /* ★★★ Gmail ユーザ名 */
	  $mail->Password   = 'bjyeietpzhsxhqol'; /* ★★★ Gmail パスワード */
	  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; /* ★★★ 暗号化（TLS)を有効に */
	  $mail->Port = 587; /* ★★★ ポートは 587 */

	  /* 受信者設定 */
	  /* 差出人アドレス, 差出人名 */
	  $mail->setFrom('noreply+EarthQuakeHasAlert@gmail.com', mb_encode_mimeheader('noreply')); 
	  /* 受信者アドレス, 受信者名（受信者名はオプション） */
	  $mail->addAddress('n138-pubnet-box@googlegroups.com');   
	  /* Cc 受信者の指定 */
	  $mail->addCC('n138-pubnet-box@googlegroups.com'); 
	 
	  /* コンテンツ設定 */
	  $mail->isHTML(true); /* HTML形式を指定 */
	  /* メール表題（タイトル） */
	  $mail->Subject = mb_encode_mimeheader('日本語メールタイトル'); 
	  /* 本文（HTML用） */
	  $mail->Body  = mb_convert_encoding('HTML メッセージ <b>BOLD</b>',"JIS","UTF-8");  
	  /* テキスト表示の本文 */
	  $mail->AltBody = mb_convert_encoding('プレインテキストメッセージ non-HTML mail clients',"JIS","UTF-8"); 
	 
	  #file_put_contents('var_dump_export.dat', var_dump_text($mail));
	  #$mail->send(); /* 送信 */
	} catch (Exception $e) {
	  /* エラー（例外：Exception）が発生した場合 */
	  error_log( 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo );
	}

}

require_once './php-internal.php';

if ( gethostbyaddr($_SERVER['REMOTE_ADDR']) == 'localhost' ) {
	http_response_code(503); 
	exit();
}

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
	error_log('['.$_SERVER['REMOTE_ADDR'].']'.'Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP405]Method NOT allowed.('.__LINE__.')');
}
if ( !isset($_GET['ts']) || ( time() - (int)$_GET['ts'] ) > 300 ) {
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
	error_log('['.$_SERVER['REMOTE_ADDR'].']'.'Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.__LINE__.')');
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
	error_log('['.$_SERVER['REMOTE_ADDR'].']'.'Error on '.__FILE__.'#'.__LINE__.'');
	die('[HTTP400]Bad request.('.__LINE__.')');
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
	$database['useraccesslog']->insert([
		time(),/* Server ts */
		date('Y/m/d H:i:s T'),
		$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],
		gethostbyaddr($_SERVER['REMOTE_ADDR']).':'.$_SERVER['REMOTE_PORT'],
		mb_strtolower($_SERVER['REQUEST_METHOD']),
		$_GET['ts'],/* Client ts */
		$google_res['success'],/* Google reCAPTCHA v3 result */
	]);
}

$grep_time=[
	(int)(new DateTime)->modify('today')->setTime(0,0,0)->format('U'),
	(int)(new DateTime)->modify('tomorrow')->setTime(0,0,0)->format('U'),
];
$feedaccessvol = calcFeedAccessVol('database_feedaccess.db', $grep_time);
if (FALSE) {
} elseif ($feedaccessvol[3] >= 10.0) {
	/* Warning: feed access limit reached!! ( = 100%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( = 100%)' ]));$discord->exec_curl();
} elseif ($feedaccessvol[3] >=  9.9) {
	/* Warning: feed access limit reached!! ( =  99%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( =  99%)' ]));$discord->exec_curl();
} elseif ($feedaccessvol[3] >=  9.5) {
	/* Warning: feed access limit reached!! ( =  95%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( =  95%)' ]));$discord->exec_curl();
} elseif ($feedaccessvol[3] >=  9.0) {
	/* Warning: feed access limit reached!! ( =  90%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( =  90%)' ]));$discord->exec_curl();
} elseif ($feedaccessvol[3] >=  7.5) {
	/* Warning: feed access limit reached!! ( =  75%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( =  75%)' ]));$discord->exec_curl();
} elseif ($feedaccessvol[3] >=  5.0) {
	/* Warning: feed access limit reached!! ( =  50%) */
	$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: feed access limit reached!! ( =  50%)' ]));$discord->exec_curl();
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

		/* jma(気象庁)発行の詳細ページに紐付けるためにjmaのEventIDを取得 */
		$data['entry'][$key]['detail']['Head']['jma']['EventID'] = date( 'YmdHis', strtotime( $data['entry'][$key]['detail']['Control']['DateTime'] ) );
		$data['entry'][$key]['detail']['Head']['jma']['link']['@attributes'] = [
			'type'=>'text/html',
			'href'=>'https://www.data.jma.go.jp/multi/quake/quake_detail.html?lang=jp&eventID=' . $data['entry'][$key]['detail']['Head']['jma']['EventID'],
		];

		$val=$data['entry'][$key]['detail'];

		/*
		 * detail                      --> detail
		 * +                           --> +
		 * +--Control                  --> +--Control
		 * +--Head                     --> +--Head
		 * +--Body                     --> +--Body
		 * |  +                        --> |  +
		 * |  +--Earthquake            --> |  +--Earthquake
		 * |  +--Comments              --> |  +--Comments
		 * |  +--Intensity {}          --> |  +--Intensity {}
		 * |  |  +                     --> |  |  +
		 * |  |  +--Observation {}     --> |  |  +--Observation {}
		 * |  |  |  +                  --> |  |  |  +
		 * |  |  |  +--CodeDefine {}   --> |  |  |  +--CodeDefine {}
		 * |  |  |  |  +               --> |  |  |  |  +
		 * |  |  |  |  +--Type []      --> |  |  |  |  +--Type []
		 * |  |  |                     --> |  |  |
		 * |  |  +--MaxInt n           --> |  |  +--MaxInt n
		 * |  |  |                     --> |  |  |
		 * |  |  +--Pref []{}          --> |  |  +--Pref []
		 * |  |  |  +                  --> |  |  |  +
		 * |  |  |  +                  --> |  |  |  +--0
		 * |  |  |  +                  --> |  |  |  |  +
		 * |  |  |  +--Name ""         --> |  |  |  |  +--Name ""
		 * |  |  |  +--Code n          --> |  |  |  |  +--Code n
		 * |  |  |  +--MaxInt n        --> |  |  |  |  +--MaxInt n
		 * |  |  |  +--Area []{}       --> |  |  |  |  +--Area []{}
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +--Name ""      --> |  |  |  |  |  +--Name ""
		 * |  |  |  |  +--Code n       --> |  |  |  |  |  +--Code n
		 * |  |  |  |  +--MaxInt n     --> |  |  |  |  |  +--MaxInt n
		 * |  |  |  |  +--City []{}    --> |  |  |  |  |  +--City []{}
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		 * |  |  |  |  +               --> |  |  |  |  |  +
		*/

		/* 各地の震度の項目が不定形だったので定形に変更 */
		error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( [ 'isset->('.__LINE__.')',
			isset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'] )
		] ) );
		if( isset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'] ) ) {
			error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( __LINE__ ) );
			error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref'] ,JSON_INVALID_UTF8_IGNORE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) );
			$tmp=$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'];
			$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']=[];
			$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][0]=$tmp;
			error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref'] ,JSON_INVALID_UTF8_IGNORE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) );
		}

/*
		foreach($val['Body']['Intensity']['Observation']['Pref']['Area'] as $key2 => $val2){
			error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( [ 'isset->('.__LINE__.')', isset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][$key2]['City']['Name'] ) ] ) );
			if( isset( $val['Body']['Intensity']['Observation']['Pref']['Area'][$key2]['City']['Name'] ) ) {
				error_log( '['.$_SERVER['REMOTE_ADDR'].']'.json_encode( __LINE__ ) );
				$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][$key2]['City'][]=
				$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][$key2]['City'];
			}

		}
*/

		#file_put_contents('var_dump_export.dat', var_dump_text([$data]), LOCK_EX);
	}

	$database['feedaccesslog']=new internalDB(dirname(__FILE__).'/'.'database_feedaccess.db');
	$database['feedaccesslog']->insert([
		time(),
		date('Y/m/d H:i:s T'),
		$data_recv_length,
	]);

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

foreach( $data['entry'] as $key => $val ){
	/*
	 * 
	 * 関東地方で震度n以上の地震が発生したとき
	 * n = 4
	 * 関東地方 = {東京都, 神奈川県, 千葉県, 埼玉県, 茨城県, 栃木県, 群馬県}
	*/
	if ( FALSE ) {
	} elseif (
		$val['detail']['Body']['Intensity']['Observation']['MaxInt'] >= 4
		&& (
			FALSE
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '東京都'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '千葉県'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '埼玉県'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '茨城県'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '栃木県'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 3) == '群馬県'
			|| mb_substr( $val['detail']['Body']['Earthquake']['Hypocenter']['Area']['Name'], 0, 4) == '神奈川県'
		)
	) {
		file_put_contents('var_dump_export.dat', json_encode([
			$val['detail']['Body']['Earthquake']['Hypocenter']['Area'],
			$val['detail']['Body']['Intensity']['Observation']['MaxInt'],
			$val['detail']['Body'],
		]));
		$discord->setValue('content', json_encode([ time(), date('Y/m/d H:i:s T'), 'Warning: Earthquake has occured!' ]));$discord->exec_curl();
		sendEmail();
	}
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');
echo json_encode( $data, JSON_INVALID_UTF8_IGNORE|JSON_NUMERIC_CHECK );
