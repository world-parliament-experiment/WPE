#!/usr/bin/env python3
"""
Scrape Chile Cámara de Diputados promoted law projects listing.

Parses the HTML table until encountering a row older than one year, and prints
a flat list of title and description pairs.

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import ssl
import urllib.request
from datetime import datetime, timedelta

from bs4 import BeautifulSoup

LIST_URL = "https://www.camara.cl/legislacion/ProyectosDeLey/leyes_promulgadas.aspx"
DOC_BASE = "https://www.camara.cl/legislacion/ProyectosDeLey/"


def create_unverified_ssl_context() -> ssl.SSLContext:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def scrape_camara_cl_promulgadas() -> list[str]:
    ctx = create_unverified_ssl_context()
    with urllib.request.urlopen(LIST_URL, context=ctx) as response:
        html = response.read()

    soup = BeautifulSoup(html, "html.parser")
    section = soup.find("div", {"class": "grid-12 lista-proyectos aleft"})
    if not section:
        return []

    output: list[str] = []
    cutoff = datetime.now() - timedelta(days=365)

    for row in section.find_all("tr"):
        cells = row.find_all("td")
        contents: list[str] = []
        for col_idx, cell in enumerate(cells):
            anchor = cell.find("a")
            if anchor is not None and col_idx == 0:
                href = anchor.get("href")
                contents.append(href or "")
            else:
                parts = cell.getText().split("\n")
                contents.append(parts[0] if parts else "")

        if not contents or len(contents) < 6:
            continue

        title = contents[3].replace("\xa0", " ").replace("'", " ")
        link = DOC_BASE + contents[0]
        desc = contents[5] + " \n" + link
        desc = desc.replace("'", " ")

        try:
            row_date = datetime.strptime(contents[5], "%d-%m-%Y")
        except ValueError:
            continue

        if row_date < cutoff:
            break

        output.append(title)
        output.append(desc)

    return output


def main() -> None:
    print(scrape_camara_cl_promulgadas())


if __name__ == "__main__":
    main()
