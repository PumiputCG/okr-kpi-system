@extends('layout')

@section('title', 'Download Documents')
@section('page_heading_key', 'menuAdminDownloadDoc')
@section('page_heading', 'Download Documents')
@section('content')
    @php
        $lang = ($lang ?? request('lang')) === 'th' ? 'th' : 'en';
        $downloadRangeUrl = \Illuminate\Support\Facades\Route::has('admin.documents.downloadExcel.range')
            ? route('admin.documents.downloadExcel.range')
            : '#';
        $formatPeriod = static function ($cycle): string {
            $start = $cycle->start_date?->format('d/m/Y') ?? $cycle->opened_at?->format('d/m/Y');
            $end = $cycle->end_date?->format('d/m/Y') ?? $cycle->closed_at?->format('d/m/Y');

            if (! $start && ! $end) {
                return '-';
            }

            return ($start ?: '-') . ' - ' . ($end ?: '-');
        };
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
        $selectedCycleId = (int) old('cycle_id', (int) ($cycles->first()->id ?? 0));
        $selectedMonths = collect(old('months', range(1, 12)))
            ->map(fn ($monthNo) => (int) $monthNo)
            ->filter(fn (int $monthNo) => $monthNo >= 1 && $monthNo <= 12)
            ->unique()
            ->sort()
            ->values()
            ->all();
        if (count($selectedMonths) < 1) {
            $selectedMonths = range(1, 12);
        }
    @endphp

    <style>
        .docs-shell {
            background: #ffffff;
            padding: 20px 22px;
            min-height: 460px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .docs-head {
            margin-bottom: 16px;
        }

        .docs-head h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111111;
        }

        .docs-head p {
            margin: 8px 0 0;
            font-size: 13px;
            color: #4b5563;
        }

        .docs-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 14px;
        }

        .docs-range-card {
            margin-bottom: 14px;
        }

        .docs-range-help {
            margin: 0 0 12px;
            font-size: 12px;
            color: #6b7280;
        }

        .docs-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
        }

        .docs-field {
            display: grid;
            gap: 6px;
        }

        .docs-field.col-6 {
            grid-column: span 6;
        }

        .docs-field.col-3 {
            grid-column: span 3;
        }

        .docs-field.col-12 {
            grid-column: span 12;
        }

        .docs-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .docs-input {
            width: 100%;
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 13px;
            color: #111111;
            outline: none;
        }

        .docs-input:focus {
            border-color: #e83e8c;
        }

        .docs-month-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            padding: 10px;
            border: 1px solid #d1d5db;
            background: #ffffff;
        }

        .docs-month-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #111111;
            user-select: none;
        }

        .docs-month-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
        }

        .docs-form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .docs-note {
            margin: 8px 0 0;
            font-size: 12px;
            color: #6b7280;
        }

        .docs-table-wrap {
            overflow-x: auto;
        }

        .docs-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 640px;
        }

        .docs-table th,
        .docs-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 13px;
            color: #111111;
            vertical-align: middle;
        }

        .docs-table th {
            background: #f9fafb;
            text-align: left;
            font-weight: 700;
        }

        .docs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e83e8c;
            color: #e83e8c;
            background: #ffffff;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .docs-btn:hover {
            background: #e83e8c;
            color: #ffffff;
        }

        .docs-empty {
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 860px) {
            .docs-form-grid {
                grid-template-columns: 1fr;
            }

            .docs-field.col-6,
            .docs-field.col-3,
            .docs-field.col-12 {
                grid-column: span 1;
            }

            .docs-month-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <section class="docs-shell">
        <div class="docs-head">
            <h2 data-i18n="docsTitle">Download Documents</h2>
            <p data-i18n="docsSubtitle">Choose a cycle and download its Excel file.</p>
        </div>

        <article class="docs-card docs-range-card">
            <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 700;" data-i18n="docsRangeTitle">
                Download Excel by Month Range
            </h3>
            <p class="docs-range-help" data-i18n="docsRangeHelp">
                Select cycle and months for Excel export.
            </p>

            @if($cycles->isEmpty())
                <p class="docs-note" data-i18n="docsRangeNoCycle">No cycle available.</p>
            @else
                <form method="GET" action="{{ $downloadRangeUrl }}" id="docs-range-form">
                    <input type="hidden" name="lang" value="{{ $lang }}">

                    <div class="docs-form-grid">
                        <div class="docs-field col-6">
                            <label class="docs-label" for="docs_cycle_id" data-i18n="docsRangeCycle">Cycle</label>
                            <select id="docs_cycle_id" name="cycle_id" class="docs-input">
                                @foreach($cycles as $cycle)
                                    @php
                                        $cycleId = (int) ($cycle->id ?? 0);
                                        $cycleName = trim((string) ($cycle->name ?? ''));
                                    @endphp
                                    <option value="{{ $cycleId }}" @selected($selectedCycleId === $cycleId)>
                                        {{ $cycleName !== '' ? $cycleName : ('Cycle #' . $cycleId) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="docs-field col-12">
                            <label class="docs-label" data-i18n="docsRangeMonths">Months</label>
                            <div class="docs-month-grid">
                                @for($monthNo = 1; $monthNo <= 12; $monthNo++)
                                    <label class="docs-month-item">
                                        <input type="checkbox"
                                               name="months[]"
                                               value="{{ $monthNo }}"
                                               @checked(in_array($monthNo, $selectedMonths, true))>
                                        <span>{{ $monthLabels[$monthNo] }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="docs-form-actions">
                        <button type="submit" class="docs-btn" data-i18n="docsRangeDownload">Download Excel</button>
                    </div>

                    @if($downloadRangeUrl === '#')
                        <p class="docs-note" data-i18n="docsRangeRoutePending">
                            Waiting for Controller/Route integration for month-range download.
                        </p>
                    @endif
                </form>
            @endif
        </article>

        <article class="docs-card">
            <div class="docs-table-wrap">
                <table class="docs-table">
                    <thead>
                    <tr>
                        <th style="width: 90px;" data-i18n="docsNo">No.</th>
                        <th data-i18n="docsCycleName">Cycle Name</th>
                        <th style="width: 230px;" data-i18n="docsPeriod">Period</th>
                        <th style="width: 180px;" data-i18n="docsManage">Manage</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cycles as $index => $cycle)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ trim((string) $cycle->name) !== '' ? $cycle->name : '-' }}</td>
                            <td>{{ $formatPeriod($cycle) }}</td>
                            <td>
                                <a class="docs-btn"
                                   href="{{ route('admin.documents.downloadExcel', ['cycle' => $cycle->id, 'lang' => $lang]) }}"
                                   data-i18n="docsDownloadExcel">
                                    Download Excel
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="docs-empty" colspan="4" data-i18n="docsEmpty">No cycle data yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <script>
        (function () {
            const form = document.getElementById('docs-range-form');
            if (!form) {
                return;
            }

            const getText = (key, fallback) => {
                if (window.AppModal && typeof window.AppModal.getText === 'function') {
                    return window.AppModal.getText(key);
                }
                return fallback;
            };

            const showAlert = (message) => {
                const safe = String(message || '').trim();
                if (!safe) {
                    return;
                }

                if (window.AppModal && typeof window.AppModal.alert === 'function') {
                    window.AppModal.alert(safe, 'error');
                    return;
                }

                window.alert(safe);
            };

            form.addEventListener('submit', (event) => {
                const action = String(form.getAttribute('action') || '').trim();
                if (!action || action === '#') {
                    event.preventDefault();
                    showAlert(getText('docsRangeRoutePending', 'Waiting for Controller/Route integration for month-range download.'));
                    return;
                }

                const monthChecks = Array.from(form.querySelectorAll('input[name="months[]"]'));
                const checkedCount = monthChecks.filter((el) => el.checked).length;
                if (checkedCount < 1) {
                    event.preventDefault();
                    showAlert(getText('docsRangeInvalid', 'Please select at least one month.'));
                }
            });
        })();
    </script>
@endsection
