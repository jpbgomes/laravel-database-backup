# Laravel Database Backup

A simple Laravel package to back up your **MySQL or MariaDB** database and send the backup file via email.

> ⚠️ Only MySQL/MariaDB are supported currently, since the package relies on `mysqldump`.

---

## 🚀 Installation

Require the package via Composer (from Packagist):

```bash
composer require jpbgomes/laravel-database-backup
```

Laravel will auto-discover the service provider. If not, add it manually to `config/app.php`:

```php
'providers' => [
    Jpbgomes\DatabaseBackup\BackupServiceProvider::class,
]
```

Publish the config file:

```bash
php artisan vendor:publish --provider="Jpbgomes\\DatabaseBackup\\BackupServiceProvider" --tag=config
```

---

## ⚙️ Configuration

Edit `config/backup.php` after publishing:

```php
return [
    'path' => storage_path('app/backups'),
    'recipient' => env('BACKUP_EMAIL', 'admin@example.com'),
    'keep_local' => false,
];
```

Set your backup recipient in `.env`:

```env
BACKUP_EMAIL=your@email.com
```

---

### Mailer Configuration

You must configure Laravel’s mailer to send the backups. A **free recommended option** is Gmail, but you must enable **2-Factor Authentication** and create an **App Password**.  

Example `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD='your_app_password'
MAIL_FROM_ADDRESS="your_email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

### 📝 Usage

Run the backup command:

```bash
php artisan backup:database
```

This will:

1. Create a `.sql` dump of your MySQL/MariaDB database using `mysqldump`.
2. Email it to the configured recipient.
3. Optionally remove the local file after sending, depending on `backup.keep_local`.

---

### ⏰ Automating Daily Backups

You can automate backups using `crontab`. Example to run **every day at 4 AM**:

Generic command:

```cron
0 4 * * * sudo php /path_to/your_project/artisan backup:database >> /dev/null 2>&1
```

Concrete example:

```cron
0 4 * * * sudo php /var/www/laravel-database-backup/artisan backup:database >> /dev/null 2>&1
```

> Adjust the path to your Laravel project’s `artisan` file.  

---

## 🛡 Security Notes & Caveats

- `mysqldump` must be installed and accessible to the PHP process user.
- DB credentials may appear briefly in process listings.
- For large databases, consider compressing the `.sql` dump before emailing.
- Automatic cache clearing is not included; handle environment-specific operations separately.

---

## 🤝 Contributing & Improvements

Suggestions for improvements:

- Support for other database types (Postgres, SQLite).
- Compression options and retention policies.
- Remote storage integrations (S3, Google Cloud, etc.).
- Unit and integration tests for the backup command.
- CI/CD pipeline for testing and releases.

---

## 📜 License

MIT — free to use, modify, and share.