import sqlite3
# https://products.sint.co.jp/topsic/blog/how-to-use-sqlite-in-python

dbname = 'py3_database.db'
dbconn = sqlite3.connect(dbname)

dbcurs = dbconn.cursor()
dbcurs.execute(
	'CREATE TABLE items(eventid INTEGER PRIMARY KEY)'
)

dbconn.close()
