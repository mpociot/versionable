# Versionable
## Easy to use Model versioning for Laravel 4 and Laravel 5

With this package you can create a history of the objects stored in your application. You just need to insert the VersionableTrait in each Model that you want to set under version control. All versions are stored in a new database table.


![image](http://img.shields.io/packagist/v/mpociot/versionable.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/versionable.svg?style=flat)
![image](http://img.shields.io/packagist/dt/mpociot/versionable.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/versionable/coverage.svg?branch=2.0)](https://codecov.io/github/mpociot/versionable?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/versionable/badges/quality-score.png?b=2.0)](https://scrutinizer-ci.com/g/mpociot/versionable/?branch=2.0)
[![Build Status](https://travis-ci.org/mpociot/versionable.svg?branch=2.0)](https://travis-ci.org/mpociot/versionable)

### Examples

Store each change of
* a product to roll back the changes later
* a user to prevent that the user can set an old password when he has to create a new one every n weeks
* a document to create a document history
* a settings model where you need to know who did the changes

### Installation

* Add the following line to your `require` array of the `composer.json` file:
`"mpociot/versionable": "1.*"`
* Update your installation `composer update`
* Run the migrations from this package
`php artisan migrate --path=vendor/mpociot/versionable/src/migrations`

### Implementation

Let the Models you want to set under version control use the `VersionableTrait`.

    use VersionableTrait;

On each update of the Model a new version will be stored in the database.

To retrieve all stored versions as an array use the `versions` attribute on your model.

    $model->versions;

To retrieve the model state of a version simply call the `getModel` method on the version object.

    $model = $version->getModel();

To restore the old state of a model, call the `restoreVersion` method on the retrieved model. This will then again create a new version, containing your current model state.

    $version->restoreVersion();
    
#### Configuration

Versionable can be configured in the Model that uses the Trait. Simply add the configuration properties in your Model.

    // do not create a new version, when only these fields changed
    public $dontVersionFields = [ 'last_login_date' ];

#### Optional field "reason"

If you want to set a reason for each version, you can set this when filling a versionable Model:

    protected $fillable = [/* more fields ...,*/ 'reason'];
    
    // in your Controller
    $model->fill($request->only([/* more fields ...,*/ 'reason']));

    // listing versions with reasons
    echo $version->reason;
