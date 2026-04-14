#!/bin/bash

# Start php-fpm in background
php-fpm &

# Wait a moment for php-fpm to start
sleep 2

# Start nginx in foreground
nginx -g 'daemon off;'
