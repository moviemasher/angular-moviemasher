FROM php:apache
MAINTAINER Movie Masher <support@moviemasher.com>

# create queue directory in case one isn't mounted
RUN mkdir -p -m 0777 /tmp/moviemasher/queue
VOLUME /tmp/moviemasher/queue

# create temporary directory in case one isn't mounted
RUN mkdir -p -m 0777 /tmp/moviemasher/temporary
VOLUME /tmp/moviemasher/temporary

# allow log directory to be mounted
RUN mkdir -p -m 0777 /tmp/moviemasher/log
VOLUME /tmp/moviemasher/log

# install our php configuration
COPY config/docker/php.ini /usr/local/etc/php/conf.d/moviemasher.ini

# install our movie masher configuration
COPY config/moviemasher.ini /var/www/config/moviemasher.ini

# install entire project
COPY . /var/www/html/

# create user data/media directory in case it doesn't exist
RUN mkdir -p -m 0777 /var/www/html/user
VOLUME /var/www/html

