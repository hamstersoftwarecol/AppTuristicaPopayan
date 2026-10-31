<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('contact_email');
            $table->string('phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('tourism_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('category', 30)->index();
            $table->string('destination')->index();
            $table->text('description');
            $table->text('conditions')->nullable();
            $table->unsignedInteger('reference_price')->nullable();
            $table->string('currency', 3)->default('COP');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tourism_service_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'tourism_service_id']);
        });
        Schema::create('travel_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tourism_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date');
            $table->unsignedSmallInteger('travelers');
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->text('response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 80);
            $table->string('excerpt', 500);
            $table->text('body');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('metadata');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('travel_inquiries');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('tourism_services');
        Schema::dropIfExists('providers');
    }
};
