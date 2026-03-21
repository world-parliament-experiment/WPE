#!/usr/bin/env python3
import requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
from bs4 import BeautifulSoup
import datetime

today = datetime.datetime.now()
output = []

# Latest session is 61 (Feb-Mar 2026), starting from 49 (new format)
session = 49
max_session = 62 # To avoid infinite loops

while session < max_session:
    url = f"https://www.ohchr.org/en/hr-bodies/hrc/regular-sessions/session{session}/res-dec-stat"
    headers = {
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language': 'en-US,en;q=0.9',
        'Accept-Encoding': 'gzip, deflate, br',
        'DNT': '1',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Fetch-Dest': 'document',
        'Sec-Fetch-Mode': 'navigate',
        'Sec-Fetch-Site': 'none',
        'Sec-Fetch-User': '?1',
        'Cache-Control': 'max-age=0',
    }
    try:
        session_obj = requests.Session()
        html = session_obj.get(url, verify=False, timeout=15, headers=headers)
        if html.status_code == 404:
            break
        html.raise_for_status()
    except requests.exceptions.RequestException as e:
        break

    soup = BeautifulSoup(html.text, 'html.parser')
    # The new site uses views-table class
    table = soup.find("table")
    
    if table:
        rows = table.find_all('tr')
        for row in rows:
            cells = row.find_all('td')
            if len(cells) >= 2:
                # First cell: Adopted Text (Number and Link)
                adopted_text_cell = cells[0]
                link_tag = adopted_text_cell.find('a')
                if not link_tag:
                    continue
                
                number = link_tag.get_text(strip=True)
                link = link_tag.get('href')
                if link and not link.startswith('http'):
                    link = 'https://www.ohchr.org' + link

                # Second cell: Title
                title = cells[1].get_text(strip=True)
                
                # Full Title
                full_title = f"HRC {session}/{number} - {title}"
                full_title = full_title[:255]
                
                # Description (including action taken if available)
                action = ""
                if len(cells) >= 5:
                    action = cells[4].get_text(strip=True)
                
                desc = f"{title}\nAdopted: {action}\nSource: {link}"
                
                output.append(full_title)
                output.append(desc)
        
        session += 1
    else:
        # No table found, might be the end or a session with no resolutions yet
        break

print(output)
