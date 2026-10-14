#!/usr/bin/env python3
"""
Scrape Indonesian DPR bill detail pages by numeric id.

Walks /uu/detail/id/{n}, collects up to 20 titles, and prints JSON. Stops after
too many consecutive failures (including non-200 responses).

Dependencies: beautifulsoup4, lxml (parser), requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup

DETAIL_URL_TEMPLATE = "https://www.dpr.go.id/uu/detail/id/{id}"
START_ID = 1300
MAX_ITEMS = 20
MAX_CONSECUTIVE_FAILURES = 10
REQUEST_TIMEOUT = 10


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_dpr_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        )
    }

    bill_id = START_ID
    consecutive_failures = 0
    stop = False

    while not stop and len(output) < MAX_ITEMS:
        url = DETAIL_URL_TEMPLATE.format(id=bill_id)
        try:
            response = requests.get(
                url,
                headers=headers,
                timeout=REQUEST_TIMEOUT,
                verify=False,
            )
        except requests.RequestException:
            consecutive_failures += 1
        else:
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, features="lxml")
                h3 = soup.find("h3")
                if h3:
                    title = h3.getText().strip()
                    if title:
                        output[title] = "Source: " + url
                        consecutive_failures = 0
                    else:
                        consecutive_failures += 1
                else:
                    consecutive_failures += 1
            else:
                consecutive_failures += 1

        if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
            stop = True
        bill_id += 1

    return output


def main() -> None:
    print(json.dumps(scrape_dpr_bills()))


if __name__ == "__main__":
    main()
