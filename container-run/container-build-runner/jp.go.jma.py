import feedparser
import json

def getAtomFeedFromURL(url='', isSummary=False):
  feed_data = feedparser.parse(url)

  if isSummary:
    # シリアライズ可能なシンプルな辞書構造に変換する
    parsed_entries = []
    for entry in feed_data.entries:
      parsed_entries.append({
        'title': getattr(entry, 'title', None),
        'link': getattr(entry, 'link', None),
        'published': getattr(entry, 'published', getattr(entry, 'updated', None)),
        'summary': getattr(entry, 'summary', None),
      })
  else:
    #print(feed_data)
    return None

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
    print(json.dumps(u_sub1, ensure_ascii=False, indent=2))
    print({'url': u_object['url']})
    u_sub1 = getAtomFeedFromURL(u_object['url'], True)
    if not u_sub1 is None and not u_sub1.get('url', None) is None:
        for u_sub1_object in u_sub1['entries']:
          #print(u_sub1_object['link'])
          u_sub2 = getAtomFeedFromURL(u_sub1_object['link'])
          #print(json.dumps(u_sub2, ensure_ascii=False, indent=2))
