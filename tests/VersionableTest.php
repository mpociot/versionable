<?php

use Illuminate\Support\Facades\Auth;
use Mockery as m;

class VersionableTest extends VersionableTestCase
{

    public function setUp()
    {
        parent::setUp();

        TestVersionableUser::flushEventListeners();
        TestVersionableUser::boot();
    }

    public function tearDown()
    {
        m::close();
        Auth::clearResolvedInstances();
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

        $version = $user->getCurrentVersion();
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

        $version = $user->getCurrentVersion();

        $this->assertEquals( $user_id, $version->user_id );
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

        $this->assertCount( 0, $user->versions );
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


}




class TestVersionableUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";
}


class TestPartialVersionableUser extends Illuminate\Database\Eloquent\Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    protected $dontVersionFields = ["last_login"];
}