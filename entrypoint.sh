#!/bin/bash
set -e

# Ejecutar import de personajes al iniciar
php bin/console app:import:personajes

# Arrancar servidor PHP
php -S 0.0.0.0:8000 -t public
