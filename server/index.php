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
	function guidv4($data = null) {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		// Output the 36 character UUID.
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
	function setCache($pdo_config=[], $data=['src'=>'','content'=>'','size'=>'',]){
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

			$sql = 'SELECT COUNT(*) FROM '.$pdo_config['connection']['tableprefix'].'_cache WHERE data_hash=?;';
			$st = $pdo -> prepare($sql);
			$res = $st -> execute([
				hash('sha256', $data['content']),
			]);
			$res = $st -> fetch(PDO::FETCH_ASSOC);
			#if($res['count']>0){ $data['content']=null; }

			$sql = 'INSERT INTO '.$pdo_config['connection']['tableprefix'].'_cache (data_src,data_feed,data_size,data_hash,uuid) VALUES (?,?,?,?,?);';
			$st = $pdo -> prepare($sql);
			$res = $st -> execute([
				$data['src'],
				$data['content'],
				$data['size'],
				hash('sha256', $data['content']),
				$this->guidv4(),
			]);
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
$webapp->setCache(
	[ 'connection'=>CONFIG['internal']['databases'][0],'option'=>PDO_OPTION, ],
	[ 'src'=>$api_endpoint, 'content'=>$data, 'size'=>$size ]
);
// $webapp->result['dumps'][]=[ 'src'=>$api_endpoint, 'content'=>$data, 'size'=>$size ];
$summary = $data = json_decode(xml2json($data), true, JSON_DEPTH, JSON_OPTION_DECODE);
foreach($summary['entry'] as $k1 => $v1){
	$v1['detail']=file_get_contents($v1['id']);
	$size=strlen($v1['detail']);
	$details=$data=json_decode(xml2json($v1['detail']), true, JSON_DEPTH, JSON_OPTION_DECODE);
	$webapp->setCache(
		[ 'connection'=>CONFIG['internal']['databases'][0],'option'=>PDO_OPTION, ],
		[ 'src'=>$v1['id'], 'content'=>$v1['detail'], 'size'=>$size ]
	);
	$v1['detail']=$details;
	$summary['entry'][hash('sha256', $v1['title'])][]=$v1;
	unset($summary['entry'][$k1]);
}

$webapp->result['data'] = ['content'=>$summary,'size'=>$size,];

echo json_encode($webapp->result_return(), JSON_OPTION_ENCODE);

