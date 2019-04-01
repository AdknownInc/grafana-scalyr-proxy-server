# Demo
## Getting Started

Copy the existing `.env.example` file into a `.env` file, filling in the required scalyr Logs Access read key as well as a Configuration Access read key.
 
To launch the grafana scalyr demo, cd into the 'docker-container' folder and call ```docker-compose up```.

## Development
If you're doing dev work, cd into the `source` directory and run `composer install`

Then run `docker-compose -f docker-compose-dev.yml up` to use the dev version of the docker-compose which will allow adhoc development of the server

## Additional Notes
For whatever reason the mysql container was having issues with initializing a `grafana` schema. Possibly will need to connect to the mysql instance and create one manually if this is the first time booting up the container


  

  