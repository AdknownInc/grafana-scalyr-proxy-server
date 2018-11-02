# Demo - Docker Basics

Adding a temp README to the repo for quick docker tips since there isn't a formal home for those at the moment and the team plans to adopt docker.

## Getting Started
To launch the grafana scalyr demo, cd into the 'docker-container' folder and call ```docker-compose up```.

## Making Changes
To have your container-level changes take effect (such as adding or removing a service, or installing something in the container) you'll need to restart the containers.
Run reset.sh and it will bring your container down and remove any built containers. You can then restart the container and your changes will have taken effect


## Modifying images
Sometimes you'll want to know the location of configuration files so that you can modify them with sed commands in the build phase. Their locations are not always obvious, so to find them you can get shell access into your active container by running
```docker exec -it $CONTAINER_NAME bash```

  

  