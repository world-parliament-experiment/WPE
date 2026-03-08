#!/usr/bin/env python
import json
import requests
import datetime
import ssl
from bs4 import BeautifulSoup
import warnings
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate warnings
warnings.filterwarnings("ignore", category=requests.packages.urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

# Google News RSS workaround for Mexican legislative initiatives
# Searching specifically for initiatives in the Mexican Congress
url = "https://news.google.com/rss/search?q=iniciativas+ley+mexico+congreso+gaceta&hl=es-419&gl=MX&ceid=MX:es-419"

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

keywords = ["iniciativa", "ley", "congreso", "senado", "diputados", "gaceta", "reforma", "decreto"]

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
            
            # Filter for relevance to ensure we get legislation-related news
            is_relevant = any(kw in title.lower() for kw in keywords)
            
            if is_relevant:
                # Clean up title (remove source name at the end usually "- Source")
                if " - " in title:
                    title = title.rsplit(" - ", 1)[0]
                
                if len(title) > 250:
                    title = title[:247] + "..."
                
                desc = f"{title}\nFecha: {pub_date}\nSource: {link}"
                
                output[title] = desc

except Exception:
    pass

# If Google News failed or returned nothing, we try a more generic search
if not output:
    try:
        url_alt = "https://news.google.com/rss/search?q=gaceta+parlamentaria+mexico+iniciativas&hl=es-419&gl=MX&ceid=MX:es-419"
        response = requests.get(url_alt, headers=header, timeout=15, verify=False)
        if response.status_code == 200:
            soup = BeautifulSoup(response.content, features="xml")
            items = soup.find_all("item")
            for item in items:
                title = item.find("title").text if item.find("title") else ""
                link = item.find("link").text if item.find("link") else ""
                if " - " in title: title = title.rsplit(" - ", 1)[0]
                if len(title) > 250: title = title[:247] + "..."
                output[title] = f"{title}\nSource: {link}"
    except:
        pass

print(json.dumps(output, ensure_ascii=False))
