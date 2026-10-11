<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blockchain_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('tx_hash')->unique();
            $table->unsignedBigInteger('block_number');
            $table->string('from_address');
            $table->string('to_address');
            $table->string('event_name')->default('OrderStatusUpdated');
            $table->string('onchain_status');
            $table->unsignedBigInteger('gas_used')->nullable();
            $table->json('raw_receipt')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_audit_trails');
    }
};

