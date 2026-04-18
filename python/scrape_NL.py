#!/usr/bin/env python3
"""
Scrape recent Dutch Tweede Kamer legislation cases via OData.

Filters government and private member bills to roughly the last 90 days and
prints JSON mapping citeertitel (or titel) to description with source URL.

Dependencies: requests, urllib3
"""

from __future__ import annotations

import datetime
import json

import requests
import urllib3

API_URL = "https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0/Zaak"
REQUEST_TIMEOUT = 15
RECENT_DAYS = 90
MAX_TITLE_LEN = 250


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_tweede_kamer() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    params = {
        "$filter": "Soort eq 'Wetgeving' or Soort eq 'Initiatiefwetgeving'",
        "$expand": "Kamerstukdossier",
        "$orderby": "GestartOp desc",
        "$top": 50,
    }
    headers = {
        "accept": "application/json",
        "User-Agent": "Mozilla/5.0",
    }

    try:
        response = requests.get(
            API_URL,
            params=params,
            headers=headers,
            timeout=REQUEST_TIMEOUT,
            verify=False,
        )
        if response.status_code != 200:
            return output
        data = response.json()
    except (requests.RequestException, ValueError):
        return output

    for item in data.get("value", []):
        date_str = item.get("GestartOp", "")
        if not date_str:
            continue
        try:
            item_date = datetime.datetime.strptime(date_str.split("T")[0], "%Y-%m-%d")
        except ValueError:
            continue
        if item_date < cutoff:
            continue

        short_title = item.get("Citeertitel")
        full_title = item.get("Titel")

        title = short_title or full_title
        if not title:
            continue
        if len(title) > MAX_TITLE_LEN:
            title = title[: MAX_TITLE_LEN - 3] + "..."

        desc = full_title or title
        dossier = item.get("Kamerstukdossier", [])
        if dossier and isinstance(dossier, list) and dossier:
            bill_nr_official = dossier[0].get("Nummer")
            link = (
                "https://www.tweedekamer.nl/kamerstukken/wetsvoorstellen/detail?"
                f"cfg=wetsvoorsteldetails&qry=wetsvoorstel%3A{bill_nr_official}"
            )
        else:
            link = (
                "https://www.tweedekamer.nl/kamerstukken/wetsvoorstellen/detail?"
                f"id={item.get('Nummer')}"
            )

        desc = f"{desc}\nSource: {link}"
        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_tweede_kamer()))


if __name__ == "__main__":
    main()
