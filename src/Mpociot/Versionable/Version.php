<?php
namespace Mpociot\Versionable;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

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
        $model = Config::get( "auth.model" );
        return $model::find( $this->user_id );
    }

    /**
     * Return the versioned model
     * @return Model
     */
    public function getModel()
    {
        $model = new $this->versionable_type();
        $model->unguard();
        $model->fill( unserialize( $this->model_data ) );
        $model->exists = true;
        $model->reguard();
        return $model;
    }

}