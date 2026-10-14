#!/usr/bin/env python3
"""
Scrape Mexico-related legislative news via Google News RSS (primary + fallback).

Keyword-filters items for relevance, normalizes titles, and prints JSON
(UTF-8 preserved).

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup

PRIMARY_RSS = (
    "https://news.google.com/rss/search?q=iniciativas+ley+mexico+congreso+gaceta"
    "&hl=es-419&gl=MX&ceid=MX:es-419"
)
FALLBACK_RSS = (
    "https://news.google.com/rss/search?q=gaceta+parlamentaria+mexico+iniciativas"
    "&hl=es-419&gl=MX&ceid=MX:es-419"
)
REQUEST_TIMEOUT = 15
MAX_TITLE_LEN = 250

KEYWORDS = [
    "iniciativa",
    "ley",
    "congreso",
    "senado",
    "diputados",
    "gaceta",
    "reforma",
    "decreto",
]


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def _normalize_google_title(raw: str) -> str:
    title = raw.strip()
    if " - " in title:
        title = title.rsplit(" - ", 1)[0]
    if len(title) > MAX_TITLE_LEN:
        title = title[: MAX_TITLE_LEN - 3] + "..."
    return title


def _fetch_rss_items(url: str, headers: dict[str, str]) -> list:
    response = requests.get(url, headers=headers, timeout=REQUEST_TIMEOUT, verify=False)
    if response.status_code != 200:
        return []
    soup = BeautifulSoup(response.content, features="xml")
    return soup.find_all("item")


def scrape_mx_news() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        )
    }

    try:
        for item in _fetch_rss_items(PRIMARY_RSS, headers):
            title_el = item.find("title")
            link_el = item.find("link")
            pub_el = item.find("pubDate")
            if not title_el or not link_el:
                continue
            title_raw = title_el.text or ""
            link = link_el.text or ""
            pub_date = pub_el.text if pub_el and pub_el.text else ""

            title = title_raw.strip()
            if not any(kw in title.lower() for kw in KEYWORDS):
                continue

            title = _normalize_google_title(title)
            desc = f"{title}\nFecha: {pub_date}\nSource: {link}"
            output[title] = desc
    except requests.RequestException:
        pass

    if output:
        return output

    try:
        for item in _fetch_rss_items(FALLBACK_RSS, headers):
            title_el = item.find("title")
            link_el = item.find("link")
            if not title_el or not link_el:
                continue
            title = _normalize_google_title(title_el.text or "")
            link = link_el.text or ""
            output[title] = f"{title}\nSource: {link}"
    except requests.RequestException:
        pass

    return output


def main() -> None:
    print(json.dumps(scrape_mx_news(), ensure_ascii=False))


if __name__ == "__main__":
    main()
