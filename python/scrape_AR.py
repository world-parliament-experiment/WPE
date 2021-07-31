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
start = 1296
errorcount = 0
rooturl = 'https://www.senado.gob.ar/'
while not stop:
    url = rooturl+'votaciones/detalleActa/'+str(start)
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

    section = soup.find("div", {'class': 'col-lg-6 col-sm-6'})
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
    title = title.replace("( - ", "")
    id = topic[2].replace(u'\xa0', u' ')
    title = id + ' - ' + title
    desc = topic[1].replace(u'\xa0', u' ')
    href = ''
        
    for links in section.findAll('a'):
        href = rooturl + links.get('href')
        desc = desc + "\n" + href
    if not href:
        errorcount += 1
        start += 1
        if errorcount == 5:
            stop = True
        continue

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