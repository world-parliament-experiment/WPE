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

url = 'http://loksabhaph.nic.in/Legislation/billintroduce.aspx'
html = urllib.request.urlopen(url, context=ctx).read()

soup = BeautifulSoup(html, 'html.parser')

section = soup.find("table", {'id': 'ContentPlaceHolder1_GR1'})
trs = section.findAll('tr')

for tr in trs[3:]:
    tds = tr.findAll('td')
    title = []
    contents = []
    desc = ""
    for id, td in enumerate(tds):          
            if td.find('a') is not None and id == 2:    
                for links in td.findAll('a'):
                    href = links.get('href')
                    if href:
                        href = href.replace(" ", "%20")
                        desc = desc + "\n" + href
                    else:
                        title = td.getText().split('As introduced')[0]
            else:
                contents.append(td.getText().split("\n")[0])
    
    if contents:
        desc = contents[3] + "\n" + desc  
        desc = desc.replace("'", " ")
        if contents[3]:
            date = datetime.strptime(contents[3], "%d/%m/%Y")
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
