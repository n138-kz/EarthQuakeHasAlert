<?php
/*
* refs docs: https://birdie0.github.io/discord-webhooks-guide/discord_webhook.html
*/
class discord{
  public $endpoint;
  public $postdata;
  function __construct() {
    $this->endpoint = 'https://discord.com/api/webhooks/xxxxx/xxxxxxxxxx';
    $this->postdata['content'] = '';
    /*
      Declarable items
      ==============================
      - content (Require)
      - username (Optional)
      - title (Optional)
    */
  }

  function setValue($key, $val){
  	if ( isset($key) !== true ){ return false; }
  	if ( isset($val) !== true ){ return false; }
    if ( $key === '' ){ return false; }
    if ( $val === '' ){ return false; }

  	$this->postdata[$key] = $val;
  }

  function exec_curl(){
    $curl_req = curl_init($this->endpoint);
    curl_setopt($curl_req,CURLOPT_POST,           TRUE);
    curl_setopt($curl_req,CURLOPT_POSTFIELDS,     http_build_query($this->postdata));
    curl_setopt($curl_req,CURLOPT_SSL_VERIFYPEER, FALSE); // オレオレ証明書対策
    curl_setopt($curl_req,CURLOPT_SSL_VERIFYHOST, FALSE); //
    curl_setopt($curl_req,CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_req,CURLOPT_COOKIEJAR,      'cookie');
    curl_setopt($curl_req,CURLOPT_COOKIEFILE,     'tmp');
    curl_setopt($curl_req,CURLOPT_FOLLOWLOCATION, TRUE); // Locationヘッダを追跡

    $curl_res=curl_exec($curl_req);
    $curl_res=json_decode($curl_res, TRUE);

    return $curl_res;
  }

  function get_resultMesg($curl_res){
    return $curl_res;
  }
}