#!/usr/bin/env python3
"""
Scrape Bundestag (Germany) plenary vote (Abstimmung) pages by numeric id.

Walks increasing ids from a start value, parses each article for title/description
and anchor hrefs, and prints a flat list of title, description pairs.

Dependencies: beautifulsoup4
"""

from __future__ import annotations

import ssl
import urllib.error
import urllib.request
from bs4 import BeautifulSoup

START_ID = 950
MAX_CONSECUTIVE_FAILURES = 5
BASE_URL = "https://www.bundestag.de/parlament/plenum/abstimmung/abstimmung?id="
USER_AGENT = "Mozilla/5.0 (Linux i686)"


def create_unverified_ssl_context() -> ssl.SSLContext:
    """SSL context without verification (same tradeoff as legacy scrapers)."""
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx


def fetch_abstimmung(act_id: int, ssl_context: ssl.SSLContext) -> bytes | None:
    """Return HTML body or None on HTTP error (caller treats as failure)."""
    req = urllib.request.Request(
        f"{BASE_URL}{act_id}",
        headers={"User-agent": USER_AGENT},
    )
    try:
        with urllib.request.urlopen(req, context=ssl_context) as response:
            return response.read()
    except urllib.error.HTTPError:
        return None


def parse_article(html: bytes) -> tuple[str, str] | None:
    """Return (title, desc) from a vote page, or None if layout missing."""
    soup = BeautifulSoup(html, "html.parser")
    section = soup.find(
        "article",
        {"class": "bt-artikel col-xs-12 bt-standard-content"},
    )
    if not section:
        return None

    topic = [t.strip() for t in section.getText().strip().split("\n") if t.strip()]
    if len(topic) < 3:
        return None

    title = topic[1].replace("\xa0", " ")
    desc = topic[2] + " \n" + topic[0]

    for link in section.find_all("a"):
        href = link.get("href")
        if href:
            desc = desc + "\n" + href

    return title, desc


def scrape_bundestag_abstimmungen() -> list[str]:
    """Collect vote summaries until consecutive failures reach the limit."""
    ctx = create_unverified_ssl_context()
    output: list[str] = []
    act_id = START_ID
    consecutive_failures = 0
    stop = False

    while not stop:
        html = fetch_abstimmung(act_id, ctx)
        if html is None:
            consecutive_failures += 1
            act_id += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        parsed = parse_article(html)
        if parsed is None:
            consecutive_failures += 1
            act_id += 1
            if consecutive_failures >= MAX_CONSECUTIVE_FAILURES:
                stop = True
            continue

        title, desc = parsed
        output.append(title)
        output.append(desc)
        act_id += 1

    return output


def main() -> None:
    print(scrape_bundestag_abstimmungen())


if __name__ == "__main__":
    main()
