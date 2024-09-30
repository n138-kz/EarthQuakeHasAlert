<?php
/*
* refs docs: https://www.google.com/recaptcha/admin
*/
class google {
  public $endpoint;
  public $postdata;
  function __construct() {
    $this->endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    $this->postdata['secret']   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $this->postdata['response'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $this->postdata['remoteip'] = '127.0.0.1';
  }

  function setKey_private($val){ $this->postdata['secret'] = $val; }
  function setKey_public($val){ $this->postdata['response'] = $val; }
  function setip_remotehost($val){ $this->postdata['remoteip'] = $val; }
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
    $result = '';
    if (!isset($curl_res['success']) || $curl_res['success'] != TRUE || $curl_res['score'] < 0.3) {
      // validation fail

      if (isset($curl_res['success']) && trim($curl_res['success']) != TRUE) {
        // result description -> $result
        foreach ($curl_res['error-codes'] as $key => $val) {
          if ($key>1) { $result.=', '; }
          $result.=$val;
        }
      }

      return $result;
    } else {
      // validation ok
      $result = 'ok';
      return $result;
    }
  }
}
