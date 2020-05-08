#!/bin/bash
set -uex

[[ ${PROJECT_DIR} =~ workspace\/(.*?)\/source ]] && echo "${BASH_REMATCH[1]}"
CONTAINER_NAME=${BASH_REMATCH[1]}
CONTAINER_NAME=${CONTAINER_NAME//_/-}
CONTAINER_NAME='php-apache-'$CONTAINER_NAME

declare -a HOSTS=(${CONTAINER_NAME} 'example.local')
declare -a SUBDOMAINS=('one' 'two' 'three' 'four' 'five' 'six' 'seven' 'eight' 'nine' 'ten')

for HOST in ${HOSTS[@]}; do
    echo '127.0.0.1' ${HOST} >> /etc/hosts

    for SUBDOMAIN in ${SUBDOMAINS[@]}; do
      echo '127.0.0.1' ${SUBDOMAIN}.${HOST} >> /etc/hosts
    done
done

cat /etc/hosts
