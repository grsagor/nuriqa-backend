# Gazian Water Module

Gazian lives as a **nwidart/laravel-modules** module at `Modules/Gazian`.

## Structure

```
Modules/Gazian/
├── app/
│   ├── Http/Controllers/Admin/   # Blade dashboard
│   ├── Http/Controllers/Api/     # Public form APIs
│   ├── Http/Requests/
│   ├── Mail/
│   ├── Models/
│   └── Providers/
├── config/config.php             # brand_name, admin_email
├── database/migrations/
├── resources/views/              # gazian::* views + emails
└── routes/
    ├── web.php                   # /gazian/admin/*
    └── api.php                   # /api/v1/gazian/*
```

## Enable / disable

```bash
php artisan module:list
php artisan module:enable Gazian
php artisan module:disable Gazian
```

## Admin dashboard

- URL: `/gazian/admin`
- Auth: same Nuriqa admin login (`role:admin`)
- Menu: Dashboard | Newsletter | Trade Enquiries
- Not linked from the Nuriqa `/admin` sidebar

## API

```
POST /api/v1/gazian/newsletter
POST /api/v1/gazian/trade-enquiry
```

## Env

```env
GAZIAN_ADMIN_EMAIL=ashraf@hajimail.com
GAZIAN_BRAND_NAME="Gazian Water"
```

## Frontend (gazian-water)

```env
NEXT_PUBLIC_GAZIAN_API_URL=http://localhost:8000/api/v1/gazian
```
