#!/bin/sh
set -e

wp core install --url=http://localhost:8889 --title=LibreSign --admin_user=admin --admin_password=password --admin_email=admin@example.org --skip-email
wp rewrite structure '/%postname%/'
wp plugin activate woocommerce
wp theme activate libresign

wp option update woocommerce_coming_soon no
wp option update woocommerce_default_country BR:RJ
wp option update woocommerce_enable_myaccount_registration yes
wp option update woocommerce_registration_generate_username yes
wp option update woocommerce_registration_generate_password no
wp option update woocommerce_cheque_settings '{"enabled":"yes","title":"Check payments"}' --format=json

if [ -z "$(wp post list --post_type=product --name=basic --format=ids)" ]; then
	wp eval-file /seed.php
fi
