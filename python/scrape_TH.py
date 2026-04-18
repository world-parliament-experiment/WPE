#!/usr/bin/env python3
"""
Scrape recent Thai bill metadata from the Politigraph GraphQL API.

Maps a shortened display title to full title, status, dates, and parliament URL.

Dependencies: requests, urllib3
"""

from __future__ import annotations

import json

import requests
import urllib3

API_URL = "https://politigraph.wevis.info/graphql"
REQUEST_TIMEOUT = 15


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


GRAPHQL_QUERY = """
{
  bills(limit: 30) {
    id
    title
    status
    proposal_date
    lis_id
  }
}
"""


def scrape_thailand_bills() -> dict[str, str]:
    _disable_insecure_request_warnings()
    output: dict[str, str] = {}

    try:
        response = requests.post(
            API_URL,
            json={"query": GRAPHQL_QUERY},
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

    bills = data.get("data", {}).get("bills", [])
    if not isinstance(bills, list):
        return output

    for bill in bills:
        if not isinstance(bill, dict):
            continue
        title_raw = (bill.get("title") or "").strip()
        title_clean = title_raw.replace("พ.ศ. ....", "").strip()

        lis_id = bill.get("lis_id")
        status = bill.get("status", "N/A")
        proposal_date = bill.get("proposal_date", "N/A")
        official_url = (
            f"https://www.parliament.go.th/section77/survey_detail.php?id={lis_id}"
            if lis_id
            else ""
        )

        display_title = title_clean
        if len(display_title) > 70:
            display_title = display_title[:67] + "..."

        desc = f"{title_raw}\n\n"
        desc += f"Status: {status}\n"
        desc += f"Proposed date: {proposal_date}\n"
        if official_url:
            desc += f"Source: {official_url}"

        if display_title:
            output[display_title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_thailand_bills(), ensure_ascii=False))


if __name__ == "__main__":
    main()
