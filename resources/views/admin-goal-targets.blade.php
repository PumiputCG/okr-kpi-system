@php
    $pageLang = (($lang ?? request('lang', 'en')) === 'th') ? 'th' : 'en';
@endphp

@extends('layout')

@section('title', $pageLang === 'th' ? 'กำหนดเป้าหมายตัวชี้วัด' : 'Target Indicator Setup')
@section('page_heading', $pageLang === 'th' ? 'กำหนดเป้าหมายตัวชี้วัด' : 'Target Indicator Setup')
@section('page_heading_key', 'homeTargetIndicatorTitle')

@section('content')
    @php
        $lang = $pageLang;
        $rows = is_array($rows ?? null) ? $rows : [];
        $deptOptions = is_array($deptOptions ?? null) ? $deptOptions : [];
        $krDeptOptions = is_array($krDeptOptions ?? null) ? $krDeptOptions : [];
        $levelThreeDeptOptions = is_array($levelThreeDeptOptions ?? null) ? $levelThreeDeptOptions : [];
        $deptFilter = (string) ($deptFilter ?? '');
        $levelThreeDeptFilter = (string) ($levelThreeDeptFilter ?? '');
        $hasHierarchyFilter = (bool) ($hasHierarchyFilter ?? false);
        $monthlySummaryUrlTemplate = $monthlySummaryUrlTemplate ?? route('kpi.summary.dept.monthly', ['root' => '__ROOT__']);
    @endphp

    <style>
        .gt-shell { display: grid; gap: 16px; }

        .gt-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .gt-toolbar-title { margin: 0; font-size: 15px; color: #4b5563; }

        .gt-view-tools {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 5px;
        }

        .gt-sort-tools {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .gt-sort-label,
        .gt-level-label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
        }

        .gt-sort-label {
            color: #111827;
        }

        .gt-filter-reset {
            height: 34px;
            min-width: 64px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
            padding: 0 11px;
            cursor: pointer;
            white-space: nowrap;
        }
        .gt-filter-reset:hover { background: #eef2ff; border-color: #a5b4fc; }
        .gt-filter-reset.is-active {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .gt-dept-filter-select {
            height: 34px;
            padding: 0 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            min-width: 80px;
            max-width: 150px;
        }
        .gt-dept-filter-select:focus { outline: 2px solid #6366f1; outline-offset: 1px; }
        .gt-dept-filter-select.is-active { background: #eef2ff; border-color: #818cf8; color: #4338ca; }

        .gt-tool-btn {
            width: 34px;
            height: 34px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #111827;
            font-size: 15px;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .gt-tool-btn:hover { background: #eef2ff; border-color: #a5b4fc; }
        .gt-tool-btn:disabled {
            color: #9ca3af;
            background: #f3f4f6;
            cursor: not-allowed;
        }
        .gt-tool-btn.is-active {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }
        #gt-pan-toggle,
        #gt-pan-toggle.is-active {
            color: #111827;
        }
        #gt-pan-toggle.is-active {
            background: #e5e7eb;
            border-color: #111827;
        }
        .gt-hand-icon {
            color: #111827;
            font-family: "Segoe UI Symbol", "Arial Unicode MS", Arial, sans-serif;
            font-size: 16px;
            line-height: 1;
        }

        .gt-zoom-value {
            min-width: 52px;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            color: #374151;
            font-variant-numeric: tabular-nums;
        }

        .gt-btn-add {
            border: 0;
            background: #e83e8c;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .gt-btn-add:hover { background: #be185d; }

        .gt-btn-add-l2 {
            border: 1px solid #8b5cf6;
            background: #faf5ff;
            color: #6d28d9;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 9px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .gt-btn-add-l2:hover { background: #ede9fe; }

        .gt-table .gt-l1-add-cell,
        .gt-table .gt-l2-add-cell {
            text-align: center;
        }

        .gt-l1-add-cell { background: #fff7fb; }
        .gt-l2-add-cell { background: #faf5ff; }

        .gt-l1-add-cell .gt-btn-add,
        .gt-l2-add-cell .gt-btn-add-l2 {
            justify-content: center;
        }

        .gt-btn-edit {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            font-size: 11px;
            padding: 3px 8px;
            cursor: pointer;
            white-space: nowrap;
        }
        .gt-btn-edit:hover { background: #e5e7eb; }

        .gt-btn-del {
            border: 1px solid #fca5a5;
            background: #fff1f2;
            color: #b91c1c;
            font-size: 11px;
            padding: 3px 8px;
            cursor: pointer;
            white-space: nowrap;
        }
        .gt-btn-del:hover { background: #fee2e2; }

        .gt-status {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            padding: 10px 14px;
            font-size: 13px;
        }

        .gt-errors {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #9f1239;
            padding: 10px 14px;
            font-size: 13px;
            display: grid;
            gap: 4px;
        }

        /* ── Table ── */
        .gt-table-wrap {
            overflow: auto;
            border: 1px solid #e5e7eb;
            max-height: calc(100vh - 220px);
            position: relative;
            touch-action: pan-x pan-y;
        }

        .gt-table-wrap.is-pan-mode {
            cursor: grab;
            user-select: none;
        }

        .gt-table-wrap.is-dragging { cursor: grabbing; }

        .gt-table-scale-layer {
            width: 100%;
            transform-origin: top left;
        }

        .gt-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 2100px; /* kept for column-width baseline; zoom on layer handles scaling */
            table-layout: fixed;
            background: #ffffff;
            font-size: 12px;
            transform-origin: top left;
        }

        .gt-table th, .gt-table td {
            border: 1px solid #e5e7eb;
            padding: 9px 10px;
            vertical-align: middle;
            text-align: center;
            line-height: 1.45;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Level headers (row 1) */
        .gt-lhdr-1 {
            background: #e83e8c;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
        }
        .gt-lhdr-2 {
            background: #7c3aed;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
        }
        .gt-lhdr-3 {
            background: #0891b2;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
        }

        /* Column sub-headers (row 2) */
        .gt-col-hdr {
            background: #f9fafb;
            font-weight: 700;
            font-size: 11px;
            color: #374151;
            white-space: nowrap;
        }

        /* L1 data cells */
        .gt-l1 { background: #fff7fb; vertical-align: middle; }
        .gt-l1-no {
            font-weight: 700;
            font-size: 18px;
            color: #be185d;
            text-align: center;
            width: 44px;
        }
        .gt-l1-title { font-weight: 600; font-size: 13px; color: #111827; white-space: pre-line; }
        .gt-l1-detail { color: #374151; font-size: 12px; white-space: pre-line; }
        .gt-l1-file { font-size: 11px; }
        .gt-l1-actions { white-space: normal; }

        /* L2 data cells */
        .gt-l2 { background: #faf5ff; vertical-align: middle; }
        .gt-l2-dept { font-weight: 700; color: #5b21b6; font-size: 12px; }
        .gt-l2-title { font-weight: 600; font-size: 12px; color: #111827; }
        .gt-l2-detail { color: #374151; font-size: 12px; white-space: pre-line; }
        .gt-l2-file { font-size: 11px; }
        .gt-l2-actions { white-space: normal; }

        /* L3 data cells */
        .gt-l3 { background: #ecfeff; vertical-align: middle; }
        .gt-l3-from { color: #0e7490; font-size: 11px; }
        .gt-l3-title { color: #111827; font-size: 12px; font-weight: 600; }
        .gt-l3-detail { color: #374151; font-size: 12px; white-space: pre-line; }
        .gt-l3-goal { color: #0f766e; font-size: 12px; font-weight: 700; }
        .gt-l3-target { color: #111827; font-size: 12px; }
        .gt-l3-unit { color: #4b5563; font-size: 12px; }

        /* Level 4 header */
        .gt-lhdr-4 {
            background: #d97706;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
        }

        /* L4 data cells */
        .gt-l4 { background: #fffbeb; vertical-align: middle; }
        .gt-l4-from { color: #92400e; font-size: 11px; }
        .gt-l4-title { color: #111827; font-size: 12px; font-weight: 600; }
        .gt-l4-detail { color: #374151; font-size: 12px; white-space: pre-line; }
        .gt-l4-criteria { color: #b45309; font-size: 12px; font-weight: 700; }
        .gt-l4-target { color: #111827; font-size: 12px; }
        .gt-l4-unit { color: #4b5563; font-size: 12px; }
        .gt-l4-months { color: #065f46; font-size: 11px; line-height: 1.6; }
        .gt-l4-months .no-months { color: #9ca3af; font-style: italic; }
        .gt-l4-view { text-align: center; }

        .gt-btn-view-months {
            border: 1px solid #d97706;
            background: #fffbeb;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            cursor: pointer;
            white-space: nowrap;
        }
        .gt-btn-view-months:hover { background: #fef3c7; border-color: #b45309; }

        /* Month detail modal */
        .gt-month-modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 12px;
        }
        .gt-month-modal-table th {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            font-weight: 700;
            font-size: 12px;
            text-align: center;
        }
        .gt-month-modal-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            text-align: center;
            font-size: 12px;
        }
        .gt-month-modal-table tr.is-approved td { background: #f0fdf4; }
        .gt-month-modal-table tr.is-pending td { background: #fffbeb; }
        .gt-month-modal-table tr.is-rejected td { background: #fff1f2; }
        .gt-month-modal-table .status-approved { color: #166534; font-weight: 700; }
        .gt-month-modal-table .status-pending { color: #92400e; }
        .gt-month-modal-table .status-rejected { color: #9f1239; }
        .gt-month-modal-table .pass-yes { color: #166534; font-weight: 700; }
        .gt-month-modal-table .pass-no { color: #9f1239; }
        .gt-month-modal-footer {
            margin-top: 14px;
            padding: 10px 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 13px;
            display: grid;
            gap: 4px;
        }
        .gt-month-modal-footer strong { color: #111827; }
        .gt-month-modal-avg { font-size: 15px; font-weight: 700; color: #0891b2; }
        .gt-month-modal-info { font-size: 12px; color: #6b7280; margin: 0 0 10px; }
        .gt-month-empty { color: #9ca3af; font-style: italic; text-align: center; padding: 20px; font-size: 13px; }

        .gt-table .gt-empty-cell { color: #9ca3af; text-align: center; font-style: italic; }

        .gt-file-link {
            color: #2563eb;
            font-size: 11px;
            text-decoration: none;
            word-break: break-all;
        }
        .gt-file-link:hover { text-decoration: underline; }

        .gt-no-data {
            border: 1px dashed #d1d5db;
            padding: 40px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            background: #fafafa;
        }

        .gt-table .gt-no-data-row {
            padding: 28px 16px;
            color: #9ca3af;
            text-align: center;
            font-style: italic;
            background: #fafafa;
        }

        .gt-actions-wrap {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: center;
        }

        /* ── Modals ── */
        .gt-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17,17,17,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 2000;
        }
        .gt-modal-backdrop.show { display: flex; }
        #modal-l3 { z-index: 13500; }
        #modal-del { z-index: 14000; }

        .gt-modal-panel {
            width: min(96vw, 560px);
            background: #ffffff;
            padding: 22px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .gt-modal-title {
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .gt-form-row {
            display: grid;
            gap: 12px;
            margin-bottom: 0;
        }

        .gt-field {
            display: grid;
            gap: 5px;
        }

        .gt-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .gt-input, .gt-textarea, .gt-select {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 8px 10px;
            font-size: 13px;
            width: 100%;
            font-family: inherit;
        }
        .gt-input:focus, .gt-textarea:focus, .gt-select:focus {
            outline: 2px solid #e83e8c;
            outline-offset: 0;
        }

        .gt-textarea { min-height: 80px; resize: vertical; }
        .gt-title-textarea { min-height: 96px; white-space: pre-wrap; }

        .gt-file-hint { font-size: 11px; color: #6b7280; }
        .gt-current-file-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #374151;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
        }
        .gt-remove-file-btn {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font-size: 11px;
            cursor: pointer;
            padding: 0;
        }

        .gt-modal-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .gt-btn-cancel {
            border: 0;
            background: #f3f4f6;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            cursor: pointer;
        }
        .gt-btn-save {
            border: 0;
            background: #e83e8c;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            cursor: pointer;
        }
        .gt-btn-save:hover { background: #be185d; }

        .gt-confirm-text {
            margin: 8px 0 0;
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
        }
        .gt-btn-danger {
            border: 0;
            background: #dc2626;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            cursor: pointer;
        }
        .gt-btn-danger:hover { background: #b91c1c; }

        /* ── L4 Monthly Modal (kpi-summary-dept style) ── */
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
        .kpi-monthly-modal.show { display: flex; }
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
        .kpi-monthly-title { margin: 0; font-size: 18px; font-weight: 700; color: #111827; }
        .kpi-monthly-close {
            border: 0; background: transparent; color: #6b7280;
            font-size: 22px; line-height: 1; cursor: pointer; padding: 0;
        }
        .kpi-monthly-close:hover { color: #111827; }
        .kpi-monthly-meta {
            display: grid; gap: 4px; padding: 12px 16px;
            font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;
        }
        .kpi-monthly-meta p { margin: 0; }
        .kpi-monthly-table-wrap {
            padding: 12px 16px; overflow-x: auto; overflow-y: auto;
            flex: 1 1 auto; min-height: 0;
        }
        .kpi-monthly-table {
            width: max-content; min-width: 1240px;
            border-collapse: collapse; table-layout: auto; font-size: 13px;
        }
        .kpi-monthly-table th,
        .kpi-monthly-table td {
            border: 1px solid #d1d5db; padding: 8px 10px;
            text-align: left; vertical-align: middle; background: #ffffff; line-height: 1.4;
        }
        .kpi-monthly-table th { background: #f3f4f6; font-weight: 600; }
        .kpi-monthly-table .text-center { text-align: center; }
        .kpi-summary-result-pass   { color: #15803d; font-weight: 600; }
        .kpi-summary-result-fail   { color: #b91c1c; font-weight: 600; }
        .kpi-summary-result-pending { color: #6b7280; font-weight: 500; }
        .kpi-summary-result-pending-review { color: #ca8a04; font-weight: 600; }
        .kpi-monthly-net-average td { font-weight: 700; background: #f8fafc; }
        .kpi-monthly-empty { text-align: center; color: #6b7280; padding: 14px; }
        .kpi-monthly-file-list {
            margin: 0; padding: 0; list-style: none; display: grid; gap: 4px; width: 100%;
        }
        .kpi-monthly-file-list li {
            line-height: 1.25; min-width: 0; position: relative; padding-left: 12px;
        }
        .kpi-monthly-file-list li::before {
            content: '\2022'; position: absolute; left: 0; top: 0;
            color: #6b7280; font-size: 12px; line-height: 1.25;
        }
        .kpi-monthly-files-cell { min-width: 220px; max-width: 280px; overflow-wrap: anywhere; }
        .kpi-monthly-file-link {
            color: #1d4ed8; text-decoration: underline; font-size: 12px;
            display: block; width: 100%; max-width: 100%;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .kpi-monthly-actions { display: flex; justify-content: flex-end; padding: 0 16px 16px; }
        .kpi-monthly-close-btn {
            border: 1px solid #d1d5db; background: #ffffff; color: #111827;
            padding: 7px 14px; cursor: pointer; font-size: 13px;
        }
        @media (max-width: 900px) {
            .kpi-monthly-modal { padding: 8px; }
            .kpi-monthly-panel { width: calc(100vw - 16px); max-height: 94vh; }
            .kpi-monthly-table { min-width: 1120px; }
            .gt-toolbar { align-items: flex-start; }
            .gt-view-tools { justify-content: flex-start; width: 100%; }
            .gt-sort-tools { justify-content: flex-start; }
        }
    </style>

    <section class="gt-shell">

        @if (session('status'))
            <div class="gt-status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="gt-errors">
                @foreach ($errors->all() as $err)
                    <span>{{ $err }}</span>
                @endforeach
            </div>
        @endif

        <div class="gt-toolbar">
            <p class="gt-toolbar-title">
                {{ $lang === 'th' ? 'ตารางลำดับชั้นเป้าหมายและตัวชี้วัด' : 'Goal & Indicator Hierarchy Table' }}
            </p>
            <div class="gt-view-tools" aria-label="{{ $lang === 'th' ? 'เครื่องมือตาราง' : 'Table tools' }}">
                <div class="gt-sort-tools" aria-label="{{ $lang === 'th' ? 'เรียงตาม' : 'Sort by' }}">
                    <span class="gt-sort-label">{{ $lang === 'th' ? 'เรียงตาม :' : 'Sort by:' }}</span>
                    <span class="gt-level-label">{{ $lang === 'th' ? 'ลำดับชั้นที่ 1' : 'Level 1' }}</span>
                    <button type="button"
                        class="gt-filter-reset{{ ! $hasHierarchyFilter ? ' is-active' : '' }}"
                        id="gt-filter-reset"
                        aria-label="{{ $lang === 'th' ? 'ทั้งหมด' : 'All' }}"
                        title="{{ $lang === 'th' ? 'แสดงข้อมูลทั้งหมด' : 'Show all data' }}">
                        {{ $lang === 'th' ? 'ทั้งหมด' : 'All' }}
                    </button>
                    @if (count($krDeptOptions) > 0)
                        <label class="gt-level-label" for="gt-l2-dept-filter">{{ $lang === 'th' ? 'ลำดับชั้น 2' : 'Level 2' }}</label>
                        <select id="gt-l2-dept-filter"
                            class="gt-dept-filter-select{{ $deptFilter !== '' ? ' is-active' : '' }}"
                            aria-label="{{ $lang === 'th' ? 'กรองแผนกลำดับชั้น 2' : 'Filter Level 2 department' }}"
                            title="{{ $lang === 'th' ? 'กรองตามแผนกลำดับชั้น 2' : 'Filter by Level 2 department' }}">
                            <option value="">{{ $lang === 'th' ? 'ทุกแผนก' : 'All depts' }}</option>
                            @foreach ($krDeptOptions as $dept)
                                <option value="{{ $dept }}" {{ $deptFilter === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if (count($levelThreeDeptOptions) > 0)
                        <label class="gt-level-label" for="gt-l3-dept-filter">{{ $lang === 'th' ? 'ลำดับชั้น 3' : 'Level 3' }}</label>
                        <select id="gt-l3-dept-filter"
                            class="gt-dept-filter-select{{ $levelThreeDeptFilter !== '' ? ' is-active' : '' }}"
                            aria-label="{{ $lang === 'th' ? 'กรองแผนกลำดับชั้น 3' : 'Filter Level 3 department' }}"
                            title="{{ $lang === 'th' ? 'กรองตามแผนกลำดับชั้น 3' : 'Filter by Level 3 department' }}">
                            <option value="">{{ $lang === 'th' ? 'ทุกแผนก' : 'All depts' }}</option>
                            @foreach ($levelThreeDeptOptions as $dept)
                                <option value="{{ $dept }}" {{ $levelThreeDeptFilter === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                @if (false && count($krDeptOptions) > 0)
                    <select id="gt-dept-filter-select"
                        class="gt-dept-filter-select{{ $deptFilter !== '' ? ' is-active' : '' }}"
                        aria-label="{{ $lang === 'th' ? 'กรองแผนก' : 'Filter department' }}"
                        title="{{ $lang === 'th' ? 'กรองตามแผนกลำดับ 2' : 'Filter by Level 2 department' }}">
                        <option value="">{{ $lang === 'th' ? 'ทุกแผนก' : 'All depts' }}</option>
                        @foreach ($krDeptOptions as $dept)
                            <option value="{{ $dept }}" {{ $deptFilter === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" class="gt-tool-btn" id="gt-zoom-out"
                    aria-label="{{ $lang === 'th' ? 'ซูมออก' : 'Zoom out' }}"
                    title="{{ $lang === 'th' ? 'ซูมออก' : 'Zoom out' }}">−</button>
                <span class="gt-zoom-value" id="gt-zoom-value">100%</span>
                <button type="button" class="gt-tool-btn" id="gt-zoom-in"
                    aria-label="{{ $lang === 'th' ? 'ซูมเข้า' : 'Zoom in' }}"
                    title="{{ $lang === 'th' ? 'ซูมเข้า' : 'Zoom in' }}">+</button>
                <button type="button" class="gt-tool-btn" id="gt-pan-toggle" aria-pressed="false"
                    aria-label="{{ $lang === 'th' ? 'มือเลื่อนตาราง' : 'Pan table' }}"
                    title="{{ $lang === 'th' ? 'มือเลื่อนตาราง' : 'Pan table' }}">
                    <span class="gt-hand-icon" aria-hidden="true">&#9995;&#65038;</span>
                </button>
            </div>
        </div>

        <div class="gt-table-wrap" id="gt-table-wrap">
            <div class="gt-table-scale-layer" id="gt-table-scale-layer">
            <table class="gt-table" id="gt-goal-table">
                <colgroup>
                    {{-- L1 (5 cols) --}}
                    <col style="width:44px">
                    <col style="width:110px">
                    <col style="width:120px">
                    <col style="width:74px">
                    <col style="width:74px">
                    {{-- L2 (5 cols) --}}
                    <col style="width:64px">
                    <col style="width:110px">
                    <col style="width:120px">
                    <col style="width:58px">
                    <col style="width:58px">
                    {{-- L3 (8 cols) --}}
                    <col style="width:140px">
                    <col style="width:90px">
                    <col style="width:90px">
                    <col style="width:120px">
                    <col style="width:50px">
                    <col style="width:60px">
                    <col style="width:60px">
                    <col style="width:74px">
                    {{-- L4 (10 cols): dept, selected_dept, code, name, objective, detail, target, score, result, actions --}}
                    <col style="width:70px">
                    <col style="width:90px">
                    <col style="width:70px">
                    <col style="width:130px">
                    <col style="width:130px">
                    <col style="width:150px">
                    <col style="width:130px">
                    <col style="width:60px">
                    <col style="width:70px">
                    <col style="width:70px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="gt-lhdr-1" colspan="5" data-i18n="gtLevel1Header">{{ $lang === 'th' ? 'ลำดับชั้น 1 · CEO' : 'Level 1 · CEO' }}</th>
                        <th class="gt-lhdr-2" colspan="5" data-i18n="gtLevel2Header">{{ $lang === 'th' ? 'ลำดับชั้น 2 · GM / DM / AM / Mgr.' : 'Level 2 · GM / DM / AM / Mgr.' }}</th>
                        <th class="gt-lhdr-3" colspan="8" data-i18n="gtLevel3Header">{{ $lang === 'th' ? 'ลำดับชั้น 3 · AM / Mgr.' : 'Level 3 · AM / Mgr.' }}</th>
                        <th class="gt-lhdr-4" colspan="10" data-i18n="gtLevel4Header">{{ $lang === 'th' ? 'ลำดับชั้น 4 · ผล KPI รายเดือน' : 'Level 4 · Monthly KPI Results' }}</th>
                    </tr>
                    <tr>
                        <th class="gt-col-hdr">#</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'ไฟล์' : 'File' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'จัดการ' : 'Actions' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'แผนก' : 'Dept' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'ไฟล์' : 'File' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'จัดการ' : 'Actions' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'จาก' : 'From' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'แผนกเป้าหมาย' : 'Target Dept' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'เกณฑ์' : 'Criteria' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'เป้าหมาย' : 'Target' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'หน่วย' : 'Unit' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'จัดการ' : 'Actions' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'แผนก' : 'Dept' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'แผนกที่เลือก' : 'Sel.Dept' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'รหัส' : 'Code' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'ชื่อ-สกุล' : 'Name' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'วัตถุประสงค์ KPI' : 'Objective' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'เป้าหมาย' : 'Target' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'คะแนน' : 'Score' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'ผลลัพธ์' : 'Result' }}</th>
                        <th class="gt-col-hdr">{{ $lang === 'th' ? 'จัดการ' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($rows) === 0)
                        <tr>
                            <td class="gt-no-data-row" colspan="28">
                                {{ $lang === 'th' ? 'ยังไม่มีเป้าหมาย — กด "+ เพิ่มลำดับ 1" ใต้ลำดับชั้น 1 เพื่อเริ่มต้น' : 'No objectives yet. Click "+ Add Level 1" under Level 1 to get started.' }}
                            </td>
                        </tr>
                    @else
                        @foreach ($rows as $obj)
                            @php
                                $krList = $obj['key_results'];
                                $objSpan = $obj['rowspan'];
                                $hasKr = count($krList) > 0;
                                $objL1Span = $hasKr ? $objSpan + 1 : 1;
                            @endphp

                            @if (! $hasKr)
                                {{-- Objective with no Key Results --}}
                                <tr>
                                    <td class="gt-l1 gt-l1-no" rowspan="1">{{ $obj['sort_no'] }}</td>
                                    <td class="gt-l1 gt-l1-title" rowspan="1">{{ $obj['title'] }}</td>
                                    <td class="gt-l1 gt-l1-detail" rowspan="1">{{ $obj['detail'] }}</td>
                                    <td class="gt-l1 gt-l1-file" rowspan="1">
                                        @if ($obj['file_path'] !== '')
                                            <a class="gt-file-link" href="{{ route('admin.goal.targets.objectives.file', ['id' => $obj['id'], 'lang' => $lang]) }}" target="_blank">
                                                {{ $obj['file_original_name'] ?: basename($obj['file_path']) }}
                                            </a>
                                        @else
                                            <span class="gt-empty-cell">—</span>
                                        @endif
                                    </td>
                                    <td class="gt-l1 gt-l1-actions" rowspan="1">
                                        <div class="gt-actions-wrap">
                                            <button type="button" class="gt-btn-edit"
                                                data-action="edit-obj"
                                                data-id="{{ $obj['id'] }}"
                                                data-title="{{ rawurlencode($obj['title']) }}"
                                                data-detail="{{ rawurlencode($obj['detail']) }}"
                                                data-file="{{ $obj['file_original_name'] }}">
                                                {{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}
                                            </button>
                                            <button type="button" class="gt-btn-del"
                                                data-action="del-obj"
                                                data-id="{{ $obj['id'] }}"
                                                data-title="{{ $obj['title'] }}">
                                                {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="gt-l2-add-cell" colspan="5">
                                        <button type="button" class="gt-btn-add-l2"
                                            data-action="add-kr"
                                            data-obj-id="{{ $obj['id'] }}"
                                            data-obj-title="{{ $obj['title'] }}">
                                            + {{ $lang === 'th' ? 'เพิ่มลำดับ 2' : 'Add Level 2' }}
                                        </button>
                                    </td>
                                    <td class="gt-empty-cell" colspan="18">—</td>
                                </tr>
                            @else
                                @foreach ($krList as $krIdx => $kr)
                                    @php
                                        $levelThreeList = $kr['level_three'];
                                        $hasLevelThree = count($levelThreeList) > 0;
                                        $krSpan = $kr['rowspan'];
                                    @endphp

                                    @if (! $hasLevelThree)
                                        <tr>
                                            @if ($krIdx === 0)
                                                <td class="gt-l1 gt-l1-no" rowspan="{{ $objL1Span }}">{{ $obj['sort_no'] }}</td>
                                                <td class="gt-l1 gt-l1-title" rowspan="{{ $objL1Span }}">{{ $obj['title'] }}</td>
                                                <td class="gt-l1 gt-l1-detail" rowspan="{{ $objL1Span }}">{{ $obj['detail'] }}</td>
                                                <td class="gt-l1 gt-l1-file" rowspan="{{ $objL1Span }}">
                                                    @if ($obj['file_path'] !== '')
                                                        <a class="gt-file-link" href="{{ route('admin.goal.targets.objectives.file', ['id' => $obj['id'], 'lang' => $lang]) }}" target="_blank">
                                                            {{ $obj['file_original_name'] ?: basename($obj['file_path']) }}
                                                        </a>
                                                    @else
                                                        <span class="gt-empty-cell">—</span>
                                                    @endif
                                                </td>
                                                <td class="gt-l1 gt-l1-actions" rowspan="{{ $objL1Span }}">
                                                    <div class="gt-actions-wrap">
                                                        <button type="button" class="gt-btn-edit"
                                                            data-action="edit-obj"
                                                            data-id="{{ $obj['id'] }}"
                                                            data-title="{{ rawurlencode($obj['title']) }}"
                                                            data-detail="{{ rawurlencode($obj['detail']) }}"
                                                            data-file="{{ $obj['file_original_name'] }}">
                                                            {{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}
                                                        </button>
                                                        <button type="button" class="gt-btn-del"
                                                            data-action="del-obj"
                                                            data-id="{{ $obj['id'] }}"
                                                            data-title="{{ $obj['title'] }}">
                                                            {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                                        </button>
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="gt-l2 gt-l2-dept">{{ $kr['dept_abbr_hr'] ?: '—' }}</td>
                                            <td class="gt-l2 gt-l2-title">{{ $kr['title'] }}</td>
                                            <td class="gt-l2 gt-l2-detail">{{ $kr['detail'] }}</td>
                                            <td class="gt-l2 gt-l2-file">
                                                @if ($kr['file_path'] !== '')
                                                    <a class="gt-file-link" href="{{ route('admin.goal.targets.key_results.file', ['id' => $kr['id'], 'lang' => $lang]) }}" target="_blank">
                                                        {{ $kr['file_original_name'] ?: basename($kr['file_path']) }}
                                                    </a>
                                                @else
                                                    <span class="gt-empty-cell">—</span>
                                                @endif
                                            </td>
                                            <td class="gt-l2 gt-l2-actions">
                                                <div class="gt-actions-wrap">
                                                    <button type="button" class="gt-btn-edit"
                                                        data-action="edit-kr"
                                                        data-id="{{ $kr['id'] }}"
                                                        data-dept="{{ $kr['dept_abbr_hr'] }}"
                                                        data-title="{{ $kr['title'] }}"
                                                        data-detail="{{ $kr['detail'] }}"
                                                        data-file="{{ $kr['file_original_name'] }}">
                                                        {{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}
                                                    </button>
                                                    <button type="button" class="gt-btn-del"
                                                        data-action="del-kr"
                                                        data-id="{{ $kr['id'] }}"
                                                        data-title="{{ $kr['title'] }}">
                                                        {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="gt-empty-cell" colspan="18">— {{ $lang === 'th' ? 'ยังไม่มีข้อมูลลำดับชั้น 3' : 'No Level 3 entries yet' }} —</td>
                                        </tr>
                                    @else
                                        @foreach ($levelThreeList as $levelThreeIdx => $levelThree)
                                            @php
                                                $l4List     = $levelThree['level_four'] ?? [];
                                                $l3Rowspan  = $levelThree['rowspan'] ?? max(1, count($l4List));
                                                $hasL4      = count($l4List) > 0;
                                            @endphp

                                            @if (! $hasL4)
                                                {{-- L3 entry with no user KPI submissions yet --}}
                                                <tr>
                                                    @if ($krIdx === 0 && $levelThreeIdx === 0)
                                                        <td class="gt-l1 gt-l1-no" rowspan="{{ $objL1Span }}">{{ $obj['sort_no'] }}</td>
                                                        <td class="gt-l1 gt-l1-title" rowspan="{{ $objL1Span }}">{{ $obj['title'] }}</td>
                                                        <td class="gt-l1 gt-l1-detail" rowspan="{{ $objL1Span }}">{{ $obj['detail'] }}</td>
                                                        <td class="gt-l1 gt-l1-file" rowspan="{{ $objL1Span }}">
                                                            @if ($obj['file_path'] !== '')
                                                                <a class="gt-file-link" href="{{ route('admin.goal.targets.objectives.file', ['id' => $obj['id'], 'lang' => $lang]) }}" target="_blank">
                                                                    {{ $obj['file_original_name'] ?: basename($obj['file_path']) }}
                                                                </a>
                                                            @else
                                                                <span class="gt-empty-cell">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="gt-l1 gt-l1-actions" rowspan="{{ $objL1Span }}">
                                                            <div class="gt-actions-wrap">
                                                                <button type="button" class="gt-btn-edit" data-action="edit-obj" data-id="{{ $obj['id'] }}" data-title="{{ rawurlencode($obj['title']) }}" data-detail="{{ rawurlencode($obj['detail']) }}" data-file="{{ $obj['file_original_name'] }}">{{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}</button>
                                                                <button type="button" class="gt-btn-del" data-action="del-obj" data-id="{{ $obj['id'] }}" data-title="{{ $obj['title'] }}">{{ $lang === 'th' ? 'ลบ' : 'Delete' }}</button>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    @if ($levelThreeIdx === 0)
                                                        <td class="gt-l2 gt-l2-dept" rowspan="{{ $krSpan }}">{{ $kr['dept_abbr_hr'] ?: '—' }}</td>
                                                        <td class="gt-l2 gt-l2-title" rowspan="{{ $krSpan }}">{{ $kr['title'] }}</td>
                                                        <td class="gt-l2 gt-l2-detail" rowspan="{{ $krSpan }}">{{ $kr['detail'] }}</td>
                                                        <td class="gt-l2 gt-l2-file" rowspan="{{ $krSpan }}">
                                                            @if ($kr['file_path'] !== '')
                                                                <a class="gt-file-link" href="{{ route('admin.goal.targets.key_results.file', ['id' => $kr['id'], 'lang' => $lang]) }}" target="_blank">{{ $kr['file_original_name'] ?: basename($kr['file_path']) }}</a>
                                                            @else
                                                                <span class="gt-empty-cell">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="gt-l2 gt-l2-actions" rowspan="{{ $krSpan }}">
                                                            <div class="gt-actions-wrap">
                                                                <button type="button" class="gt-btn-edit" data-action="edit-kr" data-id="{{ $kr['id'] }}" data-dept="{{ $kr['dept_abbr_hr'] }}" data-title="{{ $kr['title'] }}" data-detail="{{ $kr['detail'] }}" data-file="{{ $kr['file_original_name'] }}">{{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}</button>
                                                                <button type="button" class="gt-btn-del" data-action="del-kr" data-id="{{ $kr['id'] }}" data-title="{{ $kr['title'] }}">{{ $lang === 'th' ? 'ลบ' : 'Delete' }}</button>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td class="gt-l3 gt-l3-from">{{ $levelThree['from'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-from">{{ implode(', ', $levelThree['target_departments'] ?? []) ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-title">{{ $levelThree['title'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-detail">{{ $levelThree['detail'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-goal">{{ $levelThree['criteria'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-target">{{ $levelThree['target'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-unit">{{ $levelThree['unit'] ?: '—' }}</td>
                                                    <td class="gt-l3 gt-l3-actions">
                                                        <div class="gt-actions-wrap">
                                                            <button type="button" class="gt-btn-edit"
                                                                data-action="edit-l3"
                                                                data-id="{{ $levelThree['score_id'] }}"
                                                                data-title="{{ rawurlencode($levelThree['title'] ?? '') }}"
                                                                data-detail="{{ rawurlencode($levelThree['detail'] ?? '') }}"
                                                                data-from="{{ rawurlencode($levelThree['from'] ?? '') }}"
                                                                data-target-departments="{{ rawurlencode(implode(', ', $levelThree['target_departments'] ?? [])) }}"
                                                                data-operator="{{ $levelThree['criteria_operator'] ?? '' }}"
                                                                data-target="{{ $levelThree['target_value'] ?? '' }}"
                                                                data-unit-id="{{ $levelThree['kpi_unit_id'] ?? '' }}">
                                                                {{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}
                                                            </button>
                                                            <button type="button" class="gt-btn-del"
                                                                data-action="del-l3"
                                                                data-id="{{ $levelThree['score_id'] }}"
                                                                data-title="{{ $levelThree['title'] ?? '' }}">
                                                                {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="gt-empty-cell" colspan="10" style="font-style:italic;">
                                                        {{ $lang === 'th' ? '— ยังไม่มีข้อมูล KPI จากผู้ใช้ —' : '— No user KPI entries yet —' }}
                                                    </td>
                                                </tr>
                                            @else
                                                {{-- L3 entry with L4 user KPI submissions --}}
                                                @foreach ($l4List as $l4Idx => $l4)
                                                    <tr>
                                                        @if ($l4Idx === 0)
                                                            @if ($krIdx === 0 && $levelThreeIdx === 0)
                                                                <td class="gt-l1 gt-l1-no" rowspan="{{ $objL1Span }}">{{ $obj['sort_no'] }}</td>
                                                                <td class="gt-l1 gt-l1-title" rowspan="{{ $objL1Span }}">{{ $obj['title'] }}</td>
                                                                <td class="gt-l1 gt-l1-detail" rowspan="{{ $objL1Span }}">{{ $obj['detail'] }}</td>
                                                                <td class="gt-l1 gt-l1-file" rowspan="{{ $objL1Span }}">
                                                                    @if ($obj['file_path'] !== '')
                                                                        <a class="gt-file-link" href="{{ route('admin.goal.targets.objectives.file', ['id' => $obj['id'], 'lang' => $lang]) }}" target="_blank">{{ $obj['file_original_name'] ?: basename($obj['file_path']) }}</a>
                                                                    @else
                                                                        <span class="gt-empty-cell">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="gt-l1 gt-l1-actions" rowspan="{{ $objL1Span }}">
                                                                    <div class="gt-actions-wrap">
                                                                        <button type="button" class="gt-btn-edit" data-action="edit-obj" data-id="{{ $obj['id'] }}" data-title="{{ rawurlencode($obj['title']) }}" data-detail="{{ rawurlencode($obj['detail']) }}" data-file="{{ $obj['file_original_name'] }}">{{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}</button>
                                                                        <button type="button" class="gt-btn-del" data-action="del-obj" data-id="{{ $obj['id'] }}" data-title="{{ $obj['title'] }}">{{ $lang === 'th' ? 'ลบ' : 'Delete' }}</button>
                                                                    </div>
                                                                </td>
                                                            @endif
                                                            @if ($levelThreeIdx === 0)
                                                                <td class="gt-l2 gt-l2-dept" rowspan="{{ $krSpan }}">{{ $kr['dept_abbr_hr'] ?: '—' }}</td>
                                                                <td class="gt-l2 gt-l2-title" rowspan="{{ $krSpan }}">{{ $kr['title'] }}</td>
                                                                <td class="gt-l2 gt-l2-detail" rowspan="{{ $krSpan }}">{{ $kr['detail'] }}</td>
                                                                <td class="gt-l2 gt-l2-file" rowspan="{{ $krSpan }}">
                                                                    @if ($kr['file_path'] !== '')
                                                                        <a class="gt-file-link" href="{{ route('admin.goal.targets.key_results.file', ['id' => $kr['id'], 'lang' => $lang]) }}" target="_blank">{{ $kr['file_original_name'] ?: basename($kr['file_path']) }}</a>
                                                                    @else
                                                                        <span class="gt-empty-cell">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="gt-l2 gt-l2-actions" rowspan="{{ $krSpan }}">
                                                                    <div class="gt-actions-wrap">
                                                                        <button type="button" class="gt-btn-edit" data-action="edit-kr" data-id="{{ $kr['id'] }}" data-dept="{{ $kr['dept_abbr_hr'] }}" data-title="{{ $kr['title'] }}" data-detail="{{ $kr['detail'] }}" data-file="{{ $kr['file_original_name'] }}">{{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}</button>
                                                                        <button type="button" class="gt-btn-del" data-action="del-kr" data-id="{{ $kr['id'] }}" data-title="{{ $kr['title'] }}">{{ $lang === 'th' ? 'ลบ' : 'Delete' }}</button>
                                                                    </div>
                                                                </td>
                                                            @endif
                                                            <td class="gt-l3 gt-l3-from" rowspan="{{ $l3Rowspan }}">{{ $levelThree['from'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-from" rowspan="{{ $l3Rowspan }}">{{ implode(', ', $levelThree['target_departments'] ?? []) ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-title" rowspan="{{ $l3Rowspan }}">{{ $levelThree['title'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-detail" rowspan="{{ $l3Rowspan }}">{{ $levelThree['detail'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-goal" rowspan="{{ $l3Rowspan }}">{{ $levelThree['criteria'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-target" rowspan="{{ $l3Rowspan }}">{{ $levelThree['target'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-unit" rowspan="{{ $l3Rowspan }}">{{ $levelThree['unit'] ?: '—' }}</td>
                                                            <td class="gt-l3 gt-l3-actions" rowspan="{{ $l3Rowspan }}">
                                                                <div class="gt-actions-wrap">
                                                                    <button type="button" class="gt-btn-edit"
                                                                        data-action="edit-l3"
                                                                        data-id="{{ $levelThree['score_id'] }}"
                                                                        data-title="{{ rawurlencode($levelThree['title'] ?? '') }}"
                                                                        data-detail="{{ rawurlencode($levelThree['detail'] ?? '') }}"
                                                                        data-from="{{ rawurlencode($levelThree['from'] ?? '') }}"
                                                                        data-target-departments="{{ rawurlencode(implode(', ', $levelThree['target_departments'] ?? [])) }}"
                                                                        data-operator="{{ $levelThree['criteria_operator'] ?? '' }}"
                                                                        data-target="{{ $levelThree['target_value'] ?? '' }}"
                                                                        data-unit-id="{{ $levelThree['kpi_unit_id'] ?? '' }}">
                                                                        {{ $lang === 'th' ? 'แก้ไข' : 'Edit' }}
                                                                    </button>
                                                                    <button type="button" class="gt-btn-del"
                                                                        data-action="del-l3"
                                                                        data-id="{{ $levelThree['score_id'] }}"
                                                                        data-title="{{ $levelThree['title'] ?? '' }}">
                                                                        {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        {{-- ── Level 4 row ── --}}
                                                        @php
                                                            $l4Dept    = $l4['dept'] ?? '—';
                                                            $l4SelDept = $l4['selected_dept'] ?? '—';
                                                            $l4Same    = ($l4SelDept === '—' || $l4SelDept === '-' || $l4SelDept === '' || $l4SelDept === $l4Dept);
                                                        @endphp
                                                        @if($l4Same)
                                                            <td class="gt-l4 gt-l4-from" colspan="2" style="text-align:center;font-weight:600;">{{ $l4Dept }}</td>
                                                        @else
                                                            <td class="gt-l4 gt-l4-from" style="text-align:center;font-weight:600;">{{ $l4Dept }}</td>
                                                            <td class="gt-l4" style="text-align:center;color:#4b5563;font-size:11px;">{{ $l4SelDept }}</td>
                                                        @endif
                                                        <td class="gt-l4" style="font-family:monospace;font-size:11px;">{{ $l4['code'] ?? '—' }}</td>
                                                        <td class="gt-l4 gt-l4-title">{{ $l4['name'] ?? '—' }}</td>
                                                        <td class="gt-l4 gt-l4-detail">{{ $l4['objective'] ?? '—' }}</td>
                                                        <td class="gt-l4 gt-l4-detail">{{ $l4['detail'] ?? '—' }}</td>
                                                        <td class="gt-l4 gt-l4-criteria">{{ $l4['target'] ?? '—' }}</td>
                                                        <td class="gt-l4" style="text-align:center;">
                                                            <button type="button" class="gt-btn-view-months"
                                                                data-role="open-l4-monthly"
                                                                data-root-id="{{ $l4['root_id'] ?? 0 }}">
                                                                {{ $lang === 'th' ? 'ดู' : 'View' }}
                                                            </button>
                                                        </td>
                                                        <td class="gt-l4" style="text-align:center;font-size:11px;color:#0f766e;font-weight:700;">{{ $l4['result'] ?? '—' }}</td>
                                                        <td class="gt-l4" style="text-align:center;">
                                                            <button type="button" class="gt-btn-del"
                                                                data-action="del-kpi-report"
                                                                data-id="{{ $l4['root_id'] ?? 0 }}"
                                                                data-title="{{ $l4['objective'] ?? '' }}">
                                                                {{ $lang === 'th' ? 'ลบ' : 'Delete' }}
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                                <tr>
                                    <td class="gt-l2-add-cell" colspan="5">
                                        <button type="button" class="gt-btn-add-l2"
                                            data-action="add-kr"
                                            data-obj-id="{{ $obj['id'] }}"
                                            data-obj-title="{{ $obj['title'] }}">
                                            + {{ $lang === 'th' ? 'เพิ่มลำดับ 2' : 'Add Level 2' }}
                                        </button>
                                    </td>
                                    <td class="gt-empty-cell" colspan="18">—</td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    <tr class="gt-add-level1-row">
                        <td class="gt-l1-add-cell" colspan="5">
                            <button type="button" class="gt-btn-add" id="btn-open-add-obj"
                                aria-label="{{ $lang === 'th' ? 'เพิ่มลำดับ 1' : 'Add Level 1' }}"
                                title="{{ $lang === 'th' ? 'เพิ่มลำดับ 1' : 'Add Level 1' }}">
                                + {{ $lang === 'th' ? 'เพิ่มลำดับ 1' : 'Add Level 1' }}
                            </button>
                        </td>
                        <td class="gt-empty-cell" colspan="23">—</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

    </section>

    {{-- ── Modal: Add / Edit Objective (Level 1) ── --}}
    <div class="gt-modal-backdrop" id="modal-obj">
        <div class="gt-modal-panel">
            <h2 class="gt-modal-title" id="modal-obj-title">{{ $lang === 'th' ? 'เพิ่มลำดับชั้น 1' : 'Add Level 1 Objective' }}</h2>
            <form method="POST" enctype="multipart/form-data" id="form-obj">
                @csrf
                <div class="gt-form-row">
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }} *</label>
                        <textarea class="gt-textarea gt-title-textarea" name="title" id="obj-title" required maxlength="500" rows="3" placeholder="{{ $lang === 'th' ? 'ชื่อเป้าหมาย OKR สามารถกด Enter เพื่อขึ้นบรรทัดใหม่' : 'OKR objective name. Press Enter for a new line.' }}"></textarea>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</label>
                        <textarea class="gt-textarea" name="detail" id="obj-detail" maxlength="5000" placeholder="{{ $lang === 'th' ? 'อธิบายเป้าหมาย...' : 'Describe the objective...' }}"></textarea>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'ไฟล์แนบ' : 'Attachment' }}</label>
                        <div id="obj-current-file-wrap" class="gt-current-file-wrap" style="display:none;">
                            <span id="obj-current-file-name"></span>
                            <button type="button" class="gt-remove-file-btn" id="btn-remove-obj-file">✕ {{ $lang === 'th' ? 'ลบไฟล์' : 'Remove' }}</button>
                            <input type="hidden" name="remove_file" id="obj-remove-file-input" value="0">
                        </div>
                        <input class="gt-input" type="file" name="file" id="obj-file" accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg,.csv">
                        <span class="gt-file-hint">PDF, Excel, Word, Image — {{ $lang === 'th' ? 'ไม่เกิน 20MB' : 'max 20MB' }}</span>
                    </div>
                </div>
                <div class="gt-modal-actions">
                    <button type="button" class="gt-btn-cancel" id="btn-cancel-obj">{{ $lang === 'th' ? 'ยกเลิก' : 'Cancel' }}</button>
                    <button type="submit" class="gt-btn-save">{{ $lang === 'th' ? 'บันทึก' : 'Save' }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: Add / Edit Key Result (Level 2) ── --}}
    <div class="gt-modal-backdrop" id="modal-kr">
        <div class="gt-modal-panel">
            <h2 class="gt-modal-title" id="modal-kr-title">{{ $lang === 'th' ? 'เพิ่มลำดับชั้น 2' : 'Add Level 2 Key Result' }}</h2>
            <p id="modal-kr-obj-label" style="margin:0 0 14px;font-size:12px;color:#6b7280;"></p>
            <form method="POST" enctype="multipart/form-data" id="form-kr">
                @csrf
                <input type="hidden" name="okr_objective_id" id="kr-obj-id" value="">
                <div class="gt-form-row">
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'แผนก (dept_abbr_hr)' : 'Department (dept_abbr_hr)' }} *</label>
                        <select class="gt-select" name="dept_abbr_hr" id="kr-dept" required>
                            <option value="">— {{ $lang === 'th' ? 'เลือกแผนก' : 'Select department' }} —</option>
                            @foreach ($deptOptions as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'หัวข้อ Key Result' : 'Key Result Title' }} *</label>
                        <input class="gt-input" type="text" name="title" id="kr-title" required maxlength="500" placeholder="{{ $lang === 'th' ? 'ชื่อ Key Result' : 'Key Result name' }}">
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</label>
                        <textarea class="gt-textarea" name="detail" id="kr-detail" maxlength="5000"></textarea>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'ไฟล์แนบ' : 'Attachment' }}</label>
                        <div id="kr-current-file-wrap" class="gt-current-file-wrap" style="display:none;">
                            <span id="kr-current-file-name"></span>
                            <button type="button" class="gt-remove-file-btn" id="btn-remove-kr-file">✕ {{ $lang === 'th' ? 'ลบไฟล์' : 'Remove' }}</button>
                            <input type="hidden" name="remove_file" id="kr-remove-file-input" value="0">
                        </div>
                        <input class="gt-input" type="file" name="file" id="kr-file" accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg,.csv">
                        <span class="gt-file-hint">PDF, Excel, Word, Image — {{ $lang === 'th' ? 'ไม่เกิน 20MB' : 'max 20MB' }}</span>
                    </div>
                </div>
                <div class="gt-modal-actions">
                    <button type="button" class="gt-btn-cancel" id="btn-cancel-kr">{{ $lang === 'th' ? 'ยกเลิก' : 'Cancel' }}</button>
                    <button type="submit" class="gt-btn-save">{{ $lang === 'th' ? 'บันทึก' : 'Save' }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: Edit Level 3 Target ── --}}
    <div class="gt-modal-backdrop" id="modal-l3">
        <div class="gt-modal-panel">
            <h2 class="gt-modal-title">{{ $lang === 'th' ? 'แก้ไขเป้าหมายลำดับชั้น 3' : 'Edit Level 3 Target' }}</h2>
            <div class="kpi-monthly-meta" style="padding:0 0 14px;margin-bottom:14px;">
                <p><b>{{ $lang === 'th' ? 'จาก' : 'From' }}:</b> <span id="l3-from">-</span></p>
                <p><b>{{ $lang === 'th' ? 'แผนกเป้าหมาย' : 'Target Department' }}:</b> <span id="l3-target-departments">-</span></p>
            </div>
            <form method="POST" id="form-l3">
                @csrf
                <div class="gt-form-row">
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }} *</label>
                        <textarea class="gt-textarea gt-title-textarea" name="objective" id="l3-title" required maxlength="60000" rows="3"></textarea>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }} *</label>
                        <textarea class="gt-textarea" name="detail" id="l3-detail" required maxlength="60000" rows="4"></textarea>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'เกณฑ์' : 'Criteria' }} *</label>
                        <select class="gt-select" name="criteria_operator" id="l3-operator" required>
                            @foreach (['>' => '>', '>=' => '≥', '<' => '<', '<=' => '≤', '=' => '=', '!=' => '≠'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'เป้าหมาย' : 'Target' }} *</label>
                        <input class="gt-input" type="number" step="any" name="target_value" id="l3-target" required>
                    </div>
                    <div class="gt-field">
                        <label class="gt-label">{{ $lang === 'th' ? 'หน่วย' : 'Unit' }} *</label>
                        <select class="gt-select" name="kpi_unit_id" id="l3-unit" required>
                            @foreach ($kpiUnits as $unit)
                                @php
                                    $unitLabel = $lang === 'th'
                                        ? (trim((string) $unit->name_th) ?: (trim((string) $unit->name_en) ?: $unit->code))
                                        : (trim((string) $unit->name_en) ?: (trim((string) $unit->name_th) ?: $unit->code));
                                @endphp
                                <option value="{{ $unit->id }}">{{ $unitLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="gt-modal-actions">
                    <button type="button" class="gt-btn-cancel" id="btn-cancel-l3">{{ $lang === 'th' ? 'ยกเลิก' : 'Cancel' }}</button>
                    <button type="submit" class="gt-btn-save">{{ $lang === 'th' ? 'บันทึก' : 'Save' }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: Level 4 Monthly KPI Detail (copied from kpi-summary-dept) ── --}}
    <div id="gt-l4-monthly-modal" class="kpi-monthly-modal" aria-hidden="true">
        <div class="kpi-monthly-panel" role="dialog" aria-modal="true" aria-labelledby="gt-l4-monthly-title">
            <div class="kpi-monthly-head">
                <h3 id="gt-l4-monthly-title" class="kpi-monthly-title">{{ $lang === 'th' ? 'สรุปผล KPI รายเดือน' : 'KPI Monthly Summary' }}</h3>
                <button type="button" class="kpi-monthly-close" data-role="close-l4-monthly" aria-label="{{ $lang === 'th' ? 'ปิด' : 'Close' }}">&times;</button>
            </div>
            <div class="kpi-monthly-meta">
                <p><b>{{ $lang === 'th' ? 'พนักงาน' : 'Employee' }}:</b> <span data-role="l4-monthly-employee">-</span></p>
                <p><b>{{ $lang === 'th' ? 'วัตถุประสงค์' : 'Objective' }}:</b> <span data-role="l4-monthly-objective">-</span></p>
                <p><b>{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}:</b> <span data-role="l4-monthly-detail">-</span></p>
            </div>
            <div class="kpi-monthly-table-wrap">
                <table class="kpi-monthly-table">
                    <thead>
                        <tr>
                            <th style="width:180px;">{{ $lang === 'th' ? 'เดือน' : 'Month' }}</th>
                            <th style="width:120px;">{{ $lang === 'th' ? 'คะแนน' : 'Score' }}</th>
                            <th style="width:220px;">{{ $lang === 'th' ? 'ไฟล์' : 'Files' }}</th>
                            <th style="width:220px;">Action Plan</th>
                            <th>{{ $lang === 'th' ? 'เกณฑ์' : 'Criteria' }}</th>
                            <th style="width:170px;">{{ $lang === 'th' ? 'ผลลัพธ์' : 'Result' }}</th>
                            <th style="width:100px;">{{ $lang === 'th' ? 'จัดการ' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody data-role="l4-monthly-body"></tbody>
                </table>
            </div>
            <div class="kpi-monthly-actions">
                <button type="button" class="kpi-monthly-close-btn" data-role="close-l4-monthly">{{ $lang === 'th' ? 'ปิด' : 'Close' }}</button>
            </div>
        </div>
    </div>

    {{-- ── Modal: Confirm Delete ── --}}
    <div class="gt-modal-backdrop" id="modal-del">
        <div class="gt-modal-panel" style="width: min(96vw, 420px)">
            <h2 class="gt-modal-title">{{ $lang === 'th' ? 'ยืนยันการลบ' : 'Confirm Delete' }}</h2>
            <p class="gt-confirm-text" id="modal-del-text"></p>
            <form method="POST" id="form-del">
                @csrf
                <div class="gt-modal-actions">
                    <button type="submit" class="gt-btn-danger">{{ $lang === 'th' ? 'ยืนยัน' : 'Confirm' }}</button>
                    <button type="button" class="gt-btn-cancel" id="btn-cancel-del">{{ $lang === 'th' ? 'ยกเลิก' : 'Cancel' }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (() => {
        const lang = @json($lang);
        const storeObjUrl = @json(route('admin.goal.targets.objectives.store', ['lang' => $lang]));
        const storeKrUrl  = @json(route('admin.goal.targets.key_results.store', ['lang' => $lang]));
        const baseUrl     = @json(url('/admin/goal-targets'));
        const langQ       = '?lang=' + lang;

        // ── helpers ──
        const $ = (id) => document.getElementById(id);
        const show = (el) => el.classList.add('show');
        const hide = (el) => el.classList.remove('show');

        // ── Table view tools ──
        const tableWrap = $('gt-table-wrap');
        const tableLayer = $('gt-table-scale-layer');
        const tableEl = $('gt-goal-table');
        const zoomOutBtn = $('gt-zoom-out');
        const zoomInBtn = $('gt-zoom-in');
        const zoomValueEl = $('gt-zoom-value');
        const panToggleBtn = $('gt-pan-toggle');
        const ZOOM_MIN = 0.6;
        const ZOOM_MAX = 1.8;
        const ZOOM_STEP = 0.1;
        let tableZoom = 1;
        let panMode = false;
        let isPanning = false;
        let panStartX = 0;
        let panStartY = 0;
        let panStartScrollLeft = 0;
        let panStartScrollTop = 0;

        const clampZoom = (value) => {
            const next = Number(value);
            if (!Number.isFinite(next)) return 1;
            return Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, next));
        };

        const syncZoomLayout = () => {
            if (!tableLayer) return;
            tableLayer.style.zoom = String(tableZoom);
            if (zoomValueEl) {
                zoomValueEl.textContent = `${Math.round(tableZoom * 100)}%`;
            }
            if (zoomOutBtn) {
                zoomOutBtn.disabled = tableZoom <= ZOOM_MIN + 0.001;
            }
            if (zoomInBtn) {
                zoomInBtn.disabled = tableZoom >= ZOOM_MAX - 0.001;
            }
        };

        const setTableZoom = (nextZoom) => {
            if (!tableWrap || !tableLayer) return;
            const ratio = clampZoom(nextZoom) / tableZoom;
            tableZoom = clampZoom(nextZoom);
            const prevScrollLeft = tableWrap.scrollLeft;
            const prevScrollTop  = tableWrap.scrollTop;
            syncZoomLayout();
            tableWrap.scrollLeft = prevScrollLeft * ratio;
            tableWrap.scrollTop  = prevScrollTop  * ratio;
        };

        const setPanMode = (enabled) => {
            panMode = Boolean(enabled);
            if (tableWrap) {
                tableWrap.classList.toggle('is-pan-mode', panMode);
            }
            if (panToggleBtn) {
                panToggleBtn.classList.toggle('is-active', panMode);
                panToggleBtn.setAttribute('aria-pressed', panMode ? 'true' : 'false');
            }
        };

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => setTableZoom(tableZoom - ZOOM_STEP));
        }
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => setTableZoom(tableZoom + ZOOM_STEP));
        }
        if (panToggleBtn) {
            panToggleBtn.addEventListener('click', () => setPanMode(!panMode));
        }
        if (tableWrap) {
            tableWrap.addEventListener('pointerdown', (e) => {
                if (!panMode || e.button !== 0) return;
                const targetEl = e.target instanceof Element ? e.target : null;
                if (targetEl && targetEl.closest('button,a,input,select,textarea,label')) return;

                isPanning = true;
                panStartX = e.clientX;
                panStartY = e.clientY;
                panStartScrollLeft = tableWrap.scrollLeft;
                panStartScrollTop = tableWrap.scrollTop;
                tableWrap.classList.add('is-dragging');
                tableWrap.setPointerCapture(e.pointerId);
                e.preventDefault();
            });

            tableWrap.addEventListener('pointermove', (e) => {
                if (!isPanning) return;

                tableWrap.scrollLeft = panStartScrollLeft - (e.clientX - panStartX);
                tableWrap.scrollTop = panStartScrollTop - (e.clientY - panStartY);
            });

            const stopPan = (e) => {
                if (!isPanning) return;

                isPanning = false;
                tableWrap.classList.remove('is-dragging');
                if (tableWrap.hasPointerCapture(e.pointerId)) {
                    tableWrap.releasePointerCapture(e.pointerId);
                }
            };

            tableWrap.addEventListener('pointerup', stopPan);
            tableWrap.addEventListener('pointercancel', stopPan);
            tableWrap.addEventListener('lostpointercapture', () => {
                isPanning = false;
                tableWrap.classList.remove('is-dragging');
            });
        }
        syncZoomLayout();

        // ── Dept filter (Level 2) ──
        const filterResetBtn = $('gt-filter-reset');
        const levelTwoFilterSelect = $('gt-l2-dept-filter');
        const levelThreeFilterSelect = $('gt-l3-dept-filter');
        const applyHierarchyFilter = (level, value = '') => {
            const selected = String(value || '').trim().toUpperCase();
            const url = new URL(window.location.href);
            url.searchParams.delete('dept_filter');
            url.searchParams.delete('l3_dept_filter');

            if (level === 'l2' && selected !== '') {
                url.searchParams.set('dept_filter', selected);
            }
            if (level === 'l3' && selected !== '') {
                url.searchParams.set('l3_dept_filter', selected);
            }

            window.location.href = url.toString();
        };

        if (filterResetBtn) {
            filterResetBtn.addEventListener('click', () => applyHierarchyFilter('all'));
        }
        if (levelTwoFilterSelect) {
            levelTwoFilterSelect.addEventListener('change', () => {
                applyHierarchyFilter('l2', levelTwoFilterSelect.value);
            });
        }
        if (levelThreeFilterSelect) {
            levelThreeFilterSelect.addEventListener('change', () => {
                applyHierarchyFilter('l3', levelThreeFilterSelect.value);
            });
        }

        // ── Objective modal ──
        const modalObj   = $('modal-obj');
        const formObj    = $('form-obj');
        const objTitle   = $('obj-title');
        const objDetail  = $('obj-detail');
        const objFile    = $('obj-file');
        const objCurWrap = $('obj-current-file-wrap');
        const objCurName = $('obj-current-file-name');
        const objRemoveInput = $('obj-remove-file-input');

        function openAddObj() {
            $('modal-obj-title').textContent = lang === 'th' ? 'เพิ่มลำดับชั้น 1' : 'Add Level 1 Objective';
            formObj.action = storeObjUrl;
            objTitle.value = '';
            objDetail.value = '';
            objFile.value = '';
            objCurWrap.style.display = 'none';
            objRemoveInput.value = '0';
            show(modalObj);
            objTitle.focus();
        }

        function openEditObj(btn) {
            const id     = btn.dataset.id;
            const title  = decodeURIComponent(btn.dataset.title || '');
            const detail = decodeURIComponent(btn.dataset.detail || '');
            const file   = btn.dataset.file;

            $('modal-obj-title').textContent = lang === 'th' ? 'แก้ไขลำดับชั้น 1' : 'Edit Level 1 Objective';
            formObj.action = baseUrl + '/objectives/' + id + '/update' + langQ;
            objTitle.value  = title || '';
            objDetail.value = detail || '';
            objFile.value   = '';
            objRemoveInput.value = '0';

            if (file && file !== '') {
                objCurName.textContent = file;
                objCurWrap.style.display = 'flex';
            } else {
                objCurWrap.style.display = 'none';
            }

            show(modalObj);
            objTitle.focus();
        }

        $('btn-open-add-obj') && $('btn-open-add-obj').addEventListener('click', openAddObj);
        $('btn-cancel-obj').addEventListener('click', () => hide(modalObj));
        $('btn-remove-obj-file').addEventListener('click', () => {
            objRemoveInput.value = '1';
            objCurWrap.style.display = 'none';
        });

        // ── Key Result modal ──
        const modalKr    = $('modal-kr');
        const formKr     = $('form-kr');
        const krObjId    = $('kr-obj-id');
        const krDept     = $('kr-dept');
        const krTitle    = $('kr-title');
        const krDetail   = $('kr-detail');
        const krFile     = $('kr-file');
        const krCurWrap  = $('kr-current-file-wrap');
        const krCurName  = $('kr-current-file-name');
        const krRemoveInput = $('kr-remove-file-input');

        function openAddKr(btn) {
            const objId    = btn.dataset.objId;
            const objLabel = btn.dataset.objTitle;

            $('modal-kr-title').textContent = lang === 'th' ? 'เพิ่มลำดับชั้น 2' : 'Add Level 2 Key Result';
            $('modal-kr-obj-label').textContent = (lang === 'th' ? 'ใต้เป้าหมาย: ' : 'Under: ') + (objLabel || '');
            formKr.action  = storeKrUrl;
            krObjId.value  = objId;
            krDept.value   = '';
            krTitle.value  = '';
            krDetail.value = '';
            krFile.value   = '';
            krCurWrap.style.display = 'none';
            krRemoveInput.value = '0';
            show(modalKr);
            krTitle.focus();
        }

        function openEditKr(btn) {
            const id     = btn.dataset.id;
            const dept   = btn.dataset.dept;
            const title  = btn.dataset.title;
            const detail = btn.dataset.detail;
            const file   = btn.dataset.file;

            $('modal-kr-title').textContent = lang === 'th' ? 'แก้ไขลำดับชั้น 2' : 'Edit Level 2 Key Result';
            $('modal-kr-obj-label').textContent = '';
            formKr.action  = baseUrl + '/key-results/' + id + '/update' + langQ;
            krObjId.value  = '';
            krDept.value   = dept || '';
            krTitle.value  = title || '';
            krDetail.value = detail || '';
            krFile.value   = '';
            krRemoveInput.value = '0';

            if (file && file !== '') {
                krCurName.textContent = file;
                krCurWrap.style.display = 'flex';
            } else {
                krCurWrap.style.display = 'none';
            }

            show(modalKr);
            krTitle.focus();
        }

        $('btn-cancel-kr').addEventListener('click', () => hide(modalKr));
        $('btn-remove-kr-file').addEventListener('click', () => {
            krRemoveInput.value = '1';
            krCurWrap.style.display = 'none';
        });

        // ── Level 3 target modal ──
        const modalL3 = $('modal-l3');
        const formL3 = $('form-l3');
        if (modalL3 && modalL3.parentElement !== document.body) {
            document.body.appendChild(modalL3);
        }
        const l3Title = $('l3-title');
        const l3Detail = $('l3-detail');
        const l3From = $('l3-from');
        const l3TargetDepartments = $('l3-target-departments');
        const l3Operator = $('l3-operator');
        const l3Target = $('l3-target');
        const l3Unit = $('l3-unit');

        function openEditL3(btn) {
            formL3.action = baseUrl + '/level-three/' + btn.dataset.id + '/update' + langQ;
            l3Title.value = decodeURIComponent(btn.dataset.title || '');
            l3Detail.value = decodeURIComponent(btn.dataset.detail || '');
            l3From.textContent = decodeURIComponent(btn.dataset.from || '') || '-';
            l3TargetDepartments.textContent = decodeURIComponent(btn.dataset.targetDepartments || '') || '-';
            l3Operator.value = btn.dataset.operator || '';
            l3Target.value = btn.dataset.target || '';
            l3Unit.value = btn.dataset.unitId || '';
            show(modalL3);
            l3Title.focus();
        }

        $('btn-cancel-l3').addEventListener('click', () => hide(modalL3));

        // ── Delete modal ──
        const modalDel  = $('modal-del');
        const formDel   = $('form-del');
        if (modalDel && modalDel.parentElement !== document.body) {
            document.body.appendChild(modalDel);
        }

        function openDelObj(btn) {
            const id    = btn.dataset.id;
            const title = btn.dataset.title;
            $('modal-del-text').textContent = (lang === 'th'
                ? 'ต้องการลบเป้าหมาย "' + title + '" และข้อมูลทั้งหมดใต้มันใช่ไหม?'
                : 'Delete objective "' + title + '" and all its key results?');
            formDel.action = baseUrl + '/objectives/' + id + '/delete' + langQ;
            show(modalDel);
        }

        function openDelKr(btn) {
            const id    = btn.dataset.id;
            const title = btn.dataset.title;
            $('modal-del-text').textContent = (lang === 'th'
                ? 'ต้องการลบ Key Result "' + title + '" ใช่ไหม?'
                : 'Delete key result "' + title + '"?');
            formDel.action = baseUrl + '/key-results/' + id + '/delete' + langQ;
            show(modalDel);
        }

        function openDelL3(btn) {
            const id = btn.dataset.id;
            const title = btn.dataset.title;
            $('modal-del-text').textContent = (lang === 'th'
                ? 'ยืนยันลบเป้าหมายลำดับชั้น 3 "' + title + '" พร้อมรายงาน KPI ทั้งหมดที่อยู่ใต้เป้าหมายนี้?'
                : 'Delete Level 3 target "' + title + '" and all KPI reports under it?');
            formDel.action = baseUrl + '/level-three/' + id + '/delete' + langQ;
            show(modalDel);
        }

        function openDelKpiReport(btn) {
            const id    = btn.dataset.id;
            const title = btn.dataset.title;
            $('modal-del-text').textContent = (lang === 'th'
                ? 'ยืนยันลบรายงาน KPI "' + title + '" พร้อมข้อมูลรายเดือนทั้งหมด และนำรายงานนี้ออกจากการคำนวณ KPI/OKR?'
                : 'Delete KPI report "' + title + '", all monthly data, and remove it from KPI/OKR calculations?');
            formDel.action = baseUrl + '/kpi-reports/' + id + '/delete' + langQ;
            show(modalDel);
        }

        function openDelKpiMonth(btn) {
            const rootId = btn.dataset.rootId;
            const monthId = btn.dataset.id;
            const title = btn.dataset.title;
            $('modal-del-text').textContent = (lang === 'th'
                ? 'ยืนยันลบข้อมูล KPI เดือน "' + title + '" และนำเดือนนี้ออกจากการคำนวณ KPI/OKR?'
                : 'Delete KPI data for "' + title + '" and remove this month from KPI/OKR calculations?');
            formDel.action = baseUrl + '/kpi-reports/' + rootId + '/months/' + monthId + '/delete' + langQ;
            show(modalDel);
        }

        $('btn-cancel-del').addEventListener('click', () => hide(modalDel));

        // close delete-confirm modal on backdrop click only (not data-entry modals)
        modalDel.addEventListener('click', (e) => { if (e.target === modalDel) hide(modalDel); });

        // prevent backdrop clicks on data-entry modals from closing them
        [modalObj, modalKr, modalL3].forEach((m) => {
            m.querySelector('.gt-modal-panel').addEventListener('click', (e) => e.stopPropagation());
        });

        // ── Scroll preservation across POST-redirect ──
        const SCROLL_KEY = 'gt-scroll-y';
        [formObj, formKr, formL3, formDel].forEach((f) => {
            f.addEventListener('submit', () => sessionStorage.setItem(SCROLL_KEY, window.scrollY));
        });
        window.addEventListener('load', () => {
            const savedY = sessionStorage.getItem(SCROLL_KEY);
            if (savedY !== null) {
                sessionStorage.removeItem(SCROLL_KEY);
                window.scrollTo({ top: parseInt(savedY, 10), behavior: 'instant' });
            }
        });

        // ── Level 4 monthly AJAX modal (same system as สรุปรายงาน) ──
        (() => {
            const l4ModalEl = document.getElementById('gt-l4-monthly-modal');
            if (!l4ModalEl) return;

            if (l4ModalEl.parentElement !== document.body) {
                document.body.appendChild(l4ModalEl);
            }

            const monthlySummaryUrlTemplate = @json($monthlySummaryUrlTemplate);
            const l4BodyEl      = l4ModalEl.querySelector('[data-role="l4-monthly-body"]');
            const l4EmployeeEl  = l4ModalEl.querySelector('[data-role="l4-monthly-employee"]');
            const l4ObjectiveEl = l4ModalEl.querySelector('[data-role="l4-monthly-objective"]');
            const l4DetailEl    = l4ModalEl.querySelector('[data-role="l4-monthly-detail"]');
            const l4TitleEl     = document.getElementById('gt-l4-monthly-title');
            let activeL4RootId  = 0;

            const l4Texts = {
                loading:    lang === 'th' ? 'กำลังโหลดข้อมูล...' : 'Loading...',
                error:      lang === 'th' ? 'ไม่สามารถโหลดข้อมูลได้' : 'Unable to load data.',
                title:      lang === 'th' ? 'สรุปผล KPI รายเดือน' : 'KPI Monthly Summary',
                netAverage: lang === 'th' ? 'คะแนนเฉลี่ยสุทธิ' : 'Net Average Score',
            };

            const setL4ModalOpen = (open) => {
                l4ModalEl.classList.toggle('show', open);
                l4ModalEl.setAttribute('aria-hidden', open ? 'false' : 'true');
                document.body.style.overflow = open ? 'hidden' : '';
            };

            const renderL4Message = (text) => {
                if (!l4BodyEl) return;
                l4BodyEl.innerHTML = '';
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'kpi-monthly-empty';
                cell.textContent = text;
                row.appendChild(cell);
                l4BodyEl.appendChild(row);
            };

            const truncateLabel = (value, max = 30) => {
                const text = typeof value === 'string' ? value.trim() : '';
                if (text === '') return '-';
                return text.length <= max ? text : `${text.slice(0, max)}....`;
            };

            const renderL4Months = (months, netAvgScore, netAvgResult) => {
                if (!l4BodyEl) return;
                l4BodyEl.innerHTML = '';
                if (!Array.isArray(months) || months.length === 0) {
                    renderL4Message(l4Texts.error);
                    return;
                }

                months.forEach((month) => {
                    const row = document.createElement('tr');

                    const mCell = document.createElement('td');
                    mCell.textContent = month && month.month_label ? String(month.month_label) : '-';
                    row.appendChild(mCell);

                    const sCell = document.createElement('td');
                    sCell.className = 'text-center';
                    sCell.textContent = month && month.score !== undefined ? String(month.score) : '-';
                    row.appendChild(sCell);

                    const fCell = document.createElement('td');
                    fCell.className = 'kpi-monthly-files-cell';
                    const files = month && Array.isArray(month.files) ? month.files : [];
                    if (files.length === 0) {
                        fCell.textContent = '-';
                    } else {
                        const ul = document.createElement('ul');
                        ul.className = 'kpi-monthly-file-list';
                        files.forEach((file) => {
                            const url = file && typeof file.url === 'string' ? file.url.trim() : '';
                            if (url === '') return;
                            const label = file && typeof file.name === 'string' && file.name.trim() !== '' ? file.name.trim() : '-';
                            const li = document.createElement('li');
                            const a  = document.createElement('a');
                            a.className = 'kpi-monthly-file-link';
                            a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer';
                            a.textContent = truncateLabel(label); a.title = label;
                            li.appendChild(a); ul.appendChild(li);
                        });
                        fCell.appendChild(ul.children.length ? ul : document.createTextNode('-'));
                    }
                    row.appendChild(fCell);

                    const apCell = document.createElement('td');
                    apCell.className = 'kpi-monthly-files-cell';
                    const apFiles = month && Array.isArray(month.action_plan_files) ? month.action_plan_files : [];
                    if (apFiles.length === 0) {
                        apCell.textContent = '-';
                    } else {
                        const ul = document.createElement('ul');
                        ul.className = 'kpi-monthly-file-list';
                        apFiles.forEach((file) => {
                            const url = file && typeof file.url === 'string' ? file.url.trim() : '';
                            if (url === '') return;
                            const label = file && typeof file.name === 'string' && file.name.trim() !== '' ? file.name.trim() : '-';
                            const li = document.createElement('li');
                            const a  = document.createElement('a');
                            a.className = 'kpi-monthly-file-link';
                            a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer';
                            a.textContent = truncateLabel(label); a.title = label;
                            li.appendChild(a); ul.appendChild(li);
                        });
                        apCell.appendChild(ul.children.length ? ul : document.createTextNode('-'));
                    }
                    row.appendChild(apCell);

                    const cCell = document.createElement('td');
                    cCell.textContent = month && month.criteria !== undefined ? String(month.criteria) : '-';
                    row.appendChild(cCell);

                    const rCell = document.createElement('td');
                    rCell.className = `text-center ${month && month.result_class ? String(month.result_class) : 'kpi-summary-result-pending'}`;
                    rCell.textContent = month && month.result !== undefined ? String(month.result) : '-';
                    row.appendChild(rCell);

                    const actionCell = document.createElement('td');
                    actionCell.className = 'text-center';
                    const scoreId = Number(month && month.score_id ? month.score_id : 0);
                    if (month && month.can_delete && Number.isFinite(scoreId) && scoreId > 0 && activeL4RootId > 0) {
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'gt-btn-del';
                        deleteBtn.dataset.action = 'del-kpi-month';
                        deleteBtn.dataset.rootId = String(activeL4RootId);
                        deleteBtn.dataset.id = String(scoreId);
                        deleteBtn.dataset.title = month.month_label ? String(month.month_label) : '-';
                        deleteBtn.textContent = lang === 'th' ? 'ลบ' : 'Delete';
                        actionCell.appendChild(deleteBtn);
                    } else {
                        actionCell.textContent = '-';
                    }
                    row.appendChild(actionCell);

                    l4BodyEl.appendChild(row);
                });

                // Net average row
                const avgRow = document.createElement('tr');
                avgRow.className = 'kpi-monthly-net-average';
                [l4Texts.netAverage, typeof netAvgScore !== 'undefined' ? String(netAvgScore) : '-', '', '', '', typeof netAvgResult !== 'undefined' ? String(netAvgResult) : '-', ''].forEach((text, i) => {
                    const td = document.createElement('td');
                    if (i === 1 || i === 5) td.className = 'text-center';
                    td.textContent = text;
                    avgRow.appendChild(td);
                });
                l4BodyEl.appendChild(avgRow);
            };

            const openL4Monthly = async (rootId) => {
                const nid = Number(rootId || 0);
                if (!Number.isFinite(nid) || nid < 1) return;
                activeL4RootId = nid;

                if (l4EmployeeEl)  l4EmployeeEl.textContent  = '-';
                if (l4ObjectiveEl) l4ObjectiveEl.textContent = '-';
                if (l4DetailEl)    l4DetailEl.textContent    = '-';
                if (l4TitleEl)     l4TitleEl.textContent     = l4Texts.title;

                setL4ModalOpen(true);
                renderL4Message(l4Texts.loading);

                try {
                    const endpoint = monthlySummaryUrlTemplate.replace('__ROOT__', String(nid));
                    const response = await fetch(`${endpoint}?lang=${encodeURIComponent(lang)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(l4Texts.error);
                    const payload = await response.json();
                    if (l4TitleEl)     l4TitleEl.textContent     = payload && payload.title     ? String(payload.title)     : l4Texts.title;
                    if (l4EmployeeEl)  l4EmployeeEl.textContent  = payload && payload.employee  ? String(payload.employee)  : '-';
                    if (l4ObjectiveEl) l4ObjectiveEl.textContent = payload && payload.objective ? String(payload.objective) : '-';
                    if (l4DetailEl)    l4DetailEl.textContent    = payload && payload.detail    ? String(payload.detail)    : '-';
                    renderL4Months(
                        payload ? payload.months : [],
                        payload && typeof payload.net_average_score  !== 'undefined' ? payload.net_average_score  : '-',
                        payload && typeof payload.net_average_result !== 'undefined' ? payload.net_average_result : '-'
                    );
                } catch (_) {
                    renderL4Message(l4Texts.error);
                }
            };

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-role="open-l4-monthly"]');
                if (btn) openL4Monthly(btn.getAttribute('data-root-id'));
            });

            l4ModalEl.querySelectorAll('[data-role="close-l4-monthly"]').forEach((btn) => {
                btn.addEventListener('click', () => setL4ModalOpen(false));
            });
            l4ModalEl.addEventListener('click', (e) => { if (e.target === l4ModalEl) setL4ModalOpen(false); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && l4ModalEl.classList.contains('show')) setL4ModalOpen(false);
            });
        })();

        // ── Delegate button clicks ──
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const action = btn.dataset.action;
            if (action === 'add-kr')   openAddKr(btn);
            if (action === 'edit-obj') openEditObj(btn);
            if (action === 'del-obj')  openDelObj(btn);
            if (action === 'edit-kr')  openEditKr(btn);
            if (action === 'del-kr')   openDelKr(btn);
            if (action === 'edit-l3') openEditL3(btn);
            if (action === 'del-l3') openDelL3(btn);
            if (action === 'del-kpi-report') openDelKpiReport(btn);
            if (action === 'del-kpi-month') openDelKpiMonth(btn);
        });

        document.addEventListener('app:lang-changed', (e) => {
            const nextLang = e.detail && e.detail.lang === 'th' ? 'th' : 'en';
            if (nextLang === lang) return;

            const url = new URL(window.location.href);
            url.searchParams.set('lang', nextLang);
            window.location.assign(url.toString());
        });
    })();
    </script>
@endsection
