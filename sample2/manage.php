<?php session_start();
require_once './vendor/autoload.php';
require_once './php-internal.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['google']) || !isset($_SESSION['user']['google']['client_id']) ) {
	http_response_code(404);
    exit();
}
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['google']) || !isset($_SESSION['user']['google']['client_token']) ) {
	http_response_code(404);
    exit();
}

define('CLIENT_ID', $_SESSION['user']['google']['client_id']);
define('CLIENT_TOKEN', $_SESSION['user']['google']['client_token']);

try {
    $client = new Google_Client(['client_id' => CLIENT_ID]); 
    $payload = $client->verifyIdToken(CLIENT_TOKEN);
    if (!$payload) { throw $th; }
} catch (\Throwable $th) {
    http_response_code(401);
    die('Unauthorized');
}

if ( mb_strtolower($_SERVER['REQUEST_METHOD']) == 'post' && isset($_POST) && is_array($_POST) ) {
    $request['post'] = $_POST;
    if ( $request['post']['request'] == base64_encode('issueAlert') ) {
    }
    exit();
} elseif ( mb_strtolower($_SERVER['REQUEST_METHOD']) == 'get' ) {
} else {
	http_response_code(404);
    exit();
}

?><!DOCTYPE html><html lang="ja">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>xmlfeed_eqvol.php</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=6LfCHdcUAAAAAOwkHsW_7W7MfoOrvoIw9CXdLRBA"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="./lib/grecaptcha.js" id="grecaptchajs"></script>
    <script src="./lib/isset.js"></script>
    <script>
        window.addEventListener('load', function() {
            console.log('page is fully loaded');
        });
        window.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContent loaded');
        });
    </script>
    <style>
    </style>
</head>
<body>
    <div>
        <table border="1">
            <thead>
                <tr>
                    <th>アクセス日時</th>
                    <th>通信量</th>
                </tr>
                <tr>
                    <td><span class="feedaccess_summary">合計通信量</span></td>
                    <td><span class="feedaccess_summary"><?php echo calcFeedAccessVol('database_feedaccess.db')[0];?></span></td>
                    <td><span class="feedaccess_summary"><?php echo calcFeedAccessVol('database_feedaccess.db')[1];?></span></td>
                    <td><span class="feedaccess_summary"><?php echo calcFeedAccessVol('database_feedaccess.db')[2];?></span></td>
                    <td><span class="feedaccess_summary"><?php echo calcFeedAccessVol('database_feedaccess.db')[3];?></span></td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $database='database_feedaccess.db';
                    $database=new internalDB(dirname(__FILE__).'/'.$database);
                    $data=$database->select();
                    $grep_time=[
                        (int)(new DateTime)->modify('first day of')->setTime(0,0,0)->format('U'),
                        (int)(new DateTime)->modify('first day of next month')->setTime(0,0,0)->format('U'),
                    ];
                    foreach( $data as $key => $val ){
                        if ( $val[0] < $grep_time[0] ) { continue; }
                        if ( $val[0] > $grep_time[1] ) { continue; }
                    }
                    echo '<tr>';
                    echo '<td><span class="feedaccess">' . $val . '</span></td>';
                    echo '</tr>';
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
