<?php

return [

    /*
     * The version model to use.
     * Feel free to change this, if you need specific version
     * model logic.
     */
    'version_model' => \Mpociot\Versionable\Version::class,

    /*
     * The encoding to use for the model data encoding.
     * Default is 'serialize' and uses PHP serialize() but 'json' is also supported
     */
    'encoding' => 'serialize',
];
