<?php session_start();
function xml2json($data){
	$data=simplexml_load_string($data);
	$data=json_encode($data, JSON_INVALID_UTF8_IGNORE );
	return $data;
}
function loadCache(){
	$cache_name = dirname(__FILE__) . '/' . 'xmlfeed_cache.dat';
	if ( !is_readable($cache_name) ) {
		error_log('Feed cache load failed: ' . $cache_name);
	}
	$cache_mtime = filemtime($cache_name);
	$cache_result = FALSE;
	if ( $cache_mtime!==FALSE && (time()-$cache_mtime)<10 ) {
		$cache_result = json_decode(file_get_contents($cache_name), TRUE);
	}
	return $cache_result;
}
function saveCache($data){
	if ( is_array($data) ) { $data = json_encode($data); }
	$cache_name = dirname(__FILE__) . '/' . 'xmlfeed_cache.dat';
	$cache_result = file_put_contents($cache_name, $data, LOCK_EX);
	if ( $cache_result === FALSE ) {
		error_log('Feed cache save failed: ' . $cache_name);
	}
}
if( mb_strtolower($_SERVER['REQUEST_METHOD']) != 'get' ){
	http_response_code(405); die('[HTTP405]Method NOT allowed.('.dechex(__LINE__).')');
}
if ( !isset($_GET['ts']) || ( time() - $_GET['ts'] ) > 300 ) {
	http_response_code(400); die('[HTTP400]Bad request.('.dechex(__LINE__).')');
}
require_once './vendor/autoload.php';
require_once('./lib/Discode_push_class.php');
$discord = new discord();
$discord->endpoint = 'https://discord.com/api/webhooks/1115835488704663592/WySgau67OH5j_zDitnvV3ncF1gaJltQq58o5bWjVwR4cvTTfrmh8Th8zeWJuiixXzP4v';
$discord->setValue('content', json_encode([
	$_SERVER['REMOTE_ADDR'],
	$_SERVER['REMOTE_HOST'],
	$_SERVER['REMOTE_PORT'],
]));$discord->exec_curl();
$data=loadCache();
if (!$data) {
	$data='https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml';
	$data=file_get_contents($data);
	$data=xml2json($data);

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
		$data['entry'][$key]['detail'] = json_decode(
			xml2json(
				file_get_contents( $data['entry'][$key]['id'] )
			)
		, TRUE);
	}

	/* 各地の震度の項目が不定形だったので定形に変更 */
	foreach($data['entry'] as $key => $val){
		if( isset( $val['detail']['Body']['Intensity']['Observation']['Pref']['Area'] ) ) {
			/* $data['entry'][$key] --> $val */
			$data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area'][0] = $val['detail']['Body']['Intensity']['Observation']['Pref']['Area'];
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['City'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['Code'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['Name'] );
			unset( $data['entry'][$key]['detail']['Body']['Intensity']['Observation']['Pref']['Area']['MaxInt'] );
		}
	}

	saveCache( json_encode( $data ) );
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
echo json_encode( $data );
