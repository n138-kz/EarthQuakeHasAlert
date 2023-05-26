<?php
$url = 'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml';
$xml = simplexml_load_file( $url );
$json = json_encode( $xml );
$array = json_decode( $json, TRUE );

echo $array['title'] . PHP_EOL;
echo $array['updated'] . PHP_EOL;
echo $array['id'] . PHP_EOL;

foreach( $array['entry'] as $key=>$val ) {
        if( $val['title'] != '震源・震度に関する情報' ){
                continue;
        }

        var_dump( $val );
}
