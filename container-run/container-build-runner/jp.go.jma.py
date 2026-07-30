import feedparser
import json
import hashlib
import pathlib

def getAtomFeedFromURL(url=''):
  feed_data = feedparser.parse(url)
  if 'bozo_exception' in feed_data:
    feed_data.pop('bozo_exception', None)

  return feed_data

if __name__ == '__main__':
  feed_urls = [
    {'url':'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml', 'title':None},
  ]
  output_files = {
    'sub1': 'output_sub1.json',
    'sub2': 'output_sub2.json',
  }
  for u_object in feed_urls:
    print({'url': u_object['url']})
    u_sub1 = getAtomFeedFromURL(u_object['url'])
    with open(output_files.get('sub1','output.log'), mode='w') as f:
      json.dump(u_sub1, f, ensure_ascii=False, indent=2, sort_keys=True)
    if not u_sub1 is None and not u_sub1.get('entries', None) is None:
        for u_sub1_object in u_sub1['entries']:
          print(json.dumps({'url': u_sub1_object['link'], 'id': hashlib.md5(u_sub1_object['link'].encode("utf-8")).hexdigest(),'output': output_files.get('sub2','output.log')},ensure_ascii=False, indent=2, sort_keys=True))
          u_sub2 = getAtomFeedFromURL(u_sub1_object['link'])
          with open(f'{pathlib.Path(output_files.get('sub2','output.log')).stem}_{hashlib.md5(u_sub1_object['link'].encode("utf-8")).hexdigest()}{pathlib.Path(output_files.get('sub2','output.log')).suffix}', mode='w') as f:
            json.dump(u_sub2, f, ensure_ascii=False, indent=2, sort_keys=True)
