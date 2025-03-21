# [EarthQuakeHasAlert](https://github.com/n138-kz/EarthQuakeHasAlert)

## 本プログラムに関して

本プログラムは震度5弱以上の地震を検知した場合、または権限を持ているユーザ2名以上が同時（5分以内）に発動することにより、自動で安否確認用のメールを発信する為のプログラムである。
メールシステム利用にはユーザ認証を必須とし、ユーザ認証はDiscord SSOを使用する。

大きな揺れ（震度5弱以上）が発生したかどうかを確認する方法は一定間隔毎に特定のAPIサーバに問い合わせを行い、指定範囲内かつメール送信前であることとする。

手動で発信させる場合、権限を持ったユーザ2名以上で発信ボタンを押下することにより、本プログラムを発動する。
ただし既にメール送信済の場合は送信しない。なお、担当1名のみで動作させないのは誤操作によるトラブル防止の為である。

APIサーバは運用コストのなるべく少ないサーバを採用するものとし、気象庁が公開しているサーバを採用する。
ただし、APIサーバ側の制約により、1日10GBまでの通信の制約が存在する為、毎度クライアントからのリクエストをそのまま転送しない。
プログラム実行ノードと同一ノード内に一時的にファイルとして保持するものとする。

> ■ダウンロード量超過時のアクセス遮断について
　気象庁防災情報XMLを公開しているURLに対し、1日10GB以上のダウンロードを伴うアクセスが確認された場合、アクセス元のIPアドレスを遮断します。
　遮断された場合、一度取得したファイルを再度取得しない等の改修をお願いいたします。また、バグ等によりそのような動作をしていないか、ご確認をお願いいたします。

気象庁利用ガイドライン遵守のため、Atom feedサーバへのアクセスは5分ごととする。
クライアントからのリクエストに対応できるよう、内部にキャッシュしておくものとする。
また、サーバとの通信量を記録し、一定量を超えた場合管理者へ通知する。

## データ参照元

- [気象庁防災情報XMLフォーマット形式電文の公開（PULL型）](http://xml.kishou.go.jp/xmlpull.html)

Atomフィード ○高頻度フィード ※毎分更新し、直近少なくとも10分入電を掲載

- [定時](https://www.data.jma.go.jp/developer/xml/feed/regular.xml)：気象に関する情報のうち、天気概況など定時に発表されるもの
- [随時](https://www.data.jma.go.jp/developer/xml/feed/extra.xml)：気象に関する情報のうち、警報・注意報など随時発表されるもの
- [地震火山](https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml)：地震、火山に関する情報
- [その他](https://www.data.jma.go.jp/developer/xml/feed/other.xml)：上記３種類のいずれにも属さないもの

## ユーザ管理

ユーザ情報は原則保持しないものとするが例外としてアクセス制御をアプリケーション側から制御可能とするために下記データを保持する。

### 保持するデータ

- 外部SSOのユーザID
- 通信アクセス元IPアドレス
- 通信アクセス元ユーザエージェント(ソフトウェア名やそのバージョン)

アクセス制御はユーザ毎に制御するものとし、すべてのユーザに下記権限のいづれかを設定する。

### ユーザ権限レベル

| Level | Oct  | Name              | Description |
|    -: | :-:  | :-                | :- |
|     0 | 0000 | Unauthorized user | 非認証状態のユーザ |
|     1 | 0001 |                   | |
|     2 | 0010 | Authrized user    | 認証されているユーザ |
|     4 | 0100 | Manager           | 管理者 |
|     8 | 1000 | Super user        | 管理者 |

- 権限情報を保持するユーザはデフォルトで Level `1` (Authrized user) を設定する。
- 本プログラム作成,運用,保守に関わる者は Level `3` (Developer) を設定する。
  デバッグ用ログを表示できる `Authrized user` である。
  ※注：フロントエンド側のログは権限管理はしない為全ユーザーにて確認可能である。
- 権威処理を行う必要のあるユーザには Level `4` (Manager) を設定する。

## Databases

```sql
CREATE TABLE IF NOT EXISTS jma_eqvol_cache (
	"timestamp" double precision NOT NULL DEFAULT EXTRACT(epoch FROM CURRENT_TIMESTAMP),
	uuid TEXT NOT NULL PRIMARY KEY, 
	data_hash TEXT NOT NULL,
	data_size double precision NOT NULL DEFAULT 0,
	atom_feed TEXT NOT NULL
);
```

