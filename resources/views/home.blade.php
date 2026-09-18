@extends('layout')

@section('title', 'Target Indicator Setup')
@section('page_heading_key', 'homeTargetIndicatorTitle')
@section('page_heading', 'กำหนดเป้าหมายตัวชี้วัด')
@section('page_desc_key', 'homeStatus')
@section('page_description', '')

@section('content')
    @php
        $lang = request('lang', 'en') === 'th' ? 'th' : 'en';
        $announcementSaveUrl = is_string($announcementSaveUrl ?? null) ? $announcementSaveUrl : '';
        $announcementUpdateUrl = is_string($announcementUpdateUrl ?? null) ? $announcementUpdateUrl : '';
        $announcementDeleteUrl = is_string($announcementDeleteUrl ?? null) ? $announcementDeleteUrl : '';
        $adminAnnouncements = is_array($adminAnnouncements ?? null) ? $adminAnnouncements : [];
        $deptAbbrHrOptions = is_array($deptAbbrHrOptions ?? null) ? $deptAbbrHrOptions : [];
        $levelThreeDeptCards = is_array($levelThreeDeptCards ?? null) ? $levelThreeDeptCards : [];
        $levelThreeRowsByDept = is_array($levelThreeRowsByDept ?? null) ? $levelThreeRowsByDept : [];
        $levelThreeCycleLabel = trim((string) ($levelThreeCycleLabel ?? ''));
        $okrRows = is_array($okrRows ?? null) ? $okrRows : [];
    @endphp

    <style>
        .home-admin-shell {
            display: grid;
            gap: 16px;
        }

        .home-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        .home-panel-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .home-panel-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
        }

        .home-panel-hint {
            margin: 6px 0 0;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.5;
        }

        .home-panel-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .home-toolbar {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(220px, 320px);
            gap: 10px;
            align-items: center;
        }

        .home-input,
        .home-select,
        .home-textarea,
        .home-file {
            width: 100%;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 10px 12px;
            font-size: 13px;
            box-sizing: border-box;
        }

        .home-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .home-table-wrap {
            border: 1px solid #e5e7eb;
            overflow: auto;
            max-width: 100%;
        }

        .home-target-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1600px;
            background: #ffffff;
        }

        .home-target-table th,
        .home-target-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            font-size: 12px;
            color: #111827;
            vertical-align: top;
            text-align: left;
            word-break: break-word;
            line-height: 1.45;
        }

        .home-target-table th {
            background: linear-gradient(180deg, #be185d 0%, #9d174d 100%);
            color: #ffffff;
            font-weight: 700;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .cell-level1 {
            background: #fff1f7;
        }

        .cell-level2 {
            background: #fff7fb;
        }

        .cell-level3 {
            background: #ffffff;
        }

        .cell-heading {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #111827;
        }

        .cell-note {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.4;
        }

        .cell-files {
            margin: 0;
            padding-left: 16px;
            display: grid;
            gap: 3px;
        }

        .cell-files a {
            color: #1d4ed8;
            font-size: 11px;
            word-break: break-all;
        }

        .cell-empty {
            color: #6b7280;
            font-size: 11px;
        }

        .cell-action {
            min-width: 210px;
        }

        .row-action-wrap {
            display: grid;
            gap: 6px;
        }

        .row-action-group {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .row-action-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
        }

        .action-btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            cursor: pointer;
        }

        .action-btn-danger {
            border-color: #fecaca;
            color: #b91c1c;
        }

        .action-btn[disabled] {
            opacity: 0.6;
            cursor: default;
        }

        .save-meta {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }

        .save-meta.success {
            color: #15803d;
        }

        .save-meta.fail {
            color: #b91c1c;
        }

        .home-editor-panel {
            border: 1px solid #f3c3d9;
            background: #fff7fb;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .home-editor-title {
            margin: 0;
            color: #9d174d;
            font-size: 15px;
            font-weight: 800;
        }

        .home-editor-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .home-editor-field {
            display: grid;
            gap: 6px;
        }

        .home-editor-field.full {
            grid-column: 1 / -1;
        }

        .home-editor-label {
            margin: 0;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .home-file-note {
            margin: 0;
            color: #6b7280;
            font-size: 11px;
        }

        .home-editor-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .home-pagination {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .home-page-meta {
            margin: 0;
            font-size: 12px;
            color: #4b5563;
            min-width: 100px;
            text-align: center;
        }

        .home-empty {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }

        @media (max-width: 920px) {
            .home-panel {
                padding: 14px;
            }

            .home-toolbar {
                grid-template-columns: 1fr;
            }

            .home-editor-grid {
                grid-template-columns: 1fr;
            }

            .home-target-table {
                min-width: 1280px;
            }
        }
    </style>

    <section class="home-admin-shell">
        <article class="home-panel">
            <div class="home-panel-head">
                <div>
                    <h2 class="home-panel-title">{{ $lang === 'th' ? 'เป้าหมายและตัวชี้วัด (OKR)' : 'Goals & Key Results (OKR)' }}</h2>
                    <p class="home-panel-hint">
                        {{ $lang === 'th' ? 'ข้อมูลจากหน้ากำหนดเป้าหมายตัวชี้วัด อัพเดทอัตโนมัติทุกครั้งที่มีการเพิ่ม/แก้ไขข้อมูล' : 'Data from Goal Target Setup page. Updates automatically whenever goals are added or edited.' }}
                    </p>
                </div>
                <div class="home-panel-actions">
                    <a href="{{ route('admin.goal.targets', ['lang' => $lang]) }}" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        {{ $lang === 'th' ? '⚙ จัดการเป้าหมาย' : '⚙ Manage Goals' }}
                    </a>
                </div>
            </div>

            <div class="home-table-wrap">
                <table class="home-target-table">
                    <thead>
                        <tr>
                            <th>{{ $lang === 'th' ? 'ลำดับชั้น 1' : 'Level 1' }}</th>
                            <th>{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }}</th>
                            <th>{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                            <th>{{ $lang === 'th' ? 'ลำดับชั้น 2' : 'Level 2' }}</th>
                            <th>{{ $lang === 'th' ? 'แผนก' : 'Dept' }}</th>
                            <th>{{ $lang === 'th' ? 'หัวข้อ' : 'Title' }}</th>
                            <th>{{ $lang === 'th' ? 'รายละเอียด' : 'Detail' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($okrRows) === 0)
                            <tr>
                                <td colspan="7" style="text-align:center;color:#6b7280;padding:20px;">
                                    {{ $lang === 'th' ? 'ยังไม่มีเป้าหมาย — ไปที่ "จัดการเป้าหมาย" เพื่อเพิ่มข้อมูล' : 'No goals yet. Click "Manage Goals" to add.' }}
                                </td>
                            </tr>
                        @else
                            @foreach ($okrRows as $obj)
                                @php $krList = $obj['key_results']; @endphp
                                @if (count($krList) === 0)
                                    <tr>
                                        <td>{{ $obj['sort_no'] }}</td>
                                        <td>{{ $obj['title'] }}</td>
                                        <td>{{ $obj['detail'] }}</td>
                                        <td colspan="4" style="color:#9ca3af;">—</td>
                                    </tr>
                                @else
                                    @foreach ($krList as $krIdx => $kr)
                                        <tr>
                                            @if ($krIdx === 0)
                                                <td rowspan="{{ count($krList) }}">{{ $obj['sort_no'] }}</td>
                                                <td rowspan="{{ count($krList) }}">{{ $obj['title'] }}</td>
                                                <td rowspan="{{ count($krList) }}">{{ $obj['detail'] }}</td>
                                            @endif
                                            <td>{{ $kr['sort_no'] }}</td>
                                            <td>{{ $kr['dept_abbr_hr'] ?: '—' }}</td>
                                            <td>{{ $kr['title'] }}</td>
                                            <td>{{ $kr['detail'] }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    @include('home-partials.admin-announcement-card-script')
@endsection

