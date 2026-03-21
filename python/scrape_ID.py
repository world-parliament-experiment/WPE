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
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
import re
#import numpy as np

import json

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

header = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
}

# URl needs to be dynamic
import datetime
today = datetime.datetime.now()
stop = False
# start = 238  # Original start ID
start = 1300 # Try a more recent ID range for 2024-2026
errorcount = 0
max_errors = 10

while not stop and len(output) < 20: # Limit to 20 per run
    url = 'https://www.dpr.go.id/uu/detail/id/'+str(start)
    try:
        response = requests.get(url, headers=header, timeout=10, verify=False)
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, features="lxml")
            h3 = soup.find("h3")
            if h3:
                title = h3.getText().strip()
                if title:
                    desc = "Source: " + url
                    output[title] = desc
                    errorcount = 0 # reset error count on success
            else:
                errorcount += 1
        elif response.status_code == 403:
            # If we get 403, we might be blocked, but let's try a few more IDs
            errorcount += 1
        else:
            errorcount += 1
            
    except Exception as e:
        errorcount += 1
    
    if errorcount >= max_errors:
        stop = True
    
    start += 1

print(json.dumps(output))

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