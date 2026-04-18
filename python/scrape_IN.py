#!/usr/bin/env python3
"""
Scrape recent Indian bill detail pages from PRS Legislative Research.

Loads the billtrack index, follows the first few bill links, extracts PDF and
status metadata, and prints JSON (UTF-8 preserved).

Dependencies: beautifulsoup4, requests, urllib3
"""

from __future__ import annotations

import json
import re

import requests
import urllib3
from bs4 import BeautifulSoup

BASE_URL = "https://prsindia.org"
LIST_URL = f"{BASE_URL}/billtrack"
REQUEST_TIMEOUT = 15
MAX_BILLS = 10
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_prs_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
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

    soup = BeautifulSoup(response.text, "html.parser")
    bill_items: list[dict[str, str]] = []

    for h3 in soup.find_all("h3"):
        anchor = h3.find("a", href=True)
        if not anchor or "/billtrack/" not in anchor["href"]:
            continue
        href = anchor["href"]
        if any(
            x in href
            for x in (
                "/category/",
                "/field_bill_category/",
                "/search",
                "/overview-",
            )
        ):
            continue
        title = anchor.get_text().strip()
        full_url = BASE_URL + href if href.startswith("/") else href
        bill_items.append({"title": title, "url": full_url})

    for item in bill_items[:MAX_BILLS]:
        try:
            detail = requests.get(
                item["url"],
                headers=headers,
                timeout=10,
                verify=False,
            )
        except requests.RequestException:
            continue

        if detail.status_code != 200:
            continue

        det_soup = BeautifulSoup(detail.text, "html.parser")
        pdf_link = ""
        pdf_tags = det_soup.find_all("a", href=re.compile(r"\.pdf$", re.I))
        for pt in pdf_tags:
            pt_text = pt.get_text().lower()
            if (
                "bill text" in pt_text
                or "introduced" in pt_text
                or "text of the bill" in pt_text
            ):
                pdf_link = pt.get("href") or ""
                break
        if not pdf_link and pdf_tags:
            pdf_link = pdf_tags[0].get("href") or ""

        if pdf_link.startswith("../"):
            pdf_link = BASE_URL + "/" + pdf_link.replace("../", "")
        elif pdf_link.startswith("/"):
            pdf_link = BASE_URL + pdf_link

        status = "N/A"
        status_div = det_soup.find("div", class_="field-bill-status")
        if status_div:
            status = status_div.get_text().replace("Status:", "").strip()

        title = item["title"]
        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        desc = f"{title}\n\n"
        desc += f"Status: {status}\n"
        if pdf_link:
            desc += f"Official Bill Text (PDF): {pdf_link}\n"
        desc += f"Source: {item['url']}"

        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_prs_bills(), ensure_ascii=False))


if __name__ == "__main__":
    main()
