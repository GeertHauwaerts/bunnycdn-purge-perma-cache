FROM ubuntu

##
# Application Packages
##

RUN apt-get update \
  && export DEBIAN_FRONTEND=noninteractive \
  && apt-get -y install --no-install-recommends ca-certificates git php-bcmath php-cli php-curl php-mbstring php-redis php-zip php-xml unzip zip

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

##
# Cleanup
##

RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

##
# Start the Application
##

WORKDIR /opt/bppc
CMD composer run start
