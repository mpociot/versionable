<?php
namespace Mpociot\Versionable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class VersionableTrait
 * @package Mpociot\Versionable
 */
trait VersionableTrait
{

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
        return $this->morphMany(Version::class, 'versionable');
    }

    /**
     * Returns the latest version available
     * @return Version
     */
    public function currentVersion()
    {
        return $this->versions()->orderBy(Version::CREATED_AT, 'DESC')->first();
    }

    /**
     * Returns the previous version
     * @return Version
     */
    public function previousVersion()
    {
        return $this->versions()->orderBy(Version::CREATED_AT, 'DESC')->limit(1)->offset(1)->first();
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
        if ($this->versioningEnabled === true) {
            $this->versionableDirtyData = $this->getDirty();
            $this->updating             = $this->exists;
        }
    }

    /**
     * Save a new version.
     * @return void
     */
    protected function versionablePostSave()
    {
        /**
         * We'll save new versions on updating and first creation
         */
        if (
            ($this->versioningEnabled === true && $this->updating && $this->isValidForVersioning()) ||
            ($this->versioningEnabled === true && !$this->updating)
        ) {
            // Save a new version
            $version                   = new Version();
            $version->versionable_id   = $this->getKey();
            $version->versionable_type = get_class($this);
            $version->user_id          = $this->getAuthUserId();
            $version->model_data       = serialize($this->getAttributes());

            if (!empty($this->reason)) {
                $version->reason = $this->reason;
            }

            $version->save();
        }
    }

    /**
     * Determine if a new version should be created for this model.
     *
     * @return bool
     */
    private function isValidForVersioning()
    {
        $dontVersionFields = isset($this->dontVersionFields) ? $this->dontVersionFields : [];
        $removeableKeys    = array_merge($dontVersionFields, [$this->getUpdatedAtColumn()]);

        if (method_exists($this, 'getDeletedAtColumn')) {
            $removeableKeys[] = $this->getDeletedAtColumn();
        }

        return (count(array_diff_key($this->versionableDirtyData, array_flip($removeableKeys))) > 0);
    }

    /**
     * @return int|null
     */
    protected function getAuthUserId()
    {
        try {
            if (class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry')
                || class_exists($class = '\Cartalyst\Sentinel\Laravel\Facades\Sentinel')
            ) {
                return ($class::check()) ? $class::getUser()->id : null;
            } elseif (\Auth::check()) {
                return \Auth::user()->getAuthIdentifier();
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}
