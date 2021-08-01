#!/usr/bin/env python3
# To run this, you can install BeautifulSoup
# https://pypi.python.org/pypi/beautifulsoup4

# Or download the file
# http://www.py4e.com/code3/bs4.zip
# and unzip it in the same directory as this file

import requests
import urllib
from bs4 import BeautifulSoup
import ssl
import datetime


# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

today = datetime.datetime.now()
output = []

stop = False
session = 45
while not stop:

    requests.packages.urllib3.util.ssl_.DEFAULT_CIPHERS += 'HIGH:!DH:!aNULL'
    try:
        requests.packages.urllib3.contrib.pyopenssl.DEFAULT_SSL_CIPHER_LIST += 'HIGH:!DH:!aNULL'
    except AttributeError:
        # no pyopenssl support used / needed / available
        pass

    url = 'https://www.ohchr.org/EN/HRBodies/HRC/RegularSessions/Session'+str(session)+'/Pages/ResDecStat.aspx'
    try:
        html = requests.get(url)
    except requests.exceptions.RequestException as e: 
        session = session + 1
        stop = True
        continue

    soup = BeautifulSoup(html.text, 'html.parser')

    section = soup.find("table", {'class': 'HRCCleanupClass4 tablo-type1 tablo-type1--nopl automobile'})
    if section:
        trs = section.findAll('tr')

        for tr in trs:
            tds = tr.findAll('td')
            if tds:
                title = []
                contents = []
                for id, td in enumerate(tds):          
                    if td.find('a') is not None and id == 0: 
                        contents.append(td.getText().strip())
                        contents.append(td.find('a').get('href'))
                    elif td.find('a') is None and id == 0:
                        break 
                    else:
                        contents.append(td.getText().strip())
                contents.reverse()

                if contents:
                    title = contents[3].replace(u'\xa0', u' ')
                    title = contents[5] + " - " + title
                    title = title.replace("    ", " ")
                    title = title[:255]
                    desc = contents[0] + " \n" + contents[4]
                    desc = desc.replace("    ", " ")

                    output.append(title)
                    output.append(desc)
    else:
        session = session + 1
        stop = True
        continue  

    session = session + 1

print(output)

