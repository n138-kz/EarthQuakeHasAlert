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
