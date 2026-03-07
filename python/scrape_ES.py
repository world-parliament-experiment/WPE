#!/usr/bin/env python
import json
import requests
import datetime
import ssl

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

# Spain Congreso AJAX endpoint
base_url = "https://www.congreso.es/busqueda-de-iniciativas"
params_base = {
    "p_p_id": "iniciativas",
    "p_p_lifecycle": "2",
    "p_p_state": "normal",
    "p_p_mode": "view",
    "p_p_resource_id": "filtrarListado",
    "p_p_cacheability": "cacheLevelPage",
    "_iniciativas_legislatura": "15",
    "_iniciativas_indice": "1",
    "_iniciativas_resultadosPorPagina": "50",
    "_iniciativas_ordenarPor": "f_presentacion",
    "_iniciativas_sentidoOrden": "DESC",
    "_iniciativas_texto": today.year
}

header = {
    "User-Agent": "Mozilla/5.0"
}

# Types: 121 (Proyecto de Ley), 122 (Proposicion de Ley)
for type_id in ["121", "122"]:
    params = params_base.copy()
    params["_iniciativas_tipoIniciativa"] = type_id
    
    try:
        response = requests.get(base_url, params=params, headers=header, timeout=15)
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
                        
                        # Bill reference
                        bill_ref = item.get('id_iniciativa', '')
                        
                        # Create descriptive title
                        title = f"{bill_ref}: {title_raw}"
                        if len(title) > 250:
                            title = title[:247] + "..."
                        
                        # Public link
                        # Format: https://www.congreso.es/busqueda-de-iniciativas?p_p_id=iniciativas&p_p_lifecycle=0&p_p_mode=mostrarDetalle&_iniciativas_expediente=121/000001
                        link = f"https://www.congreso.es/busqueda-de-iniciativas?p_p_id=iniciativas&p_p_lifecycle=0&p_p_mode=mostrarDetalle&_iniciativas_expediente={bill_ref}"
                        
                        desc = f"{title_raw}\n\nAutor: {item.get('autor', 'N/A')}\nFecha: {date_str}\nSource: {link}"
                        output[title] = desc
                except Exception:
                    continue
    except Exception:
        pass

print(json.dumps(output))
