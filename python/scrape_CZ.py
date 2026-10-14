#!/usr/bin/env python3
"""
Scrape Czech Chamber of Deputies print (tisk) RSS, decoded as Windows-1250.

Filters items heuristically for primary bills and prints JSON (UTF-8 preserved).

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json
import re

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = "https://www.psp.cz/rss/tisky.rss"
REQUEST_TIMEOUT = 15
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_psp_rss() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        )
    }

    try:
        response = requests.get(
            RSS_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    content_text = response.content.decode("cp1250", errors="replace")
    content_text = re.sub(r"<\?xml.*?\?>", "", content_text)

    soup = BeautifulSoup(content_text, "xml")
    for item in soup.find_all("item"):
        title_el = item.find("title")
        desc_el = item.find("description")
        link_el = item.find("link")
        title = title_el.get_text().strip() if title_el else ""
        desc = desc_el.get_text().strip() if desc_el else ""
        link = link_el.get_text().strip() if link_el else ""

        if "/0" not in title and "Návrh" not in title and "Novela" not in title:
            continue

        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        output[title] = f"{desc}\nSource: {link}"

    return output


def main() -> None:
    print(json.dumps(scrape_psp_rss(), ensure_ascii=False))


if __name__ == "__main__":
    main()
