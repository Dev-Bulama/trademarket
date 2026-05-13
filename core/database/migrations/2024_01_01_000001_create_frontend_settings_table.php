<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frontend_settings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('frontend_disabled')->default(0)->comment('0=default frontend, 1=custom landing page');
            $table->unsignedBigInteger('active_page_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frontend_settings');
    }
};
