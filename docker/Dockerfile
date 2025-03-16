ARG php_version
FROM webdevops/php-nginx:${php_version}

ENV container docker
ARG whmcs_src

# copy in WHMCS archive
ADD src/$whmcs_src /whmcs.zip
COPY conf/vhost.conf /opt/docker/etc/nginx/

RUN unzip /whmcs.zip -d /app && \
    touch /app/configuration.php && chmod 0777 /app/configuration.php && \
    rm -rf /whmcs.zip && \
    chown -Rf application.application /app

WORKDIR /app

VOLUME ["/app"]
