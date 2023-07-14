FROM debian



RUN apt update && apt upgrade -y

RUN apt install -y \
    apt-transport-https \
    lsb-release \
    ca-certificates \
    wget \
    curl \
    apache2 \
    --no-install-recommends

# Add ondrej sources for old php packages
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# PHP & Extensions
RUN DEBIAN_FRONTEND=noninteractive apt update && apt upgrade -y && apt install -y \
    php5.6 \
    php5.6-bcmath \
    php5.6-curl \
    php5.6-gd \
    php5.6-mcrypt \
    php5.6-mbstring \
    php5.6-mysql \
    php5.6-sqlite3 \
    php5.6-soap \
    php5.6-xml \
    php5.6-zip \
    php5.6-pdo \
    php5.6-xdebug

RUN echo "xdebug.remote_enable=on" >> /etc/php/5.6/mods-available/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /etc/php/5.6/mods-available/xdebug.ini \
    && echo "xdebug.remote_connect_back=off" >> /etc/php/5.6/mods-available/xdebug.ini \
    && echo "xdebug.remote_host=docker.for.mac.localhost" >> /etc/php/5.6/mods-available/xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /etc/php/5.6/mods-available/xdebug.ini

# PHP files should be handled by PHP, and should be preferred over any other file type
ENV APACHE_CONFDIR /etc/apache2
RUN { \
    echo '<FilesMatch \.php$>'; \
    echo '\tSetHandler application/x-httpd-php'; \
    echo '</FilesMatch>'; \
    echo; \
    echo 'DirectoryIndex index.php index.html'; \
    echo; \
    echo '<Directory /var/www/>'; \
    echo '\tOptions +Indexes'; \
    echo '\tAllowOverride All'; \
    echo '</Directory>'; \
    } | tee "$APACHE_CONFDIR/conf-available/docker-php.conf" \
    && a2enconf docker-php && a2enmod rewrite

STOPSIGNAL WINCH
WORKDIR /var/www

COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite \
    # Enable Apache mod_rewrite
    && a2enmod headers \
    # Enable Apache mod_rewrite
    && a2enmod expires

RUN apt update && apt upgrade -y -qq && apt install -y zlib1g-dev libpng-dev libmagickwand-dev libzip-dev git 
RUN apt-get -y install gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 fonts-liberation libappindicator1 libnss3 xdg-utils

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install node
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - && \
    apt-get install -y nodejs \
    build-essential && \
    node --version && \ 
    npm --version

EXPOSE 80
CMD apachectl -D FOREGROUND
