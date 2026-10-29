FROM devitools/php-hyperf:8.3-dev@sha256:285435f969f1309e78e24a9b432f92f44dd6753621b10b5faa94affd66a1b925

RUN apk add --no-cache --virtual .mongodb-build-deps autoconf g++ make openssl-dev php83-dev \
    && curl --fail --location --retry 3 https://pecl.php.net/get/mongodb-1.21.12.tgz -o /tmp/mongodb.tgz \
    && echo 'd4d2a8ad034685eacd7ea3ed96c0c9aed8c0165ea6e79d106bdf95214d5eaa35  /tmp/mongodb.tgz' | sha256sum -c - \
    && tar -xzf /tmp/mongodb.tgz -C /tmp \
    && cd /tmp/mongodb-1.21.12 \
    && phpize83 \
    && ./configure --with-php-config=/usr/bin/php-config83 --with-mongodb-ssl=openssl \
    && make -j2 \
    && make install \
    && cd / \
    && rm -rf /tmp/mongodb* \
    && apk del .mongodb-build-deps \
    && php -r 'exit(PHP_VERSION_ID >= 80336 && phpversion("mongodb") === "1.21.12" ? 0 : 1);'
