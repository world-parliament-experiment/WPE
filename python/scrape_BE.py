#!/usr/bin/env python
import json
import requests
import datetime
import re
import urllib3
from bs4 import BeautifulSoup

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

HEADER = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
}

# Belgian Chamber of Representatives (Chambre/Kamer) legislature 56 (2024-)
# Only include original bill submissions, not amendments/reports
BILL_TYPES = {"PROPOSITION DE LOI", "PROJET DE LOI"}

seen_dossiers = set()


def build_dossier_url(dossier_id):
    return (
        "https://www.lachambre.be/kvvcr/showpage.cfm"
        "?section=/flwb&language=fr"
        f"&cfm=/site/wwwcfm/flwb/flwbn.cfm?legislat=56&dossierID={dossier_id}"
    )


def parse_date(date_str):
    try:
        return datetime.datetime.strptime(date_str.strip(), "%d/%m/%Y")
    except ValueError:
        return None


# week=1 is the most recent week, week=2 the previous one, etc.
# 13 weeks covers approximately 3 months.
for week in range(1, 14):
    url = (
        "https://www.lachambre.be/kvvcr/showpage.cfm"
        "?section=/flwb/recent&language=fr"
        f"&cfm=/site/wwwcfm/flwb/rapweekweekly.cfm?week={week}"
    )

    try:
        response = requests.get(url, headers=HEADER, timeout=15, verify=False)
        if response.status_code != 200:
            continue

        soup = BeautifulSoup(response.content, "html.parser")
        divs = soup.find_all("div", class_="linklist_1")

        if not divs:
            break

        oldest_date_in_page = None

        for div in divs:
            div_html = div.decode_contents()

            # Split by <br> tags to get individual lines within the entry.
            # Line 0 is the title; subsequent lines are "seq Date : dd/mm/yyyy TYPE"
            lines = re.split(r"<[Bb][Rr]\s*/?>", div_html)

            title = None
            for line in lines:
                line_text = BeautifulSoup(line, "html.parser").get_text(" ").strip()
                line_text = re.sub(r"\s+", " ", line_text).strip()

                if not line_text:
                    continue

                if title is None:
                    # First non-empty line is the document title
                    title = line_text.rstrip(".")
                    continue

                # Subsequent lines: "NNN  Date  :  dd/mm/yyyy  DOC_TYPE"
                # Extract dossier ID from the original HTML line (before text conversion)
                docn_match = re.search(
                    r"docn=(\d{2}K(\d+)\d{3})", line, re.IGNORECASE
                )
                if not docn_match:
                    continue

                dossier_id = docn_match.group(2)

                # Parse date and document type from the text representation
                date_type_match = re.search(
                    r"Date\s*:\s*(\d{2}/\d{2}/\d{4})\s+([\w][\w\s()/']*)",
                    line_text,
                )
                if not date_type_match:
                    continue

                date_str = date_type_match.group(1)
                doc_type = date_type_match.group(2).strip()

                item_date = parse_date(date_str)
                if item_date is None:
                    continue

                if oldest_date_in_page is None or item_date < oldest_date_in_page:
                    oldest_date_in_page = item_date

                if item_date < three_months_ago:
                    continue

                if doc_type not in BILL_TYPES:
                    continue

                if dossier_id in seen_dossiers:
                    continue

                seen_dossiers.add(dossier_id)

                dossier_url = build_dossier_url(dossier_id)

                if len(title) > 250:
                    title = title[:247] + "..."

                output[title] = f"{title}\nSource: {dossier_url}"

        # Stop iterating weeks once all documents in a page are older than 3 months
        if oldest_date_in_page and oldest_date_in_page < three_months_ago:
            break

    except Exception:
        continue

print(json.dumps(output))
