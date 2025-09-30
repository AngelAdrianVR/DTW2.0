<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('web_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('ej. hero_banner, portfolio_project, special_offer');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('publish_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_contents');
    }
};
