<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // Contoh: CA-20261011-0001
            $table->string('customer_name');
            $table->string('whatsapp_number');
            $table->string('car_model');
            $table->text('custom_notes')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('customer_wallet_address')->nullable();
            $table->enum('status', ['Created', 'Paid', 'InProcess', 'Completed', 'Cancelled'])->default('Created');
            $table->string('data_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

