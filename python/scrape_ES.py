#!/usr/bin/env python3
"""
Scrape recent Spanish Congress (Congreso) initiatives via portal AJAX endpoints.

POSTs filter payloads for several initiative types, keeps items from roughly the
last 90 days, and prints JSON (UTF-8, non-ASCII preserved).

Dependencies: requests, urllib3
"""

from __future__ import annotations

import datetime
import json
from typing import Any

import requests
import urllib3

REQUEST_TIMEOUT = 15
RECENT_DAYS = 90
MAX_TITLE_LEN = 250

SOURCES: list[dict[str, str]] = [
    {"url": "https://www.congreso.es/es/proyectos-de-ley", "cini": "121.CINI."},
    {
        "url": "https://www.congreso.es/es/proposiciones-de-ley",
        "cini": "(proposicion+adj2+ley).tipo.",
    },
    {"url": "https://www.congreso.es/es/busqueda-de-iniciativas", "cini": "181.CINI."},
    {"url": "https://www.congreso.es/es/busqueda-de-iniciativas", "cini": "184.CINI."},
]

AJAX_SUFFIX = (
    "?p_p_id=iniciativas&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&"
    "p_p_resource_id=filtrarListado&p_p_cacheability=cacheLevelPage"
)


def _disable_insecure_request_warnings() -> None:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def _collect_authors(autores_obj: Any) -> str:
    names: list[str] = []
    if isinstance(autores_obj, dict):
        for _key, val in autores_obj.items():
            if isinstance(val, dict):
                nombre = val.get("nombre", "")
                if nombre:
                    names.append(nombre)
    return ", ".join(names) if names else "N/A"


def scrape_congreso_iniciativas() -> dict[str, str]:
    _disable_insecure_request_warnings()
    today = datetime.datetime.now()
    cutoff = today - datetime.timedelta(days=RECENT_DAYS)
    output: dict[str, str] = {}

    header = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        )
    }

    for src in SOURCES:
        ajax_url = src["url"] + AJAX_SUFFIX
        payload = {
            "_iniciativas_legislatura": "15",
            "_iniciativas_estadoTramitacion": "",
            "_iniciativas_faseTramitacion": "",
            "_iniciativas_cini": src["cini"],
            "_iniciativas_tipoLlamada": "T",
            "_iniciativas_paginaActual": "1",
        }
        try:
            response = requests.post(
                ajax_url,
                data=payload,
                headers=header,
                timeout=REQUEST_TIMEOUT,
                verify=False,
            )
        except requests.RequestException:
            continue

        if response.status_code != 200:
            continue

        try:
            data = response.json()
        except ValueError:
            continue

        initiatives = data.get("lista_iniciativas", {})
        if not isinstance(initiatives, dict):
            continue

        for _key, item in initiatives.items():
            if not isinstance(item, dict):
                continue
            date_str = item.get("fecha_presentado", "")
            if not date_str:
                continue
            try:
                item_date = datetime.datetime.strptime(date_str, "%d/%m/%Y")
            except ValueError:
                continue
            if item_date < cutoff:
                continue

            title_raw = (item.get("titulo") or "").strip()
            if not title_raw:
                continue

            bill_ref = item.get("id_iniciativa", "")
            legislatura = item.get("legislatura", "XV")
            autor_str = _collect_authors(item.get("autores", {}))

            title = f"{bill_ref}: {title_raw}"
            if len(title) > MAX_TITLE_LEN:
                title = title[: MAX_TITLE_LEN - 3] + "..."

            link = (
                "https://www.congreso.es/es/busqueda-de-iniciativas?"
                "p_p_id=iniciativas&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&"
                "_iniciativas_mode=mostrarDetalle&"
                f"_iniciativas_legislatura={legislatura}&_iniciativas_id={bill_ref}"
            )
            desc = (
                f"{title_raw}\n\nAutor: {autor_str}\nFecha: {date_str}\n"
                f"Documentation: {link}"
            )
            output[title] = desc

    return output


def main() -> None:
    print(json.dumps(scrape_congreso_iniciativas(), ensure_ascii=False))


if __name__ == "__main__":
    main()
