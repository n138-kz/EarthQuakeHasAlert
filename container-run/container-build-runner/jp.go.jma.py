import feedparser
import json
import hashlib
import pathlib
import requests

def getAtomFeedFromURL(url=''):
  feed_rawdata = requests.get(url)
  feed_data = feedparser.parse(feed_rawdata.text)
  if 'feed' not in feed_data:
    feed_data['feed'] = {}
  feed_data.feed['raw'] = {}
  feed_data.feed.raw['text'] = feed_rawdata.text
  feed_data.feed.raw['length'] = len(feed_rawdata.text)
  feed_data.feed['href'] = url
  if 'bozo_exception' in feed_data:
    feed_data.pop('bozo_exception', None)

  return feed_data

if __name__ == '__main__':
  feed_urls = [
    {'url':'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml', 'title':'気象庁防災情報XMLフォーマット形式電文の公開（PULL型）/Atomフィード○高頻度フィード/地震火山'},
  ]
  output_files = {
    'sub1': 'output_sub1.json',
    'sub2': 'output_sub2.json',
  }
  for u_object in feed_urls:
    u_sub1 = getAtomFeedFromURL(u_object['url'])
    if not u_sub1 is None and not u_sub1.get('entries', None) is None:
        for u_sub1_object in u_sub1['entries']:
          u_sub2 = getAtomFeedFromURL(u_sub1_object['link'])
          u_sub1_object['details'] = u_sub2

          # debug
          with open('{stem}_{id}{suffix}'.format(
            stem = pathlib.Path(output_files.get('sub2','output.log')).stem,
            id = hashlib.md5(u_sub1_object['link'].encode("utf-8")).hexdigest(),
            suffix = pathlib.Path(output_files.get('sub2','output.log')).suffix
          ), mode='w') as f:
            json.dump(u_sub2, f, ensure_ascii=False, indent=2, sort_keys=True)
    with open(output_files.get('sub1','output.log'), mode='w') as f:
      json.dump(u_sub1, f, ensure_ascii=False, indent=2, sort_keys=True)
