#!/usr/bin/env python
import json
import requests
import datetime
import ssl
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

header = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
}

# Sources and their corresponding CINI filters for Spanish Congress
# 121.CINI. = Proyectos de Ley (Government bills)
# (proposicion+adj2+ley).tipo. = Proposiciones de Ley (Member bills)
# 181.CINI. = Preguntas con respuesta oral
# 184.CINI. = Preguntas con respuesta escrita
sources = [
    {
        "url": "https://www.congreso.es/es/proyectos-de-ley",
        "cini": "121.CINI."
    },
    {
        "url": "https://www.congreso.es/es/proposiciones-de-ley",
        "cini": "(proposicion+adj2+ley).tipo."
    },
    {
        "url": "https://www.congreso.es/es/busqueda-de-iniciativas",
        "cini": "181.CINI."
    },
    {
        "url": "https://www.congreso.es/es/busqueda-de-iniciativas",
        "cini": "184.CINI."
    }
]

ajax_base = "?p_p_id=iniciativas&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=filtrarListado&p_p_cacheability=cacheLevelPage"

for src in sources:
    ajax_url = src["url"] + ajax_base
    payload = {
        "_iniciativas_legislatura": "15",
        "_iniciativas_estadoTramitacion": "",
        "_iniciativas_faseTramitacion": "",
        "_iniciativas_cini": src["cini"],
        "_iniciativas_tipoLlamada": "T",
        "_iniciativas_paginaActual": "1"
    }
    
    try:
        response = requests.post(ajax_url, data=payload, headers=header, timeout=15, verify=False)
        if response.status_code == 200:
            data = response.json()
            initiatives = data.get('lista_iniciativas', {})
            for key in initiatives:
                item = initiatives[key]
                date_str = item.get('fecha_presentado', '')
                if not date_str:
                    continue
                
                try:
                    # Format: 12/02/2026
                    item_date = datetime.datetime.strptime(date_str, "%d/%m/%Y")
                    if item_date >= three_months_ago:
                        title_raw = item.get('titulo', '').strip()
                        if not title_raw:
                            continue
                        
                        bill_ref = item.get('id_iniciativa', '')
                        legislatura = item.get('legislatura', 'XV')
                        
                        # Author extraction
                        autores_obj = item.get('autores', {})
                        autores_list = []
                        if isinstance(autores_obj, dict):
                            for k in autores_obj:
                                nombre = autores_obj[k].get('nombre', '')
                                if nombre:
                                    autores_list.append(nombre)
                        autor_str = ", ".join(autores_list) if autores_list else "N/A"
                        
                        # Create descriptive title
                        title = f"{bill_ref}: {title_raw}"
                        if len(title) > 250:
                            title = title[:247] + "..."
                        
                        # Target URL exactly as requested:
                        link = f"https://www.congreso.es/es/busqueda-de-iniciativas?p_p_id=iniciativas&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&_iniciativas_mode=mostrarDetalle&_iniciativas_legislatura={legislatura}&_iniciativas_id={bill_ref}"
                        
                        # Using "Documentation" label as requested
                        desc = f"{title_raw}\n\nAutor: {autor_str}\nFecha: {date_str}\nDocumentation: {link}"
                        output[title] = desc
                except Exception:
                    continue
    except Exception:
        pass

print(json.dumps(output, ensure_ascii=False))
