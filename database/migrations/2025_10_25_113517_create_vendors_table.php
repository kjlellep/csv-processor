<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name')->unique();
            $t->boolean('export_csv')->default(true);

            $t->jsonb('mappings')->default(new Expression("'{}'::jsonb"));
            $t->jsonb('export_columns')->default(new Expression("'[]'::jsonb"));

            $t->char('csv_delimiter', 1)->default(',');
            $t->char('csv_enclosure', 1)->default('"');
            $t->char('csv_escape', 1)->default('\\');
            $t->boolean('has_header')->default(true);

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
