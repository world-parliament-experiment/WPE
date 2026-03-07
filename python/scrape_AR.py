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

import json

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

# URl needs to be dynamic
import datetime
today = datetime.datetime.now()
stop = False
# Global ID 2615 is approx start of 2026
start = 2600 
errorcount = 0
max_errors = 10
rooturl = 'https://www.senado.gob.ar'

while not stop and len(output) < 20:
    url = rooturl+'/votaciones/detalleActa/'+str(start)
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e.getcode() == 404: # check the return code
            errorcount += 1
            start += 1
            if errorcount >= max_errors:
                stop = True
            continue
        raise # if other than 404, raise the error

    soup = BeautifulSoup(html, 'html.parser')

    section = ""
    section = soup.find("div", {'class': 'col-lg-6 col-sm-6'})
    if not section:
        errorcount += 1
        start += 1
        if errorcount >= max_errors:
            stop = True
        continue

    ps = section.find_all('p')
    if len(ps) > 1:
        topic = ps[1].get_text().strip()
        topic = topic.replace("\n", "")
        topic = topic.replace(u'\xa0', u' ')
        if len(str(topic)) < 4:
            errorcount += 1
            start += 1
            continue
    else:
        errorcount += 1
        start += 1
        continue

    topic_split = topic.split(". ", 1)
    topic_clean = [t.strip() for t in topic_split if t.strip() != ""]
    title = topic_clean[0].replace(u'\xa0', u' ')

    if len(topic_clean) > 1:
        doc_id = topic_clean[1]
        doc_id = " ".join(doc_id.split())
        if '(' not in doc_id:
            title = doc_id + ' - ' + title
        desc = topic_clean[0] + "\n" + topic_clean[1]
        desc = " ".join(desc.split()).replace("( ", "")

        href = ''
        for links in ps[1].findAll('a'):
            link_href = links.get('href')
            if link_href:
                href = rooturl + link_href
                desc = desc + "\n" + href

        if href:
            output[title] = desc
            # successful scrape
            errorcount = 0

    start += 1

print(json.dumps(output))