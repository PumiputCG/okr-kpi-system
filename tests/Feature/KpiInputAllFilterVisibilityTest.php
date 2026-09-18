<?php

namespace Tests\Feature;

use App\Models\AppUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class KpiInputAllFilterVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('admin_department_assignments');
        Schema::dropIfExists('app_users');

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

        Schema::create('admin_department_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('dept_abbr_hr')->unique();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->json('target_user_ids_json')->nullable();
            $table->unsignedBigInteger('reviewer_user_id')->nullable();
            $table->json('reviewer_user_ids_json')->nullable();
            $table->unsignedBigInteger('assigned_by_admin_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_authenticated_user_receives_all_as_default_filter_for_target_and_report_tabs(): void
    {
        $user = AppUser::query()->create([
            'employee_code' => '71019',
            'password' => bcrypt('password'),
            'full_name_th' => 'ผู้ใช้งานทดสอบ',
            'position' => 'Manager',
            'dept_abbr_hr' => 'HRM',
            'role' => 'user',
            'is_registered' => true,
        ]);

        $this->actingAs($user);
        view()->share('errors', new ViewErrorBag);

        $html = view('kpi-input', [
            'activeCycle' => null,
            'savedCards' => [],
            'kpiUnits' => [],
            'kpiAverageByDepartment' => [],
            'okrHierarchy' => [],
            'kpiInputPolicy' => [
                'position' => 'Manager',
                'can_skip_evidence' => false,
                'show_action_plan_on_fail' => false,
                'require_action_plan_on_fail' => false,
            ],
            'kpiDepartmentTargetPolicy' => [
                'enabled' => true,
                'has_target_assignment' => true,
                'has_reviewer_assignment' => false,
                'viewer_department' => 'HRM',
                'options' => ['HRM', 'QMS'],
                'default_departments' => ['HRM'],
            ],
        ])->render();

        $this->assertStringContainsString('id="kpi-mode-target-btn"', $html);
        $this->assertStringContainsString('id="kpi-mode-report-btn"', $html);
        $this->assertStringContainsString('let currentTargetDepartmentFilterAll = true;', $html);
        $this->assertStringContainsString('let currentReportDepartmentFilterAll = true;', $html);
        $this->assertStringContainsString("allTabBtn.dataset.filterValue = 'all';", $html);
        $this->assertStringContainsString("allTabBtn.textContent = getText('kpiFilterAll');", $html);
        $this->assertStringContainsString('containerEl.appendChild(allTabBtn);', $html);
        $this->assertStringContainsString('safeDepartments.forEach((departmentCode) => {', $html);
        $this->assertStringNotContainsString('safeHierarchyOptions.forEach((option) => {', $html);
        $this->assertStringContainsString('canUseReportDepartmentSubtabs ? reportDepartments : []', $html);
    }

    public function test_kpi_hierarchy_summary_aligns_multiline_content_for_levels_one_to_three(): void
    {
        $source = file_get_contents(resource_path('views/kpi-input.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('.kpi-card-compact-hierarchy-row {', $source);
        $this->assertStringContainsString('grid-template-columns: max-content minmax(0, 1fr);', $source);
        $this->assertStringContainsString('.kpi-card-compact-hierarchy-value {', $source);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $source);
        $this->assertStringContainsString("buildCompactHierarchyRow('1', getText(l1Key), okrSummary.objectiveLabel)", $source);
        $this->assertStringContainsString("buildCompactHierarchyRow('2', getText(l2Key), okrSummary.keyResultLabel)", $source);
        $this->assertStringContainsString("buildCompactHierarchyRow('3', getText('kpiLabelOkrLevelThree'), okrSummary.levelThreeLabel)", $source);
        $this->assertStringContainsString('.kpi-card-compact-detail-row {', $source);
        $this->assertStringContainsString("buildCompactDetailRow(getText('kpiLabelObjective'), objectiveText)", $source);
        $this->assertStringContainsString("buildCompactDetailRow(getText('kpiLabelDetail'), detailText)", $source);
        $this->assertStringContainsString('<div class="kpi-card-compact-section-header">', $source);
    }

    public function test_target_kpi_can_choose_not_to_define_criteria_and_notice_defaults_to_viewer_department(): void
    {
        $inputSource = file_get_contents(resource_path('views/kpi-input.blade.php'));
        $layoutSource = file_get_contents(resource_path('views/layout.blade.php'));

        $this->assertIsString($inputSource);
        $this->assertIsString($layoutSource);
        $this->assertStringContainsString('data-role="criteria-choice"', $inputSource);
        $this->assertStringContainsString('data-role="has-criteria"', $inputSource);
        $this->assertStringContainsString('.kpi-card.is-report-mode .kpi-criteria-choice {', $inputSource);
        $this->assertStringContainsString("payload.append('has_criteria', hasCriteriaChoice ? '1' : '0');", $inputSource);
        $this->assertStringContainsString('criteriaFields.hidden = isTargetMode && resolveCardHasCriteriaChoice(cardEl) !== true;', $inputSource);
        $this->assertStringContainsString('globalNoticeDeptFilterValue = globalNoticeViewerDepartment;', $layoutSource);
        $this->assertStringContainsString('const department = globalNoticeViewerDepartment || activeDepartment;', $layoutSource);
    }
}
