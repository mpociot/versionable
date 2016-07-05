<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeVersionableIdAndTypeFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('versions', function(Blueprint $table)
		{
			$table->integer('versionable_id')->unsigned()->change();
			$table->string('versionable_type')->change();
			$table->index('versionable_id');
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
			$table->integer('versionable_id')->change();
            $table->text('versionable_type')->change();
			$table->dropIndex('versions_versionable_id_index');
		});
	}

}
