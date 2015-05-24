# Eloquent Versioned

Adds transparent versioning support to Laravel 5's Eloquent ORM.

**WARNING: This repository is currently super-duper experimental.  I will gladly accept pull requests and issues, but you probably shouldn't use this in production, and the interfaces may change without notice (although major changes will bump the version).**

## Installation

To add via composer:

```
require sbarre\eloquent-versioned
```

## Usage

In your Eloquent model class, simply add:

```php
use EloquentVersioned\Traits\Versioned;
```

### Notes

The Versioned trait requires that your table contain 3 fields to handle the versioning.

If you are creating a new table, or if you are changing an existing table, include the following lines in your `up()` migration:

```php
$table->integer('model_id')->unsigned()->default(1);
$table->integer('version')->unsigned()->default(1);
$table->integer('is_current_version')->unsigned()->default(1);
$table->index('is_current_version');
$table->index('model_id');
$table->index('version');
```

You can optionally include these lines in your `down()` migration:

```php
$table->dropColumn(['model_id','version','is_current_version']);
$table->dropIndex(['model_id','version','is_current_version']);
```

### Caveats

If you change the constants in `EloquentVersioned\VersionedBuilder` to rename the columns, remember to change them in your migrations as well.