#!/usr/bin/env python3
# To run this, you can install BeautifulSoup
# https://pypi.python.org/pypi/beautifulsoup4

# Or download the file
# http://www.py4e.com/code3/bs4.zip
# and unzip it in the same directory as this file

import urllib.request
import urllib.parse
import urllib.error
from bs4 import BeautifulSoup
import ssl
from datetime import datetime
from datetime import timedelta

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

today = datetime.now()
output = []

url = 'https://www.camara.cl/legislacion/ProyectosDeLey/leyes_promulgadas.aspx'
docurl = 'https://www.camara.cl/legislacion/ProyectosDeLey/'
html = urllib.request.urlopen(url, context=ctx).read()

soup = BeautifulSoup(html, 'html.parser')

section = soup.find("div", {'class': 'grid-12 lista-proyectos aleft'})
trs = section.findAll('tr')

for tr in trs:
    tds = tr.findAll('td')
    title = []
    contents = []
    for id, td in enumerate(tds):          
            if td.find('a') is not None and id == 0: 
                contents.append(td.find('a').get('href'))
            else:
                contents.append(td.getText().split("\n")[0])
    
    if contents:
        title = contents[3].replace(u'\xa0', u' ')
        title = title.replace("'", " ")
        link = docurl + contents[0]
        desc = contents[5] + " \n" + link
        desc = desc.replace("'", " ")
        date = datetime.strptime(contents[5], "%d-%m-%Y")
        if date < (datetime.now() - timedelta(days=365)):
            break

        output.append(title)
        output.append(desc)

#print(refs)
#list_of_contents.reverse()

#headings = list_of_contents[0::4]
#URL = list_of_contents[2::4]

print(output)
#print(headings)
#print(URL)

#f = open('UNSC.txt', 'w', encoding='utf-8', errors='replace')
#f.write("\n".join(str(item) for item in list_of_contents))
#f.close
