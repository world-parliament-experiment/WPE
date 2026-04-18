#!/usr/bin/env python3
"""
Scrape recent Nigerian National Assembly bill pages (NASS).

Parses the homepage for bill links, follows a limited number of detail pages,
and falls back to a legacy JSON endpoint if needed. Prints JSON (UTF-8).

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json
import re

import requests
import urllib3
from bs4 import BeautifulSoup

BASE_URL = "https://nass.gov.ng"
REQUEST_TIMEOUT = 15
MAX_BILLS = 10
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_nass_bills() -> dict[str, str]:
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
            BASE_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        response = None

    bill_links: list[str] = []

    if response is not None and response.status_code == 200:
        soup = BeautifulSoup(response.content, "html.parser")
        recent_section = soup.find("h3", string=re.compile("Recent Bills", re.I))
        if recent_section:
            container = recent_section.find_parent("div", class_="nobottommargin")
            if container:
                for link in container.find_all("a", href=re.compile(r"/documents/bill/\d+")):
                    url = BASE_URL + link.get("href", "")
                    if url not in bill_links:
                        bill_links.append(url)

        if not bill_links:
            for link in soup.find_all("a", href=re.compile(r"/documents/bill/\d+")):
                url = BASE_URL + link.get("href", "")
                if url not in bill_links:
                    bill_links.append(url)

        for bill_url in bill_links[:MAX_BILLS]:
            try:
                det_resp = requests.get(
                    bill_url,
                    headers=headers,
                    timeout=10,
                    verify=False,
                )
            except requests.RequestException:
                continue

            if det_resp.status_code != 200:
                continue

            det_soup = BeautifulSoup(det_resp.content, "html.parser")
            title = ""
            desc = ""

            product_desc = det_soup.find("div", class_="product-desc")
            if product_desc:
                strong_p = product_desc.find("p", recursive=False)
                if strong_p:
                    title = strong_p.get_text().strip()

                meta = det_soup.find("div", class_="product-meta")
                meta_text = meta.get_text(separator=" | ").strip() if meta else ""
                desc = f"{title}\n\nDetails: {meta_text}\nSource: {bill_url}"

            if not title:
                title = "Nigerian Legislative Bill " + bill_url.split("/")[-1]

            title = re.sub(r"\s+", " ", title)
            if len(title) > MAX_TITLE_LEN:
                title = title[: MAX_TITLE_LEN - 3] + "..."

            output[title] = desc

    if output:
        return output

    try:
        ajax_url = "https://nass.gov.ng/documents/bill_track/"
        resp = requests.get(ajax_url, headers=headers, timeout=10, verify=False)
        data = resp.json()
    except (requests.RequestException, ValueError):
        return output

    for item in data.get("data", [])[:MAX_BILLS]:
        if not isinstance(item, (list, tuple)) or len(item) < 7:
            continue
        title = item[0].strip()
        bill_id = item[6]
        chamber = item[1]
        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."
        output[title] = f"{item[0]}\nChamber: {chamber}\nSource: {BASE_URL}/documents/bill/{bill_id}"

    return output


def main() -> None:
    print(json.dumps(scrape_nass_bills(), ensure_ascii=False))


if __name__ == "__main__":
    main()
