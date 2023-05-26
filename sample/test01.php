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
	$sql .= 'CREATE TABLE tmp01 ( id text, title text, updated int, author_name text, content text );';
	$pdo->query($sql);

	$sql  = '';
	$sql .= '';
	$sql .= 'SELECT * FROM tmp01;';
	$stm = $pdo->query($sql);
	$res = $stm->fetchAll(PDO::FETCH_ASSOC);

} catch ( \Exception $e ) {
	var_dump( $e->getMessage() );
}

foreach( $array['entry'] as $key=>$val ) {
        if( FALSE === strpos($val['title'], '震') ){
		continue;
        }

	try {
		$sql  = '';
		$sql .= '';
		$sql .= 'INSERT INTO tmp01 ( id, title, updated, author_name, content ) VALUES (?, ?, ?, ?, ?);';
		$stm = $pdo->prepare($sql);
		$stm -> execute([
			$val['id'],
			$val['title'],
			strtotime( $val['updated'] ),
			$val['author']['name'],
			mb_convert_kana( $val['content'], 'as' ),
		]);
	
		$sql  = '';
		$sql .= '';
		$sql .= 'SELECT DISTINCT updated, content FROM tmp01 ORDER BY updated;';
		$stm = $pdo->query($sql);
		$res = $stm->fetchAll(PDO::FETCH_ASSOC);
		foreach( $res as $key => $val ) {
			echo $val['updated'];
			echo chr(9);
			echo date( 'Y/m/d H:i:s T', $val['updated'] );
			echo chr(9);
			echo $val['content'];
			echo PHP_EOL;
		}

	} catch ( \Exception $e ) {
		var_dump( $e->getMessage() );
	}
}
