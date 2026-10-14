#!/usr/bin/env python3
"""
Scrape Canadian Parliament bill metadata from the LegisINFO XML feed.

Builds a JSON object keyed by short title (falling back to long title) with
description including the long title and public bill URL.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup, Tag

XML_URL = "https://www.parl.ca/legisinfo/en/bills/xml"
REQUEST_TIMEOUT = 30


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def _text(el: Tag | None) -> str:
    return el.getText() if el else ""


def scrape_parl_ca_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}

    response = requests.get(XML_URL, timeout=REQUEST_TIMEOUT, verify=False)
    response.raise_for_status()

    soup = BeautifulSoup(response.content, features="xml")
    for bill in soup.find_all("Bill"):
        title = _text(bill.find("ShortTitleEn"))
        desc = _text(bill.find("LongTitleEn"))
        session = _text(bill.find("ParlSessionCode"))
        code = _text(bill.find("BillNumberFormatted"))
        link = f"https://www.parl.ca/legisinfo/en/bill/{session}/{code}"

        if title == "\n":
            title = desc

        desc = desc + "\n" + link
        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_parl_ca_bills()))


if __name__ == "__main__":
    main()
