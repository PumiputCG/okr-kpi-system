@extends('layout')

@section('title', 'OKRs Score Summary')
@section('page_heading_key', 'menuOkrAllDept')
@section('page_heading', 'OKRs Score Summary')

@section('content')
    @php
        $lang = ($lang ?? 'en') === 'th' ? 'th' : 'en';
        $cycles = $cycles ?? collect();
        $selectedCycle = $selectedCycle ?? null;
        $rows = collect($rows ?? [])->values();
        $overallResult = $overallResult ?? '-';

        $t = $lang === 'th'
            ? [
                'title' => 'สรุปคะแนน OKRs',
                'cycle' => 'รอบ',
                'departmentLabel' => 'ทุกแผนก',
                'period' => 'ช่วงเวลา',
                'no' => 'หมายเลข',
                'departmentCol' => 'แผนก',
                'deptResultCol' => 'ค่าเฉลี่ยผลลัพธ์ของแผนก (OKR)',
                'allResultCol' => 'ค่าเฉลี่ยผลลัพธ์ OKRs ทุกแผนก',
                'grabTable' => '⟷',
                'grabLabel' => 'โหมดจับเลื่อนตาราง',
                'empty' => 'ยังไม่พบข้อมูล OKR สำหรับรอบที่เลือก',
            ]
            : [
                'title' => 'OKRs Score Summary',
                'cycle' => 'Cycle',
                'departmentLabel' => 'All Departments',
                'period' => 'Period',
                'no' => 'No.',
                'departmentCol' => 'Department',
                'deptResultCol' => 'Average Department Result (OKR)',
                'allResultCol' => 'Average OKRs Result (All Departments)',
                'grabTable' => '⟷',
                'grabLabel' => 'Table drag mode',
                'empty' => 'No OKR data found for the selected cycle.',
            ];
    @endphp

    <style>
        .okr-all-shell {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
            padding: 16px;
        }

        .okr-all-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .okr-all-title {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .okr-all-cycle-filter {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .okr-all-cycle-filter select {
            min-width: 220px;
            height: 36px;
            border: 1px solid #d1d5db;
            border-radius: 0;
            padding: 0 10px;
            background: #fff;
            font-size: 13px;
            color: #111827;
        }

        .okr-all-grab-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 2px;
        }

        .okr-all-grab-btn {
            height: 30px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            line-height: 1.2;
            cursor: pointer;
            padding: 0 10px;
        }

        .okr-all-grab-btn:hover {
            background: #f3f4f6;
        }

        .okr-all-grab-btn.is-active {
            background: #fde7f3;
            border-color: #e83e8c;
            color: #111827;
        }

        .okr-all-meta {
            display: flex;
            gap: 18px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px;
            color: #1f2937;
            font-size: 13px;
        }

        .okr-all-meta b {
            color: #111827;
        }

        .okr-all-table-wrap {
            overflow-x: auto;
            border: 1px solid #d1d5db;
            background: #fff;
        }

        .okr-all-table-wrap.is-grab-mode {
            cursor: grab;
            user-select: none;
        }

        .okr-all-table-wrap.is-grab-mode .okr-all-table {
            cursor: inherit;
        }

        .okr-all-table-wrap.is-grab-mode.is-dragging {
            cursor: grabbing;
        }

        .okr-all-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
            table-layout: fixed;
        }

        .okr-all-table th,
        .okr-all-table td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            background: #ffffff;
            vertical-align: middle;
            line-height: 1.45;
            text-align: center;
            white-space: nowrap;
        }

        .okr-all-table th {
            background: #f3f4f6;
            font-weight: 600;
        }

        .okr-all-table td.okr-all-merged {
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }

        .okr-all-empty {
            text-align: center;
            color: #6b7280;
            padding: 16px;
        }
    </style>

    <section class="okr-all-shell">
        <div class="okr-all-head">
            <h2 class="okr-all-title">{{ $t['title'] }}</h2>

            <form method="GET" action="{{ route('okr.summary.all', ['lang' => $lang]) }}" class="okr-all-cycle-filter">
                <input type="hidden" name="lang" value="{{ $lang }}" data-lang-sync="1">
                <label for="cycle_id"><b>{{ $t['cycle'] }}</b></label>
                <select id="cycle_id" name="cycle_id" onchange="this.form.submit()">
                    @foreach($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected($selectedCycle && (int) $selectedCycle->id === (int) $cycle->id)>
                            {{ $cycle->name ?: ('Cycle #' . $cycle->id) }}
                        </option>
                    @endforeach
                </select>
                <div class="okr-all-grab-controls" role="group" aria-label="{{ $t['grabLabel'] }}">
                    <button type="button" class="okr-all-grab-btn" data-role="okr-all-grab-toggle" aria-pressed="false">{{ $t['grabTable'] }}</button>
                </div>
            </form>
        </div>

        <div class="okr-all-meta">
            <span><b>{{ $t['departmentCol'] }}:</b> {{ $t['departmentLabel'] }}</span>
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

        <div class="okr-all-table-wrap">
            <table class="okr-all-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">{{ $t['no'] }}</th>
                        <th style="width: 26%;">{{ $t['departmentCol'] }}</th>
                        <th style="width: 32%;">{{ $t['deptResultCol'] }}</th>
                        <th style="width: 32%;">{{ $t['allResultCol'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($rows->isNotEmpty())
                        @foreach($rows as $index => $row)
                            <tr>
                                <td>{{ $row['no'] ?? '-' }}</td>
                                <td>{{ $row['department'] ?? '-' }}</td>
                                <td>{{ $row['dept_result'] ?? '-' }}</td>
                                @if($index === 0)
                                    <td class="okr-all-merged" rowspan="{{ $rows->count() }}">{{ $overallResult }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="okr-all-empty">{{ $t['empty'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (function () {
            const tableWrap = document.querySelector('.okr-all-table-wrap');
            const grabToggleBtn = document.querySelector('[data-role="okr-all-grab-toggle"]');
            const storageKey = 'okr-summary-all-drag-enabled';

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
