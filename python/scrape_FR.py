#!/usr/bin/env python
import json
import requests
import datetime
import ssl
from bs4 import BeautifulSoup

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

# Assemblée Nationale RSS feed for parliamentary documents
url = "http://www2.assemblee-nationale.fr/feeds/detail/documents-parlementaires"

header = {
    "User-Agent": "Mozilla/5.0"
}

try:
    response = requests.get(url, headers=header, timeout=15)
    if response.status_code == 200:
        # Use BeautifulSoup to parse the XML
        soup = BeautifulSoup(response.content, features="xml")
        items = soup.find_all("item")
        
        for item in items:
            pub_date_str = item.find("pubDate").text if item.find("pubDate") else ""
            if not pub_date_str:
                continue
                
            try:
                # Format: Tue, 03 Mar 2026 00:00:00 +0000
                # We only need the date part
                date_clean = " ".join(pub_date_str.split()[1:4])
                item_date = datetime.datetime.strptime(date_clean, "%d %b %Y")
                
                if item_date >= three_months_ago:
                    title_raw = item.find("title").text if item.find("title") else ""
                    if not title_raw:
                        continue
                    
                    # France format is often: "N° 2548 - Proposition de loi de M. ..."
                    # We can use the whole title as it is quite descriptive
                    title = title_raw.strip()
                    
                    # Truncate if too long
                    if len(title) > 250:
                        title = title[:247] + "..."
                    
                    link = item.find("link").text if item.find("link") else ""
                    desc = f"{title}\nSource: {link}"
                    
                    output[title] = desc
            except Exception:
                continue
except Exception:
    pass

print(json.dumps(output))
