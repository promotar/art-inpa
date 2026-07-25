<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_programmer_training_samples')) {
            Schema::create('professional_programmer_training_samples', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('incident_id')->nullable();
                $table->unsignedBigInteger('repair_approval_id')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('source_hash', 80);
                $table->string('status', 40)->default('approved')->index();
                $table->json('diagnosis');
                $table->json('repair_outcome');
                $table->json('metadata')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index('incident_id', 'pp_train_samples_incident_idx');
                $table->index('repair_approval_id', 'pp_train_samples_approval_idx');
                $table->index('approved_by', 'pp_train_samples_user_idx');
                $table->unique('source_hash', 'pp_train_samples_hash_unique');
            });
        } else {
            $this->ensureTrainingSamplesIndexes();
        }

        if (! Schema::hasTable('professional_programmer_training_jobs')) {
            Schema::create('professional_programmer_training_jobs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('request_id', 120);
                $table->string('ai_job_id', 120)->nullable();
                $table->string('status', 40)->default('running');
                $table->unsignedInteger('samples_sent')->default(0);
                $table->boolean('training_endpoint_reachable')->default(false);
                $table->boolean('learning_verified')->default(false);
                $table->string('model_version_before', 160)->nullable();
                $table->string('model_version_after', 160)->nullable();
                $table->string('candidate_model_version', 160)->nullable();
                $table->decimal('before_score', 8, 4)->nullable();
                $table->decimal('after_score', 8, 4)->nullable();
                $table->decimal('improvement_percent', 8, 2)->nullable();
                $table->unsignedInteger('generic_answer_count')->nullable();
                $table->boolean('regression_found')->nullable();
                $table->boolean('golden_set_used_for_training')->default(false);
                $table->boolean('promoted')->default(false);
                $table->json('verification_rules')->nullable();
                $table->json('response')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index('user_id', 'pp_train_jobs_user_idx');
                $table->unique('request_id', 'pp_train_jobs_request_unique');
                $table->index('ai_job_id', 'pp_train_jobs_ai_job_idx');
                $table->index('status', 'pp_train_jobs_status_idx');
                $table->index('learning_verified', 'pp_train_jobs_verified_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_programmer_training_jobs');
        Schema::dropIfExists('professional_programmer_training_samples');
    }

    private function ensureTrainingSamplesIndexes(): void
    {
        $indexes = collect(DB::select('show index from professional_programmer_training_samples'))
            ->pluck('Key_name')
            ->all();

        Schema::table('professional_programmer_training_samples', function (Blueprint $table) use ($indexes): void {
            if (! in_array('pp_train_samples_incident_idx', $indexes, true) && Schema::hasColumn('professional_programmer_training_samples', 'incident_id')) {
                $table->index('incident_id', 'pp_train_samples_incident_idx');
            }
            if (! in_array('pp_train_samples_approval_idx', $indexes, true) && Schema::hasColumn('professional_programmer_training_samples', 'repair_approval_id')) {
                $table->index('repair_approval_id', 'pp_train_samples_approval_idx');
            }
            if (! in_array('pp_train_samples_user_idx', $indexes, true) && Schema::hasColumn('professional_programmer_training_samples', 'approved_by')) {
                $table->index('approved_by', 'pp_train_samples_user_idx');
            }
            if (! in_array('pp_train_samples_hash_unique', $indexes, true) && Schema::hasColumn('professional_programmer_training_samples', 'source_hash')) {
                $table->unique('source_hash', 'pp_train_samples_hash_unique');
            }
        });
    }
};
