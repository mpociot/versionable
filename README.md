# Versionable
## Easy to use Model versioning for Laravel 4 and Laravel 5

With this package you can create a history of the objects stored in your application. You just need to insert the VersionableTrait in each Model that you want to set under version control. All versions are stored in a new database table.

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
`php artisan migrate --package=mpociot/versionable`

### Implementation

Let the Models you want to set under version control use the `VersionableTrait`.

    use VersionableTrait;

On each update of the Model a new version will be stored in the database.

To retrieve all stored versions as an array use the `versions` attribute on your model.

    $model->versions;

To retrieve the model state of a version simply call the `getModel` method on the version object.

    $model = $version->getModel();

To restore the old state of a model, call the `restoreVersion` method on the retrieved model. This will then again create a new version, containing your current model state.

    $model->restoreVersion();
    
#### Configuration

Versionable can be configured in the Model that uses the Trait. Simply add the configuration properties in your Model.

    // do not create a new version, when only these fields changed
    public $dontVersionFields = [ 'last_login_date' ];
