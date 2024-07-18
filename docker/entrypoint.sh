#!/bin/bash


# Function to wait for MySQL
# wait_for_mysql() {
#     echo "Attempting to connect to MySQL..."
#     echo "DB_HOST: ${DB_HOST}"
#     echo "DB_USERNAME: ${DB_USERNAME}"
#     echo "DB_PASSWORD: ${DB_PASSWORD}"

#     while ! mysql -h "${DB_HOST}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -e "SELECT 1;" >/dev/null 2>&1; do
#         echo "Waiting for MySQL to be available..."
#         sleep 3
#     done

#     echo "Successfully connected to MySQL!"
# }

# Check if the vendor/autoload.php directory exists, if not, run composer install
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-progress --no-interaction
fi

# Check if the .env file exists, if not, copy from .env.example
if [ ! -f ".env" ]; then
    echo "Creating .env file for environment $APP_ENV..."
    cp .env.example .env
    php artisan key:generate
else
    echo ".env file already exists."
fi


php-fpm -D
nginx -f "daemon off;"
# Wait for MySQL to be available
# wait_for_mysql

# Run Laravel migrations and seeders
echo "Running database migrations and seeders..."
php artisan migrate --force
php artisan db:seed --force

# Clear Laravel caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Serve the application
echo "Starting Laravel development server..."
php artisan serve --port=${PORT:-8000} --host=0.0.0.0 --env=.env

# Hand over control to the main entrypoint of the PHP Docker container
exec docker-php-entrypoint "$@"
