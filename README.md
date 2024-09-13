# Monri Payments
Module for Magento 2, now Adobe Commerce

https://commercemarketplace.adobe.com/monripayments-magento2.html

## Compatibility
Our goal is to be compatible with the following Magento 2 versions:
- 2.4
- 2.3

## How to contribute

### Community
Feel free to open an Issue or Pull request on Github and it will be reviewied.

### Updating the version
When updating the project, you must make changes to the following files:
* composer.json
* etc/adminhtml/system.xml

All files should reflect the upcoming version, with the exception of `etc/module.xml` which only needs
to be updated in the case of performing database updates.

### Changelog
Changelog should be updated regularly in order to keep track of updates to the project.

### Releasing
You can package this repository using the `make package` command that will automatically
exclude any unwanted directories. The built package will be placed inside of the dist/ directory.

### .pkgignore
This file can be used for ignoring specific directories or files in the final package. In order to
ignore an entire directory, you must both write the directory name in format `DIRECTORYNAME/` and `*DIRECTORYNAME/**\*`
Any files that may contain sensitive information or otherwise not be included in the final build should be 
added to the .pkgignore file.
