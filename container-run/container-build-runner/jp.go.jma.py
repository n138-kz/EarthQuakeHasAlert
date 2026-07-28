import feedparser
import json

def getAtomFeedFromURL(url=''):
  feed_data = feedparser.parse(url)
  parsed_entries = []
  # シリアライズ可能なシンプルな辞書構造に変換する
  parsed_entries = []
  for entry in feed_data.entries:
    parsed_entries.append({
      'title': getattr(entry, 'title', None),
      'link': getattr(entry, 'link', None),
      'published': getattr(entry, 'published', getattr(entry, 'updated', None)),
      'summary': getattr(entry, 'summary', None),
    })

  feed_summary = {
    'feed_title': feed_data.feed.get('title', None),
    'feed_subtitle': feed_data.feed.get('subtitle', None),
    'entries': parsed_entries
  }

  json_data = json.dumps(feed_summary, ensure_ascii=False, indent=2)
  return feed_summary

if __name__ == '__main__':
  feed_urls = [
    {'url':'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml', 'title':None},
  ]
  for u_object in feed_urls:
    print(u_object['url'])
    print(json.dumps(getAtomFeedFromURL(u_object['url']), ensure_ascii=False, indent=2))
