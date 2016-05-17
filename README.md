# Demo of event driven ecommerce 

## Environment

### Install docker
https://docs.docker.com/engine/installation/linux/ubuntulinux/

### Building containers
```shell
docker build -t ecomm_php -f Dockerfile.php .
docker build -t ecomm_gen -f Dockerfile.gen .
docker build -t ecomm_pos -f Dockerfile.pos .
```

### Start Containers
```shell
docker run -d --hostname ecomm-mq  --name ecomm_mq   -p 8080:15672 rabbitmq:3-management
```
Open http://localhost:8080  You may need to wait a few seconds
```shell
docker run -d --name ecomm_gen --link=ecomm_mq ecomm_gen
docker run -d --name ecomm_pos --link=ecomm_mq ecomm_pos
```
