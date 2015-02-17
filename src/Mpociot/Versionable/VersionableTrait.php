<?php
namespace Mpociot\Versionable;

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
     * Initialize model events
     */
    public static function boot()
    {
        parent::boot();

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
        return $this->morphMany('\Mpociot\Versionable\Version', 'versionable');
    }

    /**
     * @return mixed
     */
    public function getCurrentVersion()
    {
        return $this->versions()->orderBy( Version::CREATED_AT , 'DESC' )->first();
    }

    /**
     * @param $version_id
     * @return null
     */
    public function getVersionModel( $version_id )
    {
        $version = $this->versions()->where("version_id","=", $version_id )->first();
        if( !is_null( $version) )
        {
            return $version->getModel();
        } else {
            return null;
        }
    }

    /**
     * Restore the model and make it the current version
     *
     * @return bool
     */
    public function restoreVersion()
    {
        unset( $this->{$this->getCreatedAtColumn()} );
        unset( $this->{$this->getUpdatedAtColumn()} );
        if( function_exists('getDeletedAtColumn') )
        {
            unset( $this->{$this->getDeletedAtColumn()} );
        }
        return $this->save();
    }

    /**
     * Pre save hook to determine if versioning is enabled and if we're updating
     * the model
     */
    public function versionablePreSave()
    {
        if( !isset( $this->versioningEnabled ) || $this->versioningEnabled === true )
        {
            $this->versionableDirtyData   = $this->getDirty();
            $this->updating               = $this->exists;
        }
    }

    /**
     * Save a new version
     */
    public function versionablePostSave()
    {
        /**
         * We'll save new versions on updating and first creation
         */
        if(
            ( (!isset( $this->versioningEnabled ) || $this->versioningEnabled === true) && $this->updating && $this->validForVersioning() ) ||
            ( (!isset( $this->versioningEnabled ) || $this->versioningEnabled === true) && !$this->updating )
        )
        {
            // Save a new version
            $version                    = new Version();
            $version->versionable_id    = $this->getKey();
            $version->versionable_type  = get_class( $this );
            $version->user_id           = $this->getAuthUserId();
            $version->model_data        = serialize( $this->getAttributes() );
            $version->save();
        }
    }

    /**
     * @return bool
     */
    private function validForVersioning()
    {
        $versionableData = $this->versionableDirtyData;
        unset( $versionableData[ $this->getUpdatedAtColumn() ] );
        if( function_exists('getDeletedAtColumn') )
        {
            unset( $versionableData[ $this->getDeletedAtColumn() ] );
        }

        if( isset($this->dontVersionFields) )
        {
            foreach( $this->dontVersionFields AS $fieldName )
            {
                unset( $versionableData[ $fieldName ] );
            }
        }
        unset( $this->versionableDirtyData );
        return ( count( $versionableData ) > 0 );
    }

    /**
     * @return int|null
     */
    private function getAuthUserId()
    {
        if( \Auth::check() )
        {
            return \Auth::id();
        }
        return null;
    }



}
