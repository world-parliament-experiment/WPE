#!/usr/bin/env python3
"""
Scrape recent Camara dos Deputados (Brazil) proposals via the open data API.

Filters PL/PEC types for the current year and roughly the last 90 days, then
prints JSON mapping a composite title to ementa and portal URL.

Dependencies: requests, urllib3
"""

from __future__ import annotations

import datetime
import json

import requests
import urllib3

API_URL = "https://dadosabertos.camara.leg.br/api/v2/proposicoes"
REQUEST_TIMEOUT = 15
RECENT_DAYS = 90


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def scrape_camara_proposicoes() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    params = {
        "ano": today.year,
        "ordem": "DESC",
        "ordenarPor": "id",
        "itens": 50,
        "siglaTipo": ["PL", "PEC"],
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
    except requests.RequestException:
        return output

    if response.status_code != 200:
        return output

    try:
        data = response.json()
    except ValueError:
        return output

    for item in data.get("dados", []):
        date_str = item.get("dataApresentacao", "")
        try:
            item_date = datetime.datetime.strptime(
                date_str.split("T")[0],
                "%Y-%m-%d",
            )
        except (ValueError, IndexError):
            continue
        if item_date < cutoff:
            continue

        bill_ref = f"{item.get('siglaTipo')} {item.get('numero')}/{item.get('ano')}"
        ementa = (item.get("ementa") or "").strip()
        title_desc = ementa
        if len(title_desc) > 150:
            title_desc = title_desc[:147] + "..."

        title = f"{bill_ref}: {title_desc}"
        prop_id = item.get("id")
        link = f"https://www.camara.leg.br/propostas-legislativas/{prop_id}"
        desc = f"{ementa}\nSource: {link}"
        output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_camara_proposicoes()))


if __name__ == "__main__":
    main()
