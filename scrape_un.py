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

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

url = 'https://www.un.org/securitycouncil/content/resolutions-adopted-security-council-2019'
html = urllib.request.urlopen(url, context=ctx).read()
soup = BeautifulSoup(html, 'html.parser')

section = soup.find("div", {'class': 'field-items'})

tds = section.findAll('td')

list_of_contents = []
refs = []

for td in tds:
    if td.find('a') is not None:
        list_of_contents.append(td.getText().strip())
        list_of_contents.append(td.find('a').get('href'))
    else:
        list_of_contents.append(td.getText().strip())

#print(refs)
print(list_of_contents)

f = open('UNSC.txt', 'w', encoding='utf-8', errors='replace')
f.write("\n".join(str(item) for item in list_of_contents))
f.close
