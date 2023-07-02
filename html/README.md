# sample2

## Require librarys

- PHP >= 7.2.0
- cron
- Bash(shell)

`cron` 並びに `Bash` については繰り返し自動実行が可能であればそれで代用可
自動チェックする場合は自動実行機能が必須。
下記のように自動化することで繰り返しチェック&feedファイルを最新に保つことが可能。(下記は5秒/毎分ごとにチェック)

```bash:crontab
* * * * * for i in {1..8} ; do sleep 5; curl "http://localhost/EarthQuakeHasAlert/sample2/xmlfeed_eqvol.php?ts=$(date +\%s)&id=x" ; done
```
