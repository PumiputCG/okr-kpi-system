<?php

use App\Http\Controllers\Admin\CycleController;
use App\Http\Controllers\Admin\DepartmentAssignmentController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\EmployeeImportController;
use App\Http\Controllers\Admin\GoalTargetController;
use App\Http\Controllers\Admin\HomeAnnouncementController;
use App\Http\Controllers\Admin\HomeHierarchyController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KpiInputController;
use App\Http\Controllers\KpiReportReviewController;
use App\Http\Controllers\KpiSummaryDeptController;
use App\Http\Controllers\OkrSummaryAllController;
use App\Http\Controllers\OkrSummaryDeptController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::view('/welcome', 'welcome')->name('welcome');
Route::get('/login', function (Request $request) {
    $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';

    return redirect()->route('welcome', ['lang' => $lang]);
})->name('login');

Route::post('/login', [AppUserAuthController::class, 'login'])->name('login.store');
Route::post('/welcome', [AppUserAuthController::class, 'login'])->name('welcome.login');
Route::post('/forgot-password/verify', [AppUserAuthController::class, 'verifyResetIdentity'])->name('forgot.verify');
Route::post('/forgot-password/reset', [AppUserAuthController::class, 'resetForgotPassword'])->name('forgot.reset');
Route::post('/logout', [AppUserAuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])
        ->middleware('admin')
        ->name('home');
    Route::get('/home/announcements/files/{file}', [HomeController::class, 'downloadAnnouncementFile'])
        ->whereNumber('file')
        ->name('home.announcements.files.download');
    Route::get('/kpi/input', [KpiInputController::class, 'index'])->name('kpi.input');
    Route::get('/kpi/input/notice/payload', [KpiInputController::class, 'noticePayload'])->name('kpi.input.notice.payload');
    Route::post('/kpi/input/item/step1/save', [KpiInputController::class, 'saveStepOne'])->name('kpi.input.item.step1.save');
    Route::post('/kpi/input/item/step2/save', [KpiInputController::class, 'saveStepTwo'])->name('kpi.input.item.step2.save');
    Route::post('/kpi/input/item/delete', [KpiInputController::class, 'deleteItem'])->name('kpi.input.item.delete');
    Route::get('/kpi/input/evidence/{score}/{index}', [KpiInputController::class, 'showEvidenceFile'])
        ->whereNumber('score')
        ->whereNumber('index')
        ->name('kpi.input.evidence.show');
    Route::get('/kpi/input/announcements/files/{file}', [KpiInputController::class, 'downloadNoticeFile'])
        ->whereNumber('file')
        ->name('kpi.input.notice.files.download');
    Route::get('/kpi/input/announcements/files/{file}/preview', [KpiInputController::class, 'previewNoticeFile'])
        ->whereNumber('file')
        ->name('kpi.input.notice.files.preview');
    Route::get('/kpi/input/goal-targets/objectives/{id}/file', [KpiInputController::class, 'downloadGoalObjectiveFile'])
        ->whereNumber('id')
        ->name('kpi.input.goal_targets.objectives.file');
    Route::get('/kpi/input/goal-targets/key-results/{id}/file', [KpiInputController::class, 'downloadGoalKeyResultFile'])
        ->whereNumber('id')
        ->name('kpi.input.goal_targets.key_results.file');
    Route::post('/kpi/input/month/save', [KpiInputController::class, 'saveMonth'])->name('kpi.input.month.save');
    Route::post('/kpi/input/result/confirm', [KpiInputController::class, 'confirmResult'])->name('kpi.input.result.confirm');
    Route::get('/kpi/review', [KpiReportReviewController::class, 'index'])->name('kpi.review.index');
    Route::post('/kpi/review/{score}/approve', [KpiReportReviewController::class, 'approve'])
        ->whereNumber('score')
        ->name('kpi.review.approve');
    Route::post('/kpi/review/{score}/reject', [KpiReportReviewController::class, 'reject'])
        ->whereNumber('score')
        ->name('kpi.review.reject');
    Route::get('/kpi/review/evidence/{score}/{index}', [KpiReportReviewController::class, 'showEvidenceFile'])
        ->whereNumber('score')
        ->whereNumber('index')
        ->name('kpi.review.evidence.show');
    Route::get('/kpi/summary/department', [KpiSummaryDeptController::class, 'index'])->name('kpi.summary.dept');
    Route::get('/kpi/summary/department/monthly/{root}', [KpiSummaryDeptController::class, 'monthlySummary'])
        ->whereNumber('root')
        ->name('kpi.summary.dept.monthly');
    Route::get('/kpi/summary/department/evidence/{score}/{index}', [KpiSummaryDeptController::class, 'showEvidenceFile'])
        ->whereNumber('score')
        ->whereNumber('index')
        ->name('kpi.summary.dept.evidence.show');
    Route::get('/okr/summary/department', [OkrSummaryDeptController::class, 'index'])->name('okr.summary.dept');
    Route::get('/okr/summary/all-departments', [OkrSummaryAllController::class, 'index'])->name('okr.summary.all');
    Route::get('/notifications', [AppNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [AppNotificationController::class, 'markRead'])
        ->whereNumber('notification')
        ->name('notifications.read');
    Route::post('/notifications/read-all', [AppNotificationController::class, 'markAllRead'])->name('notifications.read_all');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/cycle', [CycleController::class, 'index'])->name('admin.cycle');
        Route::post('/admin/cycle', [CycleController::class, 'store'])->name('admin.cycles.store');
        Route::post('/admin/cycle/months', [CycleController::class, 'saveMonths'])->name('admin.cycles.months.save');
        Route::post('/admin/cycle/{cycle}/months/{month}/toggle', [CycleController::class, 'toggleMonth'])->name('admin.cycles.months.toggle');
        Route::post('/admin/cycle/{cycle}/activate', [CycleController::class, 'activate'])->name('admin.cycles.activate');
        Route::post('/admin/cycle/{cycle}/close', [CycleController::class, 'close'])->name('admin.cycles.close');

        Route::get('/admin/documents', [DocumentController::class, 'index'])->name('admin.documents');
        Route::get('/admin/documents/excel/range', [DocumentController::class, 'downloadRangeExcel'])
            ->name('admin.documents.downloadExcel.range');
        Route::get('/admin/documents/{cycle}/excel', [DocumentController::class, 'downloadCycleExcel'])
            ->name('admin.documents.downloadExcel');

        Route::get('/admin/employees/import', [EmployeeImportController::class, 'index'])
            ->name('admin.employees.import');
        Route::post('/admin/employees/import', [EmployeeImportController::class, 'store'])
            ->name('admin.employees.import.store');

        Route::get('/admin/employee-assignments', [DepartmentAssignmentController::class, 'index'])
            ->name('admin.employee.assignments.index');
        Route::post('/admin/employee-assignments', [DepartmentAssignmentController::class, 'save'])
            ->name('admin.employee.assignments.save');

        Route::get('/admin/goal-targets', [GoalTargetController::class, 'index'])
            ->name('admin.goal.targets');
        Route::post('/admin/goal-targets/objectives', [GoalTargetController::class, 'storeObjective'])
            ->name('admin.goal.targets.objectives.store');
        Route::post('/admin/goal-targets/objectives/{id}/update', [GoalTargetController::class, 'updateObjective'])
            ->whereNumber('id')
            ->name('admin.goal.targets.objectives.update');
        Route::post('/admin/goal-targets/objectives/{id}/delete', [GoalTargetController::class, 'destroyObjective'])
            ->whereNumber('id')
            ->name('admin.goal.targets.objectives.destroy');
        Route::get('/admin/goal-targets/objectives/{id}/file', [GoalTargetController::class, 'downloadObjectiveFile'])
            ->whereNumber('id')
            ->name('admin.goal.targets.objectives.file');
        Route::post('/admin/goal-targets/key-results', [GoalTargetController::class, 'storeKeyResult'])
            ->name('admin.goal.targets.key_results.store');
        Route::post('/admin/goal-targets/key-results/{id}/update', [GoalTargetController::class, 'updateKeyResult'])
            ->whereNumber('id')
            ->name('admin.goal.targets.key_results.update');
        Route::post('/admin/goal-targets/key-results/{id}/delete', [GoalTargetController::class, 'destroyKeyResult'])
            ->whereNumber('id')
            ->name('admin.goal.targets.key_results.destroy');
        Route::get('/admin/goal-targets/key-results/{id}/file', [GoalTargetController::class, 'downloadKeyResultFile'])
            ->whereNumber('id')
            ->name('admin.goal.targets.key_results.file');
        Route::post('/admin/goal-targets/kpi-reports/{root}/delete', [GoalTargetController::class, 'destroyKpiReport'])
            ->whereNumber('root')
            ->name('admin.goal.targets.kpi_reports.destroy');
        Route::post('/admin/goal-targets/kpi-reports/{root}/months/{month}/delete', [GoalTargetController::class, 'destroyKpiReportMonth'])
            ->whereNumber('root')
            ->whereNumber('month')
            ->name('admin.goal.targets.kpi_report_months.destroy');
        Route::post('/admin/goal-targets/level-three/{target}/update', [GoalTargetController::class, 'updateLevelThreeTarget'])
            ->whereNumber('target')
            ->name('admin.goal.targets.level_three.update');
        Route::post('/admin/goal-targets/level-three/{target}/delete', [GoalTargetController::class, 'destroyLevelThreeTarget'])
            ->whereNumber('target')
            ->name('admin.goal.targets.level_three.destroy');

        Route::post('/admin/home/hierarchy/save', [HomeHierarchyController::class, 'save'])
            ->name('admin.home.hierarchy.save');
        Route::post('/admin/home/hierarchy/delete', [HomeHierarchyController::class, 'destroy'])
            ->name('admin.home.hierarchy.delete');
        Route::post('/admin/home/announcements/save', [HomeAnnouncementController::class, 'store'])
            ->name('admin.home.announcements.save');
        Route::post('/admin/home/announcements/update', [HomeAnnouncementController::class, 'update'])
            ->name('admin.home.announcements.update');
        Route::post('/admin/home/announcements/delete', [HomeAnnouncementController::class, 'destroy'])
            ->name('admin.home.announcements.delete');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/verify-identity', [ProfileController::class, 'verifyIdentity'])->name('profile.verifyIdentity');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
});

Route::get('/dashboard', function (Request $request) {
    $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
    $user = $request->user();

    if (! $user) {
        return redirect()->route('welcome', ['lang' => $lang]);
    }

    if (AppUserAuthController::isAdminRole($user->role)) {
        return redirect()->route('admin.goal.targets', ['lang' => $lang]);
    }

    return redirect()->route('profile.edit', ['lang' => $lang]);
})->middleware('auth');
