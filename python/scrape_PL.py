#!/usr/bin/env python3
"""
Scrape recent bills from the Polish Sejm public API (term 10).

Fetches a window of bills, filters by receipt date (last ~90 days), and prints
JSON mapping a prefixed title to status and source URL.

Dependencies: requests, urllib3
"""

from __future__ import annotations

import datetime
import json
import sys

import requests
import urllib3

API_URL = "https://api.sejm.gov.pl/sejm/term10/bills"
REQUEST_TIMEOUT = 15
RECENT_DAYS = 90
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_sejm_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    params = {"limit": 100, "offset": 900}
    headers = {
        "accept": "application/json",
        "User-Agent": "Mozilla/5.0",
    }

    response = requests.get(
        API_URL,
        params=params,
        headers=headers,
        timeout=REQUEST_TIMEOUT,
        verify=False,
    )
    if response.status_code != 200:
        print(
            f"Error: API returned status code {response.status_code}",
            file=sys.stderr,
        )
        sys.exit(1)

    try:
        data = response.json()
    except ValueError:
        return output

    if not isinstance(data, list):
        return output

    for item in data:
        if not isinstance(item, dict):
            continue
        date_str = item.get("dateOfReceipt", "")
        if not date_str:
            continue
        try:
            item_date = datetime.datetime.strptime(date_str, "%Y-%m-%d")
        except ValueError:
            continue
        if item_date < cutoff:
            continue

        title = (item.get("title") or "").strip()
        if not title:
            continue

        bill_nr = item.get("number", "")
        status = item.get("status", "")
        summary = item.get("description", "")
        print_num = item.get("print", "")
        if print_num:
            link = f"https://www.sejm.gov.pl/Sejm10.nsf/PrzebiegProc.xsp?nr={print_num}"
        else:
            link = f"https://api.sejm.gov.pl/sejm/term10/bills/{item.get('number')}"

        desc = ""
        if summary:
            desc = f"{summary}\n\n"
        desc += f"Status: {status}\nSource: {link}"

        full_title = f"{bill_nr}: {title}"
        if len(full_title) > MAX_TITLE_LEN:
            full_title = full_title[: MAX_TITLE_LEN - 3] + "..."

        output[full_title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_sejm_bills()))


if __name__ == "__main__":
    main()
