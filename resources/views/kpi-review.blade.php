@extends('layout')

@section('title', 'Review Reports')
@section('page_heading_key', 'menuKpiReview')
@section('page_heading', 'KPI Report Review')
@section('content')
    @php
        $lang = $lang ?? (request('lang', 'en') === 'th' ? 'th' : 'en');
        $selectedCycleId = (int) ($selectedCycle->id ?? 0);
        $selectedDepartment = strtoupper(trim((string) ($selectedDepartment ?? '')));
        $searchText = trim((string) ($searchText ?? ''));
        $reviewerDepartments = is_array($reviewerDepartments ?? null) ? $reviewerDepartments : [];
        $rows = is_array($rows ?? null) ? $rows : [];
        $stats = is_array($stats ?? null) ? $stats : [];
        $canReview = (bool) ($canReview ?? false);
        $monthNames = $lang === 'th'
            ? [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม']
            : [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        $t = $lang === 'th'
            ? [
                'title' => 'ตรวจรายงาน KPI',
                'cycle' => 'รอบ',
                'department' => 'แผนก',
                'allDepartments' => 'ทุกแผนกที่ได้รับมอบหมาย',
                'employee' => 'พนักงาน',
                'searchPlaceholder' => 'ค้นหารหัสหรือชื่อ',
                'searchBtn' => 'ค้นหา',
                'scopeNote' => 'แสดงเฉพาะพนักงานที่ถูกมอบหมายและส่งรายงานแล้ว',
                'noPermission' => 'คุณไม่ได้รับสิทธิ์เป็นผู้ตรวจรายงาน',
                'noData' => 'ยังไม่มีรายงานที่ส่งเข้ามา',
                'position' => 'ตำแหน่ง',
                'departmentLabel' => 'แผนก',
                'reportCount' => 'มีรายงาน {count} ฉบับ',
                'reportStatus' => 'สถานะรายงาน',
                'approvedTotal' => 'อนุมัติทั้งหมด',
                'pendingTotal' => 'รอดำเนินการทั้งหมด',
                'rejectedTotal' => 'ปฏิเสธทั้งหมด',
                'approved' => 'อนุมัติ',
                'pendingAction' => 'รอดำเนินการ',
                'rejected' => 'ปฏิเสธ',
                'waitingReportScore' => 'รอคะแนนรายงาน',
                'kpiItem' => 'รายการ KPI',
                'selectDepartment' => 'แผนก',
                'objective' => 'หัวข้อ',
                'detail' => 'รายละเอียด',
                'detailLabel' => 'รายละเอียด',
                'avgResult' => 'Average KPI Result',
                'itemLabel' => 'รายการที่',
                'topic' => 'หัวข้อ',
                'target' => 'เป้าหมาย',
                'unit' => 'หน่วย',
                'status' => 'สถานะ',
                'month' => 'เดือน',
                'score' => 'คะแนน',
                'criteria' => 'เกณฑ์',
                'pass' => 'ผ่านเกณฑ์',
                'fail' => 'ไม่ผ่านเกณฑ์',
                'notOpen' => '-',
                'submittedAt' => 'วันที่ส่งรายงาน',
                'reviewedAt' => 'วันที่ตรวจ',
                'evidence' => 'หลักฐาน',
                'actionPlan' => 'Action Plan',
                'noFile' => '-',
                'addRejectNote' => 'เพิ่มหมายเหตุปฏิเสธ',
                'rejectNotePlaceholder' => 'หมายเหตุปฏิเสธ',
                'approve' => 'อนุมัติ',
                'reject' => 'ปฏิเสธ',
                'confirmApprove' => 'ยืนยันการอนุมัติรายงานเดือนนี้?',
                'confirmReject' => 'ยืนยันการปฏิเสธรายงานเดือนนี้?',
                'actionFailed' => 'ไม่สามารถบันทึกผลการตรวจได้',
                'actionSuccessApproved' => 'อนุมัติรายงานเรียบร้อยแล้ว',
                'actionSuccessRejected' => 'ปฏิเสธรายงานเรียบร้อยแล้ว',
                'rejectNoteLabel' => 'หมายเหตุปฏิเสธ',
                'noDataCard' => 'ไม่มีข้อมูล',
            ]
            : [
                'title' => 'KPI Report Review',
                'cycle' => 'Cycle',
                'department' => 'Department',
                'allDepartments' => 'All assigned departments',
                'employee' => 'Employee',
                'searchPlaceholder' => 'Search code or name',
                'searchBtn' => 'Search',
                'scopeNote' => 'Only assigned employees with submitted reports are shown.',
                'noPermission' => 'You are not assigned as reviewer.',
                'noData' => 'No submitted reports found.',
                'position' => 'Position',
                'departmentLabel' => 'Department',
                'reportCount' => '{count} report(s)',
                'reportStatus' => 'Report status',
                'approvedTotal' => 'Total approved',
                'pendingTotal' => 'Total pending',
                'rejectedTotal' => 'Total rejected',
                'approved' => 'Approved',
                'pendingAction' => 'Pending action',
                'rejected' => 'Rejected',
                'waitingReportScore' => 'Waiting report score',
                'kpiItem' => 'KPI Item',
                'selectDepartment' => 'Department',
                'objective' => 'Objective',
                'detail' => 'Detail',
                'detailLabel' => 'Detail',
                'avgResult' => 'Average KPI Result',
                'itemLabel' => 'Item',
                'topic' => 'Topic',
                'target' => 'Target',
                'unit' => 'Unit',
                'status' => 'Status',
                'month' => 'Month',
                'score' => 'Score',
                'criteria' => 'Criteria',
                'pass' => 'Pass',
                'fail' => 'Fail',
                'notOpen' => '-',
                'submittedAt' => 'Submitted at',
                'reviewedAt' => 'Reviewed at',
                'evidence' => 'Evidence',
                'actionPlan' => 'Action Plan',
                'noFile' => '-',
                'addRejectNote' => 'Add reject note',
                'rejectNotePlaceholder' => 'Reject note',
                'approve' => 'Approve',
                'reject' => 'Reject',
                'confirmApprove' => 'Approve this month report?',
                'confirmReject' => 'Reject this month report?',
                'actionFailed' => 'Unable to save review result.',
                'actionSuccessApproved' => 'Report approved successfully.',
                'actionSuccessRejected' => 'Report rejected successfully.',
                'rejectNoteLabel' => 'Reject note',
                'noDataCard' => 'No data',
            ];
    @endphp
    <style>
        .review-shell { border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 12px 30px rgba(15,23,42,.08); padding: 16px; }
        .review-title { margin: 0; font-size: 22px; font-weight: 700; color: #111827; }
        .review-caption { margin: 6px 0 14px; font-size: 12px; color: #4b5563; }
        .review-filter { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end; margin-bottom: 12px; }
        .review-filter label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 600; color: #374151; }
        .review-filter select, .review-filter input { width: 100%; border: 1px solid #d1d5db; padding: 8px 10px; font-size: 13px; background: #fff; }
        .review-filter button { border: 0; background: #111827; color: #fff; font-size: 13px; padding: 9px 12px; min-width: 88px; cursor: pointer; }
        .review-top-stats { display: flex; gap: 12px; flex-wrap: wrap; margin: 0 0 12px; font-size: 12px; color: #374151; }
        .review-top-stats b { color: #111827; }
        .review-empty { border: 1px dashed #d1d5db; color: #6b7280; text-align: center; padding: 14px; }
        .review-employee { border: 1px solid #e5e7eb; margin-bottom: 10px; background: #fff; }
        .review-employee > summary, .review-kpi > summary { list-style: none; }
        .review-employee > summary::-webkit-details-marker, .review-kpi > summary::-webkit-details-marker { display: none; }
        .review-employee-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 12px; cursor: pointer; background: #f9fafb; border-bottom: 1px solid #f3f4f6; }
        .review-employee-name { margin: 0; font-size: 14px; font-weight: 700; color: #111827; line-height: 1.35; }
        .review-employee-line { margin: 4px 0 0; font-size: 12px; color: #374151; line-height: 1.45; }
        .review-report-status-lines { margin: 4px 0 0; font-size: 12px; line-height: 1.45; }
        .review-report-status-lines p { margin: 2px 0 0; }
        .review-report-status-approved { color: #15803d; font-weight: 400; }
        .review-report-status-pending { color: #92400e; font-weight: 400; }
        .review-report-status-rejected { color: #b91c1c; font-weight: 400; }
        .review-toggle { display: inline-flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #4b5563; line-height: 1; transition: transform .2s ease, color .2s ease; margin-top: 0; padding: 0 2px; }
        .review-employee-head:hover .review-toggle, .review-kpi-head:hover .review-toggle { color: #111827; }
        .review-employee[open] > summary .review-toggle, .review-kpi[open] > summary .review-toggle { transform: rotate(180deg); }
        .review-employee-body { padding: 0 12px 12px; }
        .review-kpi { border: 1px solid #e5e7eb; background: #fff; margin-top: 10px; }
        .review-kpi-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; padding: 10px; cursor: pointer; background: #fcfcfd; }
        .review-kpi-head-left { min-width: 0; flex: 1 1 auto; display: grid; gap: 6px; }
        .review-kpi-head-right { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
        .review-kpi-title { margin: 0; font-size: 13px; font-weight: 700; color: #111827; line-height: 1.45; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .review-kpi-status-card { border: 0; background: transparent; padding: 0; margin: 0; min-width: 0; max-width: 100%; display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; }
        .review-kpi-status-card-title { margin: 0; font-size: 11px; font-weight: 700; color: #111827; line-height: 1.4; white-space: nowrap; }
        .review-kpi-status-card-row { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; line-height: 1.4; color: #374151; white-space: nowrap; }
        .review-kpi-status-card-value { font-weight: 700; color: #111827; }
        .review-kpi-status-card-row.approved { color: #15803d; }
        .review-kpi-status-card-row.approved .review-kpi-status-card-value { color: #15803d; }
        .review-kpi-status-card-row.pending { color: #92400e; }
        .review-kpi-status-card-row.pending .review-kpi-status-card-value { color: #92400e; }
        .review-kpi-status-card-row.rejected { color: #b91c1c; }
        .review-kpi-status-card-row.rejected .review-kpi-status-card-value { color: #b91c1c; }
        .review-kpi-avg-corner { margin: 0; font-size: 12px; font-weight: 700; color: #111827; white-space: nowrap; }
        .review-kpi-body { border-top: 1px solid #eef2f7; padding: 10px; }
        .review-kpi-avg { margin: 0 0 6px; font-size: 12px; color: #374151; }
        .review-kpi-criteria-line { margin: 0 0 10px; font-size: 12px; color: #374151; line-height: 1.45; }
        .review-status-box { border: 1px solid #e5e7eb; background: #f9fafb; padding: 8px; margin-bottom: 10px; }
        .review-status-title { margin: 0 0 5px; font-size: 12px; font-weight: 700; color: #111827; }
        .review-status-row { display: grid; grid-template-columns: 150px 14px auto; gap: 4px; font-size: 12px; line-height: 1.5; color: #374151; }
        .review-status-value { font-weight: 700; color: #111827; }
        .review-status-row.approved { color: #15803d; }
        .review-status-row.approved .review-status-value { color: #15803d; }
        .review-status-row.pending { color: #92400e; }
        .review-status-row.pending .review-status-value { color: #92400e; }
        .review-status-row.rejected { color: #b91c1c; }
        .review-status-row.rejected .review-status-value { color: #b91c1c; }
        .review-month-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
        .review-month-card { border: 1px solid #e5e7eb; background: #fff; padding: 10px 10px 44px; display: grid; gap: 10px; position: relative; min-height: 228px; }
        .review-month-card.focus-notify-pending { border-color: #ca8a04; box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.2); background: #fffbeb; }
        .review-month-card.focus-notify-approved { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2); background: #f0fdf4; }
        .review-month-card.focus-notify-rejected { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2); background: #fef2f2; }
        .review-month-head { display: block; }
        .review-month-label { margin: 0; font-size: 12px; font-weight: 700; color: #111827; }
        .review-month-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
        .review-month-meta-group { display: grid; gap: 4px; }
        .review-month-meta-label { font-size: 11px; font-weight: 700; color: #374151; }
        .review-month-status { margin: 0; font-size: 12px; color: #6b7280; font-weight: 700; width: fit-content; padding: 5px 10px; background: #f3f4f6; border-radius: 0; }
        .review-month-status.approved { color: #166534; background: #dcfce7; }
        .review-month-status.pending { color: #92400e; background: #fef3c7; }
        .review-month-status.rejected { color: #b91c1c; background: #fee2e2; }
        .review-month-status.waiting { color: #92400e; background: #fef3c7; }
        .review-month-status.not-open { color: #6b7280; background: #f3f4f6; }
        .review-month-criteria { margin: 0; font-size: 12px; color: #6b7280; font-weight: 700; width: fit-content; padding: 5px 10px; background: #f3f4f6; }
        .review-month-criteria.success { color: #15803d; background: #dcfce7; }
        .review-month-criteria.fail { color: #b91c1c; background: #fee2e2; }
        .review-month-cycle-date { margin: 0; font-size: 12px; color: #374151; font-weight: 700; width: fit-content; padding: 5px 10px; background: #f3f4f6; }
        .review-month-empty { margin: 0; min-height: 98px; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 13px; font-weight: 700; color: #6b7280; background: #fafafa; border: 1px dashed #d1d5db; }
        .review-month-doc { margin-top: 6px; font-size: 11px; color: #374151; }
        .review-month-doc ul { margin: 3px 0 0; padding-left: 15px; }
        .review-month-doc li { margin-bottom: 2px; }
        .review-file-link { color: #1d4ed8; text-decoration: none; }
        .review-file-link:hover { text-decoration: underline; }
        .review-month-actions { position: absolute; right: 10px; bottom: 10px; display: flex; justify-content: flex-end; gap: 6px; align-items: flex-end; flex-wrap: wrap; }
        .review-btn { border: 0; color: #fff; font-size: 10px; font-weight: 700; padding: 5px 9px; cursor: pointer; }
        .review-btn.approve { background: #166534; }
        .review-btn.reject { background: #b91c1c; }
        .review-reject-form { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; min-width: 0; }
        .review-reject-controls { display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px; width: auto; flex-wrap: nowrap; }
        .review-reject-toggle { font-size: 11px; color: #374151; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
        .review-reject-input { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 11px; width: min(280px, 100%); min-width: 0; }
        @media (max-width: 1200px) { .review-month-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } }
        @media (max-width: 960px) {
            .review-filter { grid-template-columns: 1fr; }
            .review-kpi-head { align-items: flex-start; }
            .review-kpi-head-right { flex-direction: column; align-items: flex-end; gap: 4px; }
            .review-kpi-title { white-space: normal; }
            .review-kpi-status-card { min-width: 0; width: auto; }
            .review-status-row { grid-template-columns: 120px 14px auto; }
            .review-month-grid { grid-template-columns: 1fr; }
            .review-month-card { min-height: auto; }
        }
    </style>

    <section class="review-shell">
        <h2 class="review-title">{{ $t['title'] }}</h2>
        <p class="review-caption">{{ $t['scopeNote'] }}</p>

        <form method="GET" action="{{ route('kpi.review.index') }}" class="review-filter">
            <input type="hidden" name="lang" value="{{ $lang }}">
            <div>
                <label>{{ $t['cycle'] }}</label>
                <select name="cycle_id">
                    @foreach (($cycles ?? collect()) as $cycle)
                        @php $cycleId = (int) ($cycle->id ?? 0); @endphp
                        <option value="{{ $cycleId }}" @selected($cycleId === $selectedCycleId)>
                            {{ trim((string) ($cycle->name ?? '')) !== '' ? $cycle->name : ('#'.$cycleId) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>{{ $t['department'] }}</label>
                <select name="dept">
                    <option value="">{{ $t['allDepartments'] }}</option>
                    @foreach ($reviewerDepartments as $departmentCode)
                        @php $deptCode = strtoupper(trim((string) $departmentCode)); @endphp
                        <option value="{{ $deptCode }}" @selected($deptCode === $selectedDepartment)>{{ $deptCode }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>{{ $t['employee'] }}</label>
                <input type="text" name="q" value="{{ $searchText }}" placeholder="{{ $t['searchPlaceholder'] }}">
            </div>
            <button type="submit">{{ $t['searchBtn'] }}</button>
        </form>

        @if ($canReview)
            <p class="review-top-stats">
                <span>{{ $t['approved'] }} <b data-role="top-approved-count">{{ (int) ($stats['approved'] ?? 0) }}</b></span>
                <span>{{ $t['pendingAction'] }} <b data-role="top-pending-count">{{ (int) ($stats['pending'] ?? 0) }}</b></span>
                <span>{{ $t['rejected'] }} <b data-role="top-rejected-count">{{ (int) ($stats['rejected'] ?? 0) }}</b></span>
            </p>
        @endif

        @if (! $canReview)
            <div class="review-empty">{{ $t['noPermission'] }}</div>
        @elseif ($rows === [])
            <div class="review-empty">{{ $t['noData'] }}</div>
        @else
            @foreach ($rows as $row)
                @php
                    $employeeCode = trim((string) ($row['employee_code'] ?? ''));
                    $employeeName = trim((string) ($row['employee_name'] ?? ''));
                    $employeeTitle = trim($employeeCode.' '.$employeeName);
                    if ($employeeTitle === '') {
                        $employeeTitle = '-';
                    }
                    $positionText = trim((string) ($row['position'] ?? ''));
                    $departmentText = trim((string) ($row['department'] ?? ''));
                    $reportCountText = str_replace('{count}', (string) ((int) ($row['report_count'] ?? 0)), $t['reportCount']);
                @endphp
                <details class="review-employee">
                    <summary class="review-employee-head">
                        <div>
                            <p class="review-employee-name">{{ $employeeTitle }}</p>
                            <p class="review-employee-line">{{ $t['position'] }}: {{ $positionText !== '' ? $positionText : '-' }} | {{ $t['departmentLabel'] }}: {{ $departmentText !== '' ? $departmentText : '-' }}</p>
                            <p class="review-employee-line">{{ $reportCountText }}</p>
                            <div class="review-report-status-lines">
                                <p>
                                    <span class="review-report-status-approved" data-role="employee-approved-line" data-label="{{ $t['approvedTotal'] }}">{{ $t['approvedTotal'] }} : {{ (int) ($row['report_status_approved_count'] ?? 0) }}</span><br>
                                    <span class="review-report-status-pending" data-role="employee-pending-line" data-label="{{ $t['pendingTotal'] }}">{{ $t['pendingTotal'] }} : {{ (int) ($row['report_status_pending_count'] ?? 0) }}</span><br>
                                    <span class="review-report-status-rejected" data-role="employee-rejected-line" data-label="{{ $t['rejectedTotal'] }}">{{ $t['rejectedTotal'] }} : {{ (int) ($row['report_status_rejected_count'] ?? 0) }}</span>
                                </p>
                            </div>
                        </div>
                        <span class="review-toggle" aria-hidden="true">▾</span>
                    </summary>

                    <div class="review-employee-body">
                        @foreach ((array) ($row['reports'] ?? []) as $report)
                            @php
                                $reportId = (int) ($report['root_id'] ?? 0);
                                $objective = trim((string) ($report['objective'] ?? ''));
                                $detail = trim((string) ($report['detail'] ?? ''));
                                $reportDepartment = trim((string) ($report['department'] ?? ''));
                                $criteriaOperator = trim((string) ($report['criteria_operator'] ?? '-'));
                                if ($criteriaOperator === '') {
                                    $criteriaOperator = '-';
                                }
                                $targetValue = $report['target_value'] ?? null;
                                $targetText = $targetValue !== null
                                    ? rtrim(rtrim(number_format((float) $targetValue, 2, '.', ''), '0'), '.')
                                    : '-';
                                if ($targetText === '') {
                                    $targetText = '0';
                                }
                                $unitText = trim((string) ($report['unit_label'] ?? ''));
                                if ($unitText === '') {
                                    $unitText = '-';
                                }
                                $averageResult = $report['average_result'] ?? null;
                                $averageText = $averageResult !== null ? number_format((float) $averageResult, 2).'%' : '-';
                                $objectiveSummary = $objective !== ''
                                    ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $objective) ?? $objective), 80, '...')
                                    : '-';
                            @endphp
                            <details class="review-kpi">
                                <summary class="review-kpi-head">
                                    @php
                                        $criteriaDisplay = $criteriaOperator;
                                        if ($criteriaDisplay === '>=') {
                                            $criteriaDisplay = "\u{2265}";
                                        } elseif ($criteriaDisplay === '<=') {
                                            $criteriaDisplay = "\u{2264}";
                                        } elseif ($criteriaDisplay === '!=') {
                                            $criteriaDisplay = "\u{2260}";
                                        }
                                    @endphp
                                    <div class="review-kpi-head-left">
                                        <p class="review-kpi-title">
                                            {{ $t['topic'] }} : {{ $objectiveSummary }}
                                        </p>
                                        <section class="review-kpi-status-card">
                                            <p class="review-kpi-status-card-title">{{ $t['status'] }}</p>
                                            <div class="review-kpi-status-card-row approved">
                                                <span>{{ $t['approved'] }}</span>
                                                <span>:</span>
                                                <span class="review-kpi-status-card-value" data-role="report-approved-count">{{ (int) ($report['approved_count'] ?? 0) }}</span>
                                            </div>
                                            <div class="review-kpi-status-card-row pending">
                                                <span>{{ $t['pendingAction'] }}</span>
                                                <span>:</span>
                                                <span class="review-kpi-status-card-value" data-role="report-pending-count">{{ (int) ($report['pending_count'] ?? 0) }}</span>
                                            </div>
                                            <div class="review-kpi-status-card-row rejected">
                                                <span>{{ $t['rejected'] }}</span>
                                                <span>:</span>
                                                <span class="review-kpi-status-card-value" data-role="report-rejected-count">{{ (int) ($report['rejected_count'] ?? 0) }}</span>
                                            </div>
                                        </section>
                                    </div>
                                    <div class="review-kpi-head-right">
                                        <p class="review-kpi-avg-corner" data-role="report-average-corner" data-label="{{ $t['avgResult'] }}">{{ $t['avgResult'] }}: {{ $averageText }}</p>
                                        <span class="review-toggle" aria-hidden="true">▾</span>
                                    </div>
                                </summary>
                                <div class="review-kpi-body">
                                    <p class="review-kpi-avg"><strong>{{ $t['selectDepartment'] }}:</strong> {{ $reportDepartment !== '' ? $reportDepartment : '-' }}</p>
                                    <p class="review-kpi-avg"><strong>{{ $t['topic'] }}:</strong> {{ $objective !== '' ? $objective : '-' }}</p>
                                    <p class="review-kpi-avg"><strong>{{ $t['detailLabel'] }}:</strong> {{ $detail !== '' ? $detail : '-' }}</p>
                                    <p class="review-kpi-criteria-line">
                                        <strong>{{ $t['criteria'] }}:</strong> {{ $criteriaDisplay }}&nbsp;&nbsp;
                                        <strong>{{ $t['target'] }}:</strong> {{ $targetText }}&nbsp;&nbsp;
                                        <strong>{{ $t['unit'] }}:</strong> {{ $unitText }}
                                    </p>

                                    <div class="review-month-grid">
                                        @foreach ((array) ($report['months'] ?? []) as $month)
                                            @php
                                                $statusKey = strtolower(trim((string) ($month['status_key'] ?? $month['status'] ?? 'not_open')));
                                                $monthNo = (int) ($month['month_no'] ?? 0);
                                                $monthLabel = $monthNames[$monthNo] ?? ('#'.$monthNo);

                                                $statusClass = 'not-open';
                                                $statusText = $t['notOpen'];
                                                if ($statusKey === 'approved') {
                                                    $statusClass = 'approved';
                                                    $statusText = $t['approved'];
                                                } elseif ($statusKey === 'pending') {
                                                    $statusClass = 'pending';
                                                    $statusText = $t['pendingAction'];
                                                } elseif ($statusKey === 'rejected') {
                                                    $statusClass = 'rejected';
                                                    $statusText = $t['rejected'];
                                                } elseif ($statusKey === 'waiting_score') {
                                                    $statusClass = 'waiting';
                                                    $statusText = $t['waitingReportScore'];
                                                }

                                                $scoreValue = $month['score_value'] ?? null;
                                                $scoreText = $scoreValue !== null ? number_format((float) $scoreValue, 2) : '-';
                                                $isPassRaw = $month['is_pass'] ?? null;
                                                $criteriaText = '-';
                                                if ($isPassRaw === true || $isPassRaw === 1 || $isPassRaw === '1') {
                                                    $criteriaText = $t['pass'];
                                                } elseif ($isPassRaw === false || $isPassRaw === 0 || $isPassRaw === '0') {
                                                    $criteriaText = $t['fail'];
                                                }

                                                $evidenceFiles = is_array($month['evidence_files'] ?? null) ? $month['evidence_files'] : [];
                                                $actionPlanFiles = is_array($month['action_plan_files'] ?? null) ? $month['action_plan_files'] : [];
                                                $canMonthReview = (bool) ($month['can_review'] ?? false);
                                                $scoreId = (int) ($month['score_id'] ?? 0);
                                                $isNoDataMonth = in_array($statusKey, ['not_open', 'waiting_score'], true)
                                                    && $scoreText === '-'
                                                    && $criteriaText === '-'
                                                    && $evidenceFiles === []
                                                    && $actionPlanFiles === [];
                                                $criteriaClass = $criteriaText === $t['pass']
                                                    ? 'success'
                                                    : ($criteriaText === $t['fail'] ? 'fail' : '');
                                                $passFlag = '';
                                                if ($isPassRaw === true || $isPassRaw === 1 || $isPassRaw === '1') {
                                                    $passFlag = '1';
                                                } elseif ($isPassRaw === false || $isPassRaw === 0 || $isPassRaw === '0') {
                                                    $passFlag = '0';
                                                }
                                            @endphp
                                            <article
                                                class="review-month-card"
                                                data-score-id="{{ $scoreId > 0 ? $scoreId : '' }}"
                                                data-month-no="{{ $monthNo > 0 ? $monthNo : '' }}"
                                                data-status-key="{{ $statusKey }}"
                                                data-pass-flag="{{ $passFlag }}"
                                            >
                                                <div class="review-month-head">
                                                    <p class="review-month-label">{{ $monthLabel }}</p>
                                                </div>

                                                @if ($isNoDataMonth)
                                                    <p class="review-month-empty">{{ $t['noDataCard'] }}</p>
                                                @else
                                                    <div class="review-month-meta">
                                                        <div class="review-month-meta-group">
                                                            <span class="review-month-meta-label">{{ $t['criteria'] }}</span>
                                                            <p class="review-month-criteria {{ $criteriaClass }}">{{ $criteriaText }}</p>
                                                        </div>
                                                        <div class="review-month-meta-group">
                                                            <span class="review-month-meta-label">{{ $t['status'] }}</span>
                                                            <p class="review-month-status {{ $statusClass }}" data-role="month-status-text">{{ $statusText }}</p>
                                                        </div>
                                                        <div class="review-month-meta-group">
                                                            <span class="review-month-meta-label">{{ $t['score'] }}</span>
                                                            <p class="review-month-cycle-date">{{ $scoreText }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="review-month-doc">
                                                        <strong>{{ $t['submittedAt'] }}:</strong> <span data-role="submitted-at-value">{{ trim((string) ($month['submitted_at'] ?? '')) !== '' ? $month['submitted_at'] : '-' }}</span><br>
                                                        <strong>{{ $t['reviewedAt'] }}:</strong> <span data-role="reviewed-at-value">{{ trim((string) ($month['reviewed_at'] ?? '')) !== '' ? $month['reviewed_at'] : '-' }}</span>
                                                    </div>

                                                    <div class="review-month-doc">
                                                        <strong>{{ $t['evidence'] }}:</strong>
                                                        @if ($evidenceFiles === [])
                                                            {{ $t['noFile'] }}
                                                        @else
                                                            <ul>
                                                                @foreach ($evidenceFiles as $file)
                                                                    @php
                                                                        $fileUrl = trim((string) ($file['url'] ?? ''));
                                                                        $fileName = trim((string) ($file['name'] ?? ''));
                                                                    @endphp
                                                                    @if ($fileUrl !== '')
                                                                        <li><a class="review-file-link" href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ $fileName !== '' ? $fileName : '-' }}</a></li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>

                                                    <div class="review-month-doc">
                                                        <strong>{{ $t['actionPlan'] }}:</strong>
                                                        @if ($actionPlanFiles === [])
                                                            {{ $t['noFile'] }}
                                                        @else
                                                            <ul>
                                                                @foreach ($actionPlanFiles as $file)
                                                                    @php
                                                                        $fileUrl = trim((string) ($file['url'] ?? ''));
                                                                        $fileName = trim((string) ($file['name'] ?? ''));
                                                                    @endphp
                                                                    @if ($fileUrl !== '')
                                                                        <li><a class="review-file-link" href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ $fileName !== '' ? $fileName : '-' }}</a></li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (! $isNoDataMonth && $canMonthReview && $scoreId > 0)
                                                    <div class="review-month-actions" data-role="month-actions">
                                                        <form method="POST" action="{{ route('kpi.review.approve', ['score' => $scoreId, 'lang' => $lang]) }}" class="js-review-action-form" data-confirm-message="{{ $t['confirmApprove'] }}">
                                                            @csrf
                                                            <button type="submit" class="review-btn approve">{{ $t['approve'] }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('kpi.review.reject', ['score' => $scoreId, 'lang' => $lang]) }}" class="js-review-action-form review-reject-form" data-confirm-message="{{ $t['confirmReject'] }}">
                                                            @csrf
                                                            <div class="review-reject-controls">
                                                                <button type="submit" class="review-btn reject">{{ $t['reject'] }}</button>
                                                                <label class="review-reject-toggle">
                                                                    <input type="checkbox" data-role="toggle-reject-note"> {{ $t['addRejectNote'] }}
                                                                </label>
                                                            </div>
                                                            <input type="text" name="reject_detail" class="review-reject-input" data-role="reject-note-input" placeholder="{{ $t['rejectNotePlaceholder'] }}" hidden disabled>
                                                        </form>
                                                    </div>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </details>
            @endforeach
        @endif
    </section>
    <script>
        (function () {
            const actionFailedText = @json($t['actionFailed']);
            const actionSuccessApprovedText = @json($t['actionSuccessApproved']);
            const actionSuccessRejectedText = @json($t['actionSuccessRejected']);
            const rejectNoteLabelText = @json($t['rejectNoteLabel']);
            const approvedTotalLabel = @json($t['approvedTotal']);
            const pendingTotalLabel = @json($t['pendingTotal']);
            const rejectedTotalLabel = @json($t['rejectedTotal']);
            const approvedStatusText = @json($t['approved']);
            const pendingStatusText = @json($t['pendingAction']);
            const rejectedStatusText = @json($t['rejected']);
            const normalizeFocusDecision = (value) => {
                const text = String(value || '').trim().toLowerCase();
                if (text === 'approved') return 'approved';
                if (text === 'rejected') return 'rejected';
                return '';
            };
            const normalizeFocusPassFlag = (value) => {
                if (value === true || value === 1 || value === '1') return '1';
                if (value === false || value === 0 || value === '0') return '0';
                return '';
            };
            const normalizeFocusHighlight = (value) => {
                const text = String(value || '').trim().toLowerCase();
                if (text === 'pending') return 'pending';
                if (text === 'approved') return 'approved';
                if (text === 'rejected') return 'rejected';
                return '';
            };
            const notificationFocusState = (() => {
                const params = new URLSearchParams(window.location.search);
                const isFromNotification = String(params.get('focus_from') || '').trim().toLowerCase() === 'notification';
                if (!isFromNotification) {
                    return {
                        active: false,
                        scoreId: 0,
                        monthNo: 0,
                        decision: '',
                        passFlag: '',
                        highlight: '',
                    };
                }

                const scoreId = Number(params.get('focus_score_id') || 0);
                const monthNo = Number(params.get('focus_month_no') || 0);
                const decision = normalizeFocusDecision(params.get('focus_decision') || '');
                const passFlag = normalizeFocusPassFlag(params.get('focus_pass') || '');
                const highlight = normalizeFocusHighlight(params.get('focus_highlight') || '');
                const safeScoreId = Number.isFinite(scoreId) && scoreId > 0 ? scoreId : 0;
                const safeMonthNo = Number.isFinite(monthNo) && monthNo >= 1 && monthNo <= 12 ? monthNo : 0;

                return {
                    active: safeScoreId > 0 || safeMonthNo > 0,
                    scoreId: safeScoreId,
                    monthNo: safeMonthNo,
                    decision,
                    passFlag,
                    highlight,
                };
            })();

            const clearNotificationFocusParams = () => {
                try {
                    const url = new URL(window.location.href);
                    const keys = ['focus_from', 'focus_score_id', 'focus_month_no', 'focus_decision', 'focus_pass', 'focus_highlight'];
                    let changed = false;
                    keys.forEach((key) => {
                        if (url.searchParams.has(key)) {
                            url.searchParams.delete(key);
                            changed = true;
                        }
                    });
                    if (changed) {
                        history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
                    }
                } catch (error) {
                    // no-op
                }
            };

            const resolveFocusHighlightClass = (monthCard) => {
                if (notificationFocusState.highlight === 'pending') {
                    return 'focus-notify-pending';
                }
                if (notificationFocusState.highlight === 'rejected') {
                    return 'focus-notify-rejected';
                }
                if (notificationFocusState.highlight === 'approved') {
                    return 'focus-notify-approved';
                }
                if (notificationFocusState.decision === 'rejected') {
                    return 'focus-notify-rejected';
                }
                if (notificationFocusState.decision === 'approved') {
                    return 'focus-notify-approved';
                }
                if (notificationFocusState.passFlag === '0') {
                    return 'focus-notify-rejected';
                }
                if (notificationFocusState.passFlag === '1') {
                    return 'focus-notify-approved';
                }

                const statusKey = normalizeFocusDecision(monthCard ? monthCard.dataset.statusKey : '');
                if (statusKey === 'rejected') {
                    return 'focus-notify-rejected';
                }
                return 'focus-notify-approved';
            };

            const findFocusMonthCard = () => {
                const monthCards = [...document.querySelectorAll('.review-month-card')];
                if (monthCards.length < 1) {
                    return null;
                }

                if (notificationFocusState.scoreId > 0) {
                    const exactCard = monthCards.find((monthCard) => (
                        Number(monthCard && monthCard.dataset ? monthCard.dataset.scoreId || 0 : 0) === notificationFocusState.scoreId
                    ));
                    if (exactCard) {
                        return exactCard;
                    }
                }

                if (notificationFocusState.monthNo > 0) {
                    const cardsByMonth = monthCards.filter((monthCard) => (
                        Number(monthCard && monthCard.dataset ? monthCard.dataset.monthNo || 0 : 0) === notificationFocusState.monthNo
                    ));
                    if (cardsByMonth.length < 1) {
                        return null;
                    }

                    if (notificationFocusState.decision !== '') {
                        const byDecision = cardsByMonth.find((monthCard) => (
                            normalizeFocusDecision(monthCard && monthCard.dataset ? monthCard.dataset.statusKey : '') === notificationFocusState.decision
                        ));
                        if (byDecision) {
                            return byDecision;
                        }
                    }

                    if (notificationFocusState.passFlag === '1' || notificationFocusState.passFlag === '0') {
                        const byPass = cardsByMonth.find((monthCard) => (
                            normalizeFocusPassFlag(monthCard && monthCard.dataset ? monthCard.dataset.passFlag : '') === notificationFocusState.passFlag
                        ));
                        if (byPass) {
                            return byPass;
                        }
                    }

                    return cardsByMonth[0] || null;
                }

                return null;
            };

            const focusReviewMonthFromNotification = () => {
                if (!notificationFocusState.active) {
                    return;
                }

                const monthCard = findFocusMonthCard();
                clearNotificationFocusParams();
                if (!monthCard) {
                    return;
                }

                const employeeDetails = monthCard.closest('details.review-employee');
                if (employeeDetails) {
                    employeeDetails.open = true;
                }
                const reportDetails = monthCard.closest('details.review-kpi');
                if (reportDetails) {
                    reportDetails.open = true;
                }

                const highlightClass = resolveFocusHighlightClass(monthCard);
                setTimeout(() => {
                    try {
                        monthCard.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });
                    } catch (error) {
                        monthCard.scrollIntoView();
                    }

                    monthCard.classList.remove('focus-notify-pending', 'focus-notify-approved', 'focus-notify-rejected');
                    monthCard.classList.add(highlightClass);
                    setTimeout(() => {
                        monthCard.classList.remove('focus-notify-pending', 'focus-notify-approved', 'focus-notify-rejected');
                    }, 3000);
                }, 120);
            };

            focusReviewMonthFromNotification();

            const showAlert = (message, type = 'info') => {
                if (window.AppModal && typeof window.AppModal.alert === 'function') {
                    window.AppModal.alert(message, type);
                    return;
                }
                window.alert(message);
            };

            const confirmAction = (message) => new Promise((resolve) => {
                if (!window.AppModal || typeof window.AppModal.confirm !== 'function') {
                    resolve(window.confirm(message));
                    return;
                }

                const confirmOkBtn = document.getElementById('confirm-ok');
                const confirmCancelBtn = document.getElementById('confirm-cancel');
                let settled = false;
                const cleanup = () => {
                    if (confirmOkBtn) {
                        confirmOkBtn.removeEventListener('click', onConfirm, true);
                    }
                    if (confirmCancelBtn) {
                        confirmCancelBtn.removeEventListener('click', onCancel, true);
                    }
                };
                const finish = (value) => {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    cleanup();
                    resolve(!!value);
                };
                const onConfirm = () => finish(true);
                const onCancel = () => finish(false);
                if (confirmOkBtn) {
                    confirmOkBtn.addEventListener('click', onConfirm, true);
                }
                if (confirmCancelBtn) {
                    confirmCancelBtn.addEventListener('click', onCancel, true);
                }

                window.AppModal.confirm(message, () => finish(true));
            });

            const parseCount = (value) => {
                const text = String(value == null ? '' : value).trim();
                const number = Number(text.replace(/[^\d.-]/g, ''));
                return Number.isFinite(number) ? number : 0;
            };

            const normalizeMonthStatusKey = (value) => {
                const text = String(value || '').trim().toLowerCase();
                if (text === 'approved') return 'approved';
                if (text === 'rejected') return 'rejected';
                if (text === 'pending') return 'pending';
                return '';
            };

            const resolveMonthStatusDisplay = (statusKey) => {
                if (statusKey === 'approved') {
                    return { text: approvedStatusText, className: 'approved' };
                }
                if (statusKey === 'rejected') {
                    return { text: rejectedStatusText, className: 'rejected' };
                }
                return { text: pendingStatusText, className: 'pending' };
            };

            const formatNowDmyHm = () => {
                const now = new Date();
                const day = String(now.getDate()).padStart(2, '0');
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const year = String(now.getFullYear());
                const hour = String(now.getHours()).padStart(2, '0');
                const minute = String(now.getMinutes()).padStart(2, '0');
                return `${day}/${month}/${year} ${hour}:${minute}`;
            };

            const refreshReportSummary = (reportDetails) => {
                if (!reportDetails) {
                    return;
                }

                const monthCards = [...reportDetails.querySelectorAll('.review-month-card')];
                let approvedCount = 0;
                let pendingCount = 0;
                let rejectedCount = 0;
                let approvedPassCount = 0;

                monthCards.forEach((monthCard) => {
                    const statusKey = normalizeMonthStatusKey(monthCard && monthCard.dataset ? monthCard.dataset.statusKey : '');
                    if (statusKey === 'approved') {
                        approvedCount += 1;
                        if (normalizeFocusPassFlag(monthCard && monthCard.dataset ? monthCard.dataset.passFlag : '') === '1') {
                            approvedPassCount += 1;
                        }
                        return;
                    }
                    if (statusKey === 'rejected') {
                        rejectedCount += 1;
                        return;
                    }
                    if (statusKey === 'pending') {
                        pendingCount += 1;
                    }
                });

                const approvedCountEl = reportDetails.querySelector('[data-role="report-approved-count"]');
                const pendingCountEl = reportDetails.querySelector('[data-role="report-pending-count"]');
                const rejectedCountEl = reportDetails.querySelector('[data-role="report-rejected-count"]');
                if (approvedCountEl) {
                    approvedCountEl.textContent = String(approvedCount);
                }
                if (pendingCountEl) {
                    pendingCountEl.textContent = String(pendingCount);
                }
                if (rejectedCountEl) {
                    rejectedCountEl.textContent = String(rejectedCount);
                }

                const averageEl = reportDetails.querySelector('[data-role="report-average-corner"]');
                if (averageEl) {
                    const label = String(averageEl.dataset.label || 'Average KPI Result');
                    const avgText = approvedCount > 0
                        ? `${((approvedPassCount / approvedCount) * 100).toFixed(2)}%`
                        : '-';
                    averageEl.textContent = `${label}: ${avgText}`;
                }
            };

            const refreshEmployeeSummary = (employeeDetails) => {
                if (!employeeDetails) {
                    return;
                }

                const reportDetailsList = [...employeeDetails.querySelectorAll('details.review-kpi')];
                let approvedTotal = 0;
                let pendingTotal = 0;
                let rejectedTotal = 0;

                reportDetailsList.forEach((reportDetails) => {
                    approvedTotal += parseCount(reportDetails.querySelector('[data-role="report-approved-count"]')?.textContent || '0');
                    pendingTotal += parseCount(reportDetails.querySelector('[data-role="report-pending-count"]')?.textContent || '0');
                    rejectedTotal += parseCount(reportDetails.querySelector('[data-role="report-rejected-count"]')?.textContent || '0');
                });

                const approvedLineEl = employeeDetails.querySelector('[data-role="employee-approved-line"]');
                const pendingLineEl = employeeDetails.querySelector('[data-role="employee-pending-line"]');
                const rejectedLineEl = employeeDetails.querySelector('[data-role="employee-rejected-line"]');
                if (approvedLineEl) {
                    const label = String(approvedLineEl.dataset.label || approvedTotalLabel);
                    approvedLineEl.textContent = `${label} : ${approvedTotal}`;
                }
                if (pendingLineEl) {
                    const label = String(pendingLineEl.dataset.label || pendingTotalLabel);
                    pendingLineEl.textContent = `${label} : ${pendingTotal}`;
                }
                if (rejectedLineEl) {
                    const label = String(rejectedLineEl.dataset.label || rejectedTotalLabel);
                    rejectedLineEl.textContent = `${label} : ${rejectedTotal}`;
                }
            };

            const refreshTopStats = () => {
                const monthCards = [...document.querySelectorAll('.review-month-card')];
                let approvedTotal = 0;
                let pendingTotal = 0;
                let rejectedTotal = 0;

                monthCards.forEach((monthCard) => {
                    const statusKey = normalizeMonthStatusKey(monthCard && monthCard.dataset ? monthCard.dataset.statusKey : '');
                    if (statusKey === 'approved') {
                        approvedTotal += 1;
                        return;
                    }
                    if (statusKey === 'rejected') {
                        rejectedTotal += 1;
                        return;
                    }
                    if (statusKey === 'pending') {
                        pendingTotal += 1;
                    }
                });

                const topApprovedEl = document.querySelector('[data-role="top-approved-count"]');
                const topPendingEl = document.querySelector('[data-role="top-pending-count"]');
                const topRejectedEl = document.querySelector('[data-role="top-rejected-count"]');
                if (topApprovedEl) {
                    topApprovedEl.textContent = String(approvedTotal);
                }
                if (topPendingEl) {
                    topPendingEl.textContent = String(pendingTotal);
                }
                if (topRejectedEl) {
                    topRejectedEl.textContent = String(rejectedTotal);
                }
            };

            const updateMonthCardAfterDecision = (monthCard, statusKey, reviewedAtText, rejectDetailText) => {
                if (!monthCard) {
                    return;
                }

                const safeStatusKey = normalizeMonthStatusKey(statusKey) || 'pending';
                const statusDisplay = resolveMonthStatusDisplay(safeStatusKey);
                monthCard.dataset.statusKey = safeStatusKey;

                const statusEl = monthCard.querySelector('[data-role="month-status-text"]');
                if (statusEl) {
                    statusEl.classList.remove('approved', 'pending', 'rejected', 'waiting', 'not-open');
                    statusEl.classList.add(statusDisplay.className);
                    statusEl.textContent = statusDisplay.text;
                }

                const reviewedAtEl = monthCard.querySelector('[data-role="reviewed-at-value"]');
                if (reviewedAtEl) {
                    reviewedAtEl.textContent = String(reviewedAtText || formatNowDmyHm());
                }

                const actionsEl = monthCard.querySelector('[data-role="month-actions"]');
                if (actionsEl) {
                    actionsEl.remove();
                }

                const safeRejectDetail = String(rejectDetailText || '').trim();
                if (safeRejectDetail !== '') {
                    monthCard.dataset.rejectDetail = safeRejectDetail;
                } else {
                    delete monthCard.dataset.rejectDetail;
                }

                monthCard.classList.remove('focus-notify-pending', 'focus-notify-approved', 'focus-notify-rejected');
                monthCard.classList.add(safeStatusKey === 'approved' ? 'focus-notify-approved' : 'focus-notify-rejected');
                setTimeout(() => {
                    monthCard.classList.remove('focus-notify-pending', 'focus-notify-approved', 'focus-notify-rejected');
                }, 2500);
            };

            const showDecisionSuccessAlert = (statusKey, rejectDetailText) => {
                const safeStatusKey = normalizeMonthStatusKey(statusKey);
                const safeRejectDetail = String(rejectDetailText || '').trim();
                if (safeStatusKey === 'rejected') {
                    const message = safeRejectDetail !== ''
                        ? `${actionSuccessRejectedText} ${rejectNoteLabelText}: ${safeRejectDetail}`
                        : actionSuccessRejectedText;
                    showAlert(message, 'success');
                    return;
                }

                showAlert(actionSuccessApprovedText, 'success');
            };

            document.querySelectorAll('.review-reject-form').forEach((form) => {
                const toggle = form.querySelector('[data-role="toggle-reject-note"]');
                const input = form.querySelector('[data-role="reject-note-input"]');
                if (!toggle || !input) {
                    return;
                }

                const syncRejectNote = () => {
                    const checked = !!toggle.checked;
                    input.hidden = !checked;
                    input.disabled = !checked;
                    if (!checked) {
                        input.value = '';
                    }
                };

                toggle.addEventListener('change', syncRejectNote);
                syncRejectNote();
            });

            document.querySelectorAll('.js-review-action-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const rejectDetailInput = form.querySelector('input[name="reject_detail"]');
                    const rejectDetailFromForm = rejectDetailInput && !rejectDetailInput.disabled
                        ? String(rejectDetailInput.value || '').trim()
                        : '';
                    let confirmMessage = String(form.dataset.confirmMessage || '').trim();
                    if (rejectDetailFromForm !== '' && form.classList.contains('review-reject-form')) {
                        confirmMessage = `${confirmMessage} ${rejectNoteLabelText}: ${rejectDetailFromForm}`;
                    }
                    const proceed = await confirmAction(confirmMessage);
                    if (!proceed) {
                        return;
                    }

                    const monthCard = form.closest('.review-month-card');
                    const reportDetails = form.closest('details.review-kpi');
                    const employeeDetails = form.closest('details.review-employee');
                    const actionButtons = monthCard
                        ? [...monthCard.querySelectorAll('.review-month-actions button[type="submit"]')]
                        : [];
                    actionButtons.forEach((buttonEl) => {
                        buttonEl.disabled = true;
                    });

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });
                        let payload = null;
                        try {
                            payload = await response.json();
                        } catch (error) {
                            payload = null;
                        }
                        if (!response.ok || !payload || payload.ok !== true) {
                            const message = payload && payload.message ? String(payload.message) : actionFailedText;
                            showAlert(message);
                            actionButtons.forEach((buttonEl) => {
                                buttonEl.disabled = false;
                            });
                            return;
                        }

                        const statusKey = normalizeMonthStatusKey(payload.status || '');
                        const reviewedAtText = String(payload.reviewed_at || '').trim();
                        const rejectDetailText = String(payload.reject_detail || rejectDetailFromForm || '').trim();

                        updateMonthCardAfterDecision(monthCard, statusKey, reviewedAtText, rejectDetailText);
                        refreshReportSummary(reportDetails);
                        refreshEmployeeSummary(employeeDetails);
                        refreshTopStats();
                        showDecisionSuccessAlert(statusKey, rejectDetailText);
                    } catch (error) {
                        showAlert(actionFailedText);
                        actionButtons.forEach((buttonEl) => {
                            buttonEl.disabled = false;
                        });
                    }
                });
            });
        })();
    </script>
    <script>
        document.addEventListener('app:lang-changed', (event) => {
            const newLang = event && event.detail && event.detail.lang === 'th' ? 'th' : 'en';
            const currentLang = '{{ $lang }}';
            if (newLang === currentLang) return;
            const url = new URL(window.location.href);
            url.searchParams.set('lang', newLang);
            window.location.replace(url.toString());
        });
    </script>
@endsection
