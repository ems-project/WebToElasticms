ARG VERSION_ARG

FROM docker.io/elasticms/base-php-cli-dev:7.4 as builder

ARG RELEASE_ARG=""
ARG BUILD_DATE_ARG=""
ARG VCS_REF_ARG=""

COPY . /opt/src/

RUN echo "Install ..." \
    && COMPOSER_MEMORY_LIMIT=-1 composer -vvvv install --no-interaction --no-suggest --no-scripts --working-dir /opt/src -o 

FROM docker.io/elasticms/base-php-cli:7.4

ARG RELEASE_ARG
ARG BUILD_DATE_ARG
ARG VCS_REF_ARG

LABEL eu.elasticms.web2elasticms.build-date=$BUILD_DATE_ARG \
      eu.elasticms.web2elasticms.name="" \
      eu.elasticms.web2elasticms.description="" \
      eu.elasticms.web2elasticms.url="https://hub.docker.com/repository/docker/elasticms/web2ems" \
      eu.elasticms.web2elasticms.vcs-ref=$VCS_REF_ARG \
      eu.elasticms.web2elasticms.vcs-url="https://github.com/ems-project/WebToElasticms" \
      eu.elasticms.web2elasticms.vendor="sebastian.molle@gmail.com" \
      eu.elasticms.web2elasticms.version="$VERSION_ARG" \
      eu.elasticms.web2elasticms.release="$RELEASE_ARG" \
      eu.elasticms.web2elasticms.schema-version="1.0" 

COPY --from=builder /opt/src /opt/src

WORKDIR /opt/src
