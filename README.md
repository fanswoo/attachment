# Fanswoo/Attachment

## Fanswoo/Attachment for Filament

This package provides files and pictures upload manager. it can also support Filament forms field.

### Requirements

-   Laravel v11
-   Filament v3

## Installation

You can install the package via composer:

```bash
composer require fanswoo/attachment
```

After that run the `vendor:publish` command:

```bash
php artisan vendor:publish --provider=FF\\Attachment\\Attachment\\AttachmentProvider --tag=migrations
```

This will publish the migrations from `fanswoo/attachment`

And run migrates

```bash
php artisan migrate
```

## Usage

### Files usage

In you `Model` add `FF\Attachment\Relations\CanRelateFile` trait

```php
use Illuminate\Database\Eloquent\Model;
use FF\Attachment\Relations\CanRelateFile;

class Record extends Model
{
    use CanRelateFile;
}
```

### Pics usage

In you `Model` add `FF\Attachment\Relations\CanRelatePic` trait

```php
use Illuminate\Database\Eloquent\Model;
use FF\Attachment\Relations\CanRelatePic;

class Record extends Model
{
    use CanRelatePic;
}
```
