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

# URl needs to be dynamic
import datetime

# Read the XML file
url = 'https://www.parl.ca/legisinfo/en/bills/xml'
xml = requests.get(url)
soup = BeautifulSoup(xml.content, features='xml')
bills = soup.find_all("Bill")
for bill in bills:
    title = bill.find("LongTitle").getText()
    billtype = bill.find("BillDocumentTypeName").getText()
    session = bill.find("SessionNumber").getText()
    parliament = bill.find("ParliamentNumber").getText()
    code = bill.find("NumberCode").getText()

    link = "https://www.parl.ca/legisinfo/en/bill/"+parliament+"-"+session+"/"+code

    desc = billtype + "\n" + link 
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