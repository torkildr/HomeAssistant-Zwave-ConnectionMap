FROM debian:jessie
MAINTAINER torkildr

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
      apt-get install -y --no-install-recommends \
        php5-cli \
        php-pear \
        graphviz \
        ca-certificates \
        curl

COPY . /code
RUN curl -o /code/GraphViz.php \
      https://raw.githubusercontent.com/pear/Image_GraphViz/trunk/Image/GraphViz.php

WORKDIR /code

VOLUME /data

ENV LOG=OZW_Log.log
ENV CFG=zwcfg.xml
ENV OUTPUT=graph.svg

CMD ["sh", "-c", "php zwave-map.php /data/${LOG} /data/${CFG} /data/${OUTPUT}"]

