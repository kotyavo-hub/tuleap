FROM pockost/composer-mysql

RUN mkdir /root/composer && \
    echo '{ "minimum-stability": "dev" }' > /root/composer/composer.json && \
    composer global require sensiolabs/melody

ENV PATH=/root/composer/vendor/bin:$PATH

ENTRYPOINT []

