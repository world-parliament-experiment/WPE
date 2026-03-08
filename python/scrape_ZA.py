#!/usr/bin/env python
import json
import requests
import warnings
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
api_url = "https://api.pmg.org.za/bill/"

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

try:
    response = requests.get(api_url, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        data = response.json()
        bills = data.get('results', [])
        
        for bill in bills:
            title = bill.get('title', '').strip()
            code = bill.get('code', '')
            if code:
                title = f"{title} ({code})"
            
            # Metadata for description
            year = bill.get('year')
            intro_date = bill.get('date_of_introduction')
            intro_by = bill.get('introduced_by')
            status = bill.get('status', {}).get('description') if bill.get('status') else "N/A"
            bill_type = bill.get('type', {}).get('description') if bill.get('type') else "N/A"
            
            # Source URL (API gives API URL, we want the website URL if possible)
            # PMG website URLs usually follow: https://pmg.org.za/bill/{id}/
            bill_id = bill.get('id')
            web_url = f"https://pmg.org.za/bill/{bill_id}/"
            
            if len(title) > 250:
                title = title[:247] + "..."
            
            desc = f"{bill.get('title')}\n\n"
            desc += f"Code: {code}\n"
            desc += f"Status: {status}\n"
            desc += f"Type: {bill_type}\n"
            desc += f"Introduced by: {intro_by} on {intro_date}\n"
            desc += f"Source: {web_url}"
            
            if title:
                output[title] = desc

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
