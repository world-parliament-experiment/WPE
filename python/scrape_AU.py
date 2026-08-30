#!/usr/bin/env python3
"""
Scrape Australian Parliament bill search result pages by bill id.

Walks aph.gov.au bill pages, keeps those with first-reading dates in roughly the
last 90 days, and prints JSON mapping title to summary plus page URL.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import datetime
import json
import time

import requests
import urllib3
from bs4 import BeautifulSoup

ROOT_URL = (
    "https://www.aph.gov.au/Parliamentary_Business/Bills_Legislation/"
    "Bills_Search_Results/Result?bId=r"
)
START_BILL_ID = 7350
MAX_ITEMS = 20
MAX_CONSECUTIVE_ERRORS = 10
REQUEST_TIMEOUT = 15
RECENT_DAYS = 90
REQUEST_DELAY_SEC = 0.5


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_aph_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        )
    }

    bill_id = START_BILL_ID
    consecutive_errors = 0
    stop = False

    while not stop and len(output) < MAX_ITEMS:
        url = f"{ROOT_URL}{bill_id}"
        try:
            response = requests.get(
                url,
                headers=headers,
                timeout=REQUEST_TIMEOUT,
                verify=False,
            )
        except requests.RequestException:
            consecutive_errors += 1
            if consecutive_errors >= MAX_CONSECUTIVE_ERRORS:
                stop = True
            bill_id += 1
            time.sleep(REQUEST_DELAY_SEC)
            continue

        if response.status_code == 404:
            consecutive_errors += 1
            bill_id += 1
            if consecutive_errors >= MAX_CONSECUTIVE_ERRORS:
                stop = True
            time.sleep(REQUEST_DELAY_SEC)
            continue

        if response.status_code != 200:
            consecutive_errors += 1
            bill_id += 1
            time.sleep(REQUEST_DELAY_SEC)
            continue

        soup = BeautifulSoup(response.text, "html.parser")
        header_div = soup.find("div", {"id": "main_0_billSummary_divHeader"})
        title = ""
        if header_div:
            h1 = header_div.find("h1")
            if h1:
                title = h1.get_text().strip()

        if not title:
            consecutive_errors += 1
            bill_id += 1
            time.sleep(REQUEST_DELAY_SEC)
            continue

        is_recent = False
        intro_tag = soup.find(
            string=lambda text: text and "Introduced and read a first time" in text
        )
        if intro_tag:
            parent_tr = intro_tag.find_parent("tr")
            if parent_tr:
                tds = parent_tr.find_all("td")
                if len(tds) > 1:
                    date_td = tds[1]
                    date_str = date_td.get_text().strip()
                    try:
                        intro_date = datetime.datetime.strptime(date_str, "%d %b %Y")
                        if intro_date >= cutoff:
                            is_recent = True
                    except ValueError:
                        pass

        if not is_recent:
            bill_id += 1
            consecutive_errors = 0
            time.sleep(REQUEST_DELAY_SEC)
            continue

        summary_panel = soup.find("div", {"id": "main_0_summaryPanel"})
        desc = ""
        if summary_panel:
            ps = summary_panel.find_all("p")
            if ps:
                desc = ps[0].get_text().strip()

        if desc:
            desc = desc + "\n" + url
            output[title] = desc
            consecutive_errors = 0

        bill_id += 1
        time.sleep(REQUEST_DELAY_SEC)

        if consecutive_errors >= MAX_CONSECUTIVE_ERRORS:
            stop = True

    return output


def main() -> None:
    print(json.dumps(scrape_aph_bills()))


if __name__ == "__main__":
    main()
