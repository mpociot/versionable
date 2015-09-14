<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReason extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('versions', function(Blueprint $table)
		{
            $table->string('reason', 100)->nullable()->after('model_data');
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
            $table->dropColumn('reason');
		});
	}

}
