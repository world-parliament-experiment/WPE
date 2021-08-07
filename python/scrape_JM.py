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

output = []

# URl needs to be dynamic
import datetime
today = datetime.datetime.now()
year = today.year
errorcount = 0
baseurl = 'https://www.japarliament.gov.jm'
url = 'https://www.japarliament.gov.jm/index.php/publications/bills/public-bills'
html = urllib.request.urlopen(url, context=ctx).read()
soup = BeautifulSoup(html, 'html.parser')
section = soup.find("div", {'itemprop': 'articleBody'})
topics = section.findAll("li")
for item in topics:
    title = ""
    desc = ""
    title = item.getText().strip()
    title = title.replace("\n", " ")
    desc = item.getText().strip()
    href = baseurl + item.find('a').get('href')
    href = href.replace(" ", "%20")
    desc = desc + "\n" + href

    output.append(title)
    output.append(desc) 

print(output)

#print(topicno)
#print(status)
#print(url)
#print(uzeit) 
#list_of_contents.remove("\n")
#list_of_contents.remove(" ")


#print(list_of_contents)

#print(topiclist)

#f = open('BT_Tagesordnung.txt', 'w', encoding='utf-8', errors='replace')
#f.write("\n".join(str(item) for item in output))
#f.close

#f = open('BT_Tagesordnung.txt', 'a')
#f.write("\n".join(str(item) for item in url))
#f.close