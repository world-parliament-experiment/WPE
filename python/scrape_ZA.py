#!/usr/bin/env python3
"""
Scrape South African bill metadata from the PMG API.

Prints JSON mapping title (with bill code when present) to details and web URL.

Dependencies: requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3

API_URL = "https://api.pmg.org.za/bill/"
REQUEST_TIMEOUT = 15
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_pmg_bills() -> dict[str, str]:
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
            API_URL,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    try:
        data = response.json()
    except ValueError:
        return output

    bills = data.get("results", [])
    if not isinstance(bills, list):
        return output

    for bill in bills:
        if not isinstance(bill, dict):
            continue
        title = (bill.get("title") or "").strip()
        code = bill.get("code", "")
        if code:
            title = f"{title} ({code})"

        intro_date = bill.get("date_of_introduction")
        intro_by = bill.get("introduced_by")
        status_obj = bill.get("status") or {}
        status = status_obj.get("description") if isinstance(status_obj, dict) else "N/A"
        type_obj = bill.get("type") or {}
        bill_type = type_obj.get("description") if isinstance(type_obj, dict) else "N/A"
        bill_id = bill.get("id")
        web_url = f"https://pmg.org.za/bill/{bill_id}/"

        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        desc = f"{bill.get('title')}\n\n"
        desc += f"Code: {code}\n"
        desc += f"Status: {status}\n"
        desc += f"Type: {bill_type}\n"
        desc += f"Introduced by: {intro_by} on {intro_date}\n"
        desc += f"Source: {web_url}"

        if title:
            output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_pmg_bills(), ensure_ascii=False))


if __name__ == "__main__":
    main()
