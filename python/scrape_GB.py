#!/usr/bin/env python3
"""
Scrape UK Parliament bills from the official \"all bills\" RSS feed.

Prints a flat list: title, description, title, description, ... for each item.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = "https://bills.parliament.uk/rss/allbills.rss"
REQUEST_TIMEOUT = 30


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def fetch_bills_rss() -> list[str]:
    """Return flattened title/description pairs from the RSS feed."""
    _disable_insecure_request_warnings()
    response = requests.get(RSS_URL, timeout=REQUEST_TIMEOUT, verify=False)
    response.raise_for_status()

    soup = BeautifulSoup(response.content, features="xml")
    output: list[str] = []

    for item in soup.find_all("item"):
        title_el = item.find("title")
        link_el = item.find("link")
        desc_el = item.find("description")
        if not title_el or not desc_el:
            continue
        title = title_el.getText()
        desc = desc_el.getText().strip()
        output.append(title)
        output.append(desc)
        # Original also read link but only appended title and description.

    return output


def main() -> None:
    print(fetch_bills_rss())


if __name__ == "__main__":
    main()
