<?php

use Mockery as m;
use Carbon\Carbon;
use Mpociot\Versionable\Version;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\Relations\Relation;

class VersionableTest extends VersionableTestCase
{

    public function tearDown(): void
    {
        m::close();
        Auth::clearResolvedInstances();
    }

    public function testVersionableRelation()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertInstanceOf( TestVersionableUser::class, $version->versionable );
    }

    public function testInitialSaveShouldCreateVersion()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
    }

    public function testRetrievePreviousVersionFails()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
        $this->assertNull( $user->previousVersion() );
    }

    public function testRetrievePreviousVersionExists()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->save();

        $this->assertCount(2, $user->versions );
        $this->assertNotNull( $user->previousVersion() );

        $this->assertEquals( "Nono", $user->previousVersion()->getModel()->name );
    }

    public function testVersionAndModelAreEqual()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertEquals( $user->attributesToArray(), $version->getModel()->attributesToArray() );
    }


    public function testVersionsAreRelatedToUsers()
    {
        $user_id = rand(1,100);

        Auth::shouldReceive('check')
            ->andReturn( true );

        Auth::shouldReceive('id')
            ->andReturn( $user_id );

        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $this->assertEquals( $user_id, $version->user_id );
    }

    public function testGetResponsibleUserAttribute()
    {
        $responsibleOrigUser = new TestVersionableUser();
        $responsibleOrigUser->name = "Nono";
        $responsibleOrigUser->email = "nono.ma@test.php";
        $responsibleOrigUser->password = "12345";
        $responsibleOrigUser->last_login = $responsibleOrigUser->freshTimestamp();
        $responsibleOrigUser->save();

        auth()->login($responsibleOrigUser);

        $user = new TestVersionableUser();
        $user->name = "John";
        $user->email = "j.tester@test.php";
        $user->password = "67890";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $responsibleUser = $version->responsible_user;
        $this->assertEquals( $responsibleUser->getKey(), 1 );
        $this->assertEquals( $responsibleUser->name, $responsibleOrigUser->name );
        $this->assertEquals( $responsibleUser->email, $responsibleOrigUser->email );
    }


    public function testDontVersionEveryAttribute()
    {
        $user = new TestPartialVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();


        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions );
    }

    public function testVersionEveryAttribute()
    {
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 2, $user->versions );
    }

    public function testCheckForVersioningEnabled()
    {
        $user = new TestVersionableUser();
        $user->disableVersioning();

        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 0, $user->versions()->get() );

        $user->enableVersioning();
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions()->get() );
    }


    public function testCheckForVersioningEnabledLaterOn()
    {
        $user = new TestVersionableUser();

        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        $user->disableVersioning();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions );
    }

    public function testCanRevertVersion()
    {
        $user = new TestVersionableUser();

        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user_id = $user->getKey();

        $user->name = "John";
        $user->save();

        $newUser = TestVersionableUser::find( $user_id );
        $this->assertEquals( "John", $newUser->name );

        // Fetch first version and revert ist
        $newUser->versions()->first()->revert();

        $newUser = TestVersionableUser::find( $user_id );
        $this->assertEquals( "Nono", $newUser->name );
    }

    public function testCanRevertSoftDeleteVersion()
    {
        $user = new TestVersionableSoftDeleteUser();

        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user_id = $user->getKey();

        $user->name = "John";
        $user->save();

        $newUser = TestVersionableSoftDeleteUser::find( $user_id );
        $this->assertEquals( "John", $newUser->name );

        // Fetch first version and revert ist
        $reverted = $newUser->versions()->first()->revert();

        $newUser = TestVersionableSoftDeleteUser::find( $user_id );
        $this->assertEquals( "Nono", $reverted->name );
        $this->assertEquals( "Nono", $newUser->name );
    }

    public function testGetVersionModel()
    {
        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->save();

        $user->name = "Michael";
        $user->save();

        $this->assertCount( 3, $user->versions );

        $this->assertEquals( "Nono", $user->getVersionModel( 1 )->name );
        $this->assertEquals( "John", $user->getVersionModel( 2 )->name );
        $this->assertEquals( "Michael", $user->getVersionModel( 3 )->name );
        $this->assertEquals( null, $user->getVersionModel( 4 ) );

    }

    public function testGetVersionModelWithJsonField()
    {
        $model = new ModelWithJsonField();
        $model->json_field = ["foo" => "bar"];
        $model->save();

        $this->assertEquals(["foo" => "bar"], $model->getVersionModel(1)->json_field);
    }

    public function testUseReasonAttribute()
    {
        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->reason = "Doing tests";
        $user->save();

        $this->assertEquals( "Doing tests", $user->currentVersion()->reason );
    }

    public function testIgnoreDeleteTimestamp()
    {
        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1 , $user->versions );
        $user_id = $user->getKey();
        $this->assertNull( $user->deleted_at );

        $user->delete();

        $this->assertNotNull( $user->deleted_at );

        $this->assertCount( 1 , $user->versions );
    }

    public function testDiffTwoVersions()
    {

        $user = new TestVersionableUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDiffIgnoresTimestamps()
    {
        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->created_at = Carbon::now();
        $user->updated_at = Carbon::now();
        $user->deleted_at = Carbon::now();
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDiffSpecificVersions()
    {
        // Create 3 versions
        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->email = "john@snow.com";
        $user->save();

        $user->name = "Julia";
        $user->save();

        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(2, $diff);
        $this->assertEquals( "Nono", $diff["name"] );
        $this->assertEquals( "nono.ma@test.php", $diff["email"] );


        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->offset(1)->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDynamicVersionModel()
    {
        $name_v1 = 'first' ;
        $name_v2 = 'second' ;

        $model = new ModelWithDynamicVersion();
        $model->name = $name_v1 ;
        $model->save();

        $model->name = $name_v2 ;
        $model->save();

        // Assert that no row in default Version table
        $this->assertEquals( 0, Version::all()->count() );

        // But are in Custom version table
        $this->assertEquals( 2, DynamicVersionModel::all()->count() );

        // Assert that some versions exist
        $this->assertEquals( 2, $model->versions->count() );
        $this->assertEquals( $name_v2, $model->name );
        $this->assertArrayHasKey( 'name', $model->previousVersion()->diff());

        // Test the revert
        $model = $model->previousVersion()->revert();

        $this->assertEquals( $name_v1, $model->name );
    }

    public function testItUsesConfigurableVersionClass()
    {
        $this->app['config']->set('versionable.version_model', DynamicVersionModel::class);


        $name_v1 = 'first' ;
        $name_v2 = 'second' ;

        $model = new TestVersionableUser();
        $model->name = $name_v1 ;
        $model->email = $name_v1 ;
        $model->password = $name_v1 ;
        $model->save();

        $model->name = $name_v2 ;
        $model->save();

        // Assert that no row in default Version table
        $this->assertCount(0, Version::all());

        // But are in Custom version table
        $this->assertCount(2, DynamicVersionModel::all());
    }

    public function testKeepMaxVersionCount()
    {
        $name_v1 = 'first' ;
        $name_v2 = 'second' ;
        $name_v3 = 'third' ;
        $name_v4 = 'fourth' ;
        
        $model = new ModelWithMaxVersions();
        $model->email = "nono.ma@test.php";
        $model->password = "foo";
        $model->name = $name_v1 ;
        $model->save();
        
        $model->name = $name_v2 ;
        $model->save();
        
        $model->name = $name_v3 ;
        $model->save();
        
        $model->name = $name_v4 ;
        $model->save();

        // We limit the versions to only keep the latest one.
        $this->assertEquals( 2, Version::all()->count() );

        $this->assertEquals( 2, $model->versions()->count() );

        $this->assertArrayHasKey( 'name', $model->previousVersion()->diff());

        // Test the revert
        $model = $model->previousVersion()->revert();

        $this->assertEquals( $name_v3, $model->name );
    }

    public function testAllowHiddenFields() {
        $user = new TestHiddenFieldsUser();
        $user->name = "Nono";
        $user->email = "nono.ma@test.php";
        $user->password = "12345";
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->email = "j.barlow@test.php";
        $user->password = "6789";
        $user->save();
        sleep(1);

        $diff = $user->previousVersion()->diff();

        $this->assertArrayHasKey('email', $diff);
        $this->assertArrayHasKey('password', $diff);
        $this->assertEquals( 'John', $diff['name'] );
        $this->assertEquals( 'j.barlow@test.php', $diff['email'] );
        $this->assertEquals( '6789', $diff['password'] );

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testWhereModelHasMorphMap()
    {
        Relation::morphMap(['users' => TestVersionableUser::class]);
        $user = new TestVersionableUser();
        $user->name = "Test";
        $user->email = "example@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertEquals( $user->attributesToArray(), $version->getModel()->attributesToArray() );
        Relation::morphMap([], false);
    }

    public function testAddVersionableToExistingUser()
    {
        $user = new \Illuminate\Foundation\Auth\User();
        $user->name = "Nono";
        $user->email = "nono.ma@bmail.php";
        $user->password = "12345";
        $user->save();

        $this->assertNull($user->versions );

        $user = TestVersionableUser::find($user->id);
        $this->assertCount(0, $user->versions );
        $user->createInitialVersion();
        $this->assertCount(1, $user->fresh()->versions );

        //ASSERT THAT createInitialVersion() ONLY WORKS ONCE
        $user->createInitialVersion();
        $this->assertCount(1, $user->fresh()->versions );
    }

    public function testInitializeModel()
    {
        $user = new \Illuminate\Foundation\Auth\User();
        $user->name = "Nono";
        $user->email = "nono.ma@bmail.php";
        $user->password = "12345";
        $user->save();

        $this->assertNull($user->versions );

        $user = TestVersionableUser::find($user->id);
        $this->assertCount(0, $user->versions );
        
        TestVersionableUser::initializeVersions();
        $this->assertCount(1, $user->fresh()->versions );

        //ASSERT THAT createInitialVersion() ONLY WORKS ONCE
        TestVersionableUser::initializeVersions();
        $this->assertCount(1, $user->fresh()->versions );
    }

 
}




class TestVersionableUser extends \Illuminate\Foundation\Auth\User {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";
}

class TestVersionableSoftDeleteUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "users";
}

class ModelWithMaxVersions extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    protected $keepOldVersions = 2;
}

class TestPartialVersionableUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    protected $dontVersionFields = ["last_login"];
}


class DynamicVersionModel extends Version
{
    const TABLENAME = 'other_versions';
    public $table = self::TABLENAME ;
}
class ModelWithDynamicVersion extends Model
{
    const TABLENAME = 'some_data';
    public $table = self::TABLENAME ;
    //use DynamicVersionModelTrait;
    use VersionableTrait ;
    protected $versionClass = DynamicVersionModel::class ;
}
class ModelWithJsonField extends Model
{
    const TABLENAME = 'table_with_json_field';
    public $table = self::TABLENAME ;
    use VersionableTrait ;
    protected $casts = ['json_field' => 'array'];
}

class TestHiddenFieldsUser extends \Illuminate\Foundation\Auth\User {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    protected $hidden = ['email', 'password'];

    protected $versionedHiddenFields = ['email', 'password'];
}
