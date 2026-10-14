#!/usr/bin/env python3
"""
Scrape Norwegian Storting representative proposals from the official RSS feed.

Prints a flat list alternating cleaned description text and item links.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import re

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = "https://www.stortinget.no/no/Stottemeny/RSS/Representantforslag/"
REQUEST_TIMEOUT = 30


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def fetch_representative_proposals() -> list[str]:
    """Return alternating title-like text and link for each RSS item."""
    _disable_insecure_request_warnings()
    response = requests.get(RSS_URL, timeout=REQUEST_TIMEOUT, verify=False)
    response.raise_for_status()

    soup = BeautifulSoup(response.content, features="xml")
    output: list[str] = []

    for item in soup.find_all("item"):
        desc_el = item.find("description")
        link_el = item.find("link")
        if not desc_el or not link_el:
            continue
        title = desc_el.getText()
        title = re.sub(r"(fra\b).*(?=\bom)", "", title)
        desc = link_el.getText()
        output.append(title)
        output.append(desc)

    return output


def main() -> None:
    print(fetch_representative_proposals())


if __name__ == "__main__":
    main()
