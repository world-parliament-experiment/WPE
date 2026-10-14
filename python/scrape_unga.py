#!/usr/bin/env python3
"""
List UN General Assembly resolution XML files from GitHub and extract metadata.

Uses the GitHub REST API to discover Akoma Ntoso XML assets for a GA session,
parses doc titles and numbers, and prints a flat Python list (title, UN doc URL).

Dependencies: lxml, requests, urllib3
"""

from __future__ import annotations

from typing import Any

import requests
import urllib3
from lxml import etree

SESSION = 79
GITHUB_API_URL = (
    f"https://api.github.com/repos/UNxml/GAresolutions/contents/{SESSION}session/English"
)
REQUEST_TIMEOUT = 30

AKN_NS = {"akn": "http://docs.oasis-open.org/legaldocml/ns/akn/3.0"}


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def list_xml_download_urls() -> list[str]:
    headers = {"Accept": "application/vnd.github.v3+json"}
    response = requests.get(
        GITHUB_API_URL,
        headers=headers,
        verify=False,
        timeout=REQUEST_TIMEOUT,
    )
    response.raise_for_status()
    payload: Any = response.json()
    if not isinstance(payload, list):
        return []
    return [
        item["download_url"]
        for item in payload
        if isinstance(item, dict)
        and str(item.get("name", "")).endswith(".xml")
        and item.get("download_url")
    ]


def parse_resolution_entry(xml_bytes: bytes) -> tuple[str, str] | None:
    try:
        root = etree.fromstring(xml_bytes)
    except etree.XMLSyntaxError:
        return None

    title_nodes = root.xpath(
        ".//akn:docTitle/akn:span[@class='bold']/text()",
        namespaces=AKN_NS,
    )
    number_nodes = root.xpath(".//akn:docNumber/text()", namespaces=AKN_NS)

    title_text = title_nodes[-1].strip() if title_nodes else ""
    if not title_text:
        return None

    doc_number = number_nodes[-1].rstrip(".") if number_nodes else ""
    if doc_number and any(ch.isdigit() for ch in doc_number):
        desc = (
            "United Nations General Assembly Resolution\n"
            f"https://docs.un.org/A/RES/{doc_number}"
        )
        return title_text, desc

    return None


def scrape_ga_resolutions() -> list[str]:
    _disable_insecure_request_warnings()
    output: list[str] = []

    for download_url in list_xml_download_urls():
        try:
            response = requests.get(
                download_url,
                verify=False,
                timeout=REQUEST_TIMEOUT,
            )
            response.raise_for_status()
        except requests.RequestException:
            continue

        parsed = parse_resolution_entry(response.content)
        if not parsed:
            continue
        title_text, desc = parsed
        output.append(title_text)
        output.append(desc)

    return output


def main() -> None:
    print(scrape_ga_resolutions())


if __name__ == "__main__":
    main()
