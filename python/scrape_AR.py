#!/usr/bin/env python3
"""
Scrape voting act summaries from the Argentine Senate (Senado de la Nación).

Walks numeric act detail IDs on https://www.senado.gob.ar/votaciones/detalleActa/{id},
parses each page for title, description, and the first linked document URL, then
prints a JSON object mapping title to multiline description (stdout).

Dependencies: beautifulsoup4 (see https://pypi.org/project/beautifulsoup4/)
"""

from __future__ import annotations

import json
import ssl
import urllib.error
import urllib.request
from bs4 import BeautifulSoup

# Approximate ID near start of 2026 session window; script walks upward from here.
START_ACTA_ID = 2600
MAX_ITEMS = 20
MAX_CONSECUTIVE_FAILURES = 10

ROOT_URL = "https://www.senado.gob.ar"
DETALLE_ACTA_PATH = "/votaciones/detalleActa/"


def create_unverified_ssl_context() -> ssl.SSLContext:
    """SSL context that skips certificate verification.

    Some environments fail to verify this host against the local trust store.
    Skipping verification weakens protection against MITM; use only for this
    known public scrape if you accept that tradeoff.
    """
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def fetch_html(url: str, ssl_context: ssl.SSLContext) -> bytes | None:
    """GET *url* and return response body, or None on HTTP 404."""
    try:
        with urllib.request.urlopen(url, context=ssl_context) as response:
            return response.read()
    except urllib.error.HTTPError as exc:
        if exc.code == 404:
            return None
        raise


def _normalize_whitespace(text: str) -> str:
    return " ".join(text.split())


def parse_acta_entry(soup: BeautifulSoup, root_url: str) -> tuple[str, str] | None:
    """Extract (title, description) from an act detail page, or None if not parseable."""
    section = soup.find("div", {"class": "col-lg-6 col-sm-6"})
    if not section:
        return None

    paragraphs = section.find_all("p")
    if len(paragraphs) <= 1:
        return None

    topic_el = paragraphs[1]
    topic = topic_el.get_text().strip().replace("\n", "").replace("\xa0", " ")
    if len(topic) < 4:
        return None

    parts = topic.split(". ", 1)
    topic_clean = [p.strip() for p in parts if p.strip()]
    if len(topic_clean) < 2:
        return None

    title = topic_clean[0].replace("\xa0", " ")
    doc_id = _normalize_whitespace(topic_clean[1])

    if "(" not in doc_id:
        title = f"{doc_id} - {title}"

    desc = f"{topic_clean[0]}\n{topic_clean[1]}"
    desc = _normalize_whitespace(desc).replace("( ", "")

    href = ""
    for link in topic_el.find_all("a"):
        link_href = link.get("href")
        if link_href:
            href = root_url + link_href
            desc = f"{desc}\n{href}"

    if not href:
        return None

    return title, desc


def scrape_actas(
    *,
    start_id: int = START_ACTA_ID,
    max_items: int = MAX_ITEMS,
    max_consecutive_failures: int = MAX_CONSECUTIVE_FAILURES,
    root_url: str = ROOT_URL,
    ssl_context: ssl.SSLContext | None = None,
) -> dict[str, str]:
    """Collect up to *max_items* act entries by incrementing act IDs from *start_id*."""
    ctx = ssl_context or create_unverified_ssl_context()
    results: dict[str, str] = {}
    acta_id = start_id
    consecutive_failures = 0
    stop = False

    while not stop and len(results) < max_items:
        url = f"{root_url}{DETALLE_ACTA_PATH}{acta_id}"
        html = fetch_html(url, ctx)

        if html is None:
            consecutive_failures += 1
            acta_id += 1
            if consecutive_failures >= max_consecutive_failures:
                stop = True
            continue

        soup = BeautifulSoup(html, "html.parser")
        parsed = parse_acta_entry(soup, root_url)

        if parsed is None:
            consecutive_failures += 1
            acta_id += 1
            if consecutive_failures >= max_consecutive_failures:
                stop = True
            continue

        title, desc = parsed
        results[title] = desc
        consecutive_failures = 0
        acta_id += 1

    return results


def main() -> None:
    output = scrape_actas()
    print(json.dumps(output))


if __name__ == "__main__":
    main()
