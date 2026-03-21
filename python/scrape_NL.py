#!/usr/bin/env python
import json
import requests
import datetime
import ssl
import urllib3

# Suppress InsecureRequestWarning
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

# Tweede Kamer OData API v2.0
# Filters for 'Wetgeving' (Government bills) and 'Initiatiefwetgeving' (Private member bills)
url = "https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0/Zaak"
params = {
    "$filter": "Soort eq 'Wetgeving' or Soort eq 'Initiatiefwetgeving'",
    "$expand": "Kamerstukdossier",
    "$orderby": "GestartOp desc",
    "$top": 50
}

header = {
    "accept": "application/json",
    "User-Agent": "Mozilla/5.0"
}

try:
    response = requests.get(url, params=params, headers=header, timeout=15, verify=False)
    if response.status_code == 200:
        data = response.json()
        for item in data.get('value', []):
            # GestartOp format: "2026-03-03T00:00:00+01:00"
            date_str = item.get('GestartOp', '')
            if not date_str:
                continue
                
            try:
                item_date = datetime.datetime.strptime(date_str.split('T')[0], "%Y-%m-%d")
                if item_date >= three_months_ago:
                    # Citeertitel is usually the short name, Titel is the full official name
                    short_title = item.get('Citeertitel')
                    full_title = item.get('Titel')
                    bill_nr = item.get('Nummer')
                    
                    title = short_title if short_title else full_title
                    if not title:
                        continue
                        
                    # Truncate if extremely long
                    if len(title) > 250:
                        title = title[:247] + "..."
                    
                    # Description
                    desc = full_title if full_title else title
                    
                    # Link to the case on the Tweede Kamer website
                    # New format uses the Kamerstukdossier number if available
                    dossier = item.get('Kamerstukdossier', [])
                    if dossier and isinstance(dossier, list) and len(dossier) > 0:
                        bill_nr_official = dossier[0].get('Nummer')
                        link = f"https://www.tweedekamer.nl/kamerstukken/wetsvoorstellen/detail?cfg=wetsvoorsteldetails&qry=wetsvoorstel%3A{bill_nr_official}"
                    else:
                        # Fallback to Nummer (e.g. 2025Z22479) which works with id parameter
                        link = f"https://www.tweedekamer.nl/kamerstukken/wetsvoorstellen/detail?id={item.get('Nummer')}"
                    
                    desc = f"{desc}\nSource: {link}"
                    output[title] = desc
            except Exception:
                continue
except Exception:
    pass

print(json.dumps(output))
