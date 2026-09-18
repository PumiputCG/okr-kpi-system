@extends('layout')

@section('title', 'Report Summary')
@section('page_heading_key', 'menuKpiDept')
@section('page_heading', 'Report Summary')
@section('content')
    @php
        $lang = $lang ?? (request('lang', 'en') === 'th' ? 'th' : 'en');
        $q = $q ?? '';
        $departmentLabel = $departmentLabel ?? '-';
        $departmentOptions = collect($departmentOptions ?? [])
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter(fn ($code) => $code !== '')
            ->values()
            ->all();
        $selectedDepartment = strtoupper(trim((string) ($selectedDepartment ?? '')));
        $goalTopicOptions = collect($goalTopicOptions ?? [])
            ->map(fn ($option) => [
                'id' => (int) ($option['id'] ?? 0),
                'label' => trim((string) ($option['label'] ?? '')),
            ])
            ->filter(fn ($option) => $option['id'] > 0 && $option['label'] !== '')
            ->values()
            ->all();
        $selectedGoalTopicId = (int) ($selectedGoalTopicId ?? 0);
        $cycles = $cycles ?? collect();
        $selectedCycle = $selectedCycle ?? null;
        $rows = $rows ?? [];
        $rowsByDepartment = collect($rows)->groupBy(fn ($row) => $row['group_department'] ?? ($row['department'] ?? ''));

        $t = $lang === 'th'
            ? [
                'title' => 'สรุปรายงาน',
                'cycle' => 'รอบ',
                'searchPlaceholder' => 'ค้นหาด้วยรหัสพนักงานหรือชื่อ',
                'searchBtn' => 'ค้นหา',
                'department' => 'แผนก',
                'allDepartments' => 'ทุกแผนกที่ได้รับสิทธิ์',
                'period' => 'ช่วงเวลา',
                'no' => 'No.',
                'employeeCount' => 'จำนวนพนักงาน',
                'departmentCol' => 'แผนก',
                'selectedDepartmentCol' => 'แผนกที่เลือก',
                'codeCol' => 'รหัส',
                'nameCol' => 'ชื่อ-สกุล',
                'positionCol' => 'ตำแหน่ง',
                'goalTopic' => 'หัวข้อเป้าหมาย',
                'goalTopicFilter' => 'เลือกหัวข้อเป้าหมาย',
                'allGoalTopics' => 'ทุกหัวข้อเป้าหมาย',
                'objective' => 'วัตถุประสงค์',
                'detail' => 'รายละเอียด',
                'target' => 'เป้าหมาย',
                'approvedMonthlyCol' => 'ผล KPI รายเดือน',
                'reviewerNameCol' => 'ชื่อผู้ตรวจรายงาน',
                'result' => 'ค่าเฉลี่ยผลลัพธ์ KPI',
                'avgResult' => 'ผลลัพธ์ KPIs เฉลี่ยทั้งหมดรายบุคคล',
                'view' => 'ดู',
                'grabTable' => 'ลาก',
                'grabLabel' => 'โหมดจับเลื่อนตาราง',
                'monthlyTitle' => 'สรุปผล KPI รายเดือน',
                'monthlyEmployee' => 'พนักงาน',
                'monthlyObjective' => 'วัตถุประสงค์',
                'monthlyDetail' => 'รายละเอียด',
                'monthlyMonth' => 'เดือน',
                'monthlyScore' => 'คะแนน',
                'monthlyFiles' => 'ไฟล์',
                'monthlyCriteria' => 'เกณฑ์',
                'monthlyResult' => 'ผลลัพธ์',
                'monthlyNetAverage' => 'คะแนนเฉลี่ยสุทธิ',
                'monthlyLoading' => 'กำลังโหลดข้อมูล...',
                'monthlyError' => 'ไม่สามารถโหลดข้อมูล KPI รายเดือนได้',
                'close' => 'ปิด',
                'empty' => 'ยังไม่พบข้อมูล KPI ของแผนกนี้ในรอบที่เลือก',
            ]
            : [
                'title' => 'Report Summary',
                'cycle' => 'Cycle',
                'searchPlaceholder' => 'Search by employee code or name',
                'searchBtn' => 'Search',
                'department' => 'Department',
                'allDepartments' => 'All Assigned Departments',
                'period' => 'Period',
                'no' => 'No.',
                'employeeCount' => 'Employee Count',
                'departmentCol' => 'Department',
                'selectedDepartmentCol' => 'Selected Department',
                'codeCol' => 'Code',
                'nameCol' => 'Full Name',
                'positionCol' => 'Position',
                'goalTopic' => 'Goal Topic',
                'goalTopicFilter' => 'Select Goal Topic',
                'allGoalTopics' => 'All Goal Topics',
                'objective' => 'Objective',
                'detail' => 'Detail',
                'target' => 'Target',
                'approvedMonthlyCol' => 'Monthly KPI Result',
                'reviewerNameCol' => 'Reviewer Name',
                'result' => 'Average KPI Result',
                'avgResult' => 'Average Individual KPIs Result',
                'view' => 'View',
                'grabTable' => 'Drag',
                'grabLabel' => 'Table drag mode',
                'monthlyTitle' => 'KPI Monthly Summary',
                'monthlyEmployee' => 'Employee',
                'monthlyObjective' => 'Objective',
                'monthlyDetail' => 'Detail',
                'monthlyMonth' => 'Month',
                'monthlyScore' => 'Score',
                'monthlyFiles' => 'Files',
                'monthlyCriteria' => 'Criteria',
                'monthlyResult' => 'Result',
                'monthlyNetAverage' => 'Net Average Score',
                'monthlyLoading' => 'Loading KPI monthly data...',
                'monthlyError' => 'Unable to load KPI monthly data.',
                'close' => 'Close',
                'empty' => 'No KPI data found for this department in the selected cycle.',
            ];

        $monthlySummaryUrlTemplate = route('kpi.summary.dept.monthly', ['root' => '__ROOT__']);
    @endphp

    <style>
        .kpi-dept-shell {
            background: #ffffff;
            padding: 20px 22px;
            min-height: 460px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .kpi-dept-head {
            display: grid;
            gap: 10px;
            margin-bottom: 16px;
        }

        .kpi-dept-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        .kpi-dept-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
            font-size: 13px;
            color: #374151;
        }

        .kpi-dept-meta b {
            color: #111827;
        }

        .kpi-cycle-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .kpi-cycle-filter select,
        .kpi-cycle-filter input[type="text"] {
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 7px 10px;
            font-size: 13px;
        }

        .kpi-cycle-filter select {
            min-width: 230px;
        }

        .kpi-cycle-filter .kpi-goal-topic-select {
            min-width: 280px;
            max-width: 380px;
        }

        .kpi-cycle-filter input[type="text"] {
            min-width: 290px;
        }

        .kpi-search-btn {
            border: 0;
            background: #111827;
            color: #ffffff;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        .kpi-search-btn:hover {
            background: #1f2937;
        }

        .kpi-table-wrap {
            overflow-x: auto;
            border: 1px solid #d1d5db;
        }

        .kpi-table-wrap.is-grab-mode {
            cursor: grab;
            user-select: none;
        }

        .kpi-table-wrap.is-grab-mode .kpi-table {
            cursor: inherit;
        }

        .kpi-table-wrap.is-grab-mode.is-dragging {
            cursor: grabbing;
        }

        .kpi-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 2100px;
            font-size: 13px;
            table-layout: fixed;
        }

        .kpi-col-gray,
        .kpi-col-gray th,
        th.kpi-col-gray {
            background: #f3f4f6 !important;
        }

        td.kpi-col-gray {
            background: #f9fafb !important;
        }

        .kpi-table th,
        .kpi-table td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
            background: #ffffff;
            line-height: 1.45;
            white-space: normal;
            word-break: break-word;
        }

        .kpi-grab-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 2px;
        }

        .kpi-grab-btn {
            height: 30px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            line-height: 1.2;
            cursor: pointer;
            padding: 0 10px;
        }

        .kpi-grab-btn:hover {
            background: #f3f4f6;
        }

        .kpi-grab-btn.is-active {
            background: #fde7f3;
            border-color: #e83e8c;
            color: #111827;
        }

        .kpi-table th {
            background: #f3f4f6;
            font-weight: 600;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .kpi-table .text-center {
            text-align: center;
        }

        .kpi-table td[rowspan] {
            vertical-align: middle;
        }

        .kpi-col-target,
        .kpi-col-score-action,
        .kpi-col-result,
        .kpi-col-avg-result,
        .kpi-col-department {
            white-space: nowrap;
            word-break: keep-all;
            overflow-wrap: normal;
        }

        .kpi-goal-topic-l1 {
            font-weight: 700;
            font-size: 13px;
            color: #111827;
            line-height: 1.4;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kpi-goal-topic-l2 {
            font-size: 12px;
            color: #374151;
            padding-left: 12px;
            margin-top: 3px;
            line-height: 1.4;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kpi-goal-topic-l3 {
            font-size: 12px;
            color: #6b7280;
            padding-left: 24px;
            margin-top: 2px;
            line-height: 1.4;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kpi-col-score-action,
        .kpi-col-result,
        .kpi-col-avg-result,
        .kpi-col-department {
            text-align: center;
        }

        .kpi-table td.kpi-col-score-action,
        .kpi-table td.kpi-col-result,
        .kpi-table td.kpi-col-avg-result,
        .kpi-table td.kpi-col-department {
            text-align: center;
            vertical-align: middle;
        }

        .kpi-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            padding: 0;
            background: transparent;
            color: #2563eb;
            text-decoration: underline;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.3;
        }

        .kpi-view-btn:hover {
            color: #1d4ed8;
        }

        .kpi-view-btn:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: 2px;
        }

        .kpi-entry-divider td.kpi-entry-cell {
            border-bottom: 2px dashed #9ca3af;
        }

        /* Keep the "Average Individual KPIs Result" column divider solid. */
        .kpi-entry-divider td.kpi-col-avg-result {
            border-bottom: 1px solid #d1d5db;
        }

        .kpi-table tr.kpi-dept-start > td {
            border-top: 3px solid #111111 !important;
        }

        .kpi-col-code,
        .kpi-col-position {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .kpi-col-name {
            text-align: center;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .kpi-col-code {
            font-variant-numeric: tabular-nums;
            font-family: Consolas, 'Courier New', monospace;
        }

        .kpi-empty {
            text-align: center;
            color: #6b7280;
            padding: 16px;
        }

        .kpi-monthly-modal {
            position: fixed;
            inset: 0;
            z-index: 12000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(17, 24, 39, 0.56);
        }

        .kpi-monthly-modal.show {
            display: flex;
        }

        .kpi-monthly-panel {
            width: min(1280px, calc(100vw - 24px));
            max-height: 92vh;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.22);
            display: flex;
            flex-direction: column;
        }

        .kpi-monthly-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .kpi-monthly-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .kpi-monthly-close {
            border: 0;
            background: transparent;
            color: #6b7280;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .kpi-monthly-close:hover {
            color: #111827;
        }

        .kpi-monthly-meta {
            display: grid;
            gap: 4px;
            padding: 12px 16px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .kpi-monthly-meta p {
            margin: 0;
        }

        .kpi-monthly-table-wrap {
            padding: 12px 16px;
            overflow-x: auto;
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
        }

        .kpi-monthly-table {
            width: max-content;
            min-width: 1240px;
            border-collapse: collapse;
            table-layout: auto;
            font-size: 13px;
        }

        .kpi-monthly-table th,
        .kpi-monthly-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
            background: #ffffff;
            line-height: 1.4;
        }

        .kpi-monthly-table th {
            background: #f3f4f6;
            font-weight: 600;
        }

        .kpi-monthly-table .text-center {
            text-align: center;
        }

        .kpi-summary-result-pass {
            color: #15803d;
            font-weight: 600;
        }

        .kpi-summary-result-fail {
            color: #b91c1c;
            font-weight: 600;
        }

        .kpi-summary-result-pending {
            color: #6b7280;
            font-weight: 500;
        }

        .kpi-summary-result-pending-review {
            color: #ca8a04;
            font-weight: 600;
        }

        .kpi-monthly-net-average td {
            font-weight: 700;
            background: #f8fafc;
        }

        .kpi-monthly-empty {
            text-align: center;
            color: #6b7280;
            padding: 14px;
        }

        .kpi-monthly-file-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 4px;
            width: 100%;
        }

        .kpi-monthly-file-list li {
            line-height: 1.25;
            min-width: 0;
            position: relative;
            padding-left: 12px;
        }

        .kpi-monthly-file-list li::before {
            content: '\2022';
            position: absolute;
            left: 0;
            top: 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.25;
        }

        .kpi-monthly-files-cell {
            min-width: 220px;
            max-width: 280px;
            overflow-wrap: anywhere;
        }

        .kpi-monthly-file-link {
            color: #1d4ed8;
            text-decoration: underline;
            font-size: 12px;
            display: block;
            width: 100%;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 900px) {
            .kpi-monthly-modal {
                padding: 8px;
            }

            .kpi-monthly-panel {
                width: calc(100vw - 16px);
                max-height: 94vh;
            }

            .kpi-monthly-table {
                min-width: 1120px;
            }
        }

        .kpi-monthly-actions {
            display: flex;
            justify-content: flex-end;
            padding: 0 16px 16px;
        }

        .kpi-monthly-close-btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 7px 14px;
            cursor: pointer;
            font-size: 13px;
        }
    </style>

    <section class="kpi-dept-shell">
        <div class="kpi-dept-head">
            <h2 class="kpi-dept-title">{{ $t['title'] }}</h2>

            <form method="GET" action="{{ route('kpi.summary.dept', ['lang' => $lang]) }}" class="kpi-cycle-filter">
                <input type="hidden" name="lang" value="{{ $lang }}" data-lang-sync="1">

                <label for="cycle_id"><b>{{ $t['cycle'] }}</b></label>
                <select id="cycle_id" name="cycle_id" onchange="this.form.submit()">
                    @foreach($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected($selectedCycle && (int) $selectedCycle->id === (int) $cycle->id)>
                            {{ $cycle->name ?: ('Cycle #' . $cycle->id) }}
                        </option>
                    @endforeach
                </select>

                @if(count($departmentOptions) > 1)
                    <label for="dept"><b>{{ $t['department'] }}</b></label>
                    <select id="dept" name="dept" onchange="this.form.submit()">
                        <option value="">{{ $t['allDepartments'] ?? 'All Assigned Departments' }}</option>
                        @foreach($departmentOptions as $departmentCode)
                            <option value="{{ $departmentCode }}" @selected($selectedDepartment === $departmentCode)>
                                {{ $departmentCode }}
                            </option>
                        @endforeach
                    </select>
                @elseif(count($departmentOptions) === 1)
                    <input type="hidden" name="dept" value="{{ $departmentOptions[0] }}">
                @endif

                @if(count($goalTopicOptions) > 0 || $selectedGoalTopicId > 0)
                    <label for="goal_topic_id"><b>{{ $t['goalTopicFilter'] }}</b></label>
                    <select id="goal_topic_id" name="goal_topic_id" class="kpi-goal-topic-select" onchange="this.form.submit()">
                        <option value="">{{ $t['allGoalTopics'] }}</option>
                        @foreach($goalTopicOptions as $option)
                            <option value="{{ $option['id'] }}" @selected($selectedGoalTopicId === (int) $option['id'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="{{ $t['searchPlaceholder'] }}"
                >
                <button type="submit" class="kpi-search-btn">{{ $t['searchBtn'] }}</button>
                <div class="kpi-grab-controls" role="group" aria-label="{{ $t['grabLabel'] }}">
                    <button type="button" class="kpi-grab-btn" data-role="kpi-grab-toggle" aria-pressed="false">{{ $t['grabTable'] }}</button>
                </div>
            </form>

            <div class="kpi-dept-meta">
                <span><b>{{ $t['department'] }}:</b> {{ $departmentLabel }}</span>
                <span>
                    <b>{{ $t['period'] }}:</b>
                    @if($selectedCycle)
                        {{ $selectedCycle->start_date ? $selectedCycle->start_date->format('d/m/Y') : '-' }}
                        -
                        {{ $selectedCycle->end_date ? $selectedCycle->end_date->format('d/m/Y') : '-' }}
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>

        <div class="kpi-table-wrap">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">{{ $t['no'] }}</th>
                        <th style="width: 5%;">{{ $t['employeeCount'] }}</th>
                        <th style="width: 6%;">{{ $t['departmentCol'] }}</th>
                        <th style="width: 5%;">{{ $t['selectedDepartmentCol'] }}</th>
                        <th style="width: 7%;">{{ $t['codeCol'] }}</th>
                        <th style="width: 13%;">{{ $t['nameCol'] }}</th>
                        <th style="width: 9%;">{{ $t['positionCol'] }}</th>
                        <th style="width: 13%;">{{ $t['goalTopic'] }}</th>
                        <th style="width: 13%;">{{ $t['objective'] }}</th>
                        <th style="width: 13%;">{{ $t['detail'] }}</th>
                        <th style="width: 9%;">{{ $t['target'] }}</th>
                        <th style="width: 6%;">{{ $t['approvedMonthlyCol'] }}</th>
                        <th style="width: 6%;">{{ $t['result'] }}</th>
                        <th style="width: 6%;">{{ $t['avgResult'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rowsByDepartment as $departmentRows)
                        @php
                            $isFirstDepartment = $loop->first;
                            $departmentRows = collect($departmentRows)->values();
                            $departmentRowspan = $departmentRows->sum(function ($row) {
                                return max(count($row['entries'] ?? []), 1);
                            });
                        @endphp

                        @foreach($departmentRows as $userIndex => $row)
                            @php
                                $entries = $row['entries'] ?? [];
                                if ($entries === []) {
                                    $entries = [[
                                            'goal_topic' => '-',
                                            'objective' => '-',
                                            'detail' => '-',
                                            'target' => '-',
                                            'result' => '-',
                                    ]];
                                }
                                $entryCount = max(count($entries), 1);
                                $emp = $row['employee_parts'] ?? [];
                            @endphp

                            @foreach($entries as $entryIndex => $entry)
                                <tr @class([
                                    'kpi-entry-divider' => $entryIndex < ($entryCount - 1),
                                    'kpi-dept-start' => !$isFirstDepartment && $userIndex === 0 && $entryIndex === 0,
                                ])>
                                    @if($entryIndex === 0)
                                        <td class="text-center" rowspan="{{ $entryCount }}">{{ $row['no'] ?? '-' }}</td>
                                    @endif

                                    @if($userIndex === 0 && $entryIndex === 0)
                                        <td class="text-center" rowspan="{{ $departmentRowspan }}">{{ $row['employee_count'] ?? '-' }}</td>
                                    @endif

                                    @if($entryIndex === 0)
                                        @php
                                            $dept         = $row['department'] ?? '-';
                                            $selectedDept = $row['selected_department'] ?? '-';
                                            $isSameDept   = ($selectedDept === '-' || $selectedDept === '' || $selectedDept === $dept);
                                        @endphp
                                        @if($isSameDept)
                                            <td class="kpi-col-department text-center" colspan="2" rowspan="{{ $entryCount }}" style="font-weight:600;">{{ $dept }}</td>
                                        @else
                                            <td class="kpi-col-department text-center" rowspan="{{ $entryCount }}" style="font-weight:600;">{{ $dept }}</td>
                                            <td class="kpi-col-department text-center" rowspan="{{ $entryCount }}" style="color:#4b5563;">{{ $selectedDept }}</td>
                                        @endif
                                        <td class="kpi-col-code" rowspan="{{ $entryCount }}">{{ $emp['code'] ?? '-' }}</td>
                                        <td class="kpi-col-name" rowspan="{{ $entryCount }}">{{ $emp['name'] ?? '-' }}</td>
                                        <td class="kpi-col-position" rowspan="{{ $entryCount }}">{{ $emp['position'] ?? '-' }}</td>
                                    @endif

                                    <td class="kpi-entry-cell">
                                        @php
                                            $stripNum = fn(string $s): string => preg_replace('/^\d+\.\s*/', '', $s);
                                            $gtL1 = $stripNum(trim((string) ($entry['goal_topic_l1'] ?? '')));
                                            $gtL2 = $stripNum(trim((string) ($entry['goal_topic_l2'] ?? '')));
                                            $gtL3 = $stripNum(trim((string) ($entry['goal_topic_l3'] ?? ($entry['goal_topic'] ?? ''))));
                                        @endphp
                                        @if($gtL1 !== '')
                                            <div class="kpi-goal-topic-l1" title="{{ $gtL1 }}">{{ $gtL1 }}</div>
                                        @endif
                                        @if($gtL2 !== '')
                                            <div class="kpi-goal-topic-l2" title="{{ $gtL2 }}">- {{ $gtL2 }}</div>
                                        @endif
                                        @if($gtL3 !== '')
                                            <div class="kpi-goal-topic-l3" title="{{ $gtL3 }}">{{ $gtL3 }}</div>
                                        @elseif($gtL1 === '' && $gtL2 === '')
                                            <div>-</div>
                                        @endif
                                    </td>
                                    <td class="kpi-entry-cell">{{ $entry['objective'] ?? '-' }}</td>
                                    <td class="kpi-entry-cell">{{ $entry['detail'] ?? '-' }}</td>
                                    <td class="kpi-entry-cell kpi-col-target">{{ $entry['target'] ?? '-' }}</td>
                                    {{-- Approved monthly KPI column --}}
                                    <td class="kpi-entry-cell kpi-col-score-action">
                                        @if(!empty($entry['root_id']) && !empty($entry['has_approved']))
                                            <button
                                                type="button"
                                                class="kpi-view-btn"
                                                data-role="open-monthly-summary"
                                                data-root-id="{{ (int) $entry['root_id'] }}"
                                            >
                                                {{ $t['view'] }}
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="kpi-entry-cell kpi-col-result">{{ $entry['result'] ?? '-' }}</td>
                                    @if($entryIndex === 0)
                                        <td class="kpi-entry-cell kpi-col-avg-result" rowspan="{{ $entryCount }}">{{ $row['avg_result'] ?? '-' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="14" class="kpi-empty">{{ $t['empty'] }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div id="kpi-monthly-modal" class="kpi-monthly-modal" aria-hidden="true">
        <div class="kpi-monthly-panel" role="dialog" aria-modal="true" aria-labelledby="kpi-monthly-title">
            <div class="kpi-monthly-head">
                <h3 id="kpi-monthly-title" class="kpi-monthly-title">{{ $t['monthlyTitle'] }}</h3>
                <button type="button" class="kpi-monthly-close" data-role="close-monthly-modal" aria-label="{{ $t['close'] }}">
                    &times;
                </button>
            </div>

            <div class="kpi-monthly-meta">
                <p><b>{{ $t['monthlyEmployee'] }}:</b> <span data-role="monthly-employee">-</span></p>
                <p><b>{{ $t['monthlyObjective'] }}:</b> <span data-role="monthly-objective">-</span></p>
                <p><b>{{ $t['monthlyDetail'] }}:</b> <span data-role="monthly-detail">-</span></p>
            </div>

            <div class="kpi-monthly-table-wrap">
                <table class="kpi-monthly-table">
                    <thead>
                        <tr>
                            <th style="width:180px;">{{ $t['monthlyMonth'] }}</th>
                            <th style="width:120px;">{{ $t['monthlyScore'] }}</th>
                            <th style="width:220px;">{{ $t['monthlyFiles'] }}</th>
                            <th style="width:220px;">{{ $t['monthlyActionPlan'] ?? 'Action Plan' }}</th>
                            <th>{{ $t['monthlyCriteria'] }}</th>
                            <th style="width:170px;">{{ $t['monthlyResult'] }}</th>
                            <th style="width:160px;">{{ $t['reviewerNameCol'] }}</th>
                        </tr>
                    </thead>
                    <tbody data-role="monthly-body"></tbody>
                </table>
            </div>

            <div class="kpi-monthly-actions">
                <button type="button" class="kpi-monthly-close-btn" data-role="close-monthly-modal">{{ $t['close'] }}</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const tableWrap = document.querySelector('.kpi-table-wrap');
            const grabToggleBtn = document.querySelector('[data-role="kpi-grab-toggle"]');
            const storageKey = 'kpi-summary-dept-drag-enabled';

            if (!tableWrap || !grabToggleBtn) {
                return;
            }

            let isDragMode = false;
            let isDragging = false;
            let startX = 0;
            let startY = 0;
            let startScrollLeft = 0;
            let startScrollTop = 0;

            const setDragMode = (enabled) => {
                isDragMode = !!enabled;
                tableWrap.classList.toggle('is-grab-mode', isDragMode);
                grabToggleBtn.classList.toggle('is-active', isDragMode);
                grabToggleBtn.setAttribute('aria-pressed', isDragMode ? 'true' : 'false');
                window.localStorage.setItem(storageKey, isDragMode ? '1' : '0');
            };

            const stopDragging = () => {
                if (!isDragging) {
                    return;
                }

                isDragging = false;
                tableWrap.classList.remove('is-dragging');
            };

            const savedMode = window.localStorage.getItem(storageKey) === '1';
            setDragMode(savedMode);

            grabToggleBtn.addEventListener('click', () => {
                setDragMode(!isDragMode);
                stopDragging();
            });

            tableWrap.addEventListener('pointerdown', (event) => {
                if (!isDragMode || event.button !== 0) {
                    return;
                }

                if (event.target.closest('button, a, input, select, textarea, label')) {
                    return;
                }

                isDragging = true;
                startX = event.clientX;
                startY = event.clientY;
                startScrollLeft = tableWrap.scrollLeft;
                startScrollTop = tableWrap.scrollTop;
                tableWrap.classList.add('is-dragging');
                tableWrap.setPointerCapture?.(event.pointerId);
                event.preventDefault();
            });

            tableWrap.addEventListener('pointermove', (event) => {
                if (!isDragging) {
                    return;
                }

                tableWrap.scrollLeft = startScrollLeft - (event.clientX - startX);
                tableWrap.scrollTop = startScrollTop - (event.clientY - startY);
            });

            tableWrap.addEventListener('pointerup', (event) => {
                stopDragging();
                if (tableWrap.hasPointerCapture?.(event.pointerId)) {
                    tableWrap.releasePointerCapture(event.pointerId);
                }
            });
            tableWrap.addEventListener('pointercancel', stopDragging);
            tableWrap.addEventListener('pointerleave', stopDragging);
        })();
    </script>

    <script>
        (function () {
            const modalEl = document.getElementById('kpi-monthly-modal');
            if (!modalEl) {
                return;
            }

            // Move modal out of page-wrap stacking context so it always appears above layout.
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            const lang = @json($lang);
            const monthlySummaryUrlTemplate = @json($monthlySummaryUrlTemplate);
            const monthlyBodyEl = modalEl.querySelector('[data-role="monthly-body"]');
            const monthlyEmployeeEl = modalEl.querySelector('[data-role="monthly-employee"]');
            const monthlyObjectiveEl = modalEl.querySelector('[data-role="monthly-objective"]');
            const monthlyDetailEl = modalEl.querySelector('[data-role="monthly-detail"]');
            const monthlyTitleEl = document.getElementById('kpi-monthly-title');

            const texts = {
                loading: @json($t['monthlyLoading']),
                error: @json($t['monthlyError']),
                title: @json($t['monthlyTitle']),
                netAverage: @json($t['monthlyNetAverage']),
            };
            const truncateFileLabel = (value, maxLength = 30) => {
                const text = typeof value === 'string' ? value.trim() : '';
                if (text === '') {
                    return '-';
                }

                if (text.length <= maxLength) {
                    return text;
                }

                return `${text.slice(0, maxLength)}....`;
            };

            const setModalOpen = (open) => {
                modalEl.classList.toggle('show', open);
                modalEl.setAttribute('aria-hidden', open ? 'false' : 'true');
                document.body.style.overflow = open ? 'hidden' : '';
            };

            const renderMessageRow = (text) => {
                if (!monthlyBodyEl) {
                    return;
                }

                monthlyBodyEl.innerHTML = '';
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'kpi-monthly-empty';
                cell.textContent = text;
                row.appendChild(cell);
                monthlyBodyEl.appendChild(row);
            };

            const renderMonths = (months, netAverageScore = '-', netAverageResult = '-') => {
                if (!monthlyBodyEl) {
                    return;
                }

                monthlyBodyEl.innerHTML = '';
                if (!Array.isArray(months) || months.length === 0) {
                    renderMessageRow(texts.error);
                    return;
                }

                months.forEach((month) => {
                    const row = document.createElement('tr');

                    const monthCell = document.createElement('td');
                    monthCell.textContent = month && month.month_label ? String(month.month_label) : '-';
                    row.appendChild(monthCell);

                    const scoreCell = document.createElement('td');
                    scoreCell.className = 'text-center';
                    scoreCell.textContent = month && typeof month.score !== 'undefined' ? String(month.score) : '-';
                    row.appendChild(scoreCell);

                    const filesCell = document.createElement('td');
                    filesCell.className = 'kpi-monthly-files-cell';
                    const files = month && Array.isArray(month.files) ? month.files : [];
                    if (files.length === 0) {
                        filesCell.textContent = '-';
                    } else {
                        const listEl = document.createElement('ul');
                        listEl.className = 'kpi-monthly-file-list';
                        files.forEach((file) => {
                            const url = file && typeof file.url === 'string' ? file.url.trim() : '';
                            if (url === '') {
                                return;
                            }

                            const label = file && typeof file.name === 'string' && file.name.trim() !== ''
                                ? file.name.trim()
                                : '-';

                            const itemEl = document.createElement('li');
                            const linkEl = document.createElement('a');
                            linkEl.className = 'kpi-monthly-file-link';
                            linkEl.href = url;
                            linkEl.target = '_blank';
                            linkEl.rel = 'noopener noreferrer';
                            linkEl.textContent = truncateFileLabel(label);
                            linkEl.title = label;
                            itemEl.appendChild(linkEl);
                            listEl.appendChild(itemEl);
                        });

                        if (listEl.children.length === 0) {
                            filesCell.textContent = '-';
                        } else {
                            filesCell.appendChild(listEl);
                        }
                    }
                    row.appendChild(filesCell);

                    const actionPlanCell = document.createElement('td');
                    actionPlanCell.className = 'kpi-monthly-files-cell';
                    const actionPlanFiles = month && Array.isArray(month.action_plan_files) ? month.action_plan_files : [];
                    if (actionPlanFiles.length === 0) {
                        actionPlanCell.textContent = '-';
                    } else {
                        const actionPlanListEl = document.createElement('ul');
                        actionPlanListEl.className = 'kpi-monthly-file-list';
                        actionPlanFiles.forEach((file) => {
                            const url = file && typeof file.url === 'string' ? file.url.trim() : '';
                            if (url === '') {
                                return;
                            }

                            const label = file && typeof file.name === 'string' && file.name.trim() !== ''
                                ? file.name.trim()
                                : '-';

                            const itemEl = document.createElement('li');
                            const linkEl = document.createElement('a');
                            linkEl.className = 'kpi-monthly-file-link';
                            linkEl.href = url;
                            linkEl.target = '_blank';
                            linkEl.rel = 'noopener noreferrer';
                            linkEl.textContent = truncateFileLabel(label);
                            linkEl.title = label;
                            itemEl.appendChild(linkEl);
                            actionPlanListEl.appendChild(itemEl);
                        });

                        if (actionPlanListEl.children.length === 0) {
                            actionPlanCell.textContent = '-';
                        } else {
                            actionPlanCell.appendChild(actionPlanListEl);
                        }
                    }
                    row.appendChild(actionPlanCell);

                    const criteriaCell = document.createElement('td');
                    criteriaCell.textContent = month && typeof month.criteria !== 'undefined' ? String(month.criteria) : '-';
                    row.appendChild(criteriaCell);

                    const resultCell = document.createElement('td');
                    resultCell.className = `text-center ${month && month.result_class ? String(month.result_class) : 'kpi-summary-result-pending'}`;
                    resultCell.textContent = month && typeof month.result !== 'undefined' ? String(month.result) : '-';
                    row.appendChild(resultCell);

                    const reviewerCell = document.createElement('td');
                    const rn = month && typeof month.reviewer_name === 'string' ? month.reviewer_name.trim() : '';
                    reviewerCell.textContent = rn !== '' ? rn : '';
                    row.appendChild(reviewerCell);

                    monthlyBodyEl.appendChild(row);
                });

                const netAverageRow = document.createElement('tr');
                netAverageRow.className = 'kpi-monthly-net-average';

                const netAverageLabelCell = document.createElement('td');
                netAverageLabelCell.textContent = texts.netAverage;
                netAverageRow.appendChild(netAverageLabelCell);

                const netAverageScoreCell = document.createElement('td');
                netAverageScoreCell.className = 'text-center';
                netAverageScoreCell.textContent = typeof netAverageScore !== 'undefined' ? String(netAverageScore) : '-';
                netAverageRow.appendChild(netAverageScoreCell);

                const netAverageFilesCell = document.createElement('td');
                netAverageFilesCell.textContent = '';
                netAverageRow.appendChild(netAverageFilesCell);

                const netAverageActionPlanCell = document.createElement('td');
                netAverageActionPlanCell.textContent = '';
                netAverageRow.appendChild(netAverageActionPlanCell);

                const netAverageCriteriaCell = document.createElement('td');
                netAverageCriteriaCell.textContent = '';
                netAverageRow.appendChild(netAverageCriteriaCell);

                const netAverageResultCell = document.createElement('td');
                netAverageResultCell.className = 'text-center';
                netAverageResultCell.textContent = typeof netAverageResult !== 'undefined' ? String(netAverageResult) : '-';
                netAverageRow.appendChild(netAverageResultCell);

                const netAverageReviewerCell = document.createElement('td');
                netAverageReviewerCell.textContent = '';
                netAverageRow.appendChild(netAverageReviewerCell);

                monthlyBodyEl.appendChild(netAverageRow);
            };

            const openMonthlyModal = async (rootId) => {
                const normalizedRootId = Number(rootId || 0);
                if (!Number.isFinite(normalizedRootId) || normalizedRootId < 1) {
                    return;
                }

                if (monthlyEmployeeEl) {
                    monthlyEmployeeEl.textContent = '-';
                }
                if (monthlyObjectiveEl) {
                    monthlyObjectiveEl.textContent = '-';
                }
                if (monthlyDetailEl) {
                    monthlyDetailEl.textContent = '-';
                }
                if (monthlyTitleEl) {
                    monthlyTitleEl.textContent = texts.title;
                }

                setModalOpen(true);
                renderMessageRow(texts.loading);

                try {
                    const endpoint = monthlySummaryUrlTemplate.replace('__ROOT__', String(normalizedRootId));
                    const response = await fetch(`${endpoint}?lang=${encodeURIComponent(lang)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(texts.error);
                    }

                    const payload = await response.json();
                    if (monthlyTitleEl) {
                        monthlyTitleEl.textContent = payload && payload.title ? String(payload.title) : texts.title;
                    }
                    if (monthlyEmployeeEl) {
                        monthlyEmployeeEl.textContent = payload && payload.employee ? String(payload.employee) : '-';
                    }
                    if (monthlyObjectiveEl) {
                        monthlyObjectiveEl.textContent = payload && payload.objective ? String(payload.objective) : '-';
                    }
                    if (monthlyDetailEl) {
                        monthlyDetailEl.textContent = payload && payload.detail ? String(payload.detail) : '-';
                    }
                    renderMonths(
                        payload ? payload.months : [],
                        payload && typeof payload.net_average_score !== 'undefined' ? payload.net_average_score : '-',
                        payload && typeof payload.net_average_result !== 'undefined' ? payload.net_average_result : '-'
                    );
                } catch (error) {
                    renderMessageRow(texts.error);
                }
            };

            document.querySelectorAll('[data-role="open-monthly-summary"]').forEach((buttonEl) => {
                buttonEl.addEventListener('click', () => {
                    openMonthlyModal(buttonEl.getAttribute('data-root-id'));
                });
            });

            modalEl.querySelectorAll('[data-role="close-monthly-modal"]').forEach((buttonEl) => {
                buttonEl.addEventListener('click', () => {
                    setModalOpen(false);
                });
            });

            modalEl.addEventListener('click', (event) => {
                if (event.target === modalEl) {
                    setModalOpen(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modalEl.classList.contains('show')) {
                    setModalOpen(false);
                }
            });

            document.addEventListener('app:lang-changed', (event) => {
                const switchedLang = event && event.detail && event.detail.lang === 'th' ? 'th' : 'en';
                if (switchedLang === lang) {
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('lang', switchedLang);
                window.location.replace(url.toString());
            });
        })();
    </script>
@endsection


