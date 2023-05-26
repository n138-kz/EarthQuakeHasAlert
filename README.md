# EarthQuakeHasAlert

本プログラムは震度5弱以上の地震を検知した場合、または権限を持ているユーザ2名以上が同時（5分以内）に発動することにより、自動で安否確認用のメールを発信する為のプログラムである。
利用にはユーザ認証を必須とし、ユーザ認証はGoogleSSOを使用する。

大きな揺れ（震度5弱以上）が発生したかどうかを確認する方法は毎分特定のAPIサーバに問い合わせを行い、物理的地形範囲内かつ直近5分以内であることとする。

手動で発信させる場合、権限を持ったユーザ2名以上で発信ボタンを押下することにより、本プログラムを発動する。
担当1名のみで動作させないのは誤操作によるトラブル防止の為である。

## データ参照元
- [気象庁防災情報XMLフォーマット形式電文の公開（PULL型）](http://xml.kishou.go.jp/xmlpull.html)

Atomフィード
○高頻度フィード
　※毎分更新し、直近少なくとも10分入電を掲載

  - [定時](https://www.data.jma.go.jp/developer/xml/feed/regular.xml)：気象に関する情報のうち、天気概況など定時に発表されるもの
  - [随時](https://www.data.jma.go.jp/developer/xml/feed/extra.xml)：気象に関する情報のうち、警報・注意報など随時発表されるもの
  - [地震火山](https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml)：地震、火山に関する情報
  - [その他](https://www.data.jma.go.jp/developer/xml/feed/other.xml)：上記３種類のいずれにも属さないもの

```bash
[root@006984a1709a tmp]# git clone git@github.com:n138-kz/EarthQuakeHasAlert.git
Cloning into 'EarthQuakeHasAlert'...
remote: Enumerating objects: 40, done.
remote: Counting objects: 100% (40/40), done.
remote: Compressing objects: 100% (28/28), done.
remote: Total 40 (delta 10), reused 14 (delta 3), pack-reused 0
Receiving objects: 100% (40/40), 9.01 KiB | 4.50 MiB/s, done.
Resolving deltas: 100% (10/10), done.
[root@006984a1709a tmp]# ls -l
total 4.0K
drwxr-xr-x 5 root root 4.0K May 26 22:58 EarthQuakeHasAlert
[root@006984a1709a tmp]# cd EarthQuakeHasAlert/
[root@006984a1709a EarthQuakeHasAlert]# ls -l
total 24K
drwxr-xr-x 8 root root 4.0K May 26 22:58 .git
drwxr-xr-x 3 root root 4.0K May 26 22:58 .github
-rw-r--r-- 1 root root 1.1K May 26 22:58 LICENSE
-rw-r--r-- 1 root root 1.7K May 26 22:58 README.md
-rw-r--r-- 1 root root   60 May 26 22:58 composer.json
drwxr-xr-x 2 root root 4.0K May 26 22:58 sample
[root@006984a1709a EarthQuakeHasAlert]# php sample/test.php
高頻度（地震火山）
2023-05-26T21:04:51+09:00
https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml#short_1685102691
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 20:09:58 JST 【震源・震度情報】26日20時07分ころ、地震がありました。
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 20:09:58 JST 【震源・震度情報】26日20時07分ころ、地震がありました。
2023/05/26 19:07:51 JST 【震源・震度情報】26日19時03分ころ、地震がありました。
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 20:09:58 JST 【震源・震度情報】26日20時07分ころ、地震がありました。
2023/05/26 19:07:51 JST 【震源・震度情報】26日19時03分ころ、地震がありました。
2023/05/26 17:56:10 JST 【震源・震度情報】26日17時52分ころ、地震がありました。
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 20:09:58 JST 【震源・震度情報】26日20時07分ころ、地震がありました。
2023/05/26 19:07:51 JST 【震源・震度情報】26日19時03分ころ、地震がありました。
2023/05/26 17:56:10 JST 【震源・震度情報】26日17時52分ころ、地震がありました。
2023/05/26 17:00:32 JST 【震源・震度情報】26日16時57分ころ、地震がありました。
2023/05/26 21:04:41 JST 【震源・震度情報】26日21時02分ころ、地震がありました。
2023/05/26 20:09:58 JST 【震源・震度情報】26日20時07分ころ、地震がありました。
2023/05/26 19:07:51 JST 【震源・震度情報】26日19時03分ころ、地震がありました。
2023/05/26 17:56:10 JST 【震源・震度情報】26日17時52分ころ、地震がありました。
2023/05/26 17:00:32 JST 【震源・震度情報】26日16時57分ころ、地震がありました。
2023/05/26 16:42:46 JST 【震源・震度情報】26日16時39分ころ、地震がありました。
[root@006984a1709a EarthQuakeHasAlert]# 
```
