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

c_count = 9
list_of_contents = []
refs = []
output = []

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
        list_of_contents.append(td.getText().strip())
        for links in td.findAll('a'):
            href = links.get('href')
            if href != '#': 
               refs.append(href) 
    count = count + 1

#list_of_contents = np.array(list_of_contents)

uzeit = list_of_contents[0::4]
topicno = list_of_contents[1::4]
topiclist = list_of_contents[2::4]
status = list_of_contents[3::4]
url = refs[:]

#remove empty lines
topiclist = ("\n".join(str(item) for item in topiclist))
lines = topiclist.split("\n")
non_empty_lines = [line for line in lines if line.strip() != ""]

topiclist = ""

for line in non_empty_lines:
      topiclist += line + "\n"

topiclist = topiclist.split("zum Artikel")
for topics in topiclist: 
    topics = topics.replace("Sitzungseröffnung"," ")
    topics = topics.replace("Sitzungsende"," ")
    topics = topics.replace("Drucksache"," Drucksache")
    topics = topics.split("\n")
    headers = topics[1]
    contents = topics[2]
    output.append(headers.strip())
    output.append(contents.strip())

print(output)

#print(topicno)
#print(status)
#print(url)
#print(uzeit) 
#list_of_contents.remove("\n")
#list_of_contents.remove(" ")


#print(list_of_contents)

#print(topiclist)

f = open('BT_Tagesordnung.txt', 'w', encoding='utf-8', errors='replace')
f.write("\n".join(str(item) for item in output))
f.close

#f = open('BT_Tagesordnung.txt', 'a')
#f.write("\n".join(str(item) for item in url))
#f.close