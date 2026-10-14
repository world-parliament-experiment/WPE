#!/usr/bin/env python3
"""
Scrape South Korea-related legislative news via Google News RSS.

Keyword-filters Korean headlines and prints JSON (UTF-8 preserved).

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup

RSS_URL = (
    "https://news.google.com/rss/search?q="
    "%EA%B5%AD%ED%9A%8C+%EB%B0%9C%EC%9D%98+%EB%B2%95%EC%95%88"
    "&hl=ko&gl=KR&ceid=KR:ko"
)
REQUEST_TIMEOUT = 15
MAX_TITLE_LEN = 250

KEYWORDS = ["국회", "발의", "법안", "개정안", "제정안", "의안"]


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_kr_news() -> dict[str, str]:
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

    soup = BeautifulSoup(response.content, features="xml")
    for item in soup.find_all("item"):
        title_el = item.find("title")
        link_el = item.find("link")
        pub_el = item.find("pubDate")
        if not title_el or not link_el:
            continue

        title = (title_el.text or "").strip()
        if not any(kw in title for kw in KEYWORDS):
            continue

        if " - " in title:
            title = title.rsplit(" - ", 1)[0]
        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        link = link_el.text or ""
        pub_date = pub_el.text if pub_el and pub_el.text else ""
        desc = f"{title}\n날짜: {pub_date}\nSource: {link}"
        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_kr_news(), ensure_ascii=False))


if __name__ == "__main__":
    main()
