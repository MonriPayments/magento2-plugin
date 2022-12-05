#!/usr/bin/env sh
version=$(composer config version)
echo "Updating version to $version"
sed -i "s/Version - [0-9]\.[0-9]\.[0-9]/Version - $version/g" etc/adminhtml/system.xml
sed -i "s/@version [0-9]\.[0-9]\.[0-9]/@version $version/g" view/frontend/web/js/view/monri_components.js
sed -i "s/@version [0-9]\.[0-9]\.[0-9]/@version $version/g" view/frontend/web/js/view/monri_payments.js
