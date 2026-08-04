<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('item_order');
            $table->unsignedTinyInteger('raw_score');
            $table->timestamps();
            $table->unique(['survey_submission_id', 'item_order']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE survey_answers ADD CONSTRAINT chk_item_order CHECK (item_order BETWEEN 1 AND 26)');
            DB::statement('ALTER TABLE survey_answers ADD CONSTRAINT chk_raw_score CHECK (raw_score BETWEEN 1 AND 7)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
