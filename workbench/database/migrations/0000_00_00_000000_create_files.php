<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table
                ->increments('id')
                ->unsigned()
                ->unique();
            $table->char('title', 100);
            $table->integer('user_id')->nullable();
            $table->char('file_name', 100);
            $table->char('file_size', 100);
            $table->char('file_type', 100);
            $table->char('md5', 100);
            $table->integer('fileable_id')->nullable();
            $table->char('fileable_type', 100)->nullable();
            $table->char('fileable_attr', 100)->nullable();
            $table->integer('priority')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};