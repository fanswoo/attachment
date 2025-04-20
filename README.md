# Fanswoo/Attachment

## Fanswoo/Attachment for Filament

This package provides a Filament resource that shows you all of the activity logs and detailed view of each log created using the `spatie/laravel-activitylog` package. It also provides a relationship manager for related models.

### Requirements

-   Laravel v11
-   Filament v3

## Installation

You can install the package via composer:

```bash
composer require fanswoo/attachment
```

After that run the install command:

```bash
php artisan attachment:install
```

This will publish the migrations from `fanswoo/attachment`

And run migrates

```bash
php artisan migrate
```

You can manually publish the configuration file with:

```bash
php artisan vendor:publish --provider=FF\\Attachment\\Attachment\\AttachmentProvider --tag=migrations
```

## Usage

### Basic Spatie ActivityLog usage

In you `Model` add `Spatie\Activitylog\Traits\LogsActivity` trait

```php
use Illuminate\Database\Eloquent\Model;
use FF\Attachment\CanFile;

class Record extends Model
{
    use CanFile;
}
```
