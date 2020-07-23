PACKAGE_VERSION = $(shell composer config version)
PACKAGE_NAME    = $(shell composer config name | sed 's/\//_/')
FILE_PATH       = "./dist/$(PACKAGE_NAME)-$(PACKAGE_VERSION).zip"

EXCLUDE_FILES = $(shell cat .pkgignore | tr '\n' ' ')

package:
	rm $(FILE_PATH) && zip -r $(FILE_PATH) ./ -x $(EXCLUDE_FILES)

static-code-check:
    phpcs --standard=Magento2 .
