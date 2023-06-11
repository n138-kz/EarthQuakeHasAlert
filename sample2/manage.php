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
        .numeric {
            text-align: right;
        }
    </style>
</head>
<body>
    <div>
        <style>
            table.feedaccess th, table.feedaccess td {
                min-width: 100px;
            }
        </style>
        <script>
            function feedaccess_detail_open(){
                Array.prototype.forEach.call(document.querySelectorAll('tr.feedaccess'), function(element) {
                    if (element.style.display=='none') {
                        element.style.display='table-row';
                    } else {
                        element.style.display='none';
                    }
                });
            }
        </script>
        <table class="feedaccess" border="1">
            <thead>
                <tr>
                    <th rowspan="2">通信日時</th>
                    <th colspan="4">通信量</th>
                </tr>
                <tr>
                    <th>Byte</th>
                    <th>KB</th>
                    <th>MB</th>
                    <th>GB</th>
                </tr>
                <tr>
                    <th class="feedaccess_summary">合計通信量</th>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol('database_feedaccess.db')[0] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol('database_feedaccess.db')[1] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol('database_feedaccess.db')[2] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol('database_feedaccess.db')[3] );?></td>
                </tr>
            </thead>
            <tbody>
                <?php
                    function overap_int($num) {
                        try {
                            if (false) {
                            } elseif ( $num > PHP_INT_MAX ) {
                                return '';
                            } elseif ( $num < PHP_INT_MIN ) {
                                return '';
                            }
                            return number_format($num);
                        } catch (\Throwable $th) {
                            return '';
                        }
                    }
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
                        echo '<tr class="feedaccess" style="display: none;">';
                        echo '<th class="feedaccess" title="' . $val[0] . '">' . $val[1] . '</th>';
                        echo '<td class="feedaccess numeric">' . overap_int( $val[2] * ( 10 **  0 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $val[2] * ( 10 ** -3 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $val[2] * ( 10 ** -6 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $val[2] * ( 10 ** -9 ) ) . '</td>';
                        echo '</tr>';
                    }
                ?>
                <tr>
                    <td><a href="#" onclick="feedaccess_detail_open()">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div>
        <table class="useraccesslog" border="1">
        <thead>
                <tr>
                    <th>通信日時</th>
                    <th>接続元アドレス</th>
                    <th></th>
                    <th>Method</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $database='database_useraccess.db';
                    $database=new internalDB(dirname(__FILE__).'/'.$database);
                    $data=$database->select();
                    $grep_time=[
                        (int)(new DateTime)->modify('first day of')->setTime(0,0,0)->format('U'),
                        (int)(new DateTime)->modify('first day of next month')->setTime(0,0,0)->format('U'),
                    ];
                    foreach( $data as $key => $val ){
                        if ( $val[0] < $grep_time[0] ) { continue; }
                        if ( $val[0] > $grep_time[1] ) { continue; }
                        echo '<tr class="useraccess">';
                        echo '<th class="useraccess" title="' . $val[0] . '">' . $val[1] . '</th>';
                        echo '<td class="useraccess">' . $val[3] . '(' . $val[2] . ')' . '</td>';
                        echo '<th class="useraccess">' . $val[4] . '</th>';
                        echo '<th class="useraccess">' . $val[5] . '</th>';
                        echo '<th class="useraccess">' . $val[6] . '</th>';
                        echo '</tr>';
                    }
                ?>
                <tr>
                    <td><a href="#" onclick="feedaccess_detail_open()">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
