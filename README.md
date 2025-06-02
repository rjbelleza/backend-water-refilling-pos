# Laravel 10 API Installation Guide

## 🛠️ Complete Setup Instructions

### 1. Clone the Repository
```bash
# Clone using HTTPS
git clone https://github.com/rjbelleza/backend-water-refilling-pos.git

# OR clone using SSH (if you have SSH keys set up)
git clone git@github.com:rjbelleza/backend-water-refilling-pos.git

# Navigate into the project directory
cd be-water-refilling-pos
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Create database (MySQL example)
mysql -u root -p "CREATE DATABASE aquasprings;"

# Update .env with your database credentials
DB_DATABASE=aquasprings
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run Migrations and Seeding
```bash
# Run database migrations
php artisan migrate

# (Optional) Seed the database
php artisan db:seed
```

### 6. Start the Development Server
```bash
# Start Laravel development server
php artisan serve
```

### 7. (Optional) Production Setup
```bash
# Optimize the application
php artisan optimize

# Cache routes and views
php artisan route:cache
```

### 🔍 Verification Steps
After installation, verify everything works:

1. Check the development server is running:

- Visit `http://localhost:8000` in your browser

- You should see the Laravel welcome page or your API docs
