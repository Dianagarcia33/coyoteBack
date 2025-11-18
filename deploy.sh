#!/bin/bash

# Script de despliegue para el servidor de producción
# Ejecuta este script en el servidor después de hacer git pull

echo "🚀 Iniciando despliegue..."

# Instalar dependencias de Composer
echo "📦 Instalando dependencias de PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de Node (si las usas)
# echo "📦 Instalando dependencias de Node..."
# npm install --production

# Compilar assets (si usas Vite/Mix)
# echo "🔨 Compilando assets..."
# npm run build

# Limpiar cachés de Laravel
echo "🧹 Limpiando cachés..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones (opcional, descomenta si lo necesitas)
# echo "🗄️ Ejecutando migraciones..."
# php artisan migrate --force

echo "✅ Despliegue completado exitosamente!"
