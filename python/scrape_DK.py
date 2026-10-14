#!/usr/bin/env python3
"""
Scrape Danish Folketinget (Ft.dk) bill index pages by bill number.

Determines session year from the calendar (Sep–May session), walks bill ids,
and prints a flat list of title and index URL pairs.

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import datetime
import ssl
import urllib.error
import urllib.request
from bs4 import BeautifulSoup

START_BILL = 50
MAX_CONSECUTIVE_FAILURES = 5
SAMPLING_PREFIX = "https://www.ft.dk/samling/"


def create_unverified_ssl_context() -> ssl.SSLContext:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def session_year(today: datetime.date | None = None) -> int:
    """Return Folketinget session start year (Sep–May cycle)."""
    today = today or datetime.date.today()
    if today.month <= 8:
        return today.year - 1
    return today.year


def bill_url(year: int, bill_num: int) -> str:
    return f"{SAMPLING_PREFIX}{year}1/lovforslag/l{bill_num}/index.htm"


def scrape_ft_bills() -> list[str]:
    ctx = create_unverified_ssl_context()
    year = session_year()
    output: list[str] = []
    bill_num = START_BILL
    consecutive_failures = 0
    stop = False

    while not stop:
        url = bill_url(year, bill_num)
        try:
            with urllib.request.urlopen(url, context=ctx) as response:
                html = response.read()
        except urllib.error.HTTPError:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        soup = BeautifulSoup(html, "html.parser")
        section = soup.find("div", {"class": "tingdok"})
        if not section:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        heading = soup.find("h1", {"class": "tingdok-heading"})
        if not heading:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        title = heading.getText().strip()
        desc = bill_url(year, bill_num)
        output.append(title)
        output.append(desc)
        bill_num += 1

    return output


def main() -> None:
    print(scrape_ft_bills())


if __name__ == "__main__":
    main()
