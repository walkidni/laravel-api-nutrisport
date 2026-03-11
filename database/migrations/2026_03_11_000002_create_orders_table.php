<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedInteger('reference_sequence');
            $table->string('reference')->unique();
            $table->string('status');
            $table->string('payment_method');
            $table->string('delivery_method');
            $table->unsignedInteger('delivery_amount');
            $table->unsignedInteger('total_amount');
            $table->string('full_name');
            $table->string('full_address');
            $table->string('city');
            $table->string('country');
            $table->timestamps();

            $table->unique(['site_id', 'reference_sequence']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['site_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
