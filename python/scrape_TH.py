#!/usr/bin/env python
import json
import requests
import warnings
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
warnings.filterwarnings("ignore", category=urllib3.exceptions.InsecureRequestWarning)

output = {}
api_url = "https://politigraph.wevis.info/graphql"

# Query for the 30 most recent bills (ordered by their database ID or relevant sequence)
query = """
{
  bills(limit: 30) {
    id
    title
    status
    proposal_date
    lis_id
  }
}
"""

try:
    response = requests.post(api_url, json={'query': query}, timeout=15)
    if response.status_code == 200:
        data = response.json()
        bills = data.get('data', {}).get('bills', [])
        
        for bill in bills:
            title_raw = bill.get('title', '').strip()
            title_clean = title_raw.replace("พ.ศ. ....", "").strip()
            
            lis_id = bill.get('lis_id')
            status = bill.get('status', 'N/A')
            proposal_date = bill.get('proposal_date', 'N/A')
            
            # Official Parliament Section 77 URL
            official_url = f"https://www.parliament.go.th/section77/survey_detail.php?id={lis_id}" if lis_id else ""
            
            display_title = title_clean
            if len(display_title) > 70:
                display_title = display_title[:67] + "..."
            
            desc = f"{title_raw}\n\n"
            desc += f"Status: {status}\n"
            desc += f"Proposed date: {proposal_date}\n"
            if official_url:
                desc += f"Source: {official_url}"
            
            if display_title:
                output[display_title] = desc

except Exception:
    pass

print(json.dumps(output, ensure_ascii=False))
