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
start = 6721
errorcount = 0
rooturl = 'https://www.aph.gov.au/Parliamentary_Business/Bills_Legislation'
while not stop:
    
    section1 = ""
    title =""
    section2 = ""
    desc = ""
    
    url = rooturl+'/Bills_Search_Results/Result?bId=r'+str(start)
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

    section1 = soup.find("div", {'id': 'main_0_billSummary_divHeader'})
    if not section1:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    
    section1 = section1.find('h1')
    if section1:
        title = section1.get_text().strip()
        title = title.replace("'", "&#39;")
        if len(str(title)) < 4:
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

    section2 = soup.find("div", {'id': 'main_0_summaryPanel'})
    if not section2:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    
    section2 = section2.find_all('p')
    if section2:
        desc = section2[0].get_text().strip()
        desc = desc.replace("'", "&#39;")
        desc = desc + "\n" + url
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