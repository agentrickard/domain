#!/bin/bash

# Run either PHPUnit tests or PHP_CodeSniffer tests on Travis CI, depending
# on the passed in parameter.

TEST_DIRS=($MODULE_DIR/domain/tests $MODULE_DIR/domain_access/tests $MODULE_DIR/domain_alias/tests $MODULE_DIR/domain_config/tests $MODULE_DIR/domain_content/tests $MODULE_DIR/domain_source/tests)

case "$1" in
    PHP_CodeSniffer)
        cd $MODULE_DIR
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    *)
        cd $DRUPAL_DIR
        EXIT=0
        for i in ${TEST_DIRS[@]}; do
          echo " > Executing tests from $i"
          php core/scripts/run-tests.sh --suppress-deprecations --verbose --color --concurrency 4 --php `which php` --url http://example.com:8080 $i || EXIT=1
        done
        exit $EXIT
esac
