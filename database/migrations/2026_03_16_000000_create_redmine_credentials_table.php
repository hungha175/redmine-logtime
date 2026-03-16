<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('redmine_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->boolean('use_api')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redmine_credentials');
    }
};

