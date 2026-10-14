#!/usr/bin/env python3
"""
Scrape UN Security Council adopted resolutions listings (alternate TLS path).

Same parsing logic as scrape_unsc.py but always passes an explicit SSL context
to urllib when opening year index pages.

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import datetime
import ssl
import urllib.error
import urllib.request

from bs4 import BeautifulSoup

YEARS_TO_FETCH = 2
LIST_PATH = (
    "https://www.un.org/securitycouncil/content/resolutions-adopted-security-council-"
)


def create_unverified_ssl_context() -> ssl.SSLContext:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def fetch_year_page(year: int, ssl_context: ssl.SSLContext) -> bytes | None:
    url = f"{LIST_PATH}{year}"
    try:
        with urllib.request.urlopen(url, context=ssl_context) as response:
            return response.read()
    except urllib.error.HTTPError as exc:
        if exc.code == 404:
            return None
        raise


def parse_resolution_rows(html: bytes) -> list[str]:
    soup = BeautifulSoup(html, "html.parser")
    section = soup.find("div", {"class": "field-items"})
    if not section:
        return []

    output: list[str] = []
    for row in section.find_all("tr"):
        cells = row.find_all("td")
        contents: list[str] = []
        for col_idx, cell in enumerate(cells):
            anchor = cell.find("a")
            if anchor is not None and col_idx == 0:
                contents.append(cell.getText().strip())
                contents.append(anchor.get("href") or "")
            else:
                parts = cell.getText().split("\n")
                contents.append(parts[0] if parts else "")

        contents.reverse()
        if not contents:
            continue

        title = contents[0].replace("\xa0", " ").replace("'", " ")
        title = contents[3] + " - " + title
        desc = contents[0] + " \n" + contents[2] + " \n" + contents[1]
        desc = desc.replace("'", " ")
        output.append(title)
        output.append(desc)

    return output


def scrape_unsc_resolutions() -> list[str]:
    ctx = create_unverified_ssl_context()
    today = datetime.datetime.now()
    combined: list[str] = []

    for offset in range(YEARS_TO_FETCH):
        year = today.year + offset
        html = fetch_year_page(year, ctx)
        if html is None:
            continue
        combined.extend(parse_resolution_rows(html))

    return combined


def main() -> None:
    print(scrape_unsc_resolutions())


if __name__ == "__main__":
    main()
