<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->bigIncrements('_id');
            $table->char('id', 10)
                ->unique();
            $table->string('name');
            $table->string('slug')
                ->unique();
            $table->dateTime('published_at');
            $table->json('data');
            $table->json('features');
            $table->boolean('is_active')
                ->default(true);

            $table->datetimes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
