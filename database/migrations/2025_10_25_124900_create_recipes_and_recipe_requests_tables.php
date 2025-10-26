<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title', 200);
            $table->text('excerpt')->nullable();

            $table->json('instructions');

            $table->unsignedSmallInteger('number_of_persons')->default(1);
            $table->unsignedSmallInteger('time_to_cook')->default(0);
            $table->unsignedSmallInteger('time_to_prepare')->default(0);

            $table->json('ingredients');

            $table->timestamps();
        });

        Schema::create('recipe_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->text('ingredients_csv');
            $table->char('ingredients_hash', 64)->index();

            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'])->index();
            $table->uuid('recipe_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->string('webhook_url', 500)->nullable();

            $table->timestamps();

            $table->index(['ingredients_hash', 'status'], 'idx_req_hash_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_requests');
        Schema::dropIfExists('recipes');
    }
};