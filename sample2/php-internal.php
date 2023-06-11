<?php 
class internalDB {
	private $database;
	function __construct($database) {
		$this->database=[
			'path'=>'',
			'mode'=>0,
		];
		if ( !is_readable($database) ) {
			error_log('Faild open database file(mode=r): ' . $database);
		} else {
			$this->database['mode'] = $this->database['mode']|1; /* 001 */
		}
		if ( !is_writable($database) ) {
			error_log('Faild open database file(mode=w): ' . $database);
		} else {
			$this->database['mode'] = $this->database['mode']|2; /* 010 */
		}
		$this->database['path']=$database;
	}
	function insert($query=[]){
		if ( $this->database['mode']&3 !== 3 ) {
			error_log('error: no permission to database in write.');
			return FALSE;
		}
		$data=$this->select();
		$data[]=$query;
		$data=json_encode($data, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_IGNORE);
		return file_put_contents($this->database['path'], $data);
	}
	function select($query=[]){
		if ( $this->database['mode']&1 !== 1 ) {
			error_log('error: no permission to database.');
			return FALSE;
		}
		$data=file_get_contents($this->database['path']);
		$data=json_decode($data, TRUE);
		return $data;
	}
}
function var_dump_text($text) {
	/*
	* https://office-obata.com/report/memorandum/post-4494/
        */
	
	/* ①画面出力バッファリング開始 */
	ob_start();

	/* ②var_dump実行 */
	var_dump($text);

	/* ③バッファリングした内容をテキストとして出力 */
	$out_text = ob_get_contents();

	/* ④画面出力バッファリング終了 */
	ob_end_clean();

	/* ⑤取得したテキストを返す */
	return $out_text;
}
function calcFeedAccessVol($database='database_feedaccess.db', $grep_time){
	$database=new internalDB(dirname(__FILE__).'/'.$database);
	$data=$database->select();
	
	$sum=0;
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
