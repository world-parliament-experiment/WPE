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
import requests
import re
#import numpy as np

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = []

header = {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36',
}

# URl needs to be dynamic

import datetime
today = datetime.datetime.now()
stop = False
start = 238
errorcount = 0
while not stop:

    # Read the XML file
    url = 'https://dpr.go.id/uu/detail/id/'+str(start)
    #url = "https:/dpr.go.id/uu/prolegnas-long-list"
    try:
        soup = BeautifulSoup(requests.get(url, headers=header).text, features="lxml")
    except urllib.error.HTTPError as e:
        if e: # check the return code
            errorcount += 1
            start += 1
            if errorcount == 5:
                stop = True
            continue
    
    title = ""
    title = soup.find("h3").getText()
    if not title:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    desc = url
    output.append(title)
    output.append(desc) 

    start += 1

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