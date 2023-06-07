<?php session_start();
function xml2json($data){
	$data=simplexml_load_string($data);
	$data=json_encode($data, JSON_INVALID_UTF8_IGNORE );
	return $data;
}
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

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
echo json_encode( $data );
