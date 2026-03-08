#!/usr/bin/env python
import json
import requests
from bs4 import BeautifulSoup
import warnings
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
# Current session 217 URL
url = "https://www.shugiin.go.jp/internet/itdb_gian.nsf/html/gian/kaiji217.htm"
base_url = "https://www.shugiin.go.jp/internet/itdb_gian.nsf/html/gian/"

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    response = requests.get(url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        # Site uses Shift_JIS
        content = response.content.decode('shift_jis', errors='replace')
        soup = BeautifulSoup(content, 'html.parser')
        
        # Tables with bills
        tables = soup.find_all("table", class_="table")
        
        for table in tables:
            caption = table.find("caption")
            category_type = caption.get_text().strip() if caption else "議案"
            
            rows = table.find_all("tr")
            for row in rows:
                cols = row.find_all("td")
                if len(cols) >= 3:
                    # Column indices: 0: Session, 1: Number, 2: Title, 3: Status, 4: History, 5: Text
                    session_num = cols[0].get_text().strip()
                    bill_num = cols[1].get_text().strip()
                    title_raw = cols[2].get_text().strip()
                    status = cols[3].get_text().strip() if len(cols) > 3 else ""
                    
                    # Full title for DB - very aggressive truncation for Japanese UTF-8 safety (3 bytes per char)
                    # 70 chars * 3 = 210 bytes, safely under 255
                    display_title = f"[{category_type}] {title_raw} ({bill_num})"
                    
                    if len(display_title) > 70:
                        display_title = display_title[:67] + "..."
                    
                    # Link to history (keika)
                    link_tag = cols[4].find("a") if len(cols) > 4 else None
                    if not link_tag and len(cols) > 5: # check text link
                        link_tag = cols[5].find("a")
                        
                    source_link = url
                    if link_tag:
                        href = link_tag.get("href")
                        if href:
                            if href.startswith("."):
                                source_link = base_url + href[2:]
                            elif not href.startswith("http"):
                                source_link = base_url + href
                            else:
                                source_link = href
                    
                    desc = f"{title_raw}\n\n"
                    desc += f"Category: {category_type}\n"
                    desc += f"Session: {session_num}\n"
                    desc += f"Number: {bill_num}\n"
                    desc += f"Status: {status}\n"
                    desc += f"Source: {source_link}"
                    
                    output[display_title] = desc

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
