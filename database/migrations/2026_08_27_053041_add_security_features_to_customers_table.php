<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            if (!Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')
                    ->nullable()
                    ->after('email');
            }

            if (!Schema::hasColumn('customers', 'email_verification_token')) {
                $table->string('email_verification_token')
                    ->nullable()
                    ->after('email_verified_at');
            }

            if (!Schema::hasColumn('customers', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')
                    ->default(false)
                    ->after('password');
            }

            if (!Schema::hasColumn('customers', 'two_factor_otp')) {
                $table->string('two_factor_otp')
                    ->nullable()
                    ->after('two_factor_enabled');
            }

            if (!Schema::hasColumn('customers', 'two_factor_otp_expires_at')) {
                $table->timestamp('two_factor_otp_expires_at')
                    ->nullable()
                    ->after('two_factor_otp');
            }

            if (!Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('two_factor_otp_expires_at');
            }

            if (!Schema::hasColumn('customers', 'deactivated_at')) {
                $table->timestamp('deactivated_at')
                    ->nullable()
                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $columns = [
                'email_verified_at',
                'email_verification_token',
                'two_factor_enabled',
                'two_factor_otp',
                'two_factor_otp_expires_at',
                'is_active',
                'deactivated_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
