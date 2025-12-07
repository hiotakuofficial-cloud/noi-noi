# Use an official PHP image with an Apache web server
FROM php:8.2-apache

# Copy your application code from the current directory to the web server's public directory
COPY . /var/www/html/

# Expose port 80 to allow HTTP traffic
EXPOSE 80
