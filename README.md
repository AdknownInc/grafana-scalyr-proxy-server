# grafana-scalyr-proxy-server
A proxy server that receives requests from a grafana instance, parses the request into commands made to the Scalyr api and returns the scalyr data in a grafana-friendly format

## Server Configuration

#### Services/Components

* PHP 7.1

In it's current form, the docker-compose file is setup to also bootup a grafana instance. Unsure if it will stay this way.

Remember to run ```composer install``` while cd'd into the ```source/vendor``` folder
