@extends('layout')

@section('title', 'Set Cycle')
@section('page_heading_key', 'menuAdminSetCycle')
@section('page_heading', 'Set Cycle')

@section('content')
    @php
        $lang = request('lang') === 'th' ? 'th' : 'en';
        $t = [
            'title' => $lang === 'th' ? 'กำหนดรอบ' : 'Set Cycle',
            'subtitle' => $lang === 'th' ? 'จัดการรอบของระบบ OKR-KPI โดยใช้รูปแบบสร้างรอบ เปิดใช้งาน และปิดรอบ' : 'Manage OKR-KPI cycles with create, activate, and close flow.',
            'activeBox' => $lang === 'th' ? 'รอบที่เปิดใช้งานอยู่ตอนนี้' : 'Current active cycle',
            'noActive' => $lang === 'th' ? 'ยังไม่มีรอบที่เปิดใช้งาน' : 'No active cycle',
            'create' => $lang === 'th' ? 'สร้างรอบใหม่' : 'Create new cycle',
            'list' => $lang === 'th' ? 'รายการรอบทั้งหมด' : 'All cycles',
            'name' => $lang === 'th' ? 'ชื่อรอบ' : 'Cycle name',
            'code' => $lang === 'th' ? 'รหัสรอบ' : 'Code',
            'start' => $lang === 'th' ? 'วันเริ่ม' : 'Start date',
            'end' => $lang === 'th' ? 'วันสิ้นสุด' : 'End date',
            'note' => $lang === 'th' ? 'หมายเหตุ' : 'Note',
            'period' => $lang === 'th' ? 'ช่วงเวลา' : 'Period',
            'monthSchedule' => $lang === 'th' ? 'กำหนดรายเดือน' : 'Monthly schedule',
            'status' => $lang === 'th' ? 'สถานะ' : 'Status',
            'action' => $lang === 'th' ? 'จัดการ' : 'Manage',
            'open' => $lang === 'th' ? 'เปิด' : 'Open',
            'closed' => $lang === 'th' ? 'ปิดรอบ' : 'Closed',
            'legacyOpen' => $lang === 'th' ? 'เปิด (ไม่ได้ใช้งาน)' : 'Open (inactive)',
            'activate' => $lang === 'th' ? 'เปิดใช้งาน' : 'Activate',
            'close' => $lang === 'th' ? 'ปิดรอบ' : 'Close',
            'delete' => $lang === 'th' ? 'ลบ' : 'Delete',
            'save' => $lang === 'th' ? 'สร้างรอบ' : 'Create cycle',
            'reset' => $lang === 'th' ? 'ล้างค่า' : 'Reset',
            'empty' => $lang === 'th' ? 'ยังไม่มีรอบ' : 'No cycles yet.',
            'confirmClose' => $lang === 'th' ? 'ยืนยันปิดรอบนี้?' : 'Close this cycle?',
            'confirmActivate' => $lang === 'th' ? 'ยืนยันเปิดใช้งานรอบนี้?' : 'Activate this cycle?',
            'confirmDelete' => $lang === 'th' ? 'ยืนยันลบรายการนี้?' : 'Delete this row?',
            'done' => $lang === 'th' ? 'สำเร็จ' : 'Success',
            'closeFailed' => $lang === 'th' ? 'ปิดรอบไม่สำเร็จ' : 'Close failed',
            'activateFailed' => $lang === 'th' ? 'เปิดใช้งานรอบไม่สำเร็จ' : 'Activate failed',
            'createInTable' => $lang === 'th' ? 'เพิ่มเข้าตารางแล้ว กด "เปิดใช้งาน" เพื่อบันทึก' : 'Added to table. Click "Activate" to save.',
            'needName' => $lang === 'th' ? 'กรุณากรอกชื่อรอบ' : 'Please enter cycle name.',
            'dateInvalid' => $lang === 'th' ? 'วันสิ้นสุดต้องไม่ก่อนวันเริ่ม' : 'End date must be after start date.',
            'dateFormatInvalid' => $lang === 'th' ? 'กรุณากรอกวันที่รูปแบบ dd/mm/yyyy' : 'Please enter date in dd/mm/yyyy format.',
            'saving' => $lang === 'th' ? 'กำลังบันทึก...' : 'Saving...',
            'routeMissing' => $lang === 'th' ? 'ยังไม่ได้ตั้งค่า route ของ cycle' : 'Cycle routes are not configured yet.',
            'activateToManageMonths' => $lang === 'th' ? 'กรุณาเปิดรอบก่อน' : 'Please activate cycle first.',
        ];

        $storeUrl = \Illuminate\Support\Facades\Route::has('admin.cycles.store') ? route('admin.cycles.store') : '#';
        $saveMonthsUrl = \Illuminate\Support\Facades\Route::has('admin.cycles.months.save') ? route('admin.cycles.months.save') : '#';
        $monthLabels = [
            1 => $lang === 'th' ? 'มกราคม' : 'January',
            2 => $lang === 'th' ? 'กุมภาพันธ์' : 'February',
            3 => $lang === 'th' ? 'มีนาคม' : 'March',
            4 => $lang === 'th' ? 'เมษายน' : 'April',
            5 => $lang === 'th' ? 'พฤษภาคม' : 'May',
            6 => $lang === 'th' ? 'มิถุนายน' : 'June',
            7 => $lang === 'th' ? 'กรกฎาคม' : 'July',
            8 => $lang === 'th' ? 'สิงหาคม' : 'August',
            9 => $lang === 'th' ? 'กันยายน' : 'September',
            10 => $lang === 'th' ? 'ตุลาคม' : 'October',
            11 => $lang === 'th' ? 'พฤศจิกายน' : 'November',
            12 => $lang === 'th' ? 'ธันวาคม' : 'December',
        ];
        $monthCycleTitle = $lang === 'th' ? 'กำหนดรอบรายเดือน (เปิดฟอร์ม KPI)' : 'Monthly KPI Open Schedule';
        $monthSaveLabel = 'Save';
        $monthNoCycleLabel = $lang === 'th' ? 'ยังไม่มีรอบปี กรุณาสร้างรอบก่อนกำหนดรายเดือน' : 'No cycle yet. Create a cycle first.';
        $monthNoteLabel = $lang === 'th'
            ? 'กำหนดวันเปิดฟอร์ม KPI แยกตามเดือน (มกราคม - ธันวาคม)'
            : 'Set KPI form open date for each month (January - December).';
        $formatDateInput = static function ($value): string {
            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                return $raw;
            }

            try {
                return \Illuminate\Support\Carbon::parse($raw)->format('d/m/Y');
            } catch (\Throwable $e) {
                return '';
            }
        };
    @endphp

    <style>
        .cycle-shell {
            background: #ffffff;
            padding: 20px 22px;
            min-height: 560px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .cycle-head {
            margin-bottom: 16px;
        }

        .cycle-head h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111111;
        }

        .cycle-head p {
            margin: 8px 0 0;
            color: #4b5563;
            font-size: 13px;
        }

        .cycle-alert {
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .cycle-alert.success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }

        .cycle-alert.danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .cycle-alert.info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .cycle-grid {
            display: grid;
            gap: 14px;
            min-width: 0;
        }

        .cycle-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 14px;
            min-width: 0;
        }

        .cycle-card-title {
            margin: 0 0 12px;
            font-size: 17px;
            font-weight: 700;
            color: #111111;
        }

        .cycle-active-box {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 14px;
            margin-bottom: 14px;
        }

        .cycle-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .cycle-meta-item {
            border: 1px solid #eceff3;
            background: #fafbfc;
            padding: 10px 12px;
        }

        .cycle-meta-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cycle-meta-value {
            font-size: 13px;
            color: #111111;
            font-weight: 700;
            word-break: break-word;
        }

        .cycle-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .cycle-field {
            display: grid;
            gap: 6px;
        }

        .cycle-field.col-12 {
            grid-column: span 12;
        }

        .cycle-field.col-6 {
            grid-column: span 6;
        }

        .cycle-field.col-3 {
            grid-column: span 3;
        }

        .cycle-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .cycle-input,
        .cycle-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 13px;
            color: #111111;
            outline: none;
        }

        .cycle-input:focus,
        .cycle-textarea:focus {
            border-color: #e83e8c;
        }

        .cycle-textarea {
            min-height: 90px;
            resize: vertical;
        }

        .cycle-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cycle-form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .cycle-btn {
            border: 0;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, opacity 0.2s ease;
        }

        .cycle-btn.primary {
            background: #e83e8c;
            color: #ffffff;
        }

        .cycle-btn.primary:hover {
            background: #111111;
        }

        .cycle-btn.secondary {
            background: #111111;
            color: #ffffff;
        }

        .cycle-btn.secondary:hover {
            background: #e83e8c;
        }

        .cycle-btn.outline-primary {
            background: #ffffff;
            color: #e83e8c;
            border: 1px solid #e83e8c;
        }

        .cycle-btn.outline-primary:hover {
            background: #e83e8c;
            color: #ffffff;
        }

        .cycle-btn.outline-danger {
            background: #ffffff;
            color: #dc2626;
            border: 1px solid #dc2626;
        }

        .cycle-btn.outline-danger:hover {
            background: #dc2626;
            color: #ffffff;
        }

        .cycle-btn.outline-dark {
            background: #ffffff;
            color: #111111;
            border: 1px solid #111111;
        }

        .cycle-btn.outline-dark:hover {
            background: #111111;
            color: #ffffff;
        }

        .cycle-table-wrap {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            border: 1px solid #f3f4f6;
        }

        .cycle-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1240px;
        }

        .cycle-table th,
        .cycle-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 10px;
            font-size: 12px;
            vertical-align: middle;
            text-align: center;
        }

        .cycle-table th {
            background: #f9fafb;
            color: #374151;
            font-weight: 700;
            white-space: nowrap;
        }

        .cycle-table td.cycle-note-cell {
            text-align: left;
        }

        .cycle-month-list {
            display: grid;
            gap: 4px;
        }

        .cycle-month-line {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cycle-btn.mini {
            padding: 6px 8px;
            font-size: 11px;
            text-align: center;
        }

        .cycle-badge {
            display: inline-block;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 700;
        }

        .cycle-badge.open {
            background: #dcfce7;
            color: #166534;
        }

        .cycle-badge.closed {
            background: #f3f4f6;
            color: #374151;
        }

        .cycle-badge.soft {
            background: #fce7f3;
            color: #be185d;
        }

        .cycle-pagination {
            margin-top: 12px;
        }

        .cycle-month-select {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .cycle-month-select .cycle-field {
            min-width: 260px;
            flex: 1 1 320px;
        }

        .cycle-month-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .cycle-help {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 12px;
        }

        .d-none {
            display: none !important;
        }

        @media (max-width: 1150px) {
            .cycle-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .cycle-shell {
                padding: 16px;
            }

            .cycle-form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .cycle-field.col-12,
            .cycle-field.col-6,
            .cycle-field.col-3 {
                grid-column: span 1;
            }

            .cycle-meta-grid {
                grid-template-columns: 1fr;
            }

            .cycle-month-grid {
                grid-template-columns: 1fr;
            }

            .cycle-form-actions {
                justify-content: stretch;
            }

            .cycle-form-actions .cycle-btn {
                width: 100%;
            }
        }
    </style>

    <section class="cycle-shell">
        <div id="jsAlert" class="cycle-alert d-none"></div>

        @if(session('success'))
            <div class="cycle-alert success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="cycle-alert danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="cycle-alert danger">
                {{ $lang === 'th' ? 'กรุณาตรวจสอบข้อมูลที่กรอก' : 'Please check your inputs.' }}
                <div style="margin-top:8px; font-weight:500;">
                    @foreach($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="cycle-head">
            <h2>{{ $t['title'] }}</h2>
            <p>{{ $t['subtitle'] }}</p>
        </div>

        <div class="cycle-active-box">
            <h3 class="cycle-card-title" style="margin-bottom:12px;">{{ $t['activeBox'] }}</h3>

            @if(!empty($activeCycle))
                <div class="cycle-meta-grid">
                    <div class="cycle-meta-item">
                        <div class="cycle-meta-label">{{ $t['name'] }}</div>
                        <div class="cycle-meta-value">{{ $activeCycle->name ?: '-' }}</div>
                    </div>

                    <div class="cycle-meta-item">
                        <div class="cycle-meta-label">{{ $t['code'] }}</div>
                        <div class="cycle-meta-value">{{ $activeCycle->code ?: '-' }}</div>
                    </div>

                    <div class="cycle-meta-item">
                        <div class="cycle-meta-label">{{ $t['period'] }}</div>
                        <div class="cycle-meta-value">
                            {{ $activeCycle->start_date ? $activeCycle->start_date->format('d/m/Y') : '-' }}
                            -
                            {{ $activeCycle->end_date ? $activeCycle->end_date->format('d/m/Y') : '-' }}
                        </div>
                    </div>

                    <div class="cycle-meta-item">
                        <div class="cycle-meta-label">{{ $t['status'] }}</div>
                        <div class="cycle-meta-value">
                            <span class="cycle-badge open">{{ $t['open'] }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="cycle-meta-value" style="font-weight:600; color:#6b7280;">{{ $t['noActive'] }}</div>
            @endif
        </div>

        <div class="cycle-grid">
            <article class="cycle-card">
                <h3 class="cycle-card-title">{{ $t['list'] }}</h3>

                <div class="cycle-table-wrap">
                    <table class="cycle-table">
                        <thead>
                            <tr>
                                <th style="width:14%;">{{ $t['name'] }}</th>
                                <th style="width:10%;">{{ $t['code'] }}</th>
                                <th style="width:15%;">{{ $t['period'] }}</th>
                                <th style="width:33%;">{{ $t['monthSchedule'] }}</th>
                                <th style="width:14%;">{{ $t['note'] }}</th>
                                <th style="width:6%;">{{ $t['status'] }}</th>
                                <th style="width:8%;">{{ $t['action'] }}</th>
                            </tr>
                        </thead>
                        <tbody id="cycleTbody">
                            @forelse($cycles as $c)
                                @php
                                    $isActive = (bool)($c->is_active ?? false);
                                    $isOpenStatus = (string)($c->status ?? '') === \App\Models\Cycle::STATUS_OPEN;
                                    $monthRows = $c->months
                                        ->sortBy('month_no')
                                        ->filter(fn ($monthRow) => !empty($monthRow->open_at))
                                        ->values();
                                @endphp
                                <tr data-cycle-row="{{ $c->id }}">
                                    <td>{{ $c->name }}</td>
                                    <td>{{ $c->code ?: '-' }}</td>
                                    <td>
                                        {{ $c->start_date ? $c->start_date->format('d/m/Y') : '-' }}
                                        -
                                        {{ $c->end_date ? $c->end_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="cycle-note-cell">
                                        @if(!$isActive)
                                            <span style="color:#6b7280;">{{ $t['activateToManageMonths'] }}</span>
                                        @elseif($monthRows->isNotEmpty())
                                            <div class="cycle-month-list js-month-list">
                                                @foreach($monthRows as $monthRow)
                                                    @php
                                                        $monthNo = (int) ($monthRow->month_no ?? 0);
                                                        $monthName = $monthLabels[$monthNo] ?? ('M' . $monthNo);
                                                        $monthIsActive = (bool) ($monthRow->is_active ?? false);
                                                    @endphp
                                                    <div class="cycle-month-line">
                                                        <form method="POST" action="{{ route('admin.cycles.months.toggle', ['cycle' => $c->id, 'month' => $monthNo, 'lang' => $lang]) }}" style="margin:0;">
                                                            @csrf
                                                            <input type="hidden" name="is_active" value="{{ $monthIsActive ? 0 : 1 }}">
                                                            <button type="submit" class="cycle-btn {{ $monthIsActive ? 'outline-dark' : 'outline-primary' }} mini">
                                                                {{ $monthIsActive ? ($lang === 'th' ? 'ปิด' : 'Close') : ($lang === 'th' ? 'เปิด' : 'Open') }}
                                                            </button>
                                                        </form>
                                                        <span>{{ $monthName }}: {{ $monthRow->open_at->format('d/m/Y') }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="cycle-note-cell">{{ $c->note ?: '' }}</td>
                                    <td class="js-status-cell">
                                        @if($isActive)
                                            <span class="cycle-badge open js-status-badge">{{ $t['open'] }}</span>
                                        @elseif($isOpenStatus)
                                            <span class="cycle-badge soft js-status-badge">{{ $t['legacyOpen'] }}</span>
                                        @else
                                            <span class="cycle-badge closed js-status-badge">{{ $t['closed'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cycle-actions js-actions">
                                            @if($isActive)
                                                <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('admin.cycles.close') ? route('admin.cycles.close', $c->id) : '#' }}" class="js-close-form" data-cycle-id="{{ $c->id }}">
                                                    @csrf
                                                    <button type="submit" class="cycle-btn outline-dark js-close-btn">{{ $t['close'] }}</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('admin.cycles.activate') ? route('admin.cycles.activate', $c->id) : '#' }}" class="js-activate-form" data-cycle-id="{{ $c->id }}">
                                                    @csrf
                                                    <button type="submit" class="cycle-btn outline-primary js-activate-btn">{{ $t['activate'] }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="js-empty-row">
                                    <td colspan="7" style="padding:24px 10px; color:#6b7280;">{{ $t['empty'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(is_object($cycles) && method_exists($cycles, 'links'))
                    <div class="cycle-pagination">
                        {{ $cycles->links() }}
                    </div>
                @endif
            </article>

            <article class="cycle-card">
                <h3 class="cycle-card-title">{{ $t['create'] }}</h3>

                <form id="draftForm">
                    <div class="cycle-form-grid">
                        <div class="cycle-field col-6">
                            <label class="cycle-label" for="fName">{{ $t['name'] }}</label>
                            <input id="fName" type="text" name="name" class="cycle-input" required>
                        </div>

                        <div class="cycle-field col-3">
                            <label class="cycle-label" for="fStart">{{ $t['start'] }}</label>
                            <input id="fStart" type="text" name="start_date" class="cycle-input" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off">
                        </div>

                        <div class="cycle-field col-3">
                            <label class="cycle-label" for="fEnd">{{ $t['end'] }}</label>
                            <input id="fEnd" type="text" name="end_date" class="cycle-input" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off">
                        </div>

                        <div class="cycle-field col-12">
                            <label class="cycle-label" for="fNote">{{ $t['note'] }}</label>
                            <textarea id="fNote" name="note" class="cycle-textarea"></textarea>
                        </div>
                    </div>

                    <div class="cycle-form-actions">
                        <button type="button" id="btnResetDraft" class="cycle-btn secondary">{{ $t['reset'] }}</button>
                        <button type="submit" class="cycle-btn primary">{{ $t['save'] }}</button>
                    </div>
                </form>
            </article>

            <article class="cycle-card">
                <h3 class="cycle-card-title">{{ $monthCycleTitle }}</h3>
                <p class="cycle-help">{{ $monthNoteLabel }}</p>

                @if($allCycles->isEmpty())
                    <div class="cycle-alert info">{{ $monthNoCycleLabel }}</div>
                @else
                    <form method="POST" action="{{ $saveMonthsUrl }}" id="monthScheduleForm">
                        @csrf
                        <input type="hidden" name="lang" value="{{ $lang }}">
                        <input
                            type="hidden"
                            id="monthCycleIdInput"
                            name="cycle_id"
                            value="{{ old('cycle_id', $selectedMonthCycleId ?? ($allCycles->first()->id ?? '')) }}"
                        >

                        <div class="cycle-month-grid">
                            @for($monthNo = 1; $monthNo <= 12; $monthNo++)
                                <div class="cycle-field">
                                    <label class="cycle-label" for="month_{{ $monthNo }}">
                                        {{ $monthLabels[$monthNo] }}
                                    </label>
                                    <input
                                        id="month_{{ $monthNo }}"
                                        type="text"
                                        name="months[{{ $monthNo }}][open_at]"
                                        class="cycle-input"
                                        placeholder="dd/mm/yyyy"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        value="{{ $formatDateInput(old("months.$monthNo.open_at", $monthOpenAt[$monthNo] ?? '')) }}"
                                    >
                                </div>
                            @endfor
                        </div>

                        <div class="cycle-form-actions">
                            <button type="submit" class="cycle-btn primary">{{ $monthSaveLabel }}</button>
                        </div>
                    </form>
                @endif
            </article>
        </div>
    </section>

    <script>
        (function () {
            const csrf = @json(csrf_token());
            const storeUrl = @json($storeUrl);

            const t = {
                confirmClose: @json($t['confirmClose']),
                confirmActivate: @json($t['confirmActivate']),
                confirmDelete: @json($t['confirmDelete']),
                closeFailed: @json($t['closeFailed']),
                activateFailed: @json($t['activateFailed']),
                createInTable: @json($t['createInTable']),
                needName: @json($t['needName']),
                dateInvalid: @json($t['dateInvalid']),
                dateFormatInvalid: @json($t['dateFormatInvalid']),
                saving: @json($t['saving']),
                open: @json($t['open']),
                closed: @json($t['closed']),
                close: @json($t['close']),
                activate: @json($t['activate']),
                deleteTxt: @json($t['delete']),
                routeMissing: @json($t['routeMissing'])
            };

            const alertBox = document.getElementById('jsAlert');
            const tbody = document.getElementById('cycleTbody');
            const draftForm = document.getElementById('draftForm');
            const fName = document.getElementById('fName');
            const fStart = document.getElementById('fStart');
            const fEnd = document.getElementById('fEnd');
            const fNote = document.getElementById('fNote');
            const btnResetDraft = document.getElementById('btnResetDraft');
            const langThBtn = document.getElementById('lang-th');
            const langEnBtn = document.getElementById('lang-en');

            function showAlert(type, text) {
                if (!alertBox) return;
                alertBox.className = 'cycle-alert ' + (type || 'info');
                alertBox.textContent = text || '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => {
                    alertBox.className = 'cycle-alert d-none';
                    alertBox.textContent = '';
                }, 2600);
            }

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            function parseToISODate(rawValue) {
                const value = String(rawValue || '').trim();
                if (!value) return '';

                const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (isoMatch) {
                    return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
                }

                const dmyMatch = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (!dmyMatch) return '';

                const day = Number(dmyMatch[1]);
                const month = Number(dmyMatch[2]);
                const year = Number(dmyMatch[3]);
                const checkDate = new Date(year, month - 1, day);
                if (
                    checkDate.getFullYear() !== year ||
                    checkDate.getMonth() !== month - 1 ||
                    checkDate.getDate() !== day
                ) {
                    return '';
                }

                return `${year}-${pad2(month)}-${pad2(day)}`;
            }

            function formatDMY(rawValue) {
                const iso = parseToISODate(rawValue);
                if (!iso) return '-';
                const parts = iso.split('-');
                if (parts.length !== 3) return '-';
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }

            function removeEmptyRow() {
                const empty = tbody ? tbody.querySelector('.js-empty-row') : null;
                if (empty) empty.remove();
            }

            function buildCyclePageUrl(cycleId) {
                const url = new URL(window.location.href);
                if (cycleId) {
                    url.searchParams.set('month_cycle_id', String(cycleId));
                }
                return url.toString();
            }

            function redirectToCyclePage(cycleId) {
                window.location.assign(buildCyclePageUrl(cycleId));
            }

            function switchLangInstant(lang) {
                const url = new URL(window.location.href);
                url.searchParams.set('lang', lang === 'th' ? 'th' : 'en');
                window.location.replace(url.toString());
            }

            function resetForm() {
                if (fName) fName.value = '';
                if (fStart) fStart.value = '';
                if (fEnd) fEnd.value = '';
                if (fNote) fNote.value = '';
            }

            function createDraftRow(draft) {
                if (!tbody) return;

                removeEmptyRow();

                const tr = document.createElement('tr');
                tr.dataset.draft = '1';
                tr.dataset.draftId = draft.draftId;

                const tdName = document.createElement('td');
                tdName.textContent = draft.name;

                const tdCode = document.createElement('td');
                tdCode.textContent = '-';

                const tdPeriod = document.createElement('td');
                tdPeriod.textContent = formatDMY(draft.start_date) + ' - ' + formatDMY(draft.end_date);

                const tdMonthSchedule = document.createElement('td');
                tdMonthSchedule.className = 'cycle-note-cell';
                tdMonthSchedule.textContent = '-';

                const tdNote = document.createElement('td');
                tdNote.className = 'cycle-note-cell';
                tdNote.textContent = draft.note || '';

                const tdStatus = document.createElement('td');
                tdStatus.className = 'js-status-cell';
                const badge = document.createElement('span');
                badge.className = 'cycle-badge closed js-status-badge';
                badge.textContent = t.closed;
                tdStatus.appendChild(badge);

                const tdAct = document.createElement('td');
                const actions = document.createElement('div');
                actions.className = 'cycle-actions js-actions';

                const btnOpen = document.createElement('button');
                btnOpen.type = 'button';
                btnOpen.className = 'cycle-btn outline-primary js-draft-open';
                btnOpen.textContent = t.activate;
                btnOpen.dataset.draftId = draft.draftId;

                const btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'cycle-btn outline-dark js-draft-delete';
                btnDel.textContent = t.deleteTxt;
                btnDel.dataset.draftId = draft.draftId;

                actions.appendChild(btnOpen);
                actions.appendChild(btnDel);
                tdAct.appendChild(actions);

                tr.appendChild(tdName);
                tr.appendChild(tdCode);
                tr.appendChild(tdPeriod);
                tr.appendChild(tdMonthSchedule);
                tr.appendChild(tdNote);
                tr.appendChild(tdStatus);
                tr.appendChild(tdAct);

                tbody.insertBefore(tr, tbody.firstChild);
            }

            const draftMap = new Map();

            if (btnResetDraft) {
                btnResetDraft.addEventListener('click', resetForm);
            }

            if (langThBtn) {
                langThBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    switchLangInstant('th');
                }, true);
            }

            if (langEnBtn) {
                langEnBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    switchLangInstant('en');
                }, true);
            }

            if (draftForm) {
                draftForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const name = (fName && fName.value ? fName.value : '').trim();
                    const startRaw = (fStart && fStart.value ? fStart.value : '').trim();
                    const endRaw = (fEnd && fEnd.value ? fEnd.value : '').trim();
                    const note = (fNote && fNote.value ? fNote.value : '').trim();
                    const start = startRaw ? parseToISODate(startRaw) : '';
                    const end = endRaw ? parseToISODate(endRaw) : '';

                    if (!name) {
                        showAlert('danger', t.needName);
                        return;
                    }

                    if ((startRaw && !start) || (endRaw && !end)) {
                        showAlert('danger', t.dateFormatInvalid);
                        return;
                    }

                    if (start && end && end < start) {
                        showAlert('danger', t.dateInvalid);
                        return;
                    }

                    const draftId = 'd' + Date.now() + Math.random().toString(16).slice(2);
                    const draft = { draftId, name, start_date: start, end_date: end, note };

                    draftMap.set(draftId, draft);
                    createDraftRow(draft);
                    resetForm();
                    showAlert('success', t.createInTable);
                });
            }

            document.addEventListener('click', async function (e) {
                const btnDel = e.target.closest('.js-draft-delete');
                if (btnDel) {
                    const draftId = btnDel.dataset.draftId || '';
                    if (!draftId) return;
                    if (!window.confirm(t.confirmDelete)) return;

                    draftMap.delete(draftId);
                    const tr = btnDel.closest('tr');
                    if (tr) tr.remove();

                    if (tbody && !tbody.querySelector('tr')) {
                        const trEmpty = document.createElement('tr');
                        trEmpty.className = 'js-empty-row';
                        trEmpty.innerHTML = '<td colspan="7" style="padding:24px 10px; color:#6b7280;">{{ $t['empty'] }}</td>';
                        tbody.appendChild(trEmpty);
                    }
                    return;
                }

                const btnOpen = e.target.closest('.js-draft-open');
                if (btnOpen) {
                    const draftId = btnOpen.dataset.draftId || '';
                    const draft = draftMap.get(draftId);
                    if (!draft) return;

                    if (storeUrl === '#') {
                        showAlert('danger', t.routeMissing);
                        return;
                    }

                    if (!window.confirm(t.confirmActivate)) return;

                    btnOpen.disabled = true;
                    btnOpen.style.opacity = '.7';
                    showAlert('info', t.saving);

                    const fd = new FormData();
                    fd.append('name', draft.name);
                    if (draft.start_date) fd.append('start_date', draft.start_date);
                    if (draft.end_date) fd.append('end_date', draft.end_date);
                    if (draft.note) fd.append('note', draft.note);

                    try {
                        const res = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: fd
                        });

                        const data = await res.json();

                        if (!data || !data.ok || !data.cycle || !data.cycle.id) {
                            btnOpen.disabled = false;
                            btnOpen.style.opacity = '1';
                            showAlert('danger', t.activateFailed);
                            return;
                        }

                        redirectToCyclePage(data.cycle.id);
                    } catch (err) {
                        btnOpen.disabled = false;
                        btnOpen.style.opacity = '1';
                        showAlert('danger', t.activateFailed);
                    }

                    return;
                }
            });

            document.addEventListener('submit', async function (e) {
                const form = e.target;
                if (!form || !form.classList) return;

                if (form.classList.contains('js-activate-form')) {
                    e.preventDefault();

                    if (form.action === '#') {
                        showAlert('danger', t.routeMissing);
                        return;
                    }

                    if (!window.confirm(t.confirmActivate)) return;

                    const btn = form.querySelector('.js-activate-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '.7';
                    }

                    const fd = new FormData(form);
                    const token = form.querySelector('input[name="_token"]')?.value || csrf;

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: fd
                        });

                        const data = await res.json();

                        if (!data || !data.ok) {
                            if (btn) {
                                btn.disabled = false;
                                btn.style.opacity = '1';
                            }
                            showAlert('danger', (data && data.message) ? data.message : t.activateFailed);
                            return;
                        }

                        const activatedCycleId = (data && data.cycle && data.cycle.id) || form.dataset.cycleId || '';
                        redirectToCyclePage(activatedCycleId);
                    } catch (err) {
                        form.submit();
                    }

                    return;
                }

                if (form.classList.contains('js-close-form')) {
                    e.preventDefault();

                    if (form.action === '#') {
                        showAlert('danger', t.routeMissing);
                        return;
                    }

                    if (!window.confirm(t.confirmClose)) return;

                    const btn = form.querySelector('.js-close-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '.7';
                    }

                    const fd = new FormData(form);
                    const token = form.querySelector('input[name="_token"]')?.value || csrf;

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: fd
                        });

                        const data = await res.json();

                        if (!data || !data.ok) {
                            if (btn) {
                                btn.disabled = false;
                                btn.style.opacity = '1';
                            }
                            showAlert('danger', (data && data.message) ? data.message : t.closeFailed);
                            return;
                        }

                        window.location.reload();
                    } catch (err) {
                        form.submit();
                    }
                }
            });
        })();
    </script>
@endsection


