#!/usr/bin/env sh
version=$(composer config version)
echo "Updating version to $version"
sed -i "s/Version - [0-9]\.[0-9]\.[0-9]/Version - $version/g" etc/adminhtml/system.xml
