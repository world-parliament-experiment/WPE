#!/usr/bin/env python3
"""
Scrape UN Human Rights Council session resolution tables from ohchr.org.

Iterates session numbers until a page returns 404 or lacks a table, and prints a
flat list of resolution titles and descriptions.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import requests
import urllib3
from bs4 import BeautifulSoup

SESSION_START = 49
SESSION_LIMIT = 62
REQUEST_TIMEOUT = 15


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def session_url(session_num: int) -> str:
    return (
        "https://www.ohchr.org/en/hr-bodies/hrc/regular-sessions/"
        f"session{session_num}/res-dec-stat"
    )


def scrape_hrc_resolutions() -> list[str]:
    _disable_insecure_request_warnings()
    output: list[str] = []
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ),
        "Accept": (
            "text/html,application/xhtml+xml,application/xml;q=0.9,"
            "image/avif,image/webp,image/apng,*/*;q=0.8,"
            "application/signed-exchange;v=b3;q=0.7"
        ),
        "Accept-Language": "en-US,en;q=0.9",
        "Accept-Encoding": "gzip, deflate, br",
        "DNT": "1",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Sec-Fetch-User": "?1",
        "Cache-Control": "max-age=0",
    }

    session_num = SESSION_START
    client = requests.Session()

    while session_num < SESSION_LIMIT:
        url = session_url(session_num)
        try:
            html = client.get(url, verify=False, timeout=REQUEST_TIMEOUT, headers=headers)
        except requests.exceptions.RequestException:
            break

        if html.status_code == 404:
            break
        html.raise_for_status()

        soup = BeautifulSoup(html.text, "html.parser")
        table = soup.find("table")
        if not table:
            break

        for row in table.find_all("tr"):
            cells = row.find_all("td")
            if len(cells) < 2:
                continue

            adopted_cell = cells[0]
            link_tag = adopted_cell.find("a")
            if not link_tag:
                continue

            number = link_tag.get_text(strip=True)
            link = link_tag.get("href") or ""
            if link and not link.startswith("http"):
                link = "https://www.ohchr.org" + link

            title = cells[1].get_text(strip=True)
            full_title = f"HRC {session_num}/{number} - {title}"[:255]

            action = ""
            if len(cells) >= 5:
                action = cells[4].get_text(strip=True)

            desc = f"{title}\nAdopted: {action}\nSource: {link}"
            output.append(full_title)
            output.append(desc)

        session_num += 1

    return output


def main() -> None:
    print(scrape_hrc_resolutions())


if __name__ == "__main__":
    main()
