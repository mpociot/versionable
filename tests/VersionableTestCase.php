<?php

abstract class VersionableTestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->migrateUsersTable();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', TestVersionableUser::class);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:6Cu/ozj4gPtIjmXjr8EdVnGFNsdRqZfHfVjQkmTlg4Y=');
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../src/migrations/2014_09_27_212641_create_versions_table.php';

        (new \CreateVersionsTable())->up();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Mpociot\Versionable\Providers\ServiceProvider::class,
        ];
    }

    public function migrateUsersTable()
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->datetime('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create(ModelWithDynamicVersion::TABLENAME, function ($table) {
            $table->increments('id');
            $table->text('name');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create(DynamicVersionModel::TABLENAME, function ($table) {
            $table->increments('version_id');
            $table->string('versionable_id');
            $table->string('versionable_type');
            $table->string('user_id')->nullable();
            $table->binary('model_data');
            $table->string('reason', 100)->nullable();
            $table->index('versionable_id');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create(ModelWithJsonField::TABLENAME, function ($table) {
            $table->increments('id');
            $table->json('json_field');
            $table->timestamps();
        });
    }
}