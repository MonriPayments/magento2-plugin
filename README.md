# Monri Payments
Module for Magento 2

## Releasing
You can package this repository using the `make package` command that will automatically 
exclude any unwanted directories. The built package will be placed inside of the dist/ directory.

## Compatibility
Our goal is to be compatible with the following Magento 2 versions:
- 2.3
- 2.4

## How to contribute

### Updating the project
When updating the project, you must make changes to the following files:
* composer.json
* etc/adminhtml/system.xml
* etc/module.xml (this only needs to be changed if you need to perform a database update)

All files should reflect the upcoming version, with the exception of `etc/module.xml` which only needs
to be updated in the case of performing database updates.

### Changelog
Changelog should be updated regularly in order to keep track of updates to the project.

### .pkgignore
This file can be used for ignoring specific directories or files in the final package. In order to
ignore an entire directory, you must both write the directory name in format `DIRECTORYNAME/` and `*DIRECTORYNAME/**\*`
Any files that may contain sensitive information or otherwise not be included in the final build should be 
added to the .pkgignore file.
