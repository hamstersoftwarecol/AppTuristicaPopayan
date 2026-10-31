<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tourism_service_id')->constrained()->restrictOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('unit_price');
            $table->text('terms');
            $table->boolean('is_open')->default(false)->index();
            $table->timestamps();
            $table->unique(['tourism_service_id', 'service_date']);
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('departure_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('service_title');
            $table->string('provider_name');
            $table->date('service_date');
            $table->unsignedSmallInteger('travelers');
            $table->unsignedInteger('unit_price');
            $table->unsignedBigInteger('total');
            $table->string('currency', 3)->default('COP');
            $table->text('terms');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('departures');
    }
};
