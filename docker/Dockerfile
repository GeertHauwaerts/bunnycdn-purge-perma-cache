FROM ubuntu

##
# Application Packages
##

RUN apt-get update \
  && export DEBIAN_FRONTEND=noninteractive \
  && apt-get -y install --no-install-recommends composer git php-bcmath php-curl php-mbstring php-zip php-xml unzip zip

RUN composer config -g repos.packagist composer https://packagist.fi \
  && composer global require hirak/prestissimo --no-plugins --no-scripts

##
# Cleanup
##

RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

##
# Start the Application
##

WORKDIR /var/www
CMD ./docker/entrypoint.sh
