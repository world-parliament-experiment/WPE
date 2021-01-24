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
stop = ""
start = 1
while not stop:
    url = 'https://www.oireachtas.ie/en/bills/bill/'+str(year)+'/'+str(start)+'/'
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e:
            stop = "Stop!"
            continue

    soup = BeautifulSoup(html, 'html.parser')

    title = ""
    desc = ""

    title = soup.find("div", {'class': 'c-hero__content'}).getText().strip()
    if not title:
        stop = "Stop!"
        continue
    
    desc = soup.find("p", {'class': 'c-bill-intro__long-title'})
    #if not desc:
    #    desc = soup.find("p", {'class': 'NormalInd'})
        
    desc = desc.getText().strip()
    href = url
    desc = desc + "\n" + href

    output.append(title)
    output.append(desc) 
    #print(start)   

    start = start + 1

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