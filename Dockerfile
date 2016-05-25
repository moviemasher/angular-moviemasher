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
VOLUME /var/www/html

# install our php configuration
COPY config/docker/php.ini /usr/local/etc/php/conf.d/moviemasher.ini

# install our movie masher configuration
COPY config/docker/moviemasher.ini /var/www/config/moviemasher.ini

# install our redirect from web root to angular-moviemasher/app
COPY config/index.html /var/www/html/

# install entire project at root
COPY . /var/www/html/angular-moviemasher/
