#!/bin/bash

# Comprovem si s'ha passat un nom de model
if [ -z "$1" ]; then
    echo "Ús: ./make-resource.sh NomDelModel"
    exit 1
fi

MODEL=$1
# Pluralitzem el nom per al Seeder (opcional, però queda millor: StudentsTableSeeder)
PLURAL=$(echo "${MODEL}s")

echo "Generant estructura per a: $MODEL..."

# 1. Crear Model i Migració
php artisan make:model "$MODEL" -m

# 2. Crear Controlador d'API dins la carpeta Api
php artisan make:controller "Api/${MODEL}Controller" --api --model="$MODEL"

# 3. Crear el Seeder amb el nom TableSeeder
php artisan make:seeder "${PLURAL}TableSeeder"

# 4. Crear el Resource
php artisan make:resource "${MODEL}Resource"

echo "Fet! Recorda revisar la migració i el namespace del controlador."
