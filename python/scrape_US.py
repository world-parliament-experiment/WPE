#!/usr/bin/env python3
"""
Scrape titles and links from the U.S. Congress \"most viewed bills\" RSS feed.

Parses the first RSS item's embedded HTML list and prints a flat Python list
(alternating title strings and href strings) for stdout consumption.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import re

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = "https://www.congress.gov/rss/most-viewed-bills.xml"
REQUEST_TIMEOUT = 30


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def fetch_most_viewed_bills() -> list[str]:
    """Return alternating titles and link hrefs extracted from the RSS payload."""
    _disable_insecure_request_warnings()
    response = requests.get(RSS_URL, timeout=REQUEST_TIMEOUT, verify=False)
    response.raise_for_status()

    soup = BeautifulSoup(response.content, features="xml")
    first_item = soup.find("item")
    if not first_item or first_item.getText() is None:
        return []

    content = first_item.getText()
    output: list[str] = []

    for line in content.split("<li>"):
        title_match = re.search(r">(.+?)</li>", line)
        if title_match:
            title = title_match.group(1).replace("</a>", "")
            output.append(title)
        desc_match = re.search(r"href=(.+?)>", line)
        if desc_match:
            desc = desc_match.group(1).replace("'", "")
            output.append(desc)

    return output


def main() -> None:
    result: list[str] = fetch_most_viewed_bills()
    print(result)


if __name__ == "__main__":
    main()
