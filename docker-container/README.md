# Docker Basics

Adding a temp README to the repo for quick docker tips since there isn't a formal home for those at the moment and the team plans to adopt docker.

## Getting Started
To launch the mystique docker environment, cd into the 'dev-container' folder and call ```docker-compose up```.
If you want to use a php debugger, use the docker-compose-debug file with ```docker-compose -f docker-compose-debug.yml up```

Adding a -d flag will put it in detached state, not sending log messages to your terminal's STDOUT.

## Making Changes
To have your container-level changes take effect (such as adding or removing a service, or installing something in the container) you'll need to restart the containers.
Run reset.sh and it will bring your container down and remove any built containers. You can then restart the container and your changes will have taken effect


## Modifying images
Sometimes you'll want to know the location of configuration files so that you can modify them with sed commands in the build phase. Their locations are not always obvious, so to find them you can get shell access into your active container by running
```docker exec -it $CONTAINER_ID sh```

  

  