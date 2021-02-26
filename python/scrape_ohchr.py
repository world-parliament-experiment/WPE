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
    url = 'https://www.ohchr.org/EN/HRBodies/HRC/RegularSessions/Session'+str(session)+'/Pages/ResDecStat.aspx'
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e.getcode() == 404: # check the return code
            session = session + 1
            stop = True
            continue
        raise # if other than 404, raise the error

    soup = BeautifulSoup(html, 'html.parser')

    section = soup.find("table", {'class': 'HRCCleanupClass4 tablo-type1 tablo-type1--nopl automobile'})
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
                title = title.replace("    ", " ")
                desc = contents[0] + " \n" + contents[4]
                desc = desc.replace("    ", " ")

                output.append(title)
                output.append(desc)

    session = session + 1

print(output)

