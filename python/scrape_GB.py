#!/usr/bin/env python
# To run this, you can install BeautifulSoup
# https://pypi.python.org/pypi/beautifulsoup4

# Or download the file
# http://www.py4e.com/code3/bs4.zip
# and unzip it in the same directory as this file

from bs4 import BeautifulSoup
import ssl
import sys
import requests
#import numpy as np

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = []

# URl needs to be dynamic
url = 'https://bills.parliament.uk/rss/allbills.rss'
xml = requests.get(url)
soup = BeautifulSoup(xml.content, features='xml')

items = soup.find_all("item")
for item in items:
    title = item.find("title").getText()
    link = item.find("link").getText()
    desc = item.find("description").getText().strip()
    output.append(title)
    output.append(desc) 

print(output)