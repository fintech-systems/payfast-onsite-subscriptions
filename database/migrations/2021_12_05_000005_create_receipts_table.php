<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_payment_id')->nullable();
            $table->string('payfast_payment_id')->unique();
            $table->string('payment_status');
            $table->string('item_name');
            $table->string('item_description')->nullable();
            $table->string('amount_gross');
            $table->string('amount_fee');
            $table->string('amount_net');
            $table->string('payfast_token')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->string('billable_type')->nullable();
            $table->unsignedBigInteger('billable_id')->nullable();                        
            $table->string('order_id')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['billable_id', 'billable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receipts');
    }
}
