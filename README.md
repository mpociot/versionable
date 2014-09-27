# Versionable
## Easy to use Model versioning for Laravel 4 and Laravel 5

### Quickstart


Let the Models you want to set under version control use the `VersionableTrait`.

On each Model update a new version will be stored in the database.

To retrieve all stored versions use the `versions` attribute on your model.

To retrieve the model state of a version simply call the `getModel` method on the  version object.