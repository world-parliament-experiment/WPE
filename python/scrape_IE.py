#!/usr/bin/env python3
"""
Scrape Irish Oireachtas bill pages for the current calendar year.

Walks /en/bills/bill/{year}/{n}/ until consecutive HTTP failures, and prints
a flat list of title and description (long title plus bill URL).

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import datetime
import ssl
import urllib.error
import urllib.request
from bs4 import BeautifulSoup

MAX_CONSECUTIVE_FAILURES = 5
BILL_URL_TEMPLATE = "https://www.oireachtas.ie/en/bills/bill/{year}/{num}/"


def create_unverified_ssl_context() -> ssl.SSLContext:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def scrape_oireachtas_bills() -> list[str]:
    ctx = create_unverified_ssl_context()
    year = datetime.date.today().year
    output: list[str] = []
    bill_num = 1
    consecutive_failures = 0
    stop = False

    while not stop:
        url = BILL_URL_TEMPLATE.format(year=year, num=bill_num)
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
        hero = soup.find("div", {"class": "c-hero__content"})
        if not hero:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        title = hero.getText().strip()
        if not title:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        title = title.replace("'", "&#39;").replace("\n", " ")

        desc_el = soup.find("p", {"class": "c-bill-intro__long-title"})
        if not desc_el:
            consecutive_failures += 1
            bill_num += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        desc = desc_el.getText().strip().replace("'", "&#39;")
        desc = desc + "\n" + url

        output.append(title)
        output.append(desc)
        bill_num += 1

    return output


def main() -> None:
    print(scrape_oireachtas_bills())


if __name__ == "__main__":
    main()
