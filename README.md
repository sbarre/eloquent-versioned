# Eloquent Versioned

Adds transparent versioning support to Laravel 5's Eloquent ORM.

**WARNING: This repository is currently super-duper experimental.  I will gladly accept pull requests and issues, but you probably shouldn't use this in production, and the interfaces may change without notice (although major changes will bump the version).**

When using this trait (and with a table that includes the required fields), saving your model will actually create a new row instead, and increment the version number.

Using global scopes, old versions are ignored in the standard ORM operations (selects, updates, deletes) and relations (hasOne, hasMany, belongsTo, etc).

The package also provides some special methods to include old versions in queries (or only query old versions) which can be useful for showing a model's history, or the like.

## Installation

To add via Composer:

```
require sbarre/eloquent-versioned --no-dev
```

Use the `--no-dev` flag to avoid pulling down all the testing dependencies (like the *entire Laravel framework*).

## Migrations

Versioned models require that your database table contain 3 fields to handle the versioning.

If you are creating a new table, or if you are changing an existing table, include the following lines in the `up()` method of the migration:

```php
$table->integer('model_id')->unsigned()->default(1);
$table->integer('version')->unsigned()->default(1);
$table->integer('is_current_version')->unsigned()->default(1);
$table->index('is_current_version');
$table->index('model_id');
$table->index('version');
```

If your migration was altering an existing table, you should include these lines in the `down()` method of your migration:

```php
$table->dropColumn(['model_id','version','is_current_version']);
$table->dropIndex(['model_id','version','is_current_version']);
```

#### Caveats

If you change the constants in `EloquentVersioned\VersionedBuilder` to rename the columns, remember to change them in your migrations as well.

## Usage

In your Eloquent model class, start by adding the `use` statement for the Trait:

```php
use EloquentVersioned\Traits\Versioned;
```

When the trait boots it will apply the proper scope, and provides overrides on various Eloquent methods to support versioned records.

Once the trait is applied, you use your models as usual, with the standard queries behaving as usual.

```php
$project = Project::create([
    'name' => 'Project Name',
    'description' => 'Project description goes here'
])->fresh();

print_r($project->toArray());
```

This would then output (for example):

```php
Array
(
    [id] => 1
    [version] => 1
    [name] => Project Name
    [description] => Project description goes here
    [created_at] => 2015-05-24 17:16:05
    [updated_at] => 2015-05-24 17:16:05
)
```

The actual database row looks like this:

```php
Array
(
    [id] => 1
    [model_id] => 1
    [version] => 1
    [is_current_version] => 1
    [name] => Updated project Name
    [description] => Project description goes here
    [created_at] => 2015-05-24 17:16:05
    [updated_at] => 2015-05-24 17:16:05
)
```

Then if you change the model and save:

```php
$project->name = 'Updated project name';
$project->save();

print_r($project->toArray());
```
This would then output:

```php
Array
(
    [id] => 1
    [version] => 2
    [name] => Updated project Name
    [description] => Project description goes here
    [created_at] => 2015-05-24 17:16:05
    [updated_at] => 2015-05-24 17:16:45
)
```

The model mutates the `model_id` column into `id`, and hides some of the version-specific columns.  In reality this is actually a new database row that looks like this:

```php
Array
(
    [id] => 2
    [model_id] => 1
    [version] => 2
    [is_current_version] => 1
    [name] => Updated project Name
    [description] => Project description goes here
    [created_at] => 2015-05-24 17:16:05
    [updated_at] => 2015-05-24 17:16:45
)
```

While the row for our first version now looks like this:

```php
Array
(
    [id] => 1
    [model_id] => 1
    [version] => 1
    [is_current_version] => 0
    [name] => Project Name
    [description] => Project description goes here
    [created_at] => 2015-05-24 17:16:05
    [updated_at] => 2015-05-24 17:16:05
)
```

So the `is_current_version` property is what the global scope is applied against, limiting all select queries to only records where `is_current_version = 1`.

Calling `save()` on the model replicates the existing model, changes the appropriate properties (including retrieving and setting the next version on the model), and clears the `is_current_version` property on the previous version after saving the new one.

If you are making a very minor change to a model and you don't want to create a new version, you can call `saveMinor()` instead.

```php
$project->saveMinor(); // doesn't create a new version
```

#### Methods for dealing with old versions

If you want to retrieve a list of all versions of a model (or include old versions in a bigger query):

```php
$projectVersions = Project::withOldVersions()->find(1);
```

If run after our example above, this would return an array with 2 models.

You can also retrieve a list of *only* old models by using:

```php
$oldVersions = Project::onlyOldVersions()->find(1);
```

Otherwise, the rest of Eloquent's ORM operations should work as usual, including the out-of-the-box relations.

## Support & Roadmap

As indicated at the top, this package is still **very experimental** and is under active development.  The current roadmap includes test coverage and more extensive real-world testing, so pull requests and issues are always welcome!
