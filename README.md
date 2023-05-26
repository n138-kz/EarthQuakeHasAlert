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
