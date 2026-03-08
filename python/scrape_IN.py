#!/usr/bin/env python
import json
import requests
from bs4 import BeautifulSoup
import warnings
import urllib3
import datetime
import re

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
base_url = "https://prsindia.org"
list_url = f"{base_url}/billtrack"

header = {
    "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    response = requests.get(list_url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Find all bill entries
        # They usually have titles in h3 > a
        bill_items = []
        for h3 in soup.find_all("h3"):
            a = h3.find("a", href=True)
            if a and "/billtrack/" in a['href']:
                href = a['href']
                if any(x in href for x in ["/category/", "/field_bill_category/", "/search", "/overview-"]):
                    continue
                
                title = a.get_text().strip()
                full_url = base_url + href if href.startswith("/") else href
                bill_items.append({"title": title, "url": full_url})

        # Process the 10 most recent bills
        for item in bill_items[:10]:
            try:
                bill_url = item["url"]
                title = item["title"]
                
                det_resp = requests.get(bill_url, headers=header, timeout=10, verify=False)
                if det_resp.status_code == 200:
                    det_soup = BeautifulSoup(det_resp.text, 'html.parser')
                    
                    pdf_link = ""
                    # PRS detail pages have links to PDF files
                    # Look for links that contain "Bill Text" or "Introduced"
                    pdf_tags = det_soup.find_all("a", href=re.compile(r"\.pdf$", re.I))
                    for pt in pdf_tags:
                        pt_text = pt.get_text().lower()
                        if "bill text" in pt_text or "introduced" in pt_text or "text of the bill" in pt_text:
                            pdf_link = pt.get("href")
                            break
                    
                    if not pdf_link and pdf_tags:
                        pdf_link = pdf_tags[0].get("href")
                    
                    if pdf_link:
                        if pdf_link.startswith("../"):
                            # Handle PRS relative path ../files/...
                            pdf_link = base_url + "/" + pdf_link.replace("../", "")
                        elif pdf_link.startswith("/"):
                            pdf_link = base_url + pdf_link

                    # Metadata: Status
                    status = "N/A"
                    status_div = det_soup.find("div", class_="field-bill-status")
                    if status_div:
                        status = status_div.get_text().replace("Status:", "").strip()
                    
                    if len(title) > 250:
                        title = title[:247] + "..."
                    
                    desc = f"{title}\n\n"
                    desc += f"Status: {status}\n"
                    if pdf_link:
                        desc += f"Official Bill Text (PDF): {pdf_link}\n"
                    desc += f"Source: {bill_url}"
                    
                    output[title] = desc
            except:
                continue

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
