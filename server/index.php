<?php
date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');
header('Server: Hidden');
header('X-Powered-By: Hidden');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
class webapp{
	public $result=[];

	function __construct() {
		$this->result = [];
		$this->result['message'] = '';
		$this->result['evented_at'] = time();
		$this->result['connection'] = [];
		$this->result['connection']['method'] = '';
		$this->result['connection']['client'] = [];
		$this->result['connection']['client']['address'] = '';
	}
	function setMessage($text='') {
		$this->result['message'] = $text;
		return $this->result['message'];
	}
	function result_return() {
		$result = $this->result;
		$result['evented_at'] = time();
		return $result;
	}
	function loadConfig($fname=null){
		$data=[];
		$data['internal']=[];
		$data['internal']['databases']=[];
		$data['internal']['databases']['host']='';
		$data['internal']['databases']['port']='';
		$data['internal']['databases']['schema']='';
		$data['internal']['databases']['user']='';
		$data['internal']['databases']['password']='';
		$data['internal']['databases']['database']='';
		$data['internal']['databases']['tableprefix']='';
		$data['internal']['api']=[];
		$data['internal']['api']['ratelimit']=1;
		$data['internal']['api']['timelimit']='1 hour';
		$data=array_merge(
			$data,
			json_decode(file_get_contents($fname), true, JSON_DEPTH, JSON_OPTION_ENCODE)
		);
		return $data;
	}
	function setCache($pdo_config=[], $data=[]){
		$dsn = '{schema}:host={host};port={port};dbname={dbname};user={user};password={password}';
		$list = ['schema', 'host', 'port', 'user', 'password'];
		foreach($list as $k => $v){
			$dsn = str_replace('{'.$v.'}', $pdo_config['connection'][$v], $dsn);
		}
		$dsn = str_replace('{dbname}', $pdo_config['connection']['database'], $dsn);
		try {
			$pdo = new PDO(
				$dsn,
				$pdo_config['connection']['user'],
				$pdo_config['connection']['password'],
			);

			$sql = 'select count(data_hash) from '.$pdo_config['connection']['tableprefix'].'_cache where data_hash=?;';
			$st = $pdo -> prepare($sql);
			$res = $st -> execute([
				hash('sha256', $data['content']),
			]);
			$res = $st -> fetchAll();
			if($res['count']==0){
				$sql = 'insert into '.$pdo_config['connection']['tableprefix'].'_cache (atom_feed,data_size,data_hash) values (?,?,?);';
				$st = $pdo -> prepare($sql);
				$res = $st -> execute([
					$data['content'],
					$data['size'],
					hash('sha256', $data['content']),
				]);
			}
			return $res;
		} catch (\Exception $e) {
			error_log($e->getMessage());
			return $e->getTraceAsString();
		}
	}
}

function xml2json($data){
	$data=simplexml_load_string($data);
	$data=json_encode($data, JSON_OPTION_ENCODE);
	return $data;
}

const JSON_OPTION = JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR;
const JSON_DEPTH = 512;
const JSON_OPTION_ENCODE = JSON_OPTION;
const JSON_OPTION_DECODE = JSON_OPTION | JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR;
const PDO_OPTION = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => true,
	PDO::ATTR_PERSISTENT => true,
];

$webapp = new webapp();
$webapp->result['connection']['method'] = $_SERVER['REQUEST_METHOD'];
$webapp->result['connection']['client']['address'] = $_SERVER['REMOTE_ADDR'];

$config=realpath(__DIR__ . '/../.secret/config.json');
if(!file_exists($config)){
	http_response_code(500);
	$webapp->setMessage('Config file does not exist.');
	echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
if(!is_readable($config)){
	http_response_code(500);
	$webapp->setMessage('Config file does readable.');
	echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
$webapp->loadConfig($config);
define('CONFIG', $webapp->loadConfig($config));

if( ! ( substr( strtolower( $_SERVER['REQUEST_METHOD'] ), 0, 6 ) == 'option' || strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' || strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' ) ) {
	http_response_code(405);
	$webapp->setMessage('405 Method not allowed.');
	echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}
if(!isset($_SERVER['REMOTE_ADDR'])){
	http_response_code(421);
	$webapp->setMessage('421 Misdirected Request');
	echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);
	exit(1);
}

/* Download: jma.go.jp **/
$api_endpoint = 'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml';
$data = file_get_contents($api_endpoint);
$size = strlen($data);
$webapp->result['dumps'][]=$webapp->setCache(
	[ 'connection'=>CONFIG['internal']['databases'][0],'option'=>PDO_OPTION, ],
	[ 'content'=>$data, 'size'=>$size ]
);
$webapp->result['dumps'][]=[ 'src'=>$api_endpoint, 'content'=>$data, 'size'=>$size ];
$data = json_decode(xml2json($data), true, JSON_DEPTH, JSON_OPTION_DECODE);
foreach($data['entry'] as $k => $v){
	$data['entry'][hash('sha256', $v['title'])][]=$v;
	unset($data['entry'][$k]);
}
$webapp->result['data'] = ['content'=>$data,'size'=>$size,];

echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);

