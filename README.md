# grafana-scalyr-proxy-server
A proxy server that receives requests from a grafana instance, parses the request into commands made to the Scalyr api and returns the scalyr data in a grafana-friendly format

# Installation
## Get the Plugin
Ensure you've cloned https://github.com/AdknownInc/grafana-scalyr-datasource-plugin locally. 

## Config
### Docker Compose
Modify the **docker-compose.yml** to:
 - contain a Scalyr [Read API key](https://www.scalyr.com/keys) generated by your Scalyr account.
    - Replace the SCALYR_READ_KEY value with your key
 - Change the volume that contains the plugin to point your locally cloned plugin's directory. My development setup looks like this: 
    - ~
        - projects/github.com
            - grafana-scalyr-proxy-server
            - grafana-scalyr-datasource-plugin
    - For that reason, my volume is pointing to "../../grafana-scalyr-datasource-plugin"
 - Optional: Modify DOCKER_HOST_NAME=grafanaProxy to contain a hostName that corresponds with your company's serverHost name standards.
    - We'll be using this value later to verify it was installed correctly 

### Scalyr - api_key.json
Modify the file `scalyr/agent.d/api_key.json` to also have your Scalyr api key in that file.
 
## Install
Run `docker-compose up` while in the **docker-container** directory

In your browser, navigate to http://localhost:8000 and login to grafana
username: admin
password: admin

Add a datasource, the datasource'ss name is Scalyr
Enter "Scalyr" for the Name

In the **HTTP** section, enter "http://web:8080" as the URL
Set "Access" to "proxy"

Click "Save & Test"

You should see this

![Plugin Configured Screenshot](https://raw.githubusercontent.com/AdknownInc/grafana-scalyr-proxy-server/master/imgs/ScalyrDatasource.png)

After that, create a new dashboard and add a panel
Change the Data Source to **Scalyr**

Enter "First" as the target type (this can be any non-empty value)

Enter **$serverHost = 'grafanaProxy'** as the Filter

Change the Graph Function to **Count**

Change the Interval (seconds) to **30**

At the end, you'll see this:   
![First Graph Config](https://raw.githubusercontent.com/AdknownInc/grafana-scalyr-proxy-server/master/imgs/GrafanaScalyrFirstGraphConfig.png)

And that's it, you have configured the scalyr data source.

## Alerts - ElasticSearch Hack

At the moment, grafana does not support alerts of custom datasource plugins. This is because alerts call the backend of grafana and require a custom go function handler to run in response to the alert endpoint being hit.

To allow the use of alerts, this backend will support an elasticsearch datasource plugin to allow alerts. It will require your scalyr queries to be in complex query format, but you'll get grafana alerts out of it.

### ElasticsearchFaker install

#### TODO
instructions on installing an elasticsearch datasource plugin that points to this proxy server

#### TODO
instructions on setting up alerts for Scalyr 

# TODO:
* Add a Contribution guide
* Add further documentation about numeric vs facet vs complex
* Modify complex queries from using regex to using a grammar + parser so that all queries supported by Scalyr can be entered into grafana
* Add authentication
  

## Server Configuration

#### Services/Components

* PHP 7.1

In it's current form, the docker-compose file is setup to also bootup a grafana instance. Unsure if it will stay this way.
