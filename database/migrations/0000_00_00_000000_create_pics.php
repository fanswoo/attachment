<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pics', function (Blueprint $table) {
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
            $table->char('thumb', 100);
            $table->integer('picable_id')->nullable();
            $table->char('picable_type', 100)->nullable();
            $table->char('picable_attr', 100)->nullable();
            $table->integer('priority')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pics');
    }
};