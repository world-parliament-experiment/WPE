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
if  today.month <= 7:       #voting period is Sep - May 
    year = today.year - 1
else:
    year = today.year
stop = ""
start = 1
while not stop:
    #url = 'https://www.ft.dk/samling/20201/beslutningsforslag/b'+str(start)+'/index.htm'
    url = 'https://www.ft.dk/samling/'+str(year)+'1/beslutningsforslag/b'+str(start)+'/20201_b'+str(start)+'_som_fremsat.htm'
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e:
            stop = "Stop!"
            continue

    soup = BeautifulSoup(html, 'html.parser')

    section = soup.find("div", {'class': 'case-document'})
    if not section:
        stop = "Stop!"
        continue
    
    title = ""
    desc = ""

    title1 = soup.find("p", {'class': 'TitelPrefiks1'}).getText().strip()
    title2 = soup.find("p", {'class': 'Titel2'}).getText().strip()
    title = title1 + " " + title2
    desc = soup.find("p", {'class': 'Tekst1Sp'})
    if not desc:
        desc = soup.find("p", {'class': 'NormalInd'})
        
    desc = desc.getText().strip()
    href = 'https://www.ft.dk/ripdf/samling/'+str(year)+'1/beslutningsforslag/b'+str(start)+'/20201_b'+str(start)+'_som_fremsat.pdf'
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