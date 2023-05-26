<?php
date_default_timezone_set('Asia/Tokyo');

$url = 'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml';
$xml = simplexml_load_file( $url );
$json = json_encode( $xml );
$array = json_decode( $json, TRUE );

echo $array['title'] . PHP_EOL;
echo $array['updated'] . PHP_EOL;
echo $array['id'] . PHP_EOL;

try {
	$pdo = new PDO( 'sqlite::memory:' );
	$sql  = '';
	$sql .= '';
	$sql .= 'CREATE TABLE tmp01 ( id text, title text, updated int, author_name text, content text, link text );';
	$pdo->query($sql);

	$sql  = '';
	$sql .= '';
	$sql .= 'CREATE TABLE tmp02 ( kind_name text, area_code int, area_name text );';
	$pdo->query($sql);

} catch ( \Exception $e ) {
	echo __LINE__;
	var_dump( $e->getMessage() );
	exit();
}

foreach( $array['entry'] as $key=>$val ) {
	if( FALSE === strpos($val['title'], '震') ){
		continue;
	}

	try {
		$sql  = '';
		$sql .= '';
		$sql .= 'INSERT INTO tmp01 ( id, title, updated, author_name, content, link ) VALUES (?, ?, ?, ?, ?, ?);';
		$stm = $pdo->prepare($sql);
		$stm -> execute([
			$val['id'],
			$val['title'],
			strtotime( $val['updated'] ),
			$val['author']['name'],
			mb_convert_kana( $val['content'], 'as' ),
			$val['link']['@attributes']['href'],
		]);
	
	} catch ( \Exception $e ) {
		var_dump( $e->getMessage() );
	}
}

try {
	$sql  = '';
	$sql .= '';
	$sql .= 'SELECT DISTINCT updated, content, link FROM tmp01 WHERE content LIKE \'%速報%\' ORDER BY updated;';
	$stm = $pdo->query($sql);
	$res = $stm->fetchAll(PDO::FETCH_ASSOC);
	foreach( $res as $key => $val ) {
		echo date( 'Y/m/d H:i:s T', $val['updated'] );
		echo chr(9);
		echo $val['content'];
		echo PHP_EOL;

		$url = $val['link'];
		$xml = simplexml_load_file( $url );
		$json = json_encode( $xml );
		$array = json_decode( $json, TRUE );

		$detail['kind_name'] = $array['Head']['Headline']['Information']['Item'][0]['Kind'];
		$detail['area'] = $array['Head']['Headline']['Information']['Item'][0]['Areas']['Area'];
		var_dump($detail);

		$sql  = '';
		$sql .= '';
		$sql .= 'INSERT INTO tmp02 ( kind_name, area_code, area_name ) VALUES (?, ?, ?);';
		$stm = $pdo->prepare($sql);

		foreach( $detail['area'] as $key => $val ) {
			$stm -> execute([
				$detail['kind_name'],
				$detail['area']['Code'],
				$detail['area']['Name'],
			]);
		}
		
	}
} catch ( \Exception $e ) {
	var_dump( $e->getMessage() );
}
