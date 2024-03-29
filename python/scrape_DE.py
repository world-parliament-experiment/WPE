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
start = 760
errorcount = 0
while not stop:
    url= urllib.request.Request('https://www.bundestag.de/parlament/plenum/abstimmung/abstimmung?id='+str(start))
    url.add_header('User-agent', 'Mozilla/5.0 (Linux i686)')
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e:
            errorcount += 1
            start += 1
            if errorcount == 5:
                stop = True
            continue

    soup = BeautifulSoup(html, 'html.parser')

    section = soup.find("article", {'class': 'bt-artikel col-xs-12 bt-standard-content'})
    if not section:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue
    
    topic = section.getText().strip()
    topic = topic.split("\n")
    topic = [t.strip() for t in topic if t.strip() != ""]
    title = topic[1].replace(u'\xa0', u' ')
    desc = topic[2] + " \n" + topic[0]
        
    for links in section.findAll('a'):
        href = links.get('href')
        desc = desc + "\n" + href

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