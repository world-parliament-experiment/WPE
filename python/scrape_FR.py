#!/usr/bin/env python3
"""
Scrape recent French National Assembly parliamentary documents from RSS.

Filters items to roughly the last 90 days by pubDate and prints a JSON object
mapping truncated title to a short description with source link.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import datetime
import json

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = "http://www2.assemblee-nationale.fr/feeds/detail/documents-parlementaires"
REQUEST_TIMEOUT = 15
RECENT_DAYS = 90
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_assemblee_documents() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    try:
        response = requests.get(
            RSS_URL,
            headers={"User-Agent": "Mozilla/5.0"},
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    soup = BeautifulSoup(response.content, features="xml")
    for item in soup.find_all("item"):
        pub_el = item.find("pubDate")
        if not pub_el or not pub_el.text:
            continue
        pub_date_str = pub_el.text
        try:
            date_clean = " ".join(pub_date_str.split()[1:4])
            item_date = datetime.datetime.strptime(date_clean, "%d %b %Y")
        except (ValueError, IndexError):
            continue

        if item_date < cutoff:
            continue

        title_el = item.find("title")
        if not title_el or not title_el.text:
            continue
        title = title_el.text.strip()
        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        link_el = item.find("link")
        link = link_el.text.strip() if link_el and link_el.text else ""
        desc = f"{title}\nSource: {link}"
        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_assemblee_documents()))


if __name__ == "__main__":
    main()
