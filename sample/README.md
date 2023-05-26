```bash
[root@8d4ab3ba4a68 EarthQuakeHasAlert]# php sample/test01.php
高頻度（地震火山）
2023-05-26T23:47:22+09:00
https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml#short_1685112442
2023/05/26 19:05:01 JST 【震度速報】26日19時03分ころ、地震による強い揺れを感じました。震度3以上が観測された地域をお知らせします。
2023/05/26 19:06:01 JST 【震度速報】26日19時03分ころ、地震による強い揺れを感じました。震度3以上が観測された地域をお知らせします。
[root@8d4ab3ba4a68 EarthQuakeHasAlert]#
```

```bash
[root@006984a1709a EarthQuakeHasAlert]# php sample/test02.php
高頻度（地震火山）
2023-05-26T23:47:22+09:00
https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml#short_1685112442
2023/05/26 19:05:01 JST 【震度速報】26日19時03分ころ、地震による強い揺れを感じました。震度3以上が観測された地域をお知らせします。
震度５弱        茨城県南部
震度５弱        千葉県北東部

2023/05/26 19:06:01 JST 【震度速報】26日19時03分ころ、地震による強い揺れを感じました。震度3以上が観測された地域をお知らせします。
震度５弱        茨城県南部
震度５弱        千葉県北東部

[root@006984a1709a EarthQuakeHasAlert]#
```
