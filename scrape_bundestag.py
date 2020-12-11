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

# Ignore SSL certificate errors
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

c_count = 9
list_of_contents = []
refs = []

# URl needs to be dynamic
import datetime
today = datetime.datetime.now()
sel_date = today - datetime.timedelta(weeks=5)

count = 1
while count < c_count:
    sel_date = sel_date + datetime.timedelta(weeks=1)
    week = sel_date.strftime("%V")
    year = sel_date.year

    url = 'https://www.bundestag.de/apps/plenar/plenar/conferenceweekDetail.form?year='+str(year)+'&week='+str(week)
    html = urllib.request.urlopen(url, context=ctx).read()
    soup = BeautifulSoup(html, 'html.parser')

    section = soup.find("div", {'class': 'col-xs-12'})
    tds = section.findAll('td')
    for td in tds:
        list_of_contents.append(td.getText().strip('zum Artikel'))
        for links in td.findAll('a'):
            href = links.get('href')
            if href != '#': 
                refs.append(href) 
    count = count + 1


uzeit = list_of_contents[0::4]
topicno = list_of_contents[1::4]
topiclist = list_of_contents[2::4]
status = list_of_contents[3::4]
url = refs[0::1]

print(*topiclist, sep="\n")
#print(topicno)
#print(status)
#print(subject)
print(url) 
    
#print(list_of_contents)

f = open('BT_Tagesordnung.txt', 'w', encoding='utf-8', errors='replace')
f.write("\n".join(str(item) for item in topiclist))
f.close

f = open('BT_Tagesordnung.txt', 'a')
f.write("\n".join(str(item) for item in url))
f.close