<?php
namespace Mpociot\Versionable;

use Illuminate\Support\Facades\Auth;
use Mpociot\Versionable\Version;

/**
 * Class VersionableTrait
 * @package Mpociot\Versionable
 */
trait VersionableTrait
{

    /**
     * @var bool
     */
    private $updating;

    /**
     * @var array
     */
    private $versionableDirtyData;

    /**
     * @var string
     */
    private $reason;

    /**
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
     * @return mixed
     */
    public function versions()
    {
        return $this->morphMany(Version::class, 'versionable');
    }

    /**
     * @return Version
     */
    public function getCurrentVersion()
    {
        return $this->versions()->orderBy(Version::CREATED_AT, 'DESC')->first();
    }

    /**
     * @param $version_id
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
     */
    protected function versionablePreSave()
    {
        if ($this->versioningEnabled === true) {
            $this->versionableDirtyData = $this->getDirty();
            $this->updating             = $this->exists;
        }
    }

    /**
     * Save a new version
     */
    protected function versionablePostSave()
    {
        /**
         * We'll save new versions on updating and first creation
         */
        if (
            ( $this->versioningEnabled === true && $this->updating && $this->isValidForVersioning() ) ||
            ( $this->versioningEnabled === true && !$this->updating )
        ) {
            // Save a new version
            $version                   = new Version();
            $version->versionable_id   = $this->getKey();
            $version->versionable_type = get_class($this);
            $version->user_id          = $this->getAuthUserId();
            $version->model_data       = serialize($this->getAttributes());

            if (!empty( $this->reason )) {
                $version->reason = $this->reason;
            }

            $version->save();
        }
    }

    /**
     * @return bool
     */
    private function isValidForVersioning()
    {

        $versionableData = $this->versionableDirtyData;

        unset( $versionableData[ $this->getUpdatedAtColumn() ] );

        if (function_exists('getDeletedAtColumn')) {
            unset( $versionableData[ $this->getDeletedAtColumn() ] );
        }

        if (isset( $this->dontVersionFields )) {
            foreach ($this->dontVersionFields AS $fieldName) {
                unset( $versionableData[ $fieldName ] );
            }
        }

        return ( count($versionableData) > 0 );
    }

    /**
     * @return int|null
     */
    private function getAuthUserId()
    {
        if (Auth::check()) {
            return Auth::id();
        }
        return null;
    }


}
