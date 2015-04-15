#!/usr/bin/env bash

SODIUMVER="1.0.2"
PECLSODIUMVER="0.1.3"

if [[ $EUID -ne 0 ]]; then
   echo "The installer must be run as root!" 1>&2
   exit 1
fi
if [ -h /usr/bin/asgard ]; then
    echo "    Cannot install: symlink /usr/bin/asgard already exists."
   exit 1
fi

# DEPENDCIES (DEBIAN SPECIFIC)
if [ ! -f /etc/php5/mods-available/libsodium.ini ]; then
    apt-get update
    apt-get install php5-cli php5-sqlite php5-dev php-pear

    # Get GnuPG public key for Frank Denis
    gpg --fingerprint 54A2B8892CC3D6A597B92B6C210627AABA709FE1
    if [ $? -ne 0 ]; then
        echo -e "\033[33mDownloading PGP Public Key...\033[0m"
        gpg --recv-keys 54A2B8892CC3D6A597B92B6C210627AABA709FE1
        # Frank Denis (Jedi/Sector One) <pgp@pureftpd.org>
        gpg --fingerprint 54A2B8892CC3D6A597B92B6C210627AABA709FE1
        if [ $? -ne 0 ]; then
            echo -e "\033[31mCould not download PGP public key for verification\033[0m"
            exit
        fi
    fi

    if [ ! -f libsodium-$SODIUMVER.tar.gz ]; then
        wget https://download.libsodium.org/libsodium/releases/libsodium-$SODIUMVER.tar.gz
    fi
    if [ ! -f libsodium-$SODIUMVER.tar.gz.asc ]; then
        wget https://download.libsodium.org/libsodium/releases/libsodium-$SODIUMVER.tar.gz.sig
    fi

    gpg --verify libsodium-$SODIUMVER.tar.gz.sig libsodium-$SODIUMVER.tar.gz
    if [ $? -eq 0 ]; then
        tar xzvf libsodium-$SODIUMVER.tar.gz
        cd libsodium-$SODIUMVER
        # The ritual
        ./configure
        make && make check && make install
    fi

    # Now let's install the libsodium PECL package
    pecl channel-update pecl.php.net
    pecl install channel://pecl.php.net/libsodium-$PECLSODIUMVER
    echo "extension=libsodium.so" > /etc/php5/mods-available/libsodium.ini
    php5enmod libsodium
else
    pecl upgrade
fi
asgardbase=$( dirname $( readlink -f ${BASH_SOURCE[0]} ) )
ln -s $asgardbase/asgard /usr/bin/asgard
chmod 0755 /usr/bin/asgard