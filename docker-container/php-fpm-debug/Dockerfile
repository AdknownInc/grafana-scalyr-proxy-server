FROM nanoninja/php-fpm:7.1

RUN mkdir -p /var/log/myapp && chown www-data /var/log/myapp

RUN sed -i -e 's/pm.max_children = 5/pm.max_children = 25/g' /usr/local/etc/php-fpm.d/www.conf