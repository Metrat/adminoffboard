# AdminOffboard Makefile
# Version: 0.1.0

APP_NAME=adminoffboard
APP_VERSION=$(shell grep -m1 version appinfo/info.xml | grep -oP '(?<=>)[^<]+')
BUILD_DIR=build
DIST_DIR=dist
ARCHIVE_NAME=$(APP_NAME)-$(APP_VERSION).tar.gz

# PHP
PHP=php
COMPOSER=composer
PHPCS=vendor/bin/phpcs
PHPCBF=vendor/bin/phpcbf
PHPSTAN=vendor/bin/phpstan
PSALM=vendor/bin/psalm
PHPUNIT=vendor/bin/phpunit

# Node.js (for frontend assets)
NPM=npm
NODE=node

.PHONY: help install build clean test cs-check cs-fix static-analysis archive dist

help:
	@echo "AdminOffboard Makefile"
	@echo ""
	@echo "Available targets:"
	@echo "  install         Install dependencies"
	@echo "  build           Build the app"
	@echo "  clean           Clean build files"
	@echo "  test            Run tests"
	@echo "  cs-check        Check coding standards"
	@echo "  cs-fix          Fix coding standards"
	@echo "  static-analysis Run static analysis"
	@echo "  archive         Create distribution archive"
	@echo "  dist            Create distribution package"

install:
	$(COMPOSER) install --prefer-dist --no-dev --no-interaction
	$(COMPOSER) install --prefer-dist --no-interaction --working-dir=tools

build: clean install
	@echo "Building $(APP_NAME) v$(APP_VERSION)"
	mkdir -p $(BUILD_DIR)
	# Copy app files
	cp -r appinfo $(BUILD_DIR)/
	cp -r lib $(BUILD_DIR)/
	cp -r templates $(BUILD_DIR)/
	cp -r css $(BUILD_DIR)/
	cp -r js $(BUILD_DIR)/
	cp -r img $(BUILD_DIR)/
	cp -r l10n $(BUILD_DIR)/
	# Copy root files
	cp composer.json $(BUILD_DIR)/
	cp composer.lock $(BUILD_DIR)/
	cp bootstrap.php $(BUILD_DIR)/
	cp CHANGELOG.md $(BUILD_DIR)/
	cp LICENSE $(BUILD_DIR)/
	cp README.md $(BUILD_DIR)/
	# Remove development files
	rm -rf $(BUILD_DIR)/tests
	rm -rf $(BUILD_DIR)/.git
	rm -rf $(BUILD_DIR)/.gitignore
	rm -f $(BUILD_DIR)/Makefile
	rm -f $(BUILD_DIR)/phpunit.xml
	@echo "Build complete"

clean:
	rm -rf $(BUILD_DIR)
	rm -rf $(DIST_DIR)
	rm -rf coverage
	rm -rf .phpunit.cache
	rm -rf vendor
	rm -rf node_modules
	rm -f $(ARCHIVE_NAME)

test:
	$(PHPUNIT) --configuration phpunit.xml

test-coverage:
	$(PHPUNIT) --configuration phpunit.xml --coverage-html coverage/

cs-check:
	$(PHPCS) --standard=PSR12 --extensions=php lib/ appinfo/

cs-fix:
	$(PHPCBF) --standard=PSR12 --extensions=php lib/ appinfo/

static-analysis:
	$(PHPSTAN) analyse --level=8 lib/
	$(PSALM) --config=psalm.xml

archive:
	@echo "Creating distribution archive..."
	mkdir -p $(DIST_DIR)
	tar -czf $(DIST_DIR)/$(ARCHIVE_NAME) -C $(BUILD_DIR) .
	@echo "Archive created: $(DIST_DIR)/$(ARCHIVE_NAME)"

dist: build archive
	@echo "Distribution package created: $(DIST_DIR)/$(ARCHIVE_NAME)"

# Development targets
dev-install: install
	$(COMPOSER) install --prefer-dist --no-interaction
	# Install frontend dependencies if needed
	@echo "Development environment ready"

watch:
	@echo "Watching for changes..."
	# Implement watch functionality if needed

# CI/CD targets
ci: install cs-check static-analysis test
	@echo "CI checks passed"

ci-coverage: install test-coverage
	@echo "Coverage report generated"

# Database targets
db-migrate:
	$(PHP) occ db:add-migration adminoffboard

db-migrate-rollback:
	$(PHP) occ db:rollback-migration adminoffboard --rollback

# Utility targets
version:
	@echo $(APP_VERSION)

info:
	@echo "App: $(APP_NAME)"
	@echo "Version: $(APP_VERSION)"
	@echo "Build directory: $(BUILD_DIR)"
	@echo "Distribution directory: $(DIST_DIR)"

# Default target
default: help