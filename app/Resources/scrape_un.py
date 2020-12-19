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
import datetime

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

today = datetime.datetime.now()
list_of_contents = []

count = -1
while count < 2:
    year = today.year + count
    url = 'https://www.un.org/securitycouncil/content/resolutions-adopted-security-council-'+str(year)
    try:
        html = urllib.request.urlopen(url, context=ctx).read()
    except urllib.error.HTTPError as e:
        if e.getcode() == 404: # check the return code
            count = count + 1
            continue
        raise # if other than 404, raise the error

    soup = BeautifulSoup(html, 'html.parser')

    section = soup.find("div", {'class': 'field-items'})

    tds = section.findAll('td')

    for td in tds:
        if td.find('a') is not None:
            list_of_contents.append(td.getText().strip())
            list_of_contents.append(td.find('a').get('href'))
        else:
            list_of_contents.append(td.getText().strip())

    count = count + 1

#print(refs)
list_of_contents.reverse()

headings = list_of_contents[0::4]
URL = list_of_contents[2::4]

print(list_of_contents)
print(headings)
#print(URL)

f = open('UNSC.txt', 'w', encoding='utf-8', errors='replace')
f.write("\n".join(str(item) for item in list_of_contents))
f.close
