FROM php:apache
MAINTAINER Movie Masher <support@moviemasher.com>

# create needed directories
RUN mkdir -p -m 0777 /tmp/moviemasher/log
RUN mkdir -p -m 0777 /tmp/moviemasher/queue
RUN mkdir -p -m 0777 /tmp/moviemasher/temporary
RUN mkdir -p -m 0777 /var/www/html/user-media
RUN mkdir -p -m 0777 /var/www/config
RUN mkdir -p -m 0777 /var/www/user-data

# give moviemasher.rb container access to relevant ones
VOLUME /tmp/moviemasher/log
VOLUME /tmp/moviemasher/queue
VOLUME /tmp/moviemasher/temporary
VOLUME /var/www/html/user-media

# install our php configuration
COPY config/docker/php.ini /usr/local/etc/php/conf.d/moviemasher.ini

# install our movie masher configuration
COPY config/docker/moviemasher.ini /var/www/config/moviemasher.ini

# install our redirect from web root to angular-moviemasher/app
COPY config/index.html /var/www/html/

# install entire project at root
COPY . /var/www/html/angular-moviemasher/

# EVERYTHING BELOW CAN BE UNCOMMENTED TO PRODUCE DEV IMAGE
## # install node
## RUN apt-get update && apt-get install -y wget build-essential python
##
## # install Node.js
## RUN \
##   cd /tmp && \
##   wget http://nodejs.org/dist/node-latest.tar.gz && \
##   tar xvzf node-latest.tar.gz && \
##   rm -f node-latest.tar.gz && \
##   cd node-v* && \
##   ./configure && \
##   CXX="g++ -Wno-unused-local-typedefs" make && \
##   CXX="g++ -Wno-unused-local-typedefs" make install && \
##   cd /tmp && \
##   rm -rf /tmp/node-v*
##
## # install bower and grunt
## RUN \
##   npm update -g npm && \
##   npm install -g bower grunt-cli
##
## # install utilities needed by composer
## RUN apt-get update && apt-get install -y git
## RUN apt-get update && apt-get install -y zlib1g-dev && docker-php-ext-install zip
##
## # install composer PHP dependency manager globally
## RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
