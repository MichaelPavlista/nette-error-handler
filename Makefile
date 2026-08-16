CURRENT_DIR = $(CURDIR)
export MSYS_NO_PATHCONV = 1
export MSYS2_ARG_CONV_EXCL = *

# Definice nejnižší a nejvyšší podporované verze PHP.
MIN_PHP = 82
MAX_PHP = 85

# Verze PHP a PHP containeru, pod kterou se spouští composer a kontroly (např. make t PHP=85). Default = nejnižší podporovaná verze.
PHP ?= $(MIN_PHP)

# Lokální image postavený nad oficiálním php:*-cli z Docker Hubu (viz docker/Dockerfile).
IMAGE_PREFIX = nette-error-handler-php
PHP_IMAGE = $(IMAGE_PREFIX)$(PHP)
MIN_PHP_IMAGE = $(IMAGE_PREFIX)$(MIN_PHP)
MAX_PHP_IMAGE = $(IMAGE_PREFIX)$(MAX_PHP)

# Composer cache se drží v temp/, aby se soubory ve vendor/ nevytvářely pod rootem.
DOCKER_RUN = docker run --rm -u $$(id -u):$$(id -g) -e COMPOSER_HOME=/tmp/composer -e COMPOSER_CACHE_DIR=/package/temp/.composer-cache -v "$(CURRENT_DIR):/package" -w /package

# Definice výchozího příkazu.
.DEFAULT_GOAL := t

.PHONY: build-image composer-install ci composer-update cu checks c all-tests tests t all-fix fix f tf cf \
        phpstan ps phpstan-generate-baseline psgb phpcs pcs phpcs-summary pcss phpcs-fix pcsf rector r rector-fix rf

### DOCKER IMAGE #######################################################################################################
# Sestavení lokálního image nad oficiálním php:$(PHP)-cli. Docker si build cachuje, opakované volání je prakticky zdarma.
build-image-%:
	@docker build -q --build-arg PHP_VERSION=$$(echo $* | sed 's/^\(.\)/\1./') -t $(IMAGE_PREFIX)$* docker > /dev/null

### COMPOSER ###########################################################################################################
composer-install ci: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) composer install -o

composer-update cu: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) composer update -o

### TESTY ##############################################################################################################
# VŠECHNY KONTROLY BEZ composer update
checks c: phpstan phpcs rector

# VŠECHNY TESTY (stačí spustit make, volba PHP verze: make t PHP=85)
all-tests tests t: cu checks

# AUTOMATICKÉ OPRAVY KÓDU (rector + phpcs, volba PHP verze: make f PHP=85)
all-fix fix f tf cf: rector-fix phpcs-fix

# PHPSTAN — tři běhy:
#   1) hlavní analýza pro nejnovější podporovanou verzi PHP (typové chyby + deprecations, patří k ní baseline),
#   2) kontrola kompatibility s nejstarší podporovanou verzí PHP (moc nová syntax apod.), která musí běžet na nejnovější verzi PHP,
#   3) tatáž kontrola spuštěná v containeru s nejstarší podporovanou verzí PHP (pojistka — chování PHPStanu částečně závisí i na verzi PHP, pod kterou běží).
phpstan ps: build-image-$(MIN_PHP) build-image-$(MAX_PHP)
	@echo === PHPSTAN 1/3: phpstan:min-php, php$(MAX_PHP) ===
	$(DOCKER_RUN) $(MAX_PHP_IMAGE) bash -c "set -o pipefail && composer run phpstan:min-php | tee temp/phpstan-min-php-$(MAX_PHP).txt"
	@echo === PHPSTAN 2/3: phpstan:max-php, php$(MAX_PHP) ===
	$(DOCKER_RUN) $(MAX_PHP_IMAGE) bash -c "set -o pipefail && composer run phpstan:max-php | tee temp/phpstan-max-php-$(MAX_PHP).txt"
	@echo === PHPSTAN 3/3: phpstan:min-php, php$(MIN_PHP) ===
	$(DOCKER_RUN) $(MIN_PHP_IMAGE) bash -c "set -o pipefail && composer run phpstan:min-php | tee temp/phpstan-min-php-$(MIN_PHP).txt"

# Generování baseline provádíme vždy na nejvyšší verzi PHP.
phpstan-generate-baseline psgb: build-image-$(MAX_PHP)
	$(DOCKER_RUN) $(MAX_PHP_IMAGE) composer run phpstan:generate-baseline

# PHPCS
phpcs pcs: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) bash -c "set -o pipefail && composer run phpcs | tee temp/phpcs.txt"

phpcs-summary pcss: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) bash -c "set -o pipefail && composer run phpcs:summary | tee temp/phpcs.txt"

phpcs-fix pcsf: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) composer run phpcs:fix

# RECTOR
rector r: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) bash -c "set -o pipefail && composer run rector | tee temp/rector.txt"

rector-fix rf: build-image-$(PHP)
	$(DOCKER_RUN) $(PHP_IMAGE) composer run rector:fix
