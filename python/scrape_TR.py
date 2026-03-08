#!/usr/bin/env python
import json
import requests
import re
from bs4 import BeautifulSoup
import warnings
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
base_url = "https://tbmm.gov.tr"
list_url = f"{base_url}/Gundem/GelenKagitlarListe"

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    response = requests.get(list_url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        soup = BeautifulSoup(response.content, 'html.parser')
        table = soup.find("table")
        if table:
            # Most recent 5 pages
            rows = table.find_all("tr")[:5]
            for row in rows:
                link_tag = row.find("a")
                if link_tag:
                    detail_url = base_url + link_tag.get("href")
                    det_resp = requests.get(detail_url, headers=header, timeout=10, verify=False)
                    if det_resp.status_code == 200:
                        det_soup = BeautifulSoup(det_resp.content, 'html.parser')
                        text = det_soup.get_text()
                        
                        # Normalize spaces and newlines
                        text = text.replace('\xa0', ' ')
                        text = re.sub(r'\s+', ' ', text)
                        
                        # Search for proposals
                        # 1.- Düzce Milletvekili ...; Sosyal Hizmetler Kanunu ... (2/3566)
                        # The pattern is: digit.- ... ; Title (2/digit)
                        matches = re.finditer(r'(\d+\.- .*?; (.*?) \((2/\d+)\))', text)
                        
                        for m in matches:
                            full_match = m.group(1).strip()
                            title_core = m.group(2).strip()
                            proposal_id = m.group(3).strip()
                            
                            title = f"{title_core} {proposal_id}"
                            
                            if len(title) > 250:
                                title = title[:247] + "..."
                            
                            desc = f"{full_match}\nSource: {detail_url}"
                            output[title] = desc

except Exception:
    pass

print(json.dumps(output))
