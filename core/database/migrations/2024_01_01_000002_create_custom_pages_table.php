<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('html_content')->nullable();
            $table->longText('css_content')->nullable();
            $table->longText('js_content')->nullable();
            $table->longText('head_content')->nullable()->comment('External scripts, meta tags injected into <head>');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('favicon')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=draft, 1=published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_pages');
    }
};
