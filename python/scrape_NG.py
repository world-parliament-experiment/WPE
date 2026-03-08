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

base_url = "https://nass.gov.ng"
header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    # 1. Fetch homepage to get most recent bills
    response = requests.get(base_url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Look for the Recent Bills section
        # They are in a ul with class 'testimonials-grid'
        bill_links = []
        recent_section = soup.find("h3", string=re.compile("Recent Bills", re.I))
        if recent_section:
            container = recent_section.find_parent("div", class_="nobottommargin")
            if container:
                links = container.find_all("a", href=re.compile(r"/documents/bill/\d+"))
                for link in links:
                    url = base_url + link.get("href")
                    if url not in bill_links:
                        bill_links.append(url)
        
        # Fallback: search all bill links if specific section not found
        if not bill_links:
            links = soup.find_all("a", href=re.compile(r"/documents/bill/\d+"))
            for link in links:
                url = base_url + link.get("href")
                if url not in bill_links:
                    bill_links.append(url)

        # 2. Fetch each bill detail (limit to 10 for performance)
        for bill_url in bill_links[:10]:
            try:
                det_resp = requests.get(bill_url, headers=header, timeout=10, verify=False)
                if det_resp.status_code == 200:
                    det_soup = BeautifulSoup(det_resp.content, 'html.parser')
                    
                    # Find title - it's usually in a <p><strong>...</strong></p> inside product-desc
                    title = ""
                    desc = ""
                    
                    product_desc = det_soup.find("div", class_="product-desc")
                    if product_desc:
                        strong_p = product_desc.find("p", recursive=False)
                        if strong_p:
                            title = strong_p.get_text().strip()
                        
                        # Metadata
                        meta = det_soup.find("div", class_="product-meta")
                        meta_text = meta.get_text(separator=" | ").strip() if meta else ""
                        
                        desc = f"{title}\n\nDetails: {meta_text}\nSource: {bill_url}"
                    
                    if not title:
                        # Fallback title from the link text in homepage if possible or just use a placeholder
                        title = "Nigerian Legislative Bill " + bill_url.split("/")[-1]
                    
                    # Clean up title
                    title = re.sub(r'\s+', ' ', title)
                    if len(title) > 250:
                        title = title[:247] + "..."
                    
                    output[title] = desc
            except:
                continue

except Exception:
    pass

# Fallback: if homepage failed, try the AJAX endpoint but it seems older
if not output:
    try:
        ajax_url = "https://nass.gov.ng/documents/bill_track/"
        resp = requests.get(ajax_url, headers=header, timeout=10, verify=False)
        data = resp.json()
        for item in data.get('data', [])[:10]:
            title = item[0].strip()
            bill_id = item[6]
            chamber = item[1]
            if len(title) > 250:
                title = title[:247] + "..."
            output[title] = f"{item[0]}\nChamber: {chamber}\nSource: {base_url}/documents/bill/{bill_id}"
    except:
        pass

print(json.dumps(output, ensure_ascii=False))
