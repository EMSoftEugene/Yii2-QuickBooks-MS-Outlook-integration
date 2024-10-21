FROM nginx

RUN apt-get update && apt-get install -y \
nano

##
# copying nginx configuration file
##
COPY nginx.conf /etc/nginx/nginx.conf
COPY intempus.conf /etc/nginx/conf.d/intempus.conf
