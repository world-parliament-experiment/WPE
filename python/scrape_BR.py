#!/usr/bin/env python
import json
import requests
import datetime
import ssl

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

# Filter for PL (Projeto de Lei) and PEC (Proposta de Emenda à Constituição)
# We can fetch multiple pages if needed, but 50 results is usually enough for "last 3 months"
url = "https://dadosabertos.camara.leg.br/api/v2/proposicoes"
params = {
    "ano": today.year,
    "ordem": "DESC",
    "ordenarPor": "id",
    "itens": 50,
    "siglaTipo": ["PL", "PEC"]
}

header = {
    "accept": "application/json",
    "User-Agent": "Mozilla/5.0"
}

try:
    response = requests.get(url, params=params, headers=header, timeout=15)
    if response.status_code == 200:
        data = response.json()
        for item in data.get('dados', []):
            # Parse presentation date: "2026-03-06T18:39"
            date_str = item.get('dataApresentacao', '')
            try:
                # Using only the date part for comparison
                item_date = datetime.datetime.strptime(date_str.split('T')[0], "%Y-%m-%d")
                if item_date >= three_months_ago:
                    bill_ref = f"{item.get('siglaTipo')} {item.get('numero')}/{item.get('ano')}"
                    ementa = item.get('ementa', '').strip()
                    
                    # Create a descriptive title: Bill Ref + start of ementa
                    # Truncate ementa for title if it's too long
                    title_desc = ementa
                    if len(title_desc) > 150:
                        title_desc = title_desc[:147] + "..."
                    
                    title = f"{bill_ref}: {title_desc}"
                    
                    # Fix Link: The API provides 'uri' for data, but we want the public portal
                    # The most reliable public link is often the "Inteiro Teor" (Full Text) or the search detail
                    prop_id = item.get('id')
                    # This is the modern portal link format
                    link = f"https://www.camara.leg.br/propostas-legislativas/{prop_id}"
                    
                    desc = f"{ementa}\nSource: {link}"
                    output[title] = desc
            except Exception as e:
                continue
except Exception as e:
    pass

print(json.dumps(output))
