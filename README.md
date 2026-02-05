# Sistem Monitoring & Evaluasi

Aplikasi web untuk monitoring dan evaluasi proyek berbasis Laravel 12 dengan template Star Admin 2.

## Requirements

Sebelum menginstall, pastikan komputer sudah memiliki:

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL/MariaDB
- Git

## Cara Install di Komputer Lain

### 1. Clone Repository

```bash
git clone https://github.com/IqbalCaraka/monev-ak.git
cd monev-ak
```

### 2. Install Dependencies PHP

```bash
composer install
```

### 3. Install Dependencies JavaScript

```bash
npm install
```

### 4. Setup Environment

Copy file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Atau di Windows:
```bash
copy .env.example .env
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Konfigurasi Database

Buka file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monev_ak
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Buat Database

Buat database baru di MySQL dengan nama `monev_ak` atau sesuai dengan yang di `.env`:

```sql
CREATE DATABASE monev_ak;
```

### 8. Jalankan Migrasi Database

```bash
php artisan migrate
```

### 9. Build Assets

```bash
npm run build
```

### 10. Jalankan Aplikasi

#### Menggunakan Laravel Development Server:

```bash
php artisan serve
```

Buka browser dan akses: `http://localhost:8000`

#### Menggunakan Laragon (Windows):

Jika menggunakan Laragon, letakkan project di folder `C:\laragon\www\` dan akses:
- `http://monev_dit_ak.test` atau
- `http://localhost/monev_dit_ak`

## Development

Untuk development dengan hot reload (auto refresh saat ada perubahan):

```bash
npm run dev
```

Jalankan di terminal terpisah bersamaan dengan Laravel server.

## Struktur Folder

```
monev_dit_ak/
├── app/
│   └── Http/
│       └── Controllers/
│           └── DashboardController.php
├── public/
│   └── assets/                 # Template Star Admin 2
├── resources/
│   ├── views/
│   │   ├── layouts/           # Layout components
│   │   │   ├── app.blade.php
│   │   │   ├── navbar.blade.php
│   │   │   ├── sidebar.blade.php
│   │   │   └── footer.blade.php
│   │   └── dashboard/         # Dashboard views
│   │       └── index.blade.php
│   └── template/              # Template source files
├── routes/
│   └── web.php
└── .env
```

## Troubleshooting

### Error: "No application encryption key has been specified"

Jalankan:
```bash
php artisan key:generate
```

### Error: Permission denied

Di Linux/Mac, berikan permission:
```bash
chmod -R 775 storage bootstrap/cache
```

### Error: Database connection failed

Pastikan:
1. MySQL service sudah running
2. Database sudah dibuat
3. Kredensial di `.env` sudah benar

### Assets tidak muncul

Jalankan:
```bash
npm run build
```

atau untuk development:
```bash
npm run dev
```

## Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL
- **Frontend**: Bootstrap 5
- **Template**: Star Admin 2
- **Build Tool**: Vite
- **CSS Framework**: Tailwind CSS

## License

MIT License

## Contact

Repository: [https://github.com/IqbalCaraka/monev-ak](https://github.com/IqbalCaraka/monev-ak)
