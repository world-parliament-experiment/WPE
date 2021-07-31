#!/usr/bin/env python
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
import sys
#import numpy as np

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

refs = []
output = []

# URl needs to be dynamic
import datetime
today = datetime.datetime.now()
stop = False
start = 1309
errorcount = 0
rooturl = 'https://www.senado.gob.ar'
while not stop:
    url = rooturl+'/votaciones/detalleActa/'+str(start)
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e.getcode() == 404: # check the return code
            errorcount += 1
            start += 1
            if errorcount == 5:
                stop = True
            continue
        raise # if other than 404, raise the error

    soup = BeautifulSoup(html, 'html.parser')

    section = ""
    section = soup.find("div", {'class': 'col-lg-6 col-sm-6'})
    if not section:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    
    section = section.find_all('p')
    if len(section) > 1:
        topic = section[1].get_text().strip()
        topic = topic.replace("\n", "")
        topic = topic.replace(u'\xa0', u' ')
        if len(str(topic)) < 4:
            errorcount += 1
            start += 1
            if errorcount == 5:
                stop = True
            continue
    else:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    topic = topic.split(". ", 1)
    topic = [t.strip() for t in topic if t.strip() != ""]
    title = topic[0].replace(u'\xa0', u' ')
    if len(topic) > 1:
        id = topic[1]
        id = " ".join(id.split())
        if '(' not in id:
            title = id + ' - ' + title
        desc = topic[0] + "\n" + topic[1]
        desc = " ".join(desc.split())
        desc = desc.replace("( ", "")

        href = ''
            
        for links in section[1].findAll('a'):
            href = rooturl + links.get('href')
            desc = desc + "\n" + href
        if not href:
            errorcount += 1
            start += 1
            if errorcount == 5:
                stop = True
            continue
    else:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue

    output.append(title)
    output.append(desc) 

    #successful scrape
    errorcount = 0
    start += 1

print(output)