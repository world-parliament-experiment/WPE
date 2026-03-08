#!/usr/bin/env python
import json
import requests
from bs4 import BeautifulSoup
import warnings
import urllib3
import re

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
url = "https://www.psp.cz/rss/tisky.rss"
header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    response = requests.get(url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        # Decode from windows-1250/cp1250
        content_text = response.content.decode('cp1250', errors='replace')
        
        # Remove the XML declaration that says windows-1250 to avoid confusing parsers
        content_text = re.sub(r'<\?xml.*?\?>', '', content_text)
        
        soup = BeautifulSoup(content_text, "xml")
        items = soup.find_all("item")
        
        for item in items:
            title = item.find("title").get_text().strip() if item.find("title") else ""
            desc = item.find("description").get_text().strip() if item.find("description") else ""
            link = item.find("link").get_text().strip() if item.find("link") else ""
            
            # Filter for primary bills
            if "/0" in title or "Návrh" in title or "Novela" in title:
                if len(title) > 250:
                    title = title[:247] + "..."
                
                output[title] = f"{desc}\nSource: {link}"

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
