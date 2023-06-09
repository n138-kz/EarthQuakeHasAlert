<?php session_start();
date_default_timezone_set('Asia/Tokyo');
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
        table th, table td {
            min-width: 100px;
        }
    </style>
    <script>
        function item_detail_open(param){
            Array.prototype.forEach.call(document.querySelectorAll('tr.'+param), function(element) {
                if (element.style.display=='none') {
                    element.style.display='table-row';
                } else {
                    element.style.display='none';
                }
            });
        }
    </script>
</head>
<body>
    <div>
        <table class="feedaccess" border="1">
            <thead>
                <tr>
                    <?php
                        $database='database_feedaccess.db';
                    ?>
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
                    <?php
                        $grep_time=[
                            (int)(new DateTime)->modify('first day of')->setTime(0,0,0)->format('U'),
                            (int)(new DateTime)->modify('first day of next month')->setTime(0,0,0)->format('U'),
                        ];
                    ?>
                    <th class="feedaccess_summary">合計通信量(月間)</th>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[0] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[1] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[2] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[3] );?></td>
                </tr>
                <tr>
                    <?php
                        $grep_time=[
                            (int)(new DateTime)->modify('today')->setTime(0,0,0)->format('U'),
                            (int)(new DateTime)->modify('tomorrow')->setTime(0,0,0)->format('U'),
                        ];
                    ?>
                    <th class="feedaccess_summary">合計通信量(<?php echo date('Y/m/d');?>)</th>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[0] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[1] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[2] );?></td>
                    <td class="feedaccess_summary numeric"><?php echo number_format( calcFeedAccessVol( $database, $grep_time )[3] );?></td>
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
                    for ($i=(count($data)); $i >= 0; $i--) {
                        if ( $data[$i][0] < $grep_time[0] ) { continue; }
                        if ( $data[$i][0] > $grep_time[1] ) { continue; }
                        echo '<tr class="feedaccess" style="display: none;">';
                        echo '<th class="feedaccess" title="' . $data[$i][0] . '">' . date('Y/m/d H:i:s T', $data[$i][0]) . '</th>';
                        echo '<td class="feedaccess numeric">' . overap_int( $data[$i][2] * ( 10 **  0 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $data[$i][2] * ( 10 ** -3 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $data[$i][2] * ( 10 ** -6 ) ) . '</td>';
                        echo '<td class="feedaccess numeric">' . overap_int( $data[$i][2] * ( 10 ** -9 ) ) . '</td>';
                        echo '</tr>' . PHP_EOL;
                    }
                ?>
                <tr>
                    <td><a href="#" onclick="item_detail_open('feedaccess')">詳細</a></td>
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
                    <th>Method</th>
                    <th>端末との時差</th>
                    <th>Google<br />reCAPTCHA v3<br />Result</th>
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
                    for ($i=(count($data)); $i >= 0; $i--) {
                        if ( $data[$i][0] < $grep_time[0] ) { continue; }
                        if ( $data[$i][0] > $grep_time[1] ) { continue; }
                        if ( substr($data[$i][3], 0, strlen('localhost')) == 'localhost' ) {continue; }
                        echo '<tr class="useraccess" style="display: none;">';
                        echo '<th class="useraccess" title="' . $data[$i][0] . '">' . date('Y/m/d H:i:s T', $data[$i][0]) . '</th>';
                        echo '<td class="useraccess">' . $data[$i][3] . '(' . $data[$i][2] . ')' . '</td>';
                        echo '<td class="useraccess">' . $data[$i][4] . '</td>';
                        echo '<td class="useraccess numeric" data-clientts="' . $data[$i][5] . '" data-serverts="' . $data[$i][0] . '">' . ($data[$i][0]-$data[$i][5]) . '</td>';
                        echo '<td class="useraccess">' . $data[$i][6] . '</td>';
                        echo '</tr>' . PHP_EOL;
                    }
                ?>
                <tr>
                    <td><a href="#" onclick="item_detail_open('useraccess')">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
