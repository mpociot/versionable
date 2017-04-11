<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserIdType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('versions', function(Blueprint $table)
		{
			$table->string('user_id', 55)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('versions', function(Blueprint $table)
		{
			$table->integer('user_id')->change();
		});
	}

}
