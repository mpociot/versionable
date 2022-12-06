<?php
namespace Mpociot\Versionable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use phpDocumentor\Reflection\Types\Self_;

/**
 * Class VersionableTrait
 * @package Mpociot\Versionable
 */
trait VersionableTrait
{

    /**
     * Retrieve, if exists, the property that define that Version model.
     * If no property defined, use the default Version model.
     *
     * Trait cannot share properties whth their class !
     * http://php.net/manual/en/language.oop5.traits.php
     * @return unknown|string
     */
    protected function getVersionClass()
    {
        if( property_exists( self::class, 'versionClass') ) {
            return $this->versionClass;
        }

        return config('versionable.version_model', Version::class);
    }

    /**
     * Private variable to detect if this is an update
     * or an insert
     * @var bool
     */
    private $updating;

    /**
     * Contains all dirty data that is valid for versioning
     *
     * @var array
     */
    private $versionableDirtyData;

    /**
     * Optional reason, why this version was created
     * @var string
     */
    private $reason;

    /**
     * Flag that determines if the model allows versioning at all
     * @var bool
     */
    protected $versioningEnabled = true;

    /**
     * @return $this
     */
    public function enableVersioning()
    {
        $this->versioningEnabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableVersioning()
    {
        $this->versioningEnabled = false;
        return $this;
    }

    /**
     * Attribute mutator for "reason"
     * Prevent "reason" to become a database attribute of model
     *
     * @param string $value
     */
    public function setReasonAttribute($value)
    {
        $this->reason = $value;
    }

    /**
     * Initialize model events
     */
    public static function bootVersionableTrait()
    {
        static::saving(function ($model) {
            $model->versionablePreSave();
        });

        static::saved(function ($model) {
            $model->versionablePostSave();
        });

    }

    /**
     * Return all versions of the model
     * @return MorphMany
     */
    public function versions()
    {
        return $this->morphMany( $this->getVersionClass(), 'versionable');
    }

    /**
     * Returns the latest version available
     * @return Version
     */
    public function currentVersion()
    {
        return $this->getLatestVersions()->first();
    }

    /**
     * Returns the previous version
     * @return Version
     */
    public function previousVersion()
    {
        return $this->getLatestVersions()->limit(1)->offset(1)->first();
    }

    /**
     * Get a model based on the version id
     *
     * @param $version_id
     *
     * @return $this|null
     */
    public function getVersionModel($version_id)
    {
        $version = $this->versions()->where("version_id", "=", $version_id)->first();
        if (!is_null($version)) {
            return $version->getModel();
        }
        return null;
    }

    /**
     * Pre save hook to determine if versioning is enabled and if we're updating
     * the model
     * @return void
     */
    protected function versionablePreSave()
    {
        self::modelPreSave($this);
    }

    /**
     * Save a new version.
     * @return void
     */
    protected function versionablePostSave()
    {
        self::modelPostSave($this);
    }

    /**
     * Delete old versions of this model when they reach a specific count.
     *
     * @return void
     */
    private function purgeOldVersions()
    {
        $keep = isset($this->keepOldVersions) ? $this->keepOldVersions : 0;

        if ((int)$keep > 0) {
            $count = $this->versions()->count();

            if ($count > $keep) {
                $this->getLatestVersions()
                    ->take($count)
                    ->skip($keep)
                    ->get()
                    ->each(function ($version) {
                    $version->delete();
                });
            }
        }
    }

    /**
     * Determine if a new version should be created for this model.
     *
     * @return bool
     */
    private function isValidForVersioning()
    {
        $dontVersionFields = isset( $this->dontVersionFields ) ? $this->dontVersionFields : [];
        $removeableKeys    = array_merge($dontVersionFields, [$this->getUpdatedAtColumn()]);

        if (method_exists($this, 'getDeletedAtColumn')) {
            $removeableKeys[] = $this->getDeletedAtColumn();
        }

        return ( count(array_diff_key($this->versionableDirtyData, array_flip($removeableKeys))) > 0 );
    }

    /**
     * @return int|null
     */
    protected function getAuthUserId()
    {
        return Auth::check() ? Auth::id() : null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getLatestVersions()
    {
        return $this->versions()->orderByDesc('version_id');
    }

    protected static function modelPreSave(Model $model)
    {
        if ($model->versioningEnabled === true) {
            $model->versionableDirtyData = $model->getDirty();
            $model->updating             = $model->exists;
        }
    }

    protected static function modelPostSave(Model $model)
    {
        /**
         * We'll save new versions on updating and first creation
         */
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
