<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->change()->fulltext('users_first_name_fulltext');
            $table->string('last_name')->change()->fulltext('users_last_name_fulltext');
            $table->string('email')->change()->fulltext('users_email_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_first_name_fulltext');
            $table->dropIndex('users_last_name_fulltext');
            $table->dropIndex('users_email_fulltext');
        });
    }
};
