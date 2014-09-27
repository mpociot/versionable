<?php
namespace Modules\Application\Traits;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class Version
 * @package Modules\Application\Traits
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
        $model = \Config::get( "auth.model" );
        return $model::find( $this->user_id );
    }

    /**
     * Return the versioned model
     * @return mixed
     */
    public function getModel()
    {
        $model = new $this->versionable_type();
        \Eloquent::unguard();
        $model->fill( unserialize( $this->model_data ) );
        \Eloquent::reguard();
        return $model;
    }

}