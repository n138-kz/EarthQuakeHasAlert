import feedparser
import json
import hashlib
import pathlib
import requests
import xml.etree.ElementTree as ET

def parse_jma_xml_detail(xml_text):
  try:
    root = ET.fromstring(xml_text)
  except Exception:
    return []

  parsed_time_series = []

  # タグ名から名前空間 {http://...} を無視して 'Area' のみを探す
  for elem in root.iter():
    # タグの末尾が 'Area' で終わる要素を探す
    if elem.tag.endswith('Area'):
      name = None
      code = None

      # Area の子要素を走査
      for child in elem:
        if child.tag.endswith('Name'):
          name = child.text
        elif child.tag.endswith('Code'):
          code = child.text

      # Name または Code が取れていればリストに追加
      if name or code:
        parsed_time_series.append({
          'area_name': name,
          'area_code': code
        })

  return parsed_time_series

def getAtomFeedFromURL(url=''):
  feed_rawdata = requests.get(url)
  feed_rawdata.encoding = feed_rawdata.apparent_encoding
  feed_data = feedparser.parse(feed_rawdata.text)
  if 'feed' not in feed_data:
    feed_data['feed'] = {}
  feed_data.feed['href'] = url
  feed_data.feed['raw'] = {}
  feed_data.feed.raw['text'] = feed_rawdata.text
  feed_data.feed.raw['length'] = len(feed_rawdata.text)

  if 'bozo_exception' in feed_data:
    feed_data.pop('bozo_exception', None)

  try:
    feed_data['parsed_xml_areas'] = parse_jma_xml_detail(feed_rawdata.text)
  except Exception as e:
    feed_data['parsed_xml_areas'] = []

  return feed_data

if __name__ == '__main__':
  feed_urls = [
    {'url':'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml', 'title':'気象庁防災情報XMLフォーマット形式電文の公開（PULL型）/Atomフィード○高頻度フィード/地震火山'},
    {'url':'https://www.data.jma.go.jp/developer/xml/feed/eqvol_l.xml', 'title':'気象庁防災情報XMLフォーマット形式電文の公開（PULL型）/Atomフィード○長期フィード/地震火山'},
  ]
  output_files = {
    'sub1': 'output_sub1.json',
    'sub2': 'output_sub2.json',
  }
  for i, u_object in enumerate(feed_urls):
    print({'index': i, 'url': [u_object['url']]})
    u_sub1 = getAtomFeedFromURL(u_object['url'])
    if (not u_sub1 is None) and (not u_sub1.get('entries', None) is None):
        for j, u_sub1_object in enumerate(u_sub1['entries']):
          print({'index': i, 'url': [u_object['url'], u_sub1_object['link']]})
          u_sub2 = getAtomFeedFromURL(u_sub1_object['link'])
          u_sub1_object['details'] = u_sub2

          # debug
          with open('{stem}_{id}_{hash}{suffix}'.format(
            stem = pathlib.Path(output_files.get('sub2','output.log')).stem,
            id = hashlib.md5(u_sub1_object['link'].encode("utf-8")).hexdigest(),
            suffix = pathlib.Path(output_files.get('sub2','output.log')).suffix
          ), mode='w') as f:
            json.dump(u_sub2, f, ensure_ascii=False, indent=2, sort_keys=True)
    with open('{stem}_{id}_{hash}{suffix}'.format(
      stem = pathlib.Path(output_files.get('sub1','output.log')).stem,
      id = hashlib.md5(u_object['url'].encode("utf-8")).hexdigest(),
      suffix = pathlib.Path(output_files.get('sub1','output.log')).suffix
    ), mode='w') as f:
      json.dump(u_sub1, f, ensure_ascii=False, indent=2, sort_keys=True)
