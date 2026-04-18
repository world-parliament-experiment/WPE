#!/usr/bin/env python3
"""
Scrape Swedish Riksdag committee proposals from the public RSS document list.

Prints a flat list: title, link+date line, per item.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = (
    "https://data.riksdagen.se/dokumentlista/?avd=dokument&doktyp=bet&"
    "utskforslag=1&sort=debattdag&sortorder=asc&utformat=rss"
)
REQUEST_TIMEOUT = 30


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def fetch_riksdag_rss() -> list[str]:
    """Return flattened entries from the Riksdagen RSS feed."""
    _disable_insecure_request_warnings()
    response = requests.get(RSS_URL, timeout=REQUEST_TIMEOUT, verify=False)
    response.raise_for_status()

    soup = BeautifulSoup(response.content, features="xml")
    output: list[str] = []

    for item in soup.find_all("item"):
        title_el = item.find("title")
        link_el = item.find("link")
        desc_el = item.find("description")
        if not title_el or not link_el or not desc_el:
            continue
        title = title_el.getText()
        link = link_el.getText()
        description = desc_el.getText().strip()
        date = description[-10:]
        desc = link + "\n" + date
        output.append(title)
        output.append(desc)

    return output


def main() -> None:
    print(fetch_riksdag_rss())


if __name__ == "__main__":
    main()
