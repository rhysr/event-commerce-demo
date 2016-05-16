FROM ubuntu:14.04
MAINTAINER Rhys

RUN DEBIAN_FRONTEND=noninteractive apt-get -y update
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends php5-cli
RUN DEBIAN_FRONTEND=noninteractive apt-get purge -y --auto-remove

RUN useradd -ms /bin/bash app
RUN mkdir -p /var/apps
RUN chown app:app /var/apps

RUN mkdir /var/apps/ecommm
RUN ls -l /var/apps/
WORKDIR /var/apps/ecomm
COPY ./composer /var/apps/ecomm/composer
COPY ./composer.json /var/apps/ecomm/composer.json
COPY ./composer.lock /var/apps/ecomm/composer.lock
COPY ./vendor /var/apps/ecomm/vendor/

RUN chown -R app:app /var/apps/ecomm
USER app
RUN /var/apps/ecomm/composer install --prefer-dist