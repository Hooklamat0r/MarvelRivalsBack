#!/bin/bash
set -e

export APP_ENV=prod
export APP_DEBUG=0

export SYMFONY_DOTENV_VARS=0

php bin/console app:import:personajes --no-interaction || true

php -S 0.0.0.0:8000 -t public
