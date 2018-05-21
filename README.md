**Google Search Console - in PHP**

A show case project for executing Google web search from within the console using PHP, then write the results to a PDF file.

To do so, we have to have a Google account in order to setup a project there and get an API Key.

##Prerequisites

###At PHP level: 
* readline library
* PHP version >= 5.6

###1. Get Google API Key 
  While logged in to your Google Account, follow these steps:
  #####1. Navigate to [https://console.developers.google.com](https://console.developers.google.com) and create a new project
  Select project:
  ![Project select](assets/images/guide01.jpg "Project select")  
  [Project add-new](assets/images/guide02.jpg)  
  [Project choose name and create](assets/images/guide03.jpg)
  
  #####2. Within the developer console, generate a new API Key for that Project just created
  [Go to credentials and select a project](assets/images/guide04.jpg)  
  [Create an API Key Credentials](assets/images/guide05.jpg)  
  ####Then copy the key and keep it.
  
  
  
  #####3. Navigate to the Dashboard and add the Custom Search API Service to the project
  [Go to Dashboard and click enable APIs](assets/images/guide06.jpg)  
  [Search for 'search'](assets/images/guide07.jpg)  
  [select the 'Custom Search'](assets/images/guide07.jpg)  
  [Enable Service](assets/images/guide08.jpg)  
    

###2. Generate Search Engine ID
  Also while logged in to Google Account, follow these steps:
  #####1. Navigate to [https://www.google.com/cse/all](https://www.google.com/cse/all) and create a new custom search
  [Create new Custom Search](assets/images/guide21.jpg)  
  In the "Sites to Search" enter "www.google.com" then click "Create"
  [Enter site and click create](assets/images/guide22.jpg)  
  On the left navigation pane, head to "Edit search engine" and select "Setup" leaf.
  Edit the "Sites to Search" and select "Search the entire web but emphasize included sites".
  Then click "Update" at the bottom to save.
  [Enable Search Entire web](assets/images/guide23.jpg) 
  In the same page, click on "Search Engine ID" to get the ID needed, copy it.
  [Get Search Engine ID](assets/images/guide24.jpg) 
  [Get Search Engine ID](assets/images/guide25.jpg) 

  
##Usage
Clone the repo and install the required packages using composer.
In project directory execute:
```
composer install
```

Then update the ```keys.json``` file in project directory to add your "API Key" and "Search Engine ID" generated above.

Basic Usage:
```
php ./google-search.php <Phrase to search> <Number of Results>
```
Both the <Phrase> and the <Number of results> are required.

If you have troubles with SSL Certificates (on WAMP stack mostly), use the ```--no-verify``` command line option to skip SSL verification.

Use ```--help``` to print the available options



