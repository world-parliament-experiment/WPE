#!/usr/bin/env python3
"""
Scrape recent Turkish Grand National Assembly incoming papers list.

Follows a few list rows into detail pages, regex-extracts proposal lines, and
prints JSON mapping a short title to full line plus source URL.

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json
import re

import requests
import urllib3
from bs4 import BeautifulSoup

BASE_URL = "https://tbmm.gov.tr"
LIST_URL = f"{BASE_URL}/Gundem/GelenKagitlarListe"
REQUEST_TIMEOUT = 15
MAX_LIST_ROWS = 5
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_tbmm_gundem() -> dict[str, str]:
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
            LIST_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    soup = BeautifulSoup(response.content, "html.parser")
    table = soup.find("table")
    if not table:
        return output

    for row in table.find_all("tr")[:MAX_LIST_ROWS]:
        link_tag = row.find("a")
        if not link_tag:
            continue
        href = link_tag.get("href")
        if not href:
            continue
        detail_url = BASE_URL + href

        try:
            det_resp = requests.get(
                detail_url,
                headers=headers,
                timeout=10,
                verify=False,
            )
        except requests.RequestException:
            continue

        if det_resp.status_code != 200:
            continue

        det_soup = BeautifulSoup(det_resp.content, "html.parser")
        text = det_soup.get_text()
        text = text.replace("\xa0", " ")
        text = re.sub(r"\s+", " ", text)

        for match in re.finditer(r"(\d+\.- .*?; (.*?) \((2/\d+)\))", text):
            full_match = match.group(1).strip()
            title_core = match.group(2).strip()
            proposal_id = match.group(3).strip()
            title = f"{title_core} {proposal_id}"
            if len(title) > MAX_TITLE_LEN:
                title = title[: MAX_TITLE_LEN - 3] + "..."
            desc = f"{full_match}\nSource: {detail_url}"
            output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_tbmm_gundem()))


if __name__ == "__main__":
    main()
