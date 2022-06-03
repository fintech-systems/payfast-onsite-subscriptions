<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use FintechSystems\Payfast\Enums\PaymentMethod;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billable_id');
            $table->string('billable_type');
            $table->string('name');
            $table->string('plan_id');
            $table->string('payfast_status');
            $table->string('payfast_token')->unique();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paused_from')->nullable();            
            $table->timestamp('ends_at')->nullable();            
            $table->timestamp('next_bill_at')->nullable(); // Custom - added
            $table->timestamp('cancelled_at')->nullable(); // Custom - added
            $table->string('payment_method')->nullable(); // Custom - added
            $table->timestamps();

            $table->index(['billable_id', 'billable_type']);
                    
            // $table->unsignedBigInteger('amount')->nullable();
            // $table->unsignedInteger('cycles')->nullable();
            // $table->unsignedInteger('cycles_complete')->nullable();
            // $table->unsignedInteger('frequency')->nullable();

            // $table->unsignedInteger('payfast_status')->nullable();
            // $table->string('payfast_status_reason')->nullable();
            // $table->string('payfast_status_text')->nullable();
                        
            // $table->string('status');
            // $table->string('merchant_payment_id');
            // $table->string('payment_status')->nullable();
            // $table->string('subscription_status')->nullable();
            
            // $table->timestamp('next_run_at')->nullable();                        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
