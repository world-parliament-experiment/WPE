#!/usr/bin/env python3
"""
Scrape Italian Chamber of Deputies bill list HTML for titles and detail links.

Parses law sections from the published index page and prints JSON mapping title
to description including an absolute source URL when available.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3
from bs4 import BeautifulSoup

LIST_URL = "https://www.parlamento.it/leg/ldl_new/v3/sldlelencodlconvers.htm"
SITE_ORIGIN = "https://www.parlamento.it"
REQUEST_TIMEOUT = 15
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_camera_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        )
    }

    try:
        response = requests.get(
            LIST_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
        if response.status_code != 200:
            return output
        soup = BeautifulSoup(response.content, "html.parser")
        for section in soup.find_all("dl", {"class": "leggi"}):
            titles = section.find_all("p", {"class": "titoloLegge"})
            links = section.find_all("dt")

            for idx, title_el in enumerate(titles):
                title = title_el.getText().strip().strip('"')
                if len(title) > MAX_TITLE_LEN:
                    title = title[: MAX_TITLE_LEN - 3] + "..."

                href = ""
                if idx < len(links):
                    a_tag = links[idx].find("a")
                    if a_tag:
                        href = a_tag.get("href", "") or ""

                if href and not href.startswith("http"):
                    href = SITE_ORIGIN + href

                desc = title
                if href:
                    desc = f"{title}\nSource: {href}"

                if title:
                    output[title] = desc
    except requests.RequestException:
        pass

    return output


def main() -> None:
    print(json.dumps(scrape_camera_bills()))


if __name__ == "__main__":
    main()
