# Demo of event driven ecommerce 

## Environment

### Install docker
https://docs.docker.com/engine/installation/linux/ubuntulinux/

### Configure DNS
In some corporate networks the default DNS in docker containers is blocked.

In ubuntu, this can be a problem

See https://docs.docker.com/engine/installation/linux/ubuntulinux/#configure-a-dns-server-for-use-by-docker

Pay particular attention to the note about laptops

```shell
sudo vim /etc/default/docker
```
Add corporate DNS server(s)
```
DOCKER_OPTS="--dns X.X.X.X --dns Y.Y.Y.Y"   
```
Restart the docker daemon
```shell
sudo /etc/init.d/docker restart
```
### Start application
```shell
./docker-compose up -d 
./docker-compose logs -f generate pos products
```
Open `http://localhost:8989`  You may need to wait a few seconds
