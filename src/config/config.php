<?php

return [

    /*
     * The version model to use.
     * Feel free to change this, if you need specific version
     * model logic.
     */
    'version_model' => \Mpociot\Versionable\Version::class,


    /*
     * Here you can configure the versioning logic
     * to be handled within a separate job.
     */
    'use_queue' => env('VERSIONING_USE_QUEUE', false)
];
