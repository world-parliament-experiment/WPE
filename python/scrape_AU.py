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

import json
import requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
from bs4 import BeautifulSoup
import ssl
import datetime
import time

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

output = {}

header = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
}

today = datetime.datetime.now()
three_months_ago = today - datetime.timedelta(days=90)

stop = False
# start = 6721 # Old start
start = 7350 # Targeted start for 2026 bills
errorcount = 0
max_consecutive_errors = 10
rooturl = 'https://www.aph.gov.au/Parliamentary_Business/Bills_Legislation/Bills_Search_Results/Result?bId=r'

while not stop and len(output) < 20:
    url = rooturl + str(start)
    try:
        response = requests.get(url, headers=header, timeout=15, verify=False)
        if response.status_code == 404:
            errorcount += 1
            start += 1
            if errorcount >= max_consecutive_errors:
                stop = True
            continue

        if response.status_code != 200:
            errorcount += 1
            start += 1
            continue

        soup = BeautifulSoup(response.text, 'html.parser')

        # Get Title
        header_div = soup.find("div", {'id': 'main_0_billSummary_divHeader'})
        title = ""
        if header_div:
            h1 = header_div.find('h1')
            if h1:
                title = h1.get_text().strip()

        if not title:
            errorcount += 1
            start += 1
            continue

        # Check Date
        # Find "Introduced and read a first time" and the following <td>
        is_recent = False
        intro_tag = soup.find(string=lambda text: text and "Introduced and read a first time" in text)
        if intro_tag:
            parent_tr = intro_tag.find_parent('tr')
            if parent_tr:
                date_td = parent_tr.find_all('td')[1]
                if date_td:
                    date_str = date_td.get_text().strip()
                    try:
                        # Example format: 21 Nov 2024
                        intro_date = datetime.datetime.strptime(date_str, "%d %b %Y")
                        if intro_date >= three_months_ago:
                            is_recent = True
                    except:
                        pass

        if not is_recent:
            start += 1
            errorcount = 0 # It's a valid page, just too old
            continue

        # Get Summary
        summary_panel = soup.find("div", {'id': 'main_0_summaryPanel'})
        desc = ""
        if summary_panel:
            ps = summary_panel.find_all('p')
            if ps:
                desc = ps[0].get_text().strip()

        if desc:
            desc = desc + "\n" + url
            output[title] = desc
            errorcount = 0 # reset error count on success

    except Exception as e:
        errorcount += 1

    if errorcount >= max_consecutive_errors:
        stop = True

    start += 1
    time.sleep(0.5) # Be nice to their server

print(json.dumps(output))