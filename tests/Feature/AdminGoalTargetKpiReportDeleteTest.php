<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\KpiUnit;
use App\Models\OkrAllResult;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use App\Models\OkrResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGoalTargetKpiReportDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        Storage::fake('public');
    }

    public function test_admin_can_delete_level_four_report_and_recalculate_results_from_remaining_reports(): void
    {
        $fixture = $this->createHierarchyFixture();

        Storage::disk('public')->put('evidence/delete-me.pdf', 'evidence');
        Storage::disk('public')->put('action-plans/delete-me.pdf', 'action plan');

        $response = $this
            ->actingAs($fixture['admin'])
            ->post(route('admin.goal.targets.kpi_reports.destroy', [
                'root' => $fixture['deleted_report']->id,
                'lang' => 'th',
            ]));

        $response
            ->assertRedirect(route('admin.goal.targets', ['lang' => 'th']))
            ->assertSessionHas('status', 'ลบรายงาน KPI และคำนวณผลใหม่เรียบร้อยแล้ว');

        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['deleted_report']->id]);
        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['deleted_month']->id]);
        $this->assertDatabaseMissing('kpi_month_reviews', ['id' => $fixture['review']->id]);

        $this->assertDatabaseHas('okr_objectives', ['id' => $fixture['objective']->id]);
        $this->assertDatabaseHas('okr_key_results', ['id' => $fixture['key_result']->id]);
        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['target']->id]);
        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['remaining_report']->id]);

        $this->assertDatabaseHas('kpi_result', [
            'app_user_id' => $fixture['employee']->id,
            'cycle_id' => $fixture['cycle']->id,
            'dept_abbr_hr' => 'HRM',
            'result' => 40,
        ]);
        $this->assertDatabaseHas('okr_result', [
            'dept_abbr_hr' => 'HRM',
            'cycle_id' => $fixture['cycle']->id,
            'result' => 40,
        ]);
        $this->assertDatabaseHas('okr_all_result', [
            'cycle_id' => $fixture['cycle']->id,
            'result' => 40,
        ]);

        Storage::disk('public')->assertMissing('evidence/delete-me.pdf');
        Storage::disk('public')->assertMissing('action-plans/delete-me.pdf');
    }

    public function test_non_admin_cannot_delete_level_four_report(): void
    {
        $fixture = $this->createHierarchyFixture();

        $this
            ->actingAs($fixture['employee'])
            ->post(route('admin.goal.targets.kpi_reports.destroy', [
                'root' => $fixture['deleted_report']->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['deleted_report']->id]);
        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['deleted_month']->id]);
        $this->assertDatabaseHas('kpi_month_reviews', ['id' => $fixture['review']->id]);
        $this->assertDatabaseHas('kpi_result', [
            'app_user_id' => $fixture['employee']->id,
            'cycle_id' => $fixture['cycle']->id,
            'result' => 70,
        ]);
    }

    public function test_admin_can_delete_one_month_and_recalculate_report_kpi_and_okr_results(): void
    {
        $fixture = $this->createHierarchyFixture();
        $remainingMonth = KpiMonthScore::query()->create([
            'kpi_meta_id' => $fixture['deleted_report']->id,
            'app_user_id' => $fixture['employee']->id,
            'cycle_id' => $fixture['cycle']->id,
            'month_no' => 2,
            'objective' => 'Report to delete',
            'detail' => 'February report',
            'target_departments' => ['HRM'],
            'parent_target_kpi_id' => $fixture['target']->id,
            'target_value' => 100,
            'criteria_operator' => '>=',
            'mode_type' => 'report',
            'score_value' => 20,
            'is_pass' => false,
            'result' => 100,
            'submitted_at' => now(),
        ]);
        $remainingReview = KpiMonthReview::query()->create([
            'kpi_month_score_id' => $remainingMonth->id,
            'status' => KpiMonthReview::STATUS_APPROVED,
            'reviewed_by_user_id' => $fixture['admin']->id,
            'reviewed_at' => now(),
        ]);

        Storage::disk('public')->put('evidence/delete-me.pdf', 'evidence');
        Storage::disk('public')->put('action-plans/delete-me.pdf', 'action plan');

        $response = $this
            ->actingAs($fixture['admin'])
            ->post(route('admin.goal.targets.kpi_report_months.destroy', [
                'root' => $fixture['deleted_report']->id,
                'month' => $fixture['deleted_month']->id,
            ]));

        $response
            ->assertRedirect(route('admin.goal.targets', ['lang' => 'en']))
            ->assertSessionHas('status', 'Monthly KPI data deleted and results recalculated.');

        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['deleted_month']->id]);
        $this->assertDatabaseMissing('kpi_month_reviews', ['id' => $fixture['review']->id]);
        $this->assertDatabaseHas('kpi_month_scores', [
            'id' => $fixture['deleted_report']->id,
            'result' => 0,
        ]);
        $this->assertDatabaseHas('kpi_month_scores', [
            'id' => $remainingMonth->id,
            'result' => 0,
        ]);
        $this->assertDatabaseHas('kpi_month_reviews', ['id' => $remainingReview->id]);
        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['remaining_report']->id]);
        $this->assertDatabaseHas('kpi_result', [
            'app_user_id' => $fixture['employee']->id,
            'cycle_id' => $fixture['cycle']->id,
            'dept_abbr_hr' => 'HRM',
            'result' => 20,
        ]);
        $this->assertDatabaseHas('okr_result', [
            'dept_abbr_hr' => 'HRM',
            'cycle_id' => $fixture['cycle']->id,
            'result' => 20,
        ]);
        $this->assertDatabaseHas('okr_all_result', [
            'cycle_id' => $fixture['cycle']->id,
            'result' => 20,
        ]);

        Storage::disk('public')->assertMissing('evidence/delete-me.pdf');
        Storage::disk('public')->assertMissing('action-plans/delete-me.pdf');
    }

    public function test_non_admin_cannot_delete_one_month(): void
    {
        $fixture = $this->createHierarchyFixture();

        $this
            ->actingAs($fixture['employee'])
            ->post(route('admin.goal.targets.kpi_report_months.destroy', [
                'root' => $fixture['deleted_report']->id,
                'month' => $fixture['deleted_month']->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('kpi_month_scores', ['id' => $fixture['deleted_month']->id]);
        $this->assertDatabaseHas('kpi_month_reviews', ['id' => $fixture['review']->id]);
        $this->assertDatabaseHas('kpi_month_scores', [
            'id' => $fixture['deleted_report']->id,
            'result' => 100,
        ]);
    }

    public function test_admin_can_edit_level_three_target_created_by_user(): void
    {
        $fixture = $this->createHierarchyFixture();

        $response = $this
            ->actingAs($fixture['admin'])
            ->post(route('admin.goal.targets.level_three.update', [
                'target' => $fixture['target']->id,
                'lang' => 'en',
            ]), [
                'objective' => 'Updated Level 3 target',
                'detail' => 'Updated by administrator',
                // Forged input must not change the original target department.
                'target_departments' => ['QMS'],
                'target_value' => 250,
                'kpi_unit_id' => $fixture['unit']->id,
                'criteria_operator' => '<=',
            ]);

        $response
            ->assertRedirect(route('admin.goal.targets', ['lang' => 'en']))
            ->assertSessionHas('status', 'Level 3 target updated.');

        $this->assertDatabaseHas('kpi_month_scores', [
            'id' => $fixture['target']->id,
            'app_user_id' => $fixture['manager']->id,
            'objective' => 'Updated Level 3 target',
            'detail' => 'Updated by administrator',
            'target_value' => 250,
            'kpi_unit_id' => $fixture['unit']->id,
            'criteria_operator' => '<=',
            'mode_type' => 'target',
        ]);
        $this->assertSame(['HRM'], KpiMonthScore::query()->findOrFail($fixture['target']->id)->target_departments);
        $this->assertDatabaseHas('kpi_month_scores', [
            'id' => $fixture['deleted_report']->id,
            'parent_target_kpi_id' => $fixture['target']->id,
        ]);
    }

    public function test_admin_can_delete_level_three_target_and_all_linked_user_reports(): void
    {
        $fixture = $this->createHierarchyFixture();
        Storage::disk('public')->put('evidence/delete-me.pdf', 'evidence');
        Storage::disk('public')->put('action-plans/delete-me.pdf', 'action plan');

        $response = $this
            ->actingAs($fixture['admin'])
            ->post(route('admin.goal.targets.level_three.destroy', [
                'target' => $fixture['target']->id,
                'lang' => 'en',
            ]));

        $response
            ->assertRedirect(route('admin.goal.targets', ['lang' => 'en']))
            ->assertSessionHas('status', 'Level 3 target and related reports deleted.');

        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['target']->id]);
        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['deleted_report']->id]);
        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['deleted_month']->id]);
        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['remaining_report']->id]);
        $this->assertDatabaseMissing('kpi_month_reviews', ['id' => $fixture['review']->id]);
        $this->assertDatabaseMissing('kpi_result', ['app_user_id' => $fixture['employee']->id]);
        $this->assertDatabaseMissing('okr_result', ['cycle_id' => $fixture['cycle']->id]);
        $this->assertDatabaseMissing('okr_all_result', ['cycle_id' => $fixture['cycle']->id]);

        $this->assertDatabaseHas('okr_objectives', ['id' => $fixture['objective']->id]);
        $this->assertDatabaseHas('okr_key_results', ['id' => $fixture['key_result']->id]);
        $this->assertDatabaseHas('app_users', ['id' => $fixture['manager']->id]);
        $this->assertDatabaseHas('app_users', ['id' => $fixture['employee']->id]);

        Storage::disk('public')->assertMissing('evidence/delete-me.pdf');
        Storage::disk('public')->assertMissing('action-plans/delete-me.pdf');
    }

    public function test_deleting_level_three_target_removes_many_linked_level_four_reports_and_excludes_them_from_calculation(): void
    {
        $fixture = $this->createHierarchyFixture();
        $otherTarget = KpiMonthScore::query()->create([
            'app_user_id' => $fixture['manager']->id,
            'cycle_id' => $fixture['cycle']->id,
            'month_no' => 0,
            'objective' => 'Unrelated Level 3 target',
            'detail' => 'Must remain',
            'target_departments' => ['HRM'],
            'okr_objective_id' => $fixture['objective']->id,
            'okr_key_result_id' => $fixture['key_result']->id,
            'target_value' => 100,
            'kpi_unit_id' => $fixture['unit']->id,
            'criteria_operator' => '>=',
            'mode_type' => 'target',
            'score_value' => 0,
            'is_pass' => false,
        ]);

        $linkedRootIds = [];
        $linkedMonthIds = [];
        $linkedReviewIds = [];
        $unrelatedRootIds = [];
        $bulkUserIds = [];

        for ($userIndex = 1; $userIndex <= 15; $userIndex++) {
            $user = AppUser::query()->create([
                'employee_code' => 'BULK-'.str_pad((string) $userIndex, 2, '0', STR_PAD_LEFT),
                'full_name_en' => 'Bulk Employee '.$userIndex,
                'position' => 'Staff',
                'dept_abbr_hr' => 'HRM',
                'role' => 'user',
                'is_registered' => true,
            ]);
            $bulkUserIds[] = $user->id;

            for ($reportIndex = 1; $reportIndex <= 3; $reportIndex++) {
                $root = KpiMonthScore::query()->create([
                    'app_user_id' => $user->id,
                    'cycle_id' => $fixture['cycle']->id,
                    'month_no' => 0,
                    'objective' => "Linked report {$userIndex}-{$reportIndex}",
                    'detail' => 'Must be deleted',
                    'target_departments' => ['HRM'],
                    'parent_target_kpi_id' => $fixture['target']->id,
                    'target_value' => 100,
                    'criteria_operator' => '>=',
                    'mode_type' => 'report',
                    'score_value' => 100,
                    'is_pass' => true,
                    'result' => 100,
                ]);
                $linkedRootIds[] = $root->id;

                for ($monthNo = 1; $monthNo <= 2; $monthNo++) {
                    $month = KpiMonthScore::query()->create([
                        'kpi_meta_id' => $root->id,
                        'app_user_id' => $user->id,
                        'cycle_id' => $fixture['cycle']->id,
                        'month_no' => $monthNo,
                        'objective' => $root->objective,
                        'detail' => 'Linked month',
                        'target_departments' => ['HRM'],
                        'parent_target_kpi_id' => $fixture['target']->id,
                        'target_value' => 100,
                        'criteria_operator' => '>=',
                        'mode_type' => 'report',
                        'score_value' => 100,
                        'is_pass' => true,
                        'result' => 100,
                        'submitted_at' => now(),
                    ]);
                    $linkedMonthIds[] = $month->id;
                    $review = KpiMonthReview::query()->create([
                        'kpi_month_score_id' => $month->id,
                        'status' => KpiMonthReview::STATUS_APPROVED,
                        'reviewed_by_user_id' => $fixture['admin']->id,
                        'reviewed_at' => now(),
                    ]);
                    $linkedReviewIds[] = $review->id;
                }
            }

            $unrelatedRoot = KpiMonthScore::query()->create([
                'app_user_id' => $user->id,
                'cycle_id' => $fixture['cycle']->id,
                'month_no' => 0,
                'objective' => 'Unrelated report '.$userIndex,
                'detail' => 'Must remain in calculation',
                'target_departments' => ['HRM'],
                'parent_target_kpi_id' => $otherTarget->id,
                'target_value' => 100,
                'criteria_operator' => '>=',
                'mode_type' => 'report',
                'score_value' => 25,
                'is_pass' => false,
                'result' => 25,
            ]);
            $unrelatedRootIds[] = $unrelatedRoot->id;

            KpiResult::query()->create([
                'app_user_id' => $user->id,
                'cycle_id' => $fixture['cycle']->id,
                'dept_abbr_hr' => 'HRM',
                'result' => 81.25,
            ]);
        }

        $this
            ->actingAs($fixture['admin'])
            ->post(route('admin.goal.targets.level_three.destroy', [
                'target' => $fixture['target']->id,
                'lang' => 'en',
            ]))
            ->assertRedirect(route('admin.goal.targets', ['lang' => 'en']));

        $this->assertCount(45, $linkedRootIds);
        $this->assertCount(90, $linkedMonthIds);
        $this->assertSame(0, KpiMonthScore::query()->whereIn('id', array_merge($linkedRootIds, $linkedMonthIds))->count());
        $this->assertSame(0, KpiMonthReview::query()->whereIn('id', $linkedReviewIds)->count());
        $this->assertDatabaseMissing('kpi_month_scores', ['id' => $fixture['target']->id]);

        $this->assertSame(15, KpiMonthScore::query()->whereIn('id', $unrelatedRootIds)->count());
        $this->assertDatabaseHas('kpi_month_scores', ['id' => $otherTarget->id]);
        $this->assertSame(15, KpiResult::query()
            ->whereIn('app_user_id', $bulkUserIds)
            ->where('cycle_id', $fixture['cycle']->id)
            ->where('result', 25)
            ->count());
        $this->assertDatabaseHas('okr_result', [
            'dept_abbr_hr' => 'HRM',
            'cycle_id' => $fixture['cycle']->id,
            'result' => 25,
        ]);
        $this->assertDatabaseHas('okr_all_result', [
            'cycle_id' => $fixture['cycle']->id,
            'result' => 25,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createHierarchyFixture(): array
    {
        $admin = AppUser::query()->create([
            'employee_code' => 'ADMIN-01',
            'full_name_en' => 'Administrator',
            'role' => 'admin',
            'is_registered' => true,
        ]);
        $employee = AppUser::query()->create([
            'employee_code' => 'EMP-01',
            'full_name_en' => 'Employee One',
            'position' => 'Staff',
            'dept_abbr_hr' => 'HRM',
            'role' => 'user',
            'is_registered' => true,
        ]);
        $manager = AppUser::query()->create([
            'employee_code' => 'MGR-01',
            'full_name_en' => 'Manager One',
            'position' => 'Manager',
            'dept_abbr_hr' => 'HRM',
            'role' => 'user',
            'is_registered' => true,
        ]);
        $unit = KpiUnit::query()->create([
            'code' => 'PERCENT',
            'name_th' => 'เปอร์เซ็นต์',
            'name_en' => 'Percent',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $cycle = Cycle::query()->create([
            'name' => 'Test Cycle',
            'code' => 'TEST-2026',
            'status' => Cycle::STATUS_OPEN,
            'is_active' => true,
        ]);
        $objective = OkrObjective::query()->create([
            'cycle_id' => $cycle->id,
            'sort_no' => 1,
            'title' => 'Level 1 objective',
            'detail' => 'CEO objective',
            'created_by_admin_id' => $admin->id,
        ]);
        $keyResult = OkrKeyResult::query()->create([
            'okr_objective_id' => $objective->id,
            'dept_abbr_hr' => 'HRM',
            'sort_no' => 1,
            'title' => 'Level 2 key result',
            'detail' => 'Department result',
        ]);
        $target = KpiMonthScore::query()->create([
            'app_user_id' => $manager->id,
            'cycle_id' => $cycle->id,
            'month_no' => 0,
            'objective' => 'Level 3 target',
            'detail' => 'Manager target',
            'target_departments' => ['HRM'],
            'okr_objective_id' => $objective->id,
            'okr_key_result_id' => $keyResult->id,
            'target_value' => 100,
            'kpi_unit_id' => $unit->id,
            'criteria_operator' => '>=',
            'mode_type' => 'target',
            'score_value' => 0,
            'is_pass' => false,
        ]);
        $deletedReport = KpiMonthScore::query()->create([
            'app_user_id' => $employee->id,
            'cycle_id' => $cycle->id,
            'month_no' => 0,
            'objective' => 'Report to delete',
            'detail' => 'Deleted report detail',
            'target_departments' => ['HRM'],
            'okr_objective_id' => $objective->id,
            'okr_key_result_id' => $keyResult->id,
            'parent_target_kpi_id' => $target->id,
            'target_value' => 100,
            'criteria_operator' => '>=',
            'mode_type' => 'report',
            'score_value' => 100,
            'is_pass' => true,
            'result' => 100,
        ]);
        $deletedMonth = KpiMonthScore::query()->create([
            'kpi_meta_id' => $deletedReport->id,
            'app_user_id' => $employee->id,
            'cycle_id' => $cycle->id,
            'month_no' => 1,
            'objective' => 'Report to delete',
            'detail' => 'January report',
            'target_departments' => ['HRM'],
            'parent_target_kpi_id' => $target->id,
            'target_value' => 100,
            'criteria_operator' => '>=',
            'mode_type' => 'report',
            'score_value' => 100,
            'is_pass' => true,
            'result' => 100,
            'evidence_files' => [
                ['path' => 'evidence/delete-me.pdf', 'name' => 'delete-me.pdf'],
            ],
            'action_plan_files' => [
                ['path' => 'action-plans/delete-me.pdf', 'name' => 'delete-me.pdf'],
            ],
            'submitted_at' => now(),
        ]);
        $review = KpiMonthReview::query()->create([
            'kpi_month_score_id' => $deletedMonth->id,
            'status' => KpiMonthReview::STATUS_APPROVED,
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
        ]);
        $remainingReport = KpiMonthScore::query()->create([
            'app_user_id' => $employee->id,
            'cycle_id' => $cycle->id,
            'month_no' => 0,
            'objective' => 'Remaining report',
            'detail' => 'Must remain in calculation',
            'target_departments' => ['HRM'],
            'okr_objective_id' => $objective->id,
            'okr_key_result_id' => $keyResult->id,
            'parent_target_kpi_id' => $target->id,
            'target_value' => 100,
            'criteria_operator' => '>=',
            'mode_type' => 'report',
            'score_value' => 40,
            'is_pass' => false,
            'result' => 40,
        ]);

        KpiResult::query()->create([
            'app_user_id' => $employee->id,
            'cycle_id' => $cycle->id,
            'dept_abbr_hr' => 'HRM',
            'result' => 70,
        ]);
        OkrResult::query()->create([
            'dept_abbr_hr' => 'HRM',
            'cycle_id' => $cycle->id,
            'result' => 70,
        ]);
        OkrAllResult::query()->create([
            'cycle_id' => $cycle->id,
            'result' => 70,
        ]);

        return [
            'admin' => $admin,
            'employee' => $employee,
            'manager' => $manager,
            'unit' => $unit,
            'cycle' => $cycle,
            'objective' => $objective,
            'key_result' => $keyResult,
            'target' => $target,
            'deleted_report' => $deletedReport,
            'deleted_month' => $deletedMonth,
            'review' => $review,
            'remaining_report' => $remainingReport,
        ];
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'kpi_month_reviews',
            'kpi_month_scores',
            'okr_key_results',
            'okr_objectives',
            'kpi_result',
            'okr_result',
            'okr_all_result',
            'kpi_units',
            'cycles',
            'app_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('app_users', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('password')->nullable();
            $table->string('full_name_th')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('position')->nullable();
            $table->string('dept_abbr_hr')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_registered')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('status')->default('closed');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::create('kpi_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name_th');
            $table->string('name_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('okr_objectives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedSmallInteger('sort_no')->default(1);
            $table->string('title', 500);
            $table->text('detail')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();
        });
        Schema::create('okr_key_results', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('okr_objective_id');
            $table->string('dept_abbr_hr')->nullable();
            $table->unsignedSmallInteger('sort_no')->default(1);
            $table->string('title', 500);
            $table->text('detail')->nullable();
            $table->timestamps();
        });
        Schema::create('kpi_month_scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kpi_meta_id')->nullable();
            $table->unsignedBigInteger('app_user_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedTinyInteger('month_no');
            $table->text('objective');
            $table->text('detail');
            $table->json('target_departments')->nullable();
            $table->unsignedBigInteger('okr_objective_id')->nullable();
            $table->unsignedBigInteger('okr_key_result_id')->nullable();
            $table->unsignedBigInteger('parent_target_kpi_id')->nullable();
            $table->decimal('target_value', 30, 10);
            $table->unsignedBigInteger('kpi_unit_id')->nullable();
            $table->string('criteria_operator', 5);
            $table->string('mode_type', 20)->nullable();
            $table->decimal('score_value', 30, 10);
            $table->boolean('is_pass')->default(false);
            $table->decimal('result', 5, 2)->nullable();
            $table->json('evidence_files')->nullable();
            $table->json('action_plan_files')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('kpi_month_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kpi_month_score_id')->unique();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_detail')->nullable();
            $table->timestamps();
        });
        Schema::create('kpi_result', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('app_user_id');
            $table->unsignedBigInteger('cycle_id');
            $table->string('dept_abbr_hr');
            $table->decimal('result', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['app_user_id', 'cycle_id', 'dept_abbr_hr']);
        });
        Schema::create('okr_result', function (Blueprint $table): void {
            $table->id();
            $table->string('dept_abbr_hr');
            $table->unsignedBigInteger('cycle_id');
            $table->decimal('result', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['dept_abbr_hr', 'cycle_id']);
        });
        Schema::create('okr_all_result', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->unique();
            $table->decimal('result', 5, 2)->nullable();
            $table->timestamps();
        });
    }
}
