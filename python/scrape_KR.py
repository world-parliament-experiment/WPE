#!/usr/bin/env python
import json
import requests
import datetime
import ssl
from bs4 import BeautifulSoup
import warnings
import urllib3
import re

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

# Google News RSS workaround for South Korean legislative initiatives
# Querying for "국회 발의 법안" (National Assembly proposed bills)
url = "https://news.google.com/rss/search?q=%EA%B5%AD%ED%9A%8C+%EB%B0%9C%EC%9D%98+%EB%B2%95%EC%95%88&hl=ko&gl=KR&ceid=KR:ko"

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

# Keywords to ensure we get legislation-related news
# 국회 (National Assembly), 발의 (propose), 법안 (bill), 개정안 (amendment)
keywords = ["국회", "발의", "법안", "개정안", "제정안", "의안"]

try:
    response = requests.get(url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        soup = BeautifulSoup(response.content, features="xml")
        items = soup.find_all("item")
        
        for item in items:
            title_raw = item.find("title").text if item.find("title") else ""
            link = item.find("link").text if item.find("link") else ""
            pub_date = item.find("pubDate").text if item.find("pubDate") else ""
            
            title = title_raw.strip()
            
            # Filter for relevance
            is_relevant = any(kw in title for kw in keywords)
            
            if is_relevant:
                # Clean up title (remove source name at the end)
                if " - " in title:
                    title = title.rsplit(" - ", 1)[0]
                
                if len(title) > 250:
                    title = title[:247] + "..."
                
                desc = f"{title}\n날짜: {pub_date}\nSource: {link}"
                
                output[title] = desc

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
