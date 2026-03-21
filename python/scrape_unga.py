import requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
from lxml import etree

session = 79
output = []
# GitHub API URL to list files in the repository
api_url = "https://api.github.com/repos/UNxml/GAresolutions/contents/"+str(session)+"session/English"

# Headers with your GitHub token (optional, if you're hitting rate limits)
headers = {
    "Accept": "application/vnd.github.v3+json",
    # "Authorization": "token YOUR_GITHUB_TOKEN"  # Uncomment and add your token if needed
}

def get_file_urls(api_url):
    """Fetches URLs for XML files from the GitHub API."""
    response = requests.get(api_url, headers=headers, verify=False)
    response.raise_for_status()
    
    # List of XML file URLs
    file_urls = [
        item["download_url"]
        for item in response.json()
        if item["name"].endswith(".xml")
    ]
    
    return file_urls

def parse_xml_from_url(url):
    """Fetches and parses XML data from a URL."""
    response = requests.get(url, verify=False)
    response.raise_for_status()

    try:
        # Parse the XML content with lxml
        root = etree.fromstring(response.content)
        
        # Define namespaces if present (Akoma Ntoso namespace in this case)
        namespaces = {'akn': 'http://docs.oasis-open.org/legaldocml/ns/akn/3.0'}
        
        # Use XPath to find <span> within <docTitle> with namespaces
        title = root.xpath(".//akn:docTitle/akn:span[@class='bold']/text()", namespaces=namespaces)

                # Use XPath to find <span> within <docTitle> with namespaces
        pdf = root.xpath(".//akn:docNumber/text()", namespaces=namespaces)
        
        # Print each extracted text
        for text in title:
            title = text.strip()
        output.append(title)
        
        for text in pdf:
            pdf = text.rstrip(".")
        if any(ch.isdigit() for ch in pdf):
            desc = 'United Nations General Assembly Resolution\nhttps://docs.un.org/A/RES/'+pdf
            output.append(desc)
        else:    
            output.pop()
            
    except etree.XMLSyntaxError as e:
        print(f"Error parsing XML: {e}")
        return

def main():
    # Get the list of XML file URLs
    xml_file_urls = get_file_urls(api_url)
    
    # Process each XML file
    for url in xml_file_urls:

        parse_xml_from_url(url)

# Run the main function
main()
print(output)
