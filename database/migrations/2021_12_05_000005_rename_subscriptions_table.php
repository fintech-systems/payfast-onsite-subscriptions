<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('subscriptions', 'payfast_token')) {
            throw new \Exception('Cannot rename table: subscriptions table does not contain payfast_token column');
        }
        
        Schema::rename('subscriptions', 'payfast_subscriptions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::rename('payfast_subscriptions', 'subscriptions');
    }
}; 
