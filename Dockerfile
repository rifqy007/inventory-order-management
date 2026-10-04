FROM php:8.2-apache-bookworm

# Memperbarui daftar package Debian dan memasang dependency sistem.
#
# Fungsi masing-masing package:
# - libzip-dev:
#   Library development yang dibutuhkan untuk membangun ekstensi PHP zip.
#
# - unzip:
#   Digunakan Composer untuk mengekstrak dependency berformat ZIP.
#
# - git:
#   Digunakan Composer sebagai alternatif ketika package perlu diambil
#   langsung dari source repository.
#
# Setelah dependency tersedia:
# - docker-php-ext-install memasang ekstensi PDO MySQL dan ZIP.
# - a2enmod rewrite mengaktifkan clean URL seperti /dashboard.
#
# rm -rf /var/lib/apt/lists/* menghapus cache daftar package agar
# ukuran image Docker tidak membesar secara tidak perlu.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        dom \
        xml \
        xmlwriter \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Menyalin konfigurasi VirtualHost Apache.
# Konfigurasi ini menetapkan folder public sebagai DocumentRoot.
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Mengambil executable Composer dari image resmi Composer.
# Dengan cara ini Composer tersedia di container aplikasi tanpa
# melakukan instalasi manual menggunakan installer tambahan.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Menentukan folder kerja utama di dalam container.
WORKDIR /var/www/html

# Salin kode aplikasi agar image dapat berjalan tanpa bind mount.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY app ./app
COPY config ./config
COPY public ./public
COPY views ./views
COPY scripts ./scripts
