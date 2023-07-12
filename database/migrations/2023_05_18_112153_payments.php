<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Payments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 36)->index()->primary();
            $table->char('hirer_id', 36)->index()->nullable();
            $table->char('booking_id', 36)->index()->nullable();
            $table->char('token', 36)->nullable();
            $table->string('amount')->nullable();
            $table->string('reference')->nullable();
            $table->string('error')->nullable();
            $table->string('status')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->char('created_by', 36)->nullable();
            $table->char('updated_by', 36)->nullable();
            $table->char('deleted_by', 36)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
