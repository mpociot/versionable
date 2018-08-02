# Versionable
## Easy to use Model versioning for Laravel

![image](http://img.shields.io/packagist/v/mpociot/versionable.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/versionable.svg?style=flat)
![image](http://img.shields.io/packagist/dt/mpociot/versionable.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/versionable/coverage.svg?branch=master)](https://codecov.io/github/mpociot/versionable?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/versionable/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/versionable/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/versionable.svg?branch=master)](https://travis-ci.org/mpociot/versionable)

Keep track of all your model changes and revert to previous versions of it.

```php
// Restore to the previous change
$content->previousVersion()->revert();

// Get model from a version
$oldModel = Version::find(100)->getModel();
```

<a name="installation" />

## Installation

In order to add Versionable to your project, just add 

    "mpociot/versionable": "~3.0"

to your composer.json. Then run `composer install` or `composer update`.

Or run `composer require mpociot/versionable ` if you prefere that.

Run the migrations to create the "versions" table that will hold all version information.

```bash
php artisan migrate --path=vendor/mpociot/versionable/src/migrations
```

<a name="usage" />

## Usage

Let the Models you want to set under version control use the `VersionableTrait`.

```php
class Content extends Model {
	
	use Mpociot\Versionable\VersionableTrait;
	
}
```
That's it!

Every time you update your model, a new version containing the previous attributes will be stored in your database. 

All timestamps and the optional soft-delete timestamp will be ignored.

<a name="exclude" />

### Exclude attributes from versioning

Sometimes you don't want to create a version *every* time an attribute on your model changes. For example your User model might have a `last_login_at` attribute. 
I'm pretty sure you don't want to create a new version of your User model every time that user logs in.

To exclude specific attributes from versioning, add a new array property to your model named `dontVersionFields`.

```php
class User extends Model {
	
	use Mpociot\Versionable\VersionableTrait;
	
	/**
	 * @var array
	 */
	protected $dontVersionFields = [ 'last_login_at' ];

}
```

<a name="maximum" />

### Maximum number of stored versions

You can control the maximum number of stored versions per model. By default, there will be no limit and all versions will be saved.
Depending on your application, this could lead to a lot of versions, so you might want to limit the amount of stored versions.

You can do this by setting a `$keepOldVersions` property on your versionable models:

```php
class User {

    use VersionableTrait;

    // Keep the last 10 versions.
    protected $keepOldVersions = 10;

}
```

<a name="retrieve" />

### Retrieving all versions associated to a model

To retrieve all stored versions use the `versions` attribute on your model.

This attribute can also be accessed like any other Laravel relation, since it is a `MorphMany` relation.

```php
$model->versions;
```

<a name="diff" />

### Getting a diff of two versions

If you want to know, what exactly has changed between two versions, use the version model's `diff` method.

The diff method takes a version model as an argument. This defines the version to diff against. If no version is provided, it will use the current version.

```php
/**
 * Create a diff against the current version
 */
$diff = $page->previousVersion()->diff();


/**
 * Create a diff against a specific version
 */
$diff = $page->currentVersion()->diff( $version );
```

The result will be an associative array containing the attribute name as the key, and the different attribute value.

<a name="revert" />

### Revert to a previous version

Saving versions is pretty cool, but the real benefit will be the ability to revert to a specific version.

There are multiple ways to do this.

**Revert to the previous version**

You can easily revert to the version prior to the currently active version using:

```php
$content->previousVersion()->revert();
```

**Revert to a specific version ID**

You can also revert to a specific version ID of a model using:

```php
$revertedModel = Version::find( $version_id )->revert();

```

<a name="disableVersioning" />

### Disable versioning

In some situations you might want to disable versioning a specific model completely for the current request.

You can do this by using the `disableVersioning` and `enableVersioning` methods on the versionable model.

```php
$user = User::find(1);
$user->disableVersioning();

// This will not create a new version entry.
$user->update([
    'some_attribute' => 'changed value'
]);
```

<a name="differentVersionTable" />

### Use different version table

Some times we want to have models versions in differents tables. By default versions are stored in the table 'versions', defined in Mpociot\Versionable\Version::$table.

To use a different table to store version for some model we have to change the table name. To do so, create a model that extends Mpociot\Versionable\Version and set the $table property to another table name.

```php
class MyModelVersion extends Version
{
    $table = 'mymodel_versions';
    ...
}

```

In the model that you want it use this specific versions table, use the `VersionableTrait` Trait and add the property `$versionClass` with value the specific version model.
 
```php
class MyModel extends Eloquent
{
    use VersionableTrait ;
    protected $versionClass = MyModelVersion::class ;
    ... 
}

```

And do not forget to create a migration for this versions table, exactly as the default versions table.

<a name="license" />

## License

Versionable is free software distributed under the terms of the [MIT license](https://opensource.org/licenses/MIT).
