<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTransactionReferenceColumnNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_references', function (Blueprint $table) {
            $table->renameColumn('book_id', 'referenceable_id');
            $table->renameColumn('type', 'referenceable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_references', function (Blueprint $table) {
            $table->renameColumn('referenceable_id','book_id');
            $table->renameColumn('referenceable_type','type');
        });
    }
}
