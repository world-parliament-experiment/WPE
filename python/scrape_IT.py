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

url= urllib.request.Request('https://www.parlamento.it/leg/ldl_new/v3/sldlelencodlconvers.htm')
url.add_header('User-agent', 'Mozilla/5.0 (Linux i686)')
try:
    html = urllib.request.urlopen(url, context=ctx).read()
except urllib.error.HTTPError as e:
    if e:
        raise e

soup = BeautifulSoup(html, 'html.parser')

laws = soup.findAll("dl", {'class': 'leggi'})
for section in laws:
    
    titles = section.findAll("p", {'class': 'titoloLegge'})
    links = section.findAll("dt")
         
    for id, t in enumerate(titles):
        title = t.getText().strip() 
        desc = links[id].find('a').get('href')
        output.append(title)
        output.append(desc)    


print(output)

