PACKAGE_VERSION = $(shell composer config version)
PACKAGE_NAME    = $(shell composer config name | sed 's/\//_/')

EXCLUDE_FILES = $(shell cat .pkgignore | sed 's/^.*$/"\.\/&"\/' | tr '\n' ' ')

package:
	zip -r "./dist/$(PACKAGE_NAME)-$(PACKAGE_VERSION).zip" ./ -x $(EXCLUDE_FILES)