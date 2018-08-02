<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery as m;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\Version;
use Mpociot\Versionable\VersionableTrait;

class VersionableTest extends VersionableTestCase
{

    public function setUp()
    {
        parent::setUp();

        TestVersionableUser::flushEventListeners();
        TestVersionableUser::boot();

        TestVersionableSoftDeleteUser::flushEventListeners();
        TestVersionableSoftDeleteUser::boot();

        TestPartialVersionableUser::flushEventListeners();
        TestPartialVersionableUser::boot();
    }

    public function tearDown()
    {
        m::close();
        Auth::clearResolvedInstances();
    }

    public function testVersionableRelation()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertInstanceOf( TestVersionableUser::class, $version->versionable );
    }

    public function testInitialSaveShouldCreateVersion()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
    }

    public function testRetrievePreviousVersionFails()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
        $this->assertNull( $user->previousVersion() );
    }

    public function testRetrievePreviousVersionExists()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        // Needed because otherwise timestamps are exactly the same
        sleep(1);

        $user->name = "John";
        $user->save();

        $this->assertCount(2, $user->versions );
        $this->assertNotNull( $user->previousVersion() );

        $this->assertEquals( "Marcel", $user->previousVersion()->getModel()->name );
    }

    public function testVersionAndModelAreEqual()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $this->assertEquals( $user_id, $version->user_id );
    }

    public function testGetResponsibleUserAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( true );

        Auth::shouldReceive('id')
            ->andReturn( 1 );


        $responsibleOrigUser = new TestVersionableUser();
        $responsibleOrigUser->name = "Marcel";
        $responsibleOrigUser->email = "m.pociot@test.php";
        $responsibleOrigUser->password = "12345";
        $responsibleOrigUser->last_login = $responsibleOrigUser->freshTimestamp();
        $responsibleOrigUser->save();

        // Needed because otherwise timestamps are exactly the same
        sleep(1);

        Config::shouldReceive('get')
            ->once()
            ->with("providers.users.model")
            ->andReturn('TestVersionableUser');

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
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestPartialVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();


        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions );
    }

    public function testVersionEveryAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 2, $user->versions );
    }

    public function testCheckForVersioningEnabled()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->disableVersioning();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        $this->assertEquals( "Marcel", $newUser->name );
    }

    public function testCanRevertSoftDeleteVersion()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        $this->assertEquals( "Marcel", $reverted->name );
        $this->assertEquals( "Marcel", $newUser->name );
    }

    public function testGetVersionModel()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = "John";
        $user->save();

        $user->name = "Michael";
        $user->save();

        $this->assertCount( 3, $user->versions );

        $this->assertEquals( "Marcel", $user->getVersionModel( 1 )->name );
        $this->assertEquals( "John", $user->getVersionModel( 2 )->name );
        $this->assertEquals( "Michael", $user->getVersionModel( 3 )->name );
        $this->assertEquals( null, $user->getVersionModel( 4 ) );

    }

    public function testUseReasonAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->reason = "Doing tests";
        $user->save();

        $this->assertEquals( "Doing tests", $user->currentVersion()->reason );
    }

    public function testIgnoreDeleteTimestamp()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
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
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDiffIgnoresTimestamps()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

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
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->email = "john@snow.com";
        $user->save();
        sleep(1);

        $user->name = "Julia";
        $user->save();

        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(2, $diff);
        $this->assertEquals( "Marcel", $diff["name"] );
        $this->assertEquals( "m.pociot@test.php", $diff["email"] );


        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->offset(1)->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDynamicVersionModel()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $name_v1 = 'first' ;
        $name_v2 = 'second' ;
        $name_v3 = 'thrid' ;
        $model = new ModelWithDynamicVersion();
    	$model->name = $name_v1 ;
    	$model->save();
    	$model->name = $name_v2 ;
    	$model->save();
    	// Create a third version because previousVersion()->revert() is buggy if only 2 versions exist.
    	$model->name = $name_v3 ;
    	$model->save();

    	// Assert that no row in default Version table
    	$this->assertEquals( 0, Version::all()->count() );
    	// But are in Custom version table
    	$this->assertEquals( 3, DynamicVersionModel::all()->count() );

    	// Assert that some versions exist
    	$this->assertEquals( 3, $model->versions->count() );
    	$this->assertEquals( $name_v3, $model->name );
    	$this->assertArrayHasKey( 'name', $model->previousVersion()->diff());

        // Test the revert
        $model = $model->previousVersion()->revert();

        $this->assertEquals( $name_v2, $model->name );
    }
 
}




class TestVersionableUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";
}

class TestVersionableSoftDeleteUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "users";
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

