#!/usr/bin/env python3
"""
Scrape Japanese House of Representatives (Shugiin) bill tables for a session.

Decodes Shift_JIS HTML, walks table rows, and prints JSON keyed by a short display
title with category, session, status, and source link.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup

SESSION_URL = "https://www.shugiin.go.jp/internet/itdb_gian.nsf/html/gian/kaiji217.htm"
BASE_URL = "https://www.shugiin.go.jp/internet/itdb_gian.nsf/html/gian/"
REQUEST_TIMEOUT = 15


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_shugiin_gian() -> dict[str, str]:
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
            SESSION_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    content = response.content.decode("shift_jis", errors="replace")
    soup = BeautifulSoup(content, "html.parser")

    for table in soup.find_all("table", class_="table"):
        caption = table.find("caption")
        category_type = caption.get_text().strip() if caption else "議案"

        for row in table.find_all("tr"):
            cols = row.find_all("td")
            if len(cols) < 3:
                continue

            session_num = cols[0].get_text().strip()
            bill_num = cols[1].get_text().strip()
            title_raw = cols[2].get_text().strip()
            status = cols[3].get_text().strip() if len(cols) > 3 else ""

            display_title = f"[{category_type}] {title_raw} ({bill_num})"
            if len(display_title) > 70:
                display_title = display_title[:67] + "..."

            link_tag = cols[4].find("a") if len(cols) > 4 else None
            if not link_tag and len(cols) > 5:
                link_tag = cols[5].find("a")

            source_link = SESSION_URL
            if link_tag:
                href = link_tag.get("href")
                if href:
                    if href.startswith("."):
                        source_link = BASE_URL + href[2:]
                    elif not href.startswith("http"):
                        source_link = BASE_URL + href
                    else:
                        source_link = href

            desc = f"{title_raw}\n\n"
            desc += f"Category: {category_type}\n"
            desc += f"Session: {session_num}\n"
            desc += f"Number: {bill_num}\n"
            desc += f"Status: {status}\n"
            desc += f"Source: {source_link}"

            output[display_title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_shugiin_gian(), ensure_ascii=False))


if __name__ == "__main__":
    main()
