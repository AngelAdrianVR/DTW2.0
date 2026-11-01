<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('route_name')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('visitor_id', 100)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('device', 20)->default('desktop');
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index('visited_at');
            $table->index(['visited_at', 'is_bot']);
            $table->index('path');
            $table->index('visitor_id');
            $table->index('referrer_host');
            $table->index('device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
