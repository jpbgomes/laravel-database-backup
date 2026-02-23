# Laravel Database Backup

A simple Laravel package to back up your **MySQL or MariaDB** database and send the backup file via email, now with optional ZIP compression, password protection, and extra file/folder inclusion.

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
    'recipients' => array_filter(array_map('trim', explode(',', env('BACKUP_EMAIL', 'admin@example.com')))),
    'zip_password' => env('BACKUP_ZIP_PASSWORD', null),
    'include' => [
        // '.env',
        // 'storage/app/public',
    ],
    'keep_local' => false,
];
```

Set your backup recipients and optional ZIP password in `.env`:

```env
BACKUP_EMAIL=jpbgomesbusiness@gmail.com,arroz@gmail.com
BACKUP_ZIP_PASSWORD=
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

## 📝 Usage

Run the backup command:

```bash
php artisan backup:database
```

This will:

1. Create a `.sql` dump using `mysqldump`.
2. Generate a `.zip` file containing the SQL dump.
3. Optionally:

   * Protect the zip with a password.
   * Include extra folders/files in the zip.
4. Send the ZIP file to all configured recipients.
5. Optionally remove the local ZIP file.

---

## ⚙️ Advanced Configuration

### Multiple Recipients

```env
BACKUP_EMAIL=email1@gmail.com,email2@gmail.com
```

### Password Protect ZIP

```env
BACKUP_ZIP_PASSWORD=YourStrongPassword
```

If empty, the ZIP will not be encrypted.

### Include Additional Files/Folders

Edit `config/backup.php`:

```php
'include' => [
    '.env',
    'storage/app/public',
],
```

Paths are relative to `base_path()`.

---

## ⏰ Automating Daily Backups

You can automate backups using `crontab`. Example to run **every day at 4 AM**:

```cron
0 4 * * * sudo php /path_to/your_project/artisan backup:database >> /dev/null 2>&1
```

> Adjust the path to your Laravel project’s `artisan` file.

---

## 🛡 Security Notes & Caveats

* `mysqldump` must be installed and accessible to the PHP process user.
* DB credentials may appear briefly in process listings.
* For large databases, the backup is compressed in a ZIP.
* SQL file is never sent directly, reducing exposure.
* Password-protected ZIP uses AES-256 encryption.

---

## 🤝 Contributing & Improvements

Suggestions for improvements:

* Support for other database types (Postgres, SQLite).
* Remote storage integrations (S3, Google Cloud, etc.).
* Unit and integration tests for the backup command.
* CI/CD pipeline for testing and releases.

---

## 📜 License

MIT — free to use, modify, and share.
