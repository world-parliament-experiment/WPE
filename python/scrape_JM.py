#!/usr/bin/env python3
"""
Scrape Jamaica Parliament public bills listing.

Fetches the bills page, iterates list items under the article body, and prints
a flat list of title text and description (text plus document URL).

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import ssl
import urllib.request
from bs4 import BeautifulSoup

BASE_URL = "https://www.japarliament.gov.jm"
LIST_URL = f"{BASE_URL}/index.php/publications/bills/public-bills"


def create_unverified_ssl_context() -> ssl.SSLContext:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def scrape_jm_public_bills() -> list[str]:
    ctx = create_unverified_ssl_context()
    with urllib.request.urlopen(LIST_URL, context=ctx) as response:
        html = response.read()

    soup = BeautifulSoup(html, "html.parser")
    section = soup.find("div", {"itemprop": "articleBody"})
    if not section:
        return []

    output: list[str] = []
    for item in section.find_all("li"):
        title = item.getText().strip().replace("\n", " ")
        desc = item.getText().strip()
        link = item.find("a")
        if not link:
            continue
        href = link.get("href")
        if not href:
            continue
        href = BASE_URL + href
        href = href.replace(" ", "%20")
        desc = desc + "\n" + href
        output.append(title)
        output.append(desc)

    return output


def main() -> None:
    print(scrape_jm_public_bills())


if __name__ == "__main__":
    main()
