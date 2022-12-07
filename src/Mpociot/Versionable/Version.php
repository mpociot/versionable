<?php
namespace Mpociot\Versionable;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\Jobs\VersionableJob;

/**
 * Class Version
 * @package Mpociot\Versionable
 */
class Version extends Eloquent
{

    /**
     * @var string
     */
    public $table = "versions";

    /**
     * @var string
     */
    protected $primaryKey = "version_id";

    /**
     * Sets up the relation
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function versionable()
    {
        return $this->morphTo();
    }

    /**
     * Return the user responsible for this version
     * @return mixed
     */
    public function getResponsibleUserAttribute()
    {
        $model = Config::get("auth.providers.users.model");
        return $model::find($this->user_id);
    }

    /**
     * Return the versioned model
     * @return Model
     */
    public function getModel()
    {
        $modelData = is_resource($this->model_data)
            ? stream_get_contents($this->model_data,-1,0)
            : $this->model_data;

        $className = self::getActualClassNameForMorph($this->versionable_type);
        $model = new $className();
        $model->unguard();
        $model->fill(unserialize($modelData));
        $model->exists = true;
        $model->reguard();
        return $model;
    }


    /**
     * Revert to the stored model version make it the current version
     *
     * @return Model
     */
    public function revert()
    {
        $model = $this->getModel();
        unset( $model->{$model->getCreatedAtColumn()} );
        unset( $model->{$model->getUpdatedAtColumn()} );
        if (method_exists($model, 'getDeletedAtColumn')) {
            unset( $model->{$model->getDeletedAtColumn()} );
        }
        $model->save();
        return $model;
    }

    /**
     * Diff the attributes of this version model against another version.
     * If no version is provided, it will be diffed against the current version.
     *
     * @param Version|null $againstVersion
     * @return array
     */
    public function diff(Version $againstVersion = null)
    {
        $model = $this->getModel();
        $diff  = $againstVersion ? $againstVersion->getModel() : $this->versionable()->withTrashed()->first()->currentVersion()->getModel();

        $diffArray = array_diff_assoc($diff->getAttributes(), $model->getAttributes());

        if (isset( $diffArray[ $model->getCreatedAtColumn() ] )) {
            unset( $diffArray[ $model->getCreatedAtColumn() ] );
        }
        if (isset( $diffArray[ $model->getUpdatedAtColumn() ] )) {
            unset( $diffArray[ $model->getUpdatedAtColumn() ] );
        }
        if (method_exists($model, 'getDeletedAtColumn') && isset( $diffArray[ $model->getDeletedAtColumn() ] )) {
            unset( $diffArray[ $model->getDeletedAtColumn() ] );
        }

        return $diffArray;
    }

    public static function createVersionForModel(Model $model)
    {
        if (
            ( $model->versioningEnabled === true && $model->updating && $model->isValidForVersioning() ) ||
            ( $model->versioningEnabled === true && !$model->updating && !is_null($model->versionableDirtyData) && count($model->versionableDirtyData))
        ) {
            // Save a new version
            $class                     = $model->getVersionClass();
            $version                   = new $class();
            $version->versionable_id   = $model->getKey();
            $version->versionable_type = method_exists($model, 'getMorphClass') ? $model->getMorphClass() : get_class($model);
            $version->user_id          = $model->getAuthUserId();

            $versionedHiddenFields = $model->versionedHiddenFields ?? [];
            $model->makeVisible($versionedHiddenFields);
            $version->model_data       = serialize($model->attributesToArray());
            $model->makeHidden($versionedHiddenFields);

            if (!empty( $model->reason )) {
                $version->reason = $model->reason;
            }

            $version->save();

            $model->purgeOldVersions();
        }
    }
}
