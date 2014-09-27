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
     * Initialize model events
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->preSave();
        });

        static::saved(function ($model) {
            $model->postSave();
        });

    }

    public function versions()
    {
        return $this->morphMany('\Mpociot\Versionable\Version', 'versionable');
    }

    /**
     * Pre save hook to determine if versioning is enabled and if we're updating
     * the model
     */
    public function preSave()
    {
        if( !isset( $this->versioningEnabled ) || $this->versioningEnabled === true )
        {
            $this->updating     = $this->exists;
        }
    }

    /**
     * Save a new version
     */
    public function postSave()
    {
        if( (!isset( $this->versioningEnabled ) || $this->versioningEnabled === true) && $this->updating && $this->validForVersioning() )
        {
            // Save a new version
            $version                    = new Version();
            $version->versionable_id    = $this->getKey();
            $version->versionable_type  = get_class( $this );
            $version->user_id           = $this->getAuthUserId();
            $version->model_data        = serialize( $this->toArray() );
            $version->save();
        }
    }

    private function validForVersioning()
    {
        $versionableData = $this->getDirty();
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