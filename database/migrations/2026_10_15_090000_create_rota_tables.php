<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('department', 32);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'date']);
        });

        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ISO weekday: 1 = Monday ... 7 = Sunday
            $table->unsignedTinyInteger('weekday');
            $table->boolean('available')->default(true);
            $table->string('from_time', 5)->nullable();
            $table->string('until_time', 5)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'weekday']);
        });

        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('taker_id')->nullable()->constrained('users')->nullOnDelete();
            // open -> pending (someone picked it up) -> approved / declined, or cancelled
            $table->string('status', 16)->default('open');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('swap_requests');
        Schema::dropIfExists('time_off_requests');
        Schema::dropIfExists('availabilities');
        Schema::dropIfExists('shifts');
    }
};
