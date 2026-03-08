#!/usr/bin/env python
import json
import requests
import datetime
import ssl
import sys

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

# Sejm API term 10 (started Nov 2023, active in 2026)
url = "https://api.sejm.gov.pl/sejm/term10/bills"
# We fetch the most recent batch (approx 1000 bills exist)
params = {
    "limit": 100,
    "offset": 900
}

header = {
    "accept": "application/json",
    "User-Agent": "Mozilla/5.0"
}

response = requests.get(url, params=params, headers=header, timeout=15)
if response.status_code == 200:
    data = response.json()
    # The list is usually chronological by receipt, but we check just in case
    for item in data:
        date_str = item.get('dateOfReceipt', '')
        if not date_str:
            continue
            
        try:
            item_date = datetime.datetime.strptime(date_str, "%Y-%m-%d")
            if item_date >= three_months_ago:
                title = item.get('title', '').strip()
                if not title:
                    continue
                
                # Bill number and status
                bill_nr = item.get('number', '')
                status = item.get('status', '')
                
                # Detailed description
                summary = item.get('description', '')
                
                # Official link to the bill's legislative process
                # Using the bill number or print number
                print_num = item.get('print', '')
                if print_num:
                    link = f"https://www.sejm.gov.pl/Sejm10.nsf/PrzebiegProc.xsp?nr={print_num}"
                else:
                    # Fallback to search if print not available yet
                    link = f"https://api.sejm.gov.pl/sejm/term10/bills/{item.get('number')}"

                desc = ""
                if summary:
                    desc = f"{summary}\n\n"
                
                desc += f"Status: {status}\nSource: {link}"
                
                # Prefix title with bill reference for clarity
                full_title = f"{bill_nr}: {title}"
                if len(full_title) > 250:
                    full_title = full_title[:247] + "..."
                    
                output[full_title] = desc
        except Exception:
            continue
else:
    sys.stderr.write(f"Error: API returned status code {response.status_code}\n")
    sys.exit(1)

print(json.dumps(output))
