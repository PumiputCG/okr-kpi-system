@extends('layout')

@section('title', 'Overall Score Summary')
@section('page_heading_key', 'menuOkrDept')
@section('page_heading', 'Overall Score Summary')
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
        $cycles = $cycles ?? collect();
        $selectedCycle = $selectedCycle ?? null;
        $rows = $rows ?? [];
        $rowsByDepartment = collect($rows)->groupBy(fn ($row) => $row['group_department'] ?? ($row['department'] ?? ''));

        $t = $lang === 'th'
            ? [
                'title' => 'สรุปภาพรวมคะแนน',
                'cycle' => 'รอบ',
                'searchPlaceholder' => 'ค้นหาด้วยรหัสพนักงานหรือชื่อ',
                'searchBtn' => 'ค้นหา',
                'department' => 'แผนก',
                'allDepartments' => 'ทุกแผนกที่ได้รับสิทธิ์',
                'period' => 'ช่วงเวลา',
                'no' => 'No.',
                'employeeCount' => 'จำนวนพนักงาน',
                'codeCol' => 'รหัส',
                'nameCol' => 'ชื่อ-สกุล',
                'positionCol' => 'ตำแหน่ง',
                'departmentCol' => 'แผนก',
                'selectedDepartmentCol' => 'แผนกที่เลือก',
                'kpiResult' => 'ผลลัพธ์ KPIs เฉลี่ยทั้งหมดรายบุคคล',
                'okrResult' => 'ค่าเฉลี่ยผลลัพธ์ของแผนก (OKR)',
                'grabTable' => 'ลาก',
                'grabLabel' => 'โหมดจับเลื่อนตาราง',
                'empty' => 'ยังไม่พบข้อมูลพนักงานตามเงื่อนไขในรอบที่เลือก',
            ]
            : [
                'title' => 'Overall Score Summary',
                'cycle' => 'Cycle',
                'searchPlaceholder' => 'Search by employee code or name',
                'searchBtn' => 'Search',
                'department' => 'Department',
                'allDepartments' => 'All Assigned Departments',
                'period' => 'Period',
                'no' => 'No.',
                'employeeCount' => 'Employee Count',
                'codeCol' => 'Code',
                'nameCol' => 'Full Name',
                'positionCol' => 'Position',
                'departmentCol' => 'Department',
                'selectedDepartmentCol' => 'Selected Department',
                'kpiResult' => 'Average Individual KPIs Result',
                'okrResult' => 'Average Department Result (OKR)',
                'grabTable' => 'Drag',
                'grabLabel' => 'Table drag mode',
                'empty' => 'No employee data found for the selected cycle.',
            ];
    @endphp

    <style>
        .okr-dept-shell {
            background: #ffffff;
            padding: 20px 22px;
            min-height: 460px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .okr-dept-head {
            display: grid;
            gap: 10px;
            margin-bottom: 16px;
        }

        .okr-dept-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        .okr-cycle-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .okr-cycle-filter select,
        .okr-cycle-filter input[type="text"] {
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 7px 10px;
            font-size: 13px;
        }

        .okr-cycle-filter select {
            min-width: 240px;
        }

        .okr-cycle-filter input[type="text"] {
            min-width: 290px;
        }

        .okr-search-btn {
            border: 0;
            background: #111827;
            color: #ffffff;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        .okr-search-btn:hover {
            background: #1f2937;
        }

        .okr-grab-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 2px;
        }

        .okr-grab-btn {
            height: 30px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            line-height: 1.2;
            cursor: pointer;
            padding: 0 10px;
        }

        .okr-grab-btn:hover {
            background: #f3f4f6;
        }

        .okr-grab-btn.is-active {
            background: #fde7f3;
            border-color: #e83e8c;
            color: #111827;
        }

        .okr-dept-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
            font-size: 13px;
            color: #374151;
        }

        .okr-dept-meta b {
            color: #111827;
        }

        .okr-table-wrap {
            overflow-x: auto;
            border: 1px solid #d1d5db;
        }

        .okr-table-wrap.is-grab-mode {
            cursor: grab;
            user-select: none;
        }

        .okr-table-wrap.is-grab-mode .okr-table {
            cursor: inherit;
        }

        .okr-table-wrap.is-grab-mode.is-dragging {
            cursor: grabbing;
        }

        .okr-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1180px;
            table-layout: auto;
            font-size: 13px;
        }

        .okr-table th,
        .okr-table td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            background: #ffffff;
            vertical-align: middle;
            line-height: 1.45;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .okr-table th {
            background: #f3f4f6;
            font-weight: 600;
            text-align: center;
            white-space: normal;
        }

        .okr-table th.okr-col-employee-count {
            white-space: normal;
            line-height: 1.25;
        }

        .okr-table td.text-center {
            text-align: center;
            white-space: normal;
        }

        .okr-table td.okr-merged {
            text-align: center;
            vertical-align: middle;
            white-space: normal;
            font-weight: 600;
        }

        .okr-table tr.okr-dept-start > td {
            border-top: 3px solid #111111 !important;
        }

        .okr-col-code,
        .okr-col-position {
            text-align: center;
            vertical-align: middle;
        }

        .okr-col-department,
        .okr-col-position {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .okr-col-name {
            text-align: center;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .okr-col-code {
            font-variant-numeric: tabular-nums;
            font-family: Consolas, 'Courier New', monospace;
        }

        .okr-empty {
            text-align: center;
            color: #6b7280;
            padding: 16px;
        }
    </style>

    <section class="okr-dept-shell">
        <div class="okr-dept-head">
            <h2 class="okr-dept-title">{{ $t['title'] }}</h2>

            <form method="GET" action="{{ route('okr.summary.dept', ['lang' => $lang]) }}" class="okr-cycle-filter">
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

                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="{{ $t['searchPlaceholder'] }}"
                >
                <button type="submit" class="okr-search-btn">{{ $t['searchBtn'] }}</button>
                <div class="okr-grab-controls" role="group" aria-label="{{ $t['grabLabel'] }}">
                    <button type="button" class="okr-grab-btn" data-role="okr-grab-toggle" aria-pressed="false">{{ $t['grabTable'] }}</button>
                </div>
            </form>

            <div class="okr-dept-meta">
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

        <div class="okr-table-wrap">
            <table class="okr-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">{{ $t['no'] }}</th>
                        <th class="okr-col-employee-count" style="width: 9%;">{{ $t['employeeCount'] }}</th>
                        <th style="width: 10%;">{{ $t['departmentCol'] }}</th>
                        <th style="width: 10%;">{{ $t['selectedDepartmentCol'] }}</th>
                        <th style="width: 8%;">{{ $t['codeCol'] }}</th>
                        <th style="width: 14%;">{{ $t['nameCol'] }}</th>
                        <th style="width: 13%;">{{ $t['positionCol'] }}</th>
                        <th style="width: 21%;">{{ $t['kpiResult'] }}</th>
                        <th style="width: 22%;">{{ $t['okrResult'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rowsByDepartment as $departmentRows)
                        @php
                            $isFirstDepartment = $loop->first;
                            $departmentRows = collect($departmentRows)->values();
                            $rowspan = max($departmentRows->count(), 1);
                        @endphp
                        @foreach($departmentRows as $index => $row)
                            <tr @class(['okr-dept-start' => !$isFirstDepartment && $index === 0])>
                                <td class="text-center">{{ $row['no'] ?? '-' }}</td>
                                @if($index === 0)
                                    <td class="okr-merged" rowspan="{{ $rowspan }}">{{ $row['employee_count'] ?? '-' }}</td>
                                @endif
                                @php
                                    $emp          = $row['employee_parts'] ?? [];
                                    $dept         = $row['department'] ?? '-';
                                    $selectedDept = $row['selected_department'] ?? '-';
                                    $isSameDept   = ($selectedDept === '-' || $selectedDept === '—' || $selectedDept === '' || $selectedDept === $dept);
                                @endphp
                                @if($isSameDept)
                                    <td class="okr-col-department" colspan="2" style="font-weight:600;text-align:center;">{{ $dept }}</td>
                                @else
                                    <td class="okr-col-department" style="font-weight:600;text-align:center;">{{ $dept }}</td>
                                    <td class="okr-col-department" style="text-align:center;color:#4b5563;">{{ $selectedDept }}</td>
                                @endif
                                <td class="okr-col-code">{{ $emp['code'] ?? '-' }}</td>
                                <td class="okr-col-name">{{ $emp['name'] ?? '-' }}</td>
                                <td class="okr-col-position">{{ $emp['position'] ?? '-' }}</td>
                                <td class="text-center">{{ $row['kpi_result'] ?? '-' }}</td>
                                @if($index === 0)
                                    <td class="okr-merged" rowspan="{{ $rowspan }}">{{ $row['okr_result'] ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="okr-empty">{{ $t['empty'] }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (function () {
            const tableWrap = document.querySelector('.okr-table-wrap');
            const grabToggleBtn = document.querySelector('[data-role="okr-grab-toggle"]');
            const storageKey = 'okr-summary-dept-drag-enabled';

            if (tableWrap && grabToggleBtn) {
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
            }

            const serverLang = @json($lang === 'th' ? 'th' : 'en');
            document.addEventListener('app:lang-changed', (event) => {
                const switchedLang = event && event.detail && event.detail.lang === 'th' ? 'th' : 'en';
                if (switchedLang === serverLang) {
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('lang', switchedLang);
                window.location.replace(url.toString());
            });
        })();
    </script>
@endsection

