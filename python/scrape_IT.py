#!/usr/bin/env python
import json
import requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
import datetime
import ssl
from bs4 import BeautifulSoup
import warnings

# Ignore SSL certificate warnings
warnings.filterwarnings("ignore", category=requests.packages.urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

# URL from the original IT scraper
url = 'https://www.parlamento.it/leg/ldl_new/v3/sldlelencodlconvers.htm'

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
}

try:
    response = requests.get(url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        # Use explicit encoding if needed, but requests usually handles it
        soup = BeautifulSoup(response.content, 'html.parser')

        # Logic from the original scraper
        laws_sections = soup.findAll("dl", {'class': 'leggi'})
        for section in laws_sections:
            titles = section.findAll("p", {'class': 'titoloLegge'})
            links = section.findAll("dt")
                 
            for idx, t in enumerate(titles):
                title = t.getText().strip() 
                # Remove leading/trailing quotes often found in Italian law titles
                title = title.strip('"')
                
                # Truncate if too long for DB (usually 255 chars)
                if len(title) > 250:
                    title = title[:247] + "..."
                
                # Get link
                href = ""
                if idx < len(links):
                    a_tag = links[idx].find('a')
                    if a_tag:
                        href = a_tag.get('href', '')
                
                if href and not href.startswith('http'):
                    href = 'https://www.parlamento.it' + href
                
                desc = title
                if href:
                    desc = f"{title}\nSource: {href}"
                
                if title:
                    output[title] = desc
except Exception:
    pass

print(json.dumps(output))
