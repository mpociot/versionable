# Versionable
## Easy to use Model versioning for Laravel 4 and Laravel 5

### Installation

* Add the following line to your `require` array of the `composer.json` file:
`"mpociot/versionable": "1.*"`
* Update your installation `composer update`
* Run the migrations from this package
`php artisan migrate --package=mpociot/versionable`

### Implementation



Let the Models you want to set under version control use the `VersionableTrait`.

On each Model update a new version will be stored in the database.

To retrieve all stored versions use the `versions` attribute on your model.

To retrieve the model state of a version simply call the `getModel` method on the  version object.