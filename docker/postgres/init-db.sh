#!/bin/sh
# Runs automatically on first container start (docker-entrypoint-initdb.d).
# Creates the app role and this deployment's database + a matching _test
# database, matching the native local-dev setup in docs/decisions.md.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-SQL
    CREATE ROLE ${APP_DB_USER} WITH LOGIN PASSWORD '${APP_DB_PASSWORD}' CREATEDB;
    CREATE DATABASE ${APP_DB_NAME} OWNER ${APP_DB_USER};
    CREATE DATABASE ${APP_DB_NAME}_test OWNER ${APP_DB_USER};
SQL
