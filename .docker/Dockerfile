FROM prestashop/prestashop-git:7.2
WORKDIR /var/www/html/

# To run files with the same group as your primary user
ARG GROUP_ID
ARG USER_ID

RUN groupmod -g $GROUP_ID www-data \
  && usermod -u $USER_ID -g $GROUP_ID www-data

#COPY .docker/install_ps.sh /tmp/install_ps.sh

# Permissions
RUN mkdir -p /var/www/.npm
RUN mkdir -p /var/www/.composer
RUN mkdir -p /var/www/html/var

RUN chown -R www-data:www-data /var/www/
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN find /var/www/html -type f -exec chmod 644 {} \;

#RUN chmod +x /tmp/install_ps.sh
RUN chmod -R a+w /var/www/html/var
RUN chmod -R a+w /var/www/html/modules
RUN chmod a+x /var/www/html/tools/assets/build.sh

# Installation
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt install -y nodejs

RUN runuser -g www-data -u www-data -- /usr/local/bin/composer install --no-interaction
RUN runuser -g www-data -u www-data -- /usr/bin/make assets

#ENTRYPOINT [ "/tmp/install_ps.sh" ]