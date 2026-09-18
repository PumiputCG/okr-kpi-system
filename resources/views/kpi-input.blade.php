@extends('layout')

@section('title', 'Add KPIs')
@section('page_heading_key', 'menuKpiInput')
@section('page_heading', 'Add KPIs')
@section('content')
    @php
        $activeCycle = $activeCycle ?? null;
        $cycleContext = $cycleContext ?? [
            'has_active_cycle' => false,
            'cycle_id' => null,
            'cycle_name' => null,
            'cycle_code' => null,
            'months' => [],
        ];
        $kpiAverage = $kpiAverage ?? null;
        $kpiAverageByDepartment = is_array($kpiAverageByDepartment ?? null) ? $kpiAverageByDepartment : [];
        $savedCards = $savedCards ?? [];
        $kpiUnits = $kpiUnits ?? [];
        $kpiInputPolicy = $kpiInputPolicy ?? [
            'position' => '',
            'can_skip_evidence' => false,
            'show_action_plan_on_fail' => false,
            'require_action_plan_on_fail' => false,
        ];
        $noticeVisibilityLevel = isset($noticeVisibilityLevel) ? (int) $noticeVisibilityLevel : 0;
        $noticeAnnouncementsByLevel = is_array($noticeAnnouncementsByLevel ?? null) ? $noticeAnnouncementsByLevel : [];
        $noticeLevelThreeCard = is_array($noticeLevelThreeCard ?? null) ? $noticeLevelThreeCard : null;
        $noticeLevelThreeRows = is_array($noticeLevelThreeRows ?? null) ? $noticeLevelThreeRows : [];
        $noticeLevelThreeCycleLabel = trim((string) ($noticeLevelThreeCycleLabel ?? ''));
        $kpiDepartmentTargetPolicy = is_array($kpiDepartmentTargetPolicy ?? null) ? $kpiDepartmentTargetPolicy : [];
    @endphp
    <style>
        .kpi-shell {
            background: #ffffff;
            padding: 20px 22px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .kpi-shell-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .kpi-cycle-box {
            margin-top: 14px;
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 13px;
            color: #111111;
            background: #f9fafb;
            display: grid;
            gap: 4px;
        }

        .kpi-cycle-box.info {
            border-color: #dbeafe;
            background: #eff6ff;
        }

        .kpi-cycle-box.warn {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .kpi-cycle-main {
            font-weight: 400;
        }

        .kpi-cycle-help {
            color: #4b5563;
        }

        .kpi-overview-grid {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, minmax(240px, 1fr));
            gap: 12px;
            max-width: 860px;
        }

        .kpi-mode-tabs {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 100%;
            overflow: visible;
            flex-wrap: wrap;
            position: relative;
            z-index: 40;
        }

        .kpi-mode-tabs[hidden] {
            display: none !important;
        }

        .kpi-mode-group {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .kpi-mode-group[hidden] {
            display: none !important;
        }

        .kpi-mode-group.is-active > .kpi-mode-tab {
            border-color: #e83e8c;
            background: #fde7f3;
        }

        .kpi-mode-subtabs {
            position: absolute;
            left: 0;
            top: 100%;
            min-width: 280px;
            max-width: min(620px, 88vw);
            max-height: min(70vh, 520px);
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #fbcfe8;
            border-radius: 12px;
            padding: 8px;
            display: grid;
            gap: 6px;
            box-shadow: 0 18px 30px rgba(190, 24, 93, 0.18);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-6px);
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        }

        .kpi-mode-subtabs[hidden] {
            display: none !important;
        }

        .kpi-mode-group:hover .kpi-mode-subtabs {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0);
        }

        .kpi-mode-subtab-btn {
            border: 1px solid #f3f4f6;
            background: #ffffff;
            color: #1f2937;
            padding: 8px 10px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            transition: background 0.18s ease, border-color 0.18s ease;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .kpi-mode-subtab-btn:hover {
            background: #fdf2f8;
            border-color: #f9a8d4;
        }

        .kpi-mode-subtab-btn.is-active {
            background: #fce7f3;
            border-color: #ec4899;
            color: #9d174d;
        }

        .kpi-mode-subtab-btn.is-level2 {
            padding-left: 22px;
            font-weight: 400;
            font-size: 12px;
            color: #374151;
        }

        .kpi-mode-subtab-btn.is-level2.is-active {
            font-weight: 700;
            color: #9d174d;
        }

        .kpi-mode-subtab-section {
            display: grid;
            gap: 6px;
        }

        .kpi-mode-subtab-section + .kpi-mode-subtab-section {
            border-top: 1px solid #f3f4f6;
            padding-top: 8px;
            margin-top: 2px;
        }

        .kpi-mode-subtabs > .kpi-mode-subtab-btn + .kpi-mode-subtab-section {
            border-top: 1px solid #f3f4f6;
            padding-top: 8px;
            margin-top: 2px;
        }

        .kpi-mode-subtab-section-title {
            margin: 0;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .kpi-editor-zone {
            margin-top: 14px;
            border: 1px solid #fce7f3;
            background: #fff8fc;
            padding: 12px;
        }

        .kpi-editor-zone-title {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: #9d174d;
        }

        .kpi-mode-tab {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 999px;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .kpi-mode-tab.is-active {
            border-color: #e83e8c;
            background: #fde7f3;
        }

        .kpi-overview-card {
            padding: 16px 18px;
            display: grid;
            gap: 10px;
            min-height: 120px;
            align-content: center;
        }

        .kpi-avg-dept-list {
            display: grid;
            gap: 8px;
            height: 100%;
        }

        #kpi-avg-dept-card.kpi-overview-card {
            padding: 0;
        }

        .kpi-avg-dept-card {
            border: 1px solid #86efac;
            background: #dcfce7;
            padding: 14px 16px;
            height: 100%;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            text-align: center;
        }

        .kpi-avg-dept-text {
            margin: 0;
            color: #166534;
            font-size: 13px;
            font-weight: 400;
            line-height: 1.35;
            text-align: center;
        }

        .kpi-avg-dept-text strong {
            font-weight: 700;
        }

        .kpi-avg-dept-value {
            margin: 8px 0 0;
            color: #166534;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.1;
            text-align: center;
        }

        .kpi-avg-dept-value.is-target-pending {
            margin-top: 0;
            font-weight: 400;
        }

        .kpi-cycle-overview-card {
            border: 1px solid #dbeafe;
            background: #eff6ff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            text-align: center;
            min-height: 120px;
            height: 100%;
            box-sizing: border-box;
        }

        .kpi-cycle-overview-line {
            margin: 0;
            color: #1e3a8a;
            font-size: 14px;
            font-weight: 400;
        }

        .kpi-notice-modal {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            background: rgba(17, 17, 17, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .kpi-notice-modal.show {
            display: flex;
        }

        .kpi-notice-panel {
            width: min(1180px, calc(100vw - 28px));
            min-width: min(380px, calc(100vw - 28px));
            height: min(86vh, 840px);
            min-height: 360px;
            max-height: calc(100vh - 28px);
            background: #ffffff;
            display: flex;
            flex-direction: column;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 24px 56px rgba(17, 17, 17, 0.34);
            resize: both;
            position: relative;
        }

        .kpi-notice-head {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            background: #fdf2f8;
            position: relative;
            cursor: move;
            touch-action: none;
            user-select: none;
        }

        .kpi-notice-title {
            margin: 0;
            font-size: 22px;
            color: #9d174d;
            font-weight: 700;
            text-align: center;
        }

        .kpi-notice-close {
            position: absolute;
            right: 14px;
            top: 10px;
            width: 34px;
            height: 34px;
            border: 1px solid #f3f4f6;
            border-radius: 999px;
            background: #ffffff;
            color: #111111;
            font-size: 22px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
            touch-action: manipulation;
        }

        .kpi-notice-close:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .kpi-notice-body {
            flex: 1 1 auto;
            overflow: auto;
            padding: 20px;
            display: grid;
            gap: 18px;
            align-content: start;
            background: #fffafc;
        }

        .kpi-notice-section {
            border: 1px solid #fbcfe8;
            background: #ffffff;
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .kpi-notice-section-title {
            margin: 0;
            font-size: 18px;
            color: #9d174d;
            font-weight: 700;
        }

        .kpi-notice-level-list {
            display: grid;
            gap: 10px;
        }

        #kpi-notice-level2-list {
            display: flex;
            align-items: stretch;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        #kpi-notice-level2-list .kpi-notice-item {
            flex: 0 0 min(460px, 88vw);
            min-width: min(460px, 88vw);
        }

        #kpi-notice-level2-list .kpi-notice-empty {
            width: 100%;
        }

        .kpi-notice-item {
            border: 1px solid #f3f4f6;
            background: #ffffff;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .kpi-notice-item.is-expandable {
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .kpi-notice-item.is-expandable:hover {
            border-color: #f9a8d4;
            box-shadow: 0 10px 20px rgba(17, 17, 17, 0.08);
        }

        .kpi-notice-item.is-expandable:focus-visible {
            outline: 2px solid #ec4899;
            outline-offset: 2px;
        }

        .kpi-notice-item-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            align-items: start;
        }

        .kpi-notice-item-label {
            font-weight: 700;
            color: #111827;
            font-size: 13px;
        }

        .kpi-notice-item-value {
            color: #1f2937;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .kpi-notice-item-value.is-clamp-3 {
            white-space: normal;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
        }

        .kpi-notice-file-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 4px;
        }

        .kpi-notice-file-list a {
            color: #be185d;
            text-decoration: underline;
        }

        .kpi-notice-empty {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .kpi-notice-level3-card {
            border: 1px solid #f9a8d4;
            background: #fce7f3;
            text-align: center;
            padding: 14px;
            display: grid;
            gap: 6px;
            cursor: pointer;
        }

        .kpi-notice-level3-card:hover {
            border-color: #f472b6;
            background: #fbcfe8;
        }

        .kpi-notice-level3-main,
        .kpi-notice-level3-sub,
        .kpi-notice-level3-hint {
            margin: 0;
            color: #9d174d;
        }

        .kpi-notice-level3-main {
            font-size: 16px;
            font-weight: 700;
        }

        .kpi-notice-level3-sub {
            font-size: 26px;
            font-weight: 700;
        }

        .kpi-notice-level3-hint {
            font-size: 14px;
            font-weight: 600;
        }

        .kpi-notice-cycle {
            margin: 0;
            font-size: 13px;
            color: #4b5563;
            text-align: center;
        }

        .kpi-notice-table-wrap {
            border: 1px solid #f3f4f6;
            overflow: auto;
            background: #ffffff;
        }

        .kpi-notice-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .kpi-notice-table th,
        .kpi-notice-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }

        .kpi-notice-table th {
            background: #fdf2f8;
            color: #9d174d;
            font-weight: 700;
            white-space: nowrap;
        }

        .kpi-notice-table td:nth-child(6) {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .kpi-notice-sub-modal {
            position: fixed;
            inset: 0;
            z-index: 2147483500;
            background: rgba(17, 17, 17, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .kpi-notice-sub-modal.show {
            display: flex;
        }

        .kpi-notice-sub-panel {
            width: min(1200px, calc(100vw - 40px));
            max-height: calc(100vh - 40px);
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 50px rgba(17, 17, 17, 0.25);
            display: flex;
            flex-direction: column;
        }

        .kpi-notice-sub-head {
            padding: 14px 16px;
            background: #fdf2f8;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .kpi-notice-sub-title {
            margin: 0;
            color: #9d174d;
            font-size: 19px;
            font-weight: 700;
            text-align: center;
        }

        .kpi-notice-sub-close {
            position: absolute;
            right: 10px;
            top: 8px;
            width: 34px;
            height: 34px;
            border: 1px solid #f3f4f6;
            border-radius: 999px;
            background: #ffffff;
            color: #111111;
            font-size: 22px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }

        .kpi-notice-sub-close:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .kpi-notice-sub-body {
            padding: 14px;
            overflow: auto;
            background: #ffffff;
            flex: 1 1 auto;
        }

        .kpi-notice-preview-iframe {
            width: 100%;
            min-height: 70vh;
            border: 1px solid #f3f4f6;
            background: #ffffff;
        }

        .kpi-notice-preview-image {
            display: block;
            max-width: 100%;
            max-height: 72vh;
            margin: 0 auto;
            object-fit: contain;
            border: 1px solid #f3f4f6;
            background: #ffffff;
        }

        .kpi-notice-preview-empty {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
            padding: 30px 12px;
        }

        .kpi-add-btn {
            border: 0;
            background: #111111;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 14px;
            cursor: pointer;
        }

        .kpi-add-btn:hover {
            background: #e83e8c;
        }

        .kpi-card-list {
            display: grid;
            gap: 14px;
            margin-top: 16px;
            min-width: 0;
        }

        .kpi-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 14px;
            position: relative;
            min-width: 0;
        }

        .kpi-stage-scroll {
            display: none;
        }

        .kpi-card.kpi-card-just-added {
            border-color: #e83e8c;
            box-shadow: 0 0 0 2px rgba(232, 62, 140, 0.2);
        }

        .kpi-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .kpi-card-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kpi-card-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }

        .kpi-card-remove {
            border: 0;
            background: #f3f4f6;
            color: #111827;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            cursor: pointer;
        }

        .kpi-card-remove:hover {
            background: #111111;
            color: #ffffff;
        }

        .kpi-card-edit {
            border: 0;
            background: #111111;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            cursor: pointer;
            display: none;
        }

        .kpi-card-edit:hover {
            background: #e83e8c;
        }

        .kpi-card-compact {
            display: none;
            margin: 10px 0 0;
            font-size: 13px;
            color: #374151;
            background: #f8fafc;
            padding: 8px 10px;
        }

        .kpi-card-compact-row {
            margin: 2px 0;
        }

        .kpi-card-compact-hierarchy {
            display: grid;
            gap: 6px;
        }

        .kpi-card-compact-hierarchy-row {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: start;
            gap: 8px;
            padding: 4px 0 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .kpi-card-compact-hierarchy-row.is-level-2 {
            margin-left: 16px;
        }

        .kpi-card-compact-hierarchy-row.is-level-3 {
            margin-left: 32px;
        }

        .kpi-card-compact-hierarchy-label {
            color: #111111;
            line-height: 1.6;
            white-space: nowrap;
        }

        .kpi-card-compact-hierarchy-value {
            min-width: 0;
            color: #374151;
            line-height: 1.6;
            overflow-wrap: anywhere;
            text-wrap: pretty;
        }

        .kpi-card-compact-spacer {
            height: 8px;
        }

        .kpi-card-compact-section-header {
            margin: 6px 0 0;
            padding: 6px 8px;
            border-radius: 8px;
            background: #fdf2f8;
            font-size: 13px;
            font-weight: 700;
            color: #9d174d;
        }

        .kpi-card-compact-section-body {
            display: grid;
            gap: 6px;
            padding: 8px 0 0 16px;
        }

        .kpi-card-compact-detail-row {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: start;
            gap: 8px;
        }

        .kpi-card-compact-detail-label {
            color: #111111;
            line-height: 1.6;
            white-space: nowrap;
        }

        .kpi-card-compact-detail-value {
            min-width: 0;
            color: #374151;
            line-height: 1.6;
            overflow-wrap: anywhere;
            text-wrap: pretty;
        }

        .kpi-compact-status {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }

        .kpi-compact-status-summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .kpi-compact-status-summary::-webkit-details-marker {
            display: none;
        }

        .kpi-compact-status-summary::marker {
            content: '';
        }

        .kpi-compact-status-title {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .kpi-compact-status-counts {
            margin-top: 2px;
            display: grid;
            gap: 2px;
        }

        .kpi-compact-status-count-line {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 8px;
            font-size: 12px;
            color: #374151;
            line-height: 1.25;
        }

        .kpi-compact-status-count-value {
            font-weight: 700;
        }

        .kpi-compact-status-colon {
            font-weight: 700;
            color: #6b7280;
        }

        .kpi-compact-status-count-line.status-pending {
            color: #ca8a04;
        }

        .kpi-compact-status-count-line.status-rejected {
            color: #dc2626;
        }

        .kpi-compact-status-count-line.status-approved {
            color: #16a34a;
        }

        .kpi-compact-status-count-line.status-waiting-score {
            color: #111111;
        }

        .kpi-compact-status-chevron {
            color: #6b7280;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            transform-origin: center;
            transition: transform 0.2s ease;
        }

        .kpi-compact-status[open] .kpi-compact-status-chevron {
            transform: rotate(180deg);
        }

        .kpi-compact-status-panel {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d1d5db;
        }

        .kpi-compact-status-panel-title {
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
        }

        .kpi-compact-status-month-list {
            display: grid;
            grid-template-columns: max-content max-content max-content;
            justify-content: start;
            column-gap: 2px;
            row-gap: 4px;
            align-items: center;
        }

        .kpi-compact-status-month-row {
            display: contents;
        }

        .kpi-compact-status-month-label {
            font-size: 12px;
            color: #374151;
            line-height: 1.25;
        }

        .kpi-compact-status-month-value {
            font-weight: 700;
            justify-self: start;
            padding-left: 8px;
        }

        .kpi-compact-status-month-value.status-pending {
            color: #ca8a04;
        }

        .kpi-compact-status-month-value.status-rejected {
            color: #dc2626;
        }

        .kpi-compact-status-month-value.status-approved {
            color: #16a34a;
        }

        .kpi-compact-status-month-value.status-waiting-score {
            color: #111111;
        }

        .kpi-compact-status-month-value.status-not-open {
            color: #6b7280;
        }

        .kpi-card.is-collapsed .kpi-card-compact {
            display: block;
        }

        .kpi-card.is-collapsed [data-role="card-body"] {
            display: none;
        }

        .kpi-card-report-status {
            position: absolute;
            right: 14px;
            bottom: 12px;
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.01em;
            display: none;
        }

        .kpi-card.has-report-status .kpi-card-report-status {
            display: block;
        }

        .kpi-card-report-status.status-pending {
            color: #ca8a04;
        }

        .kpi-card-report-status.status-rejected {
            color: #dc2626;
        }

        .kpi-card-report-status.status-approved {
            color: #16a34a;
        }

        .kpi-fields {
            display: grid;
            gap: 14px;
            margin-top: 14px;
        }

        .kpi-sel-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }

        .kpi-sel-label-row .kpi-label {
            margin-bottom: 0;
        }

        .kpi-sel-change-btn {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            cursor: pointer;
            border-radius: 4px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .kpi-sel-change-btn:hover {
            background: #e83e8c;
            color: #ffffff;
            border-color: #e83e8c;
        }

        .kpi-sel-preview {
            margin-top: 8px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #e83e8c;
            font-size: 13px;
            line-height: 1.6;
        }

        .kpi-sel-l1 {
            font-weight: 700;
            color: #111111;
        }

        .kpi-sel-l2 {
            padding-left: 24px;
            color: #374151;
            margin-top: 6px;
        }

        .kpi-sel-l3 {
            padding-left: 24px;
            color: #6b7280;
            margin-top: 4px;
        }

        .kpi-mode-hint {
            display: none;
            font-size: 12px;
            font-weight: 700;
            color: #9d174d;
            background: #fdf2f8;
            border-left: 3px solid #e83e8c;
            padding: 6px 10px;
        }

        .kpi-card.is-target-mode .kpi-mode-hint-target { display: block; }
        .kpi-card.is-report-mode .kpi-mode-hint-report { display: block; }

        .kpi-fields-row-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-start;
        }

        .kpi-fields-row-meta > .kpi-stage-field {
            flex: 0 0 auto;
            min-width: 160px;
        }

        .kpi-fields-row-2col {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 14px;
            align-items: start;
        }

        .kpi-fields-action-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .kpi-stage-field {
            min-width: 0;
        }
        .kpi-hierarchy-dept-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            max-width: 320px;
        }
        .kpi-hierarchy-dept-label {
            font-size: 11px;
            color: #6b7280;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .kpi-hierarchy-dept-select {
            flex: 1;
            min-width: 0;
            max-width: 200px;
            height: 28px;
            padding: 0 4px;
            font-size: 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
        }
        .kpi-hierarchy-dept-select.has-filter {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .kpi-input[data-role="okr-hierarchy"] {
            max-width: 320px;
            background: #f3f4f6 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='none' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' d='M1 1l4 4 4-4'/%3E%3C/svg%3E") no-repeat right 10px center;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            border-color: #e5e7eb;
            appearance: none;
            -webkit-appearance: none;
            padding-right: 28px;
        }


        .kpi-l3-dropdown {
            position: relative;
            max-width: 320px;
        }

        .kpi-l3-trigger {
            width: 100%;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
            padding: 10px 10px 10px 12px;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .kpi-l3-trigger:hover {
            border-color: #d1d5db;
            background: #e9eaec;
        }

        .kpi-l3-trigger-text {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #374151;
        }

        .kpi-l3-trigger-text.has-value {
            color: #111111;
        }

        .kpi-l3-arrow {
            flex: 0 0 10px;
            width: 10px;
            height: 6px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='none' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' d='M1 1l4 4 4-4'/%3E%3C/svg%3E") no-repeat center center;
            font-size: 0;
            transition: transform 0.15s;
        }

        .kpi-l3-dropdown.open .kpi-l3-arrow {
            transform: rotate(180deg);
        }

        .kpi-l3-options {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 320px;
            max-width: 520px;
            z-index: 300;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-top: none;
            max-height: 320px;
            overflow-y: auto;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }

        .kpi-l3-option {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }

        .kpi-l3-option:last-child {
            border-bottom: none;
        }

        .kpi-l3-option:hover {
            background: #f9fafb;
        }

        .kpi-l3-option.is-selected {
            background: #eff6ff;
        }

        .kpi-l3-option-placeholder {
            padding: 10px 12px;
            color: #9ca3af;
            font-size: 14px;
            cursor: default;
        }

        .kpi-l3-opt-l1 {
            font-size: 13px;
            font-weight: 700;
            color: #111111;
        }

        .kpi-l3-opt-l2 {
            font-size: 12px;
            color: #374151;
            padding-left: 14px;
            margin-top: 3px;
        }

        .kpi-l3-opt-l3 {
            font-size: 12px;
            color: #6b7280;
            padding-left: 30px;
            margin-top: 2px;
        }


        .kpi-department-target-select {
            display: none;
        }

        .kpi-department-picker {
            position: relative;
        }

        .kpi-department-picker-toggle {
            width: 100%;
            min-height: 42px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 14px;
            text-align: left;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kpi-department-picker-panel {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 15;
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(17, 24, 39, 0.12);
            padding: 4px;
        }

        .kpi-department-picker-option {
            width: 100%;
            border: 0;
            background: #ffffff;
            color: #111111;
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
            cursor: pointer;
        }

        .kpi-department-picker-option:hover {
            background: #f3f4f6;
        }

        .kpi-department-picker-option.is-selected {
            background: #fdf2f8;
            color: #9d174d;
            font-weight: 700;
        }

        .kpi-card-mode-wrap {
            min-width: 0;
        }

        .kpi-card-mode-switch {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background: #ffffff;
            min-height: 42px;
        }

        .kpi-card-mode-btn {
            border: 0;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            padding: 10px 8px;
        }

        .kpi-card-mode-btn + .kpi-card-mode-btn {
            border-left: 1px solid #e5e7eb;
        }

        .kpi-card-mode-btn.is-active {
            background: #fde7f3;
            color: #9d174d;
        }

        .kpi-card-mode-btn:disabled {
            cursor: not-allowed;
            color: #9ca3af;
            background: #f9fafb;
        }

        .kpi-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .kpi-input,
        .kpi-textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 14px;
        }

        .kpi-textarea {
            min-height: 64px;
            resize: vertical;
        }

        .kpi-next-btn {
            border: 0;
            background: #111111;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 16px;
            cursor: pointer;
        }

        .kpi-next-btn:hover {
            background: #e83e8c;
        }

        .kpi-next-btn[data-mode="edit"] {
            background: #6b7280;
        }

        .kpi-next-btn[data-mode="edit"]:hover {
            background: #4b5563;
        }

        .kpi-target-section {
            margin-top: 12px;
            background: #fff8fc;
            padding: 14px;
            display: none;
        }

        .kpi-target-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            align-items: end;
        }

        .kpi-criteria-choice {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 18px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border: 1px solid #f3c9de;
            background: #ffffff;
        }

        .kpi-criteria-choice[hidden],
        .kpi-card.is-report-mode .kpi-criteria-choice {
            display: none;
        }

        .kpi-criteria-choice-question {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .kpi-criteria-choice-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .kpi-criteria-choice-option input {
            accent-color: #e83e8c;
        }

        .kpi-target-grid[hidden] {
            display: none;
        }

        .kpi-target-input {
            max-width: 180px;
        }

        .kpi-unit-inline {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kpi-unit-inline .kpi-input {
            margin: 0;
            flex: 1 1 0;
            min-width: 0;
        }

        .kpi-unit-other-input {
            display: none;
            min-width: 120px;
        }

        .kpi-step-action {
            display: grid;
            gap: 6px;
            align-items: start;
            align-self: end;
        }

        .kpi-save-hint {
            margin: 0;
            font-size: 12px;
            color: #15803d;
            line-height: 1.25;
        }

        .kpi-target-title {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 700;
        }

        .kpi-score-section {
            margin-top: 12px;
            border: 1px solid #f1f5f9;
            padding: 14px;
            display: none;
        }

        .kpi-score-title {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 700;
        }

        .kpi-month-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .kpi-month-item {
            border: 1px solid #e5e7eb;
            padding: 10px 10px 44px;
            display: grid;
            gap: 10px;
            background: #ffffff;
            position: relative;
        }

        .kpi-month-item.is-locked {
            background: #f9fafb;
            border-style: dashed;
        }

        .kpi-month-item.focus-action-plan {
            border-color: #e83e8c;
            box-shadow: 0 0 0 2px rgba(232, 62, 140, 0.18);
        }

        .kpi-month-item.focus-notify-approved {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
            background: #f0fdf4;
        }

        .kpi-month-item.focus-notify-rejected {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
            background: #fef2f2;
        }

        .kpi-month-item.is-rejected {
            border-color: #ef4444;
            background: #fff5f5;
        }

        .kpi-month-review-note {
            margin: 0;
            font-size: 11px;
            color: #b91c1c;
            line-height: 1.45;
        }

        .kpi-reject-detail-text {
            margin: 0;
            color: #b91c1c;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .kpi-month-review-toggle {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font-size: 11px;
            font-weight: 600;
            padding: 0;
            text-align: right;
            white-space: nowrap;
            cursor: pointer;
            position: absolute;
            right: 74px;
            bottom: 13px;
            line-height: 1.2;
            max-width: calc(100% - 140px);
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kpi-month-review-toggle[hidden] {
            display: none;
        }

        .kpi-month-head {
            display: block;
        }

        .kpi-month-evidence-row {
            display: grid;
            gap: 6px;
        }

        .kpi-month-action-plan-row {
            display: grid;
            gap: 6px;
        }

        .kpi-month-action-plan-row[hidden] {
            display: none;
        }

        .kpi-month-field {
            display: grid;
            gap: 4px;
        }

        .kpi-score-input {
            width: 50%;
            min-width: 110px;
            max-width: 150px;
        }

        .kpi-score-input-row {
            display: flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            max-width: 100%;
        }

        .kpi-score-unit {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            min-height: 1em;
        }

        .kpi-month-field-label {
            font-size: 11px;
            font-weight: 700;
            color: #4b5563;
        }

        .kpi-file-input {
            border: 0;
            background: transparent;
            padding: 0;
            font-size: 13px;
            color: #111111;
            width: 100%;
        }

        .kpi-file-selected {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }

        .kpi-file-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 4px;
        }

        .kpi-file-link {
            font-size: 12px;
            color: #be185d;
            text-decoration: none;
            word-break: break-all;
        }

        .kpi-file-link:hover {
            text-decoration: underline;
        }

        .kpi-month-save-btn {
            border: 0;
            background: #111111;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 5px 9px;
            cursor: pointer;
            min-width: 50px;
            position: absolute;
            right: 10px;
            bottom: 10px;
        }

        .kpi-month-save-btn:hover {
            background: #e83e8c;
        }

        .kpi-month-save-btn.is-edit {
            background: #6b7280;
        }

        .kpi-month-save-btn.is-edit:hover {
            background: #4b5563;
        }

        .kpi-month-save-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .kpi-month-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .kpi-month-meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .kpi-month-meta-group {
            display: grid;
            gap: 4px;
        }

        .kpi-month-meta-label {
            font-size: 11px;
            font-weight: 700;
            color: #374151;
        }

        .kpi-month-status {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            font-weight: 700;
            width: fit-content;
            padding: 5px 10px;
            background: #f3f4f6;
        }

        .kpi-month-status.not-saved {
            border: 1px solid #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .kpi-month-status.success {
            color: #15803d;
            background: #dcfce7;
        }

        .kpi-month-status.fail {
            color: #b91c1c;
            background: #fee2e2;
        }

        .kpi-month-status.locked {
            color: #111111;
            background: #f3f4f6;
            font-size: 12px;
            font-weight: 400;
        }

        .kpi-month-status.review-pending {
            color: #92400e;
            background: #fef3c7;
        }

        .kpi-month-status.review-approved {
            color: #166534;
            background: #dcfce7;
        }

        .kpi-month-status.review-rejected {
            color: #b91c1c;
            background: #fee2e2;
        }

        .kpi-month-criteria {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            font-weight: 700;
            width: fit-content;
            padding: 5px 10px;
            background: #f3f4f6;
        }

        .kpi-month-criteria.success {
            color: #15803d;
            background: #dcfce7;
        }

        .kpi-month-criteria.fail {
            color: #b91c1c;
            background: #fee2e2;
        }

        .kpi-month-cycle-date {
            margin: 0;
            font-size: 12px;
            color: #374151;
            font-weight: 700;
            width: fit-content;
            padding: 5px 10px;
            background: #f3f4f6;
        }

        .kpi-result-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: minmax(260px, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .kpi-result-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .kpi-confirm-btn {
            border: 0;
            background: #e83e8c;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 14px;
            cursor: pointer;
        }

        .kpi-confirm-btn:hover {
            background: #111111;
        }

        .kpi-hide-btn {
            border: 0;
            background: #111111;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 14px;
            cursor: pointer;
            display: none;
        }

        .kpi-hide-btn:hover {
            background: #e83e8c;
        }

        .kpi-status {
            margin-top: 7px;
            font-size: 12px;
            color: #6b7280;
        }

        .kpi-result-value {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #111111;
            min-height: 22px;
        }

        .kpi-card.is-target-mode .kpi-result-value {
            padding-left: 18px;
            font-weight: 400;
        }

        .kpi-result-breakdown {
            margin-top: 8px;
            display: grid;
            gap: 4px;
            font-size: 12px;
            color: #374151;
        }

        .kpi-result-breakdown-row {
            display: flex;
            align-items: baseline;
        }

        .kpi-result-breakdown-value {
            font-weight: 700;
            color: #111111;
        }

        .kpi-result-breakdown-value.pending {
            color: #92400e;
        }

        .kpi-status.success {
            color: #15803d;
            font-weight: 700;
        }

        .kpi-summary-box {
            margin-top: 14px;
            border: 1px solid #f1f5f9;
            padding: 12px;
            background: #ffffff;
        }

        .kpi-summary-title {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: #111111;
        }

        .kpi-summary-table-wrap {
            overflow-x: auto;
        }

        .kpi-summary-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 520px;
        }

        .kpi-summary-table th,
        .kpi-summary-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
            font-size: 12px;
            vertical-align: top;
        }

        .kpi-summary-table th {
            color: #374151;
            font-weight: 700;
        }

        .kpi-summary-result-pass {
            color: #15803d;
            font-weight: 700;
        }

        .kpi-summary-result-fail {
            color: #b91c1c;
            font-weight: 700;
        }

        .kpi-summary-result-pending {
            color: #ca8a04;
            font-weight: 700;
        }

        .kpi-summary-result-not-saved {
            color: #111111;
        }

        .kpi-summary-net-average td {
            font-weight: 700;
            background: #f8fafc;
        }

        @media (max-width: 1180px) {
            .kpi-overview-grid,
            .kpi-target-grid,
            .kpi-result-grid {
                grid-template-columns: 1fr;
            }

            .kpi-fields-row-2col {
                grid-template-columns: 1fr;
            }

            .kpi-mode-subtabs {
                max-width: min(88vw, 320px);
            }

            .kpi-unit-inline {
                flex-direction: column;
                align-items: stretch;
            }

            .kpi-unit-other-input {
                min-width: 0;
            }

            .kpi-month-grid {
                grid-template-columns: 1fr;
            }

            .kpi-notice-item-row {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .kpi-notice-modal {
                padding: 10px;
            }

            .kpi-notice-panel {
                width: calc(100vw - 20px);
                min-width: calc(100vw - 20px);
                max-height: calc(100vh - 20px);
                min-height: min(360px, calc(100vh - 20px));
            }

        }

    </style>

    <section class="kpi-shell">
        <div class="kpi-shell-head">
            <div>
                <h2 data-i18n="kpiIntroTitle" style="margin: 0; font-size: 22px;">Add KPIs</h2>
                <p data-i18n="kpiIntroDescription" style="margin: 8px 0 0; color: #4b5563;">
                    กรุณาตรวจสอบวัตถุประสงค์และรายละเอียดของเป้าหมาย ก่อนดำเนินการกำหนด Target และบันทึกคะแนน KPI รายเดือน
                </p>
            </div>
            <button id="add-kpi-item-btn" type="button" class="kpi-add-btn" data-i18n="kpiAddCard">Add KPI Report</button>
        </div>

        @if($activeCycle)
            <div class="kpi-overview-grid">
                <div id="kpi-avg-dept-card" class="kpi-overview-card">
                    <div id="kpi-avg-dept-list" class="kpi-avg-dept-list" hidden></div>
                </div>
                <div class="kpi-overview-card kpi-cycle-overview-card">
                    <p class="kpi-cycle-overview-line">
                        <span data-i18n="kpiCycleActiveLabel">Active Cycle</span>:
                        {{ $activeCycle->name ?: '-' }}
                    </p>
                    <p class="kpi-cycle-overview-line">
                        <span data-i18n="kpiCyclePeriodLabel">Period</span>:
                        {{ $activeCycle->start_date ? $activeCycle->start_date->format('d/m/Y') : '-' }}
                        -
                        {{ $activeCycle->end_date ? $activeCycle->end_date->format('d/m/Y') : '-' }}
                    </p>
                </div>
            </div>
        @else
            <div class="kpi-cycle-box warn">
                <span data-i18n="kpiCycleNoActive">No active cycle. Please contact admin to activate cycle first.</span>
            </div>
        @endif

        <div id="kpi-mode-tabs" class="kpi-mode-tabs" hidden>
            <div id="kpi-mode-target-group" class="kpi-mode-group">
                <button id="kpi-mode-target-btn" type="button" class="kpi-mode-tab">เป้าหมาย</button>
                <div id="kpi-mode-target-subtabs" class="kpi-mode-subtabs" hidden></div>
            </div>
            <div id="kpi-mode-report-group" class="kpi-mode-group">
                <button id="kpi-mode-report-btn" type="button" class="kpi-mode-tab">รายงาน</button>
                <div id="kpi-mode-report-subtabs" class="kpi-mode-subtabs" hidden></div>
            </div>
        </div>
        <section id="kpi-editor-zone" class="kpi-editor-zone" hidden>
            <h3 id="kpi-editor-zone-title" class="kpi-editor-zone-title" data-i18n="kpiEditorZoneTitle">Add KPI Report</h3>
            <div id="kpi-editor-list" class="kpi-card-list"></div>
        </section>
        <div id="kpi-item-list" class="kpi-card-list"></div>
    </section>

    <template id="kpi-item-template">
        <article class="kpi-card">
            <div class="kpi-card-header">
                <h3 class="kpi-card-title" data-role="item-title">KPI Item #1</h3>
                <div class="kpi-card-header-actions">
                    <button type="button" class="kpi-card-edit" data-role="edit-card">Edit</button>
                    <button type="button" class="kpi-card-remove" data-role="remove-item">Remove</button>
                </div>
            </div>

            <div class="kpi-card-compact" data-role="compact-summary">Result: pending</div>

            <div data-role="card-body">
                <div class="kpi-fields">

                    {{-- Row 1: ประเภท KPI + เลือกแผนก --}}
                    <div class="kpi-fields-row-meta">
                        <div class="kpi-stage-field kpi-card-mode-wrap" data-role="card-mode-wrap" hidden>
                            <label class="kpi-label" data-role="label-card-mode">ประเภท KPI</label>
                            <div class="kpi-card-mode-switch" data-role="card-mode-switch">
                                <button type="button" class="kpi-card-mode-btn" data-role="card-mode-target">เป้าหมาย</button>
                                <button type="button" class="kpi-card-mode-btn is-active" data-role="card-mode-report">รายงาน</button>
                            </div>
                        </div>
                        <div class="kpi-stage-field" data-role="target-departments-wrap" hidden>
                            <label class="kpi-label" data-role="label-target-departments">เลือกแผนก</label>
                            <div class="kpi-department-picker" data-role="target-departments-picker">
                                <button type="button" class="kpi-department-picker-toggle" data-role="target-departments-summary">เลือก</button>
                                <div class="kpi-department-picker-panel" data-role="target-departments-options" hidden></div>
                            </div>
                            <select class="kpi-input kpi-department-target-select" data-role="target-departments" hidden></select>
                        </div>
                    </div>

                    {{-- Row 2: เลือกหัวข้อลำดับ 1 / หัวข้อลำดับ 2 --}}
                    <div class="kpi-stage-field" data-role="okr-hierarchy-wrap" hidden>
                        <div class="kpi-sel-label-row">
                            <label class="kpi-label" data-role="label-okr-hierarchy">เลือกหัวข้อลำดับ 1 / หัวข้อลำดับ 2</label>
                            <button type="button" class="kpi-sel-change-btn" data-role="hierarchy-change-btn" hidden>เปลี่ยน</button>
                        </div>
                        <div data-role="hierarchy-select-wrap">
                            <div class="kpi-hierarchy-dept-row">
                                <span class="kpi-hierarchy-dept-label">ค้นหาแผนก</span>
                                <select class="kpi-hierarchy-dept-select" data-role="okr-hierarchy-dept-filter"></select>
                            </div>
                            <select class="kpi-input" data-role="okr-hierarchy"></select>
                        </div>
                        <div class="kpi-sel-preview" data-role="hierarchy-preview" hidden>
                            <div class="kpi-sel-l1" data-role="hierarchy-preview-l1"></div>
                            <div class="kpi-sel-l2" data-role="hierarchy-preview-l2"></div>
                        </div>
                    </div>

                    {{-- Row 3: เลือกหัวข้อลำดับ 3 --}}
                    <div class="kpi-stage-field" data-role="okr-level-three-wrap" hidden>
                        <div class="kpi-sel-label-row">
                            <label class="kpi-label" data-role="label-okr-level-three">เลือก</label>
                            <button type="button" class="kpi-sel-change-btn" data-role="l3-change-btn" hidden>เปลี่ยน</button>
                        </div>
                        <div data-role="l3-select-wrap">
                            <select style="display:none" data-role="okr-level-three"></select>
                            <div class="kpi-l3-dropdown" data-role="okr-level-three-dropdown">
                                <button type="button" class="kpi-l3-trigger" data-role="okr-level-three-trigger">
                                    <span class="kpi-l3-trigger-text" data-role="okr-level-three-display"></span>
                                    <span class="kpi-l3-arrow">&#9660;</span>
                                </button>
                                <div class="kpi-l3-options" data-role="okr-level-three-options" hidden></div>
                            </div>
                        </div>
                        <div class="kpi-sel-preview" data-role="l3-preview" hidden>
                            <div class="kpi-sel-l1" data-role="l3-preview-l1"></div>
                            <div class="kpi-sel-l2" data-role="l3-preview-l2"></div>
                            <div class="kpi-sel-l3" data-role="l3-preview-l3"></div>
                        </div>
                    </div>

                    {{-- Mode hint --}}
                    <div class="kpi-mode-hint kpi-mode-hint-target">เพิ่มข้อมูลลำดับที่ 3</div>
                    <div class="kpi-mode-hint kpi-mode-hint-report">เพิ่มข้อมูลลำดับที่ 4</div>

                    {{-- Row 4: Objective + Detail --}}
                    <div class="kpi-fields-row-2col">
                        <div class="kpi-stage-field">
                            <label class="kpi-label" data-role="label-objective">Objective</label>
                            <input class="kpi-input" data-role="objective" type="text">
                        </div>
                        <div class="kpi-stage-field">
                            <label class="kpi-label" data-role="label-detail">Detail</label>
                            <textarea class="kpi-textarea" data-role="detail"></textarea>
                        </div>
                    </div>

                    {{-- Save --}}
                    <div class="kpi-fields-action-row">
                        <button type="button" class="kpi-next-btn" data-role="open-target">Save</button>
                        <p class="kpi-save-hint" data-role="open-target-saved-hint" hidden>Saved</p>
                    </div>

                </div>{{-- /.kpi-fields --}}

                <section class="kpi-target-section" data-role="target-section">
                    <h4 class="kpi-target-title" data-role="label-criteria-section">Define Criteria</h4>
                    <div class="kpi-criteria-choice" data-role="criteria-choice" hidden>
                        <p class="kpi-criteria-choice-question" data-role="criteria-choice-question">Do you want to define criteria?</p>
                        <label class="kpi-criteria-choice-option">
                            <input type="radio" value="1" data-role="has-criteria">
                            <span data-role="criteria-choice-yes">Yes</span>
                        </label>
                        <label class="kpi-criteria-choice-option">
                            <input type="radio" value="0" data-role="has-criteria">
                            <span data-role="criteria-choice-no">No</span>
                        </label>
                    </div>
                    <div class="kpi-target-grid" data-role="criteria-fields">
                        <div>
                            <label class="kpi-label" data-role="label-target">Target</label>
                            <input class="kpi-input kpi-target-input" data-role="target" type="number" step="any">
                        </div>
                        <div>
                            <label class="kpi-label" data-role="label-unit">Unit</label>
                            <div class="kpi-unit-inline">
                                <select class="kpi-input" data-role="unit"></select>
                                <input class="kpi-input kpi-unit-other-input" data-role="unit-other" type="text" maxlength="60">
                            </div>
                        </div>
                        <div>
                            <label class="kpi-label" data-role="label-criteria">Criteria</label>
                            <select class="kpi-input" data-role="criteria"></select>
                        </div>
                    </div>
                    <div class="kpi-fields-action-row" style="margin-top:10px;">
                        <button type="button" class="kpi-next-btn" data-role="open-score">Save</button>
                        <p class="kpi-save-hint" data-role="open-score-saved-hint" hidden>Saved</p>
                    </div>
                </section>

                <section class="kpi-score-section" data-role="score-section">
                    <h4 class="kpi-score-title" data-role="label-score-title">KPI Score (12 Months)</h4>
                    <div class="kpi-month-grid" data-role="month-grid"></div>

                    <div class="kpi-summary-box" data-role="summary-box">
                        <h5 class="kpi-summary-title" data-role="summary-title">KPI Monthly Summary</h5>
                        <div class="kpi-summary-table-wrap">
                            <table class="kpi-summary-table">
                                <thead>
                                    <tr>
                                        <th data-role="summary-col-month">Month</th>
                                        <th data-role="summary-col-score">Score</th>
                                        <th data-role="summary-col-criteria">Criteria</th>
                                        <th data-role="summary-col-result">Result</th>
                                    </tr>
                                </thead>
                                <tbody data-role="summary-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="kpi-result-grid">
                        <div>
                            <label class="kpi-label" data-role="label-result">Average KPI Result</label>
                            <p class="kpi-result-value" data-role="result">Waiting for approval</p>
                            <div class="kpi-result-breakdown" data-role="result-breakdown"></div>
                        </div>
                        <div class="kpi-result-actions">
                            <button type="button" class="kpi-confirm-btn" data-role="confirm-result">Confirm Result</button>
                            <button type="button" class="kpi-hide-btn" data-role="hide-card">Hide</button>
                        </div>
                    </div>
                </section>
            </div>
            <p class="kpi-card-report-status" data-role="card-report-status" hidden></p>
        </article>
    </template>
    <div id="kpi-reject-detail-modal" class="kpi-notice-sub-modal" aria-hidden="true">
        <div class="kpi-notice-sub-panel" role="dialog" aria-modal="true" aria-labelledby="kpi-reject-detail-modal-title">
            <div class="kpi-notice-sub-head">
                <h4 id="kpi-reject-detail-modal-title" class="kpi-notice-sub-title">Rejection detail</h4>
                <button id="kpi-reject-detail-modal-close" type="button" class="kpi-notice-sub-close" aria-label="Close">&#x2715;</button>
            </div>
            <div class="kpi-notice-sub-body">
                <p id="kpi-reject-detail-modal-text" class="kpi-reject-detail-text">-</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const listEl = document.getElementById('kpi-item-list');
            const addBtn = document.getElementById('add-kpi-item-btn');
            const editorZoneEl = document.getElementById('kpi-editor-zone');
            const editorZoneTitleEl = document.getElementById('kpi-editor-zone-title');
            const editorListEl = document.getElementById('kpi-editor-list');
            const template = document.getElementById('kpi-item-template');
            const kpiModeTabsEl = document.getElementById('kpi-mode-tabs');
            const kpiModeTargetGroup = document.getElementById('kpi-mode-target-group');
            const kpiModeReportGroup = document.getElementById('kpi-mode-report-group');
            const kpiModeTargetBtn = document.getElementById('kpi-mode-target-btn');
            const kpiModeReportBtn = document.getElementById('kpi-mode-report-btn');
            const kpiModeTargetSubtabsEl = document.getElementById('kpi-mode-target-subtabs');
            const kpiModeReportSubtabsEl = document.getElementById('kpi-mode-report-subtabs');
            const kpiAvgDeptCardEl = document.getElementById('kpi-avg-dept-card');
            const kpiAvgDeptListEl = document.getElementById('kpi-avg-dept-list');
            const noticeFabEl = document.getElementById('kpi-notice-fab');
            const noticeModalEl = document.getElementById('kpi-notice-modal');
            const noticePanelEl = noticeModalEl ? noticeModalEl.querySelector('.kpi-notice-panel') : null;
            const noticeHeadEl = noticeModalEl ? noticeModalEl.querySelector('.kpi-notice-head') : null;
            const noticeCloseBtn = document.getElementById('kpi-notice-close');
            const noticeLevel1SectionEl = document.getElementById('kpi-notice-level1-section');
            const noticeLevel2SectionEl = document.getElementById('kpi-notice-level2-section');
            const noticeLevel3SectionEl = document.getElementById('kpi-notice-level3-section');
            const noticeLevel1ListEl = document.getElementById('kpi-notice-level1-list');
            const noticeLevel2ListEl = document.getElementById('kpi-notice-level2-list');
            const noticeLevel3CardEl = document.getElementById('kpi-notice-level3-card');
            const noticeLevel3MainEl = document.getElementById('kpi-notice-level3-main');
            const noticeLevel3SubEl = document.getElementById('kpi-notice-level3-sub');
            const noticeLevel3HintEl = document.getElementById('kpi-notice-level3-hint');
            const noticeLevel3TableBodyEl = document.getElementById('kpi-notice-level3-table-body');
            const noticeLevel3ModalEl = document.getElementById('kpi-notice-level3-modal');
            const noticeLevel3ModalTitleEl = document.getElementById('kpi-notice-level3-modal-title');
            const noticeLevel3ModalCloseEl = document.getElementById('kpi-notice-level3-modal-close');
            const noticeFileModalEl = document.getElementById('kpi-notice-file-modal');
            const noticeFileModalTitleEl = document.getElementById('kpi-notice-file-modal-title');
            const noticeFileModalCloseEl = document.getElementById('kpi-notice-file-modal-close');
            const noticePreviewImageEl = document.getElementById('kpi-notice-preview-image');
            const noticePreviewIframeEl = document.getElementById('kpi-notice-preview-iframe');
            const noticePreviewEmptyEl = document.getElementById('kpi-notice-preview-empty');
            const noticeAnnouncementModalEl = document.getElementById('kpi-notice-announcement-modal');
            const noticeAnnouncementModalTitleEl = document.getElementById('kpi-notice-announcement-modal-title');
            const noticeAnnouncementModalCloseEl = document.getElementById('kpi-notice-announcement-modal-close');
            const noticeAnnouncementModalCardEl = document.getElementById('kpi-notice-announcement-modal-card');
            const rejectDetailModalEl = document.getElementById('kpi-reject-detail-modal');
            const rejectDetailModalTitleEl = document.getElementById('kpi-reject-detail-modal-title');
            const rejectDetailModalCloseEl = document.getElementById('kpi-reject-detail-modal-close');
            const rejectDetailModalTextEl = document.getElementById('kpi-reject-detail-modal-text');
            const moveOverlayToBody = (element) => {
                if (!element || !document.body || element.parentElement === document.body) {
                    return;
                }
                document.body.appendChild(element);
            };
            moveOverlayToBody(noticeModalEl);
            moveOverlayToBody(noticeLevel3ModalEl);
            moveOverlayToBody(noticeFileModalEl);
            moveOverlayToBody(noticeAnnouncementModalEl);
            moveOverlayToBody(rejectDetailModalEl);
            const cycleContext = @json($cycleContext);
            const savedCards = @json($savedCards);
            const unitOptions = @json($kpiUnits);
            const kpiInputPolicy = @json($kpiInputPolicy);
            const viewerUserId = Number(@json((int) auth()->id())) || 0;
            const noticeVisibilityLevel = Number(@json($noticeVisibilityLevel ?? 0)) || 0;
            const noticeAnnouncementsByLevel = @json($noticeAnnouncementsByLevel ?? []);
            const noticeLevelThreeCard = @json($noticeLevelThreeCard ?? null);
            const noticeLevelThreeRows = @json($noticeLevelThreeRows ?? []);
            const noticeLevelThreeCycleLabel = @json($noticeLevelThreeCycleLabel ?? '');
            const kpiDepartmentTargetPolicy = @json($kpiDepartmentTargetPolicy ?? []);
            const okrHierarchyRaw = @json($okrHierarchy ?? []);
            let kpiAverageByDepartmentState = @json($kpiAverageByDepartment ?? []);
            const saveStepOneUrl = @json(\Illuminate\Support\Facades\Route::has('kpi.input.item.step1.save') ? route('kpi.input.item.step1.save') : '#');
            const saveStepTwoUrl = @json(\Illuminate\Support\Facades\Route::has('kpi.input.item.step2.save') ? route('kpi.input.item.step2.save') : '#');
            const deleteItemUrl = @json(\Illuminate\Support\Facades\Route::has('kpi.input.item.delete') ? route('kpi.input.item.delete') : '#');
            const saveMonthUrl = @json(\Illuminate\Support\Facades\Route::has('kpi.input.month.save') ? route('kpi.input.month.save') : '#');
            const confirmResultUrl = @json(\Illuminate\Support\Facades\Route::has('kpi.input.result.confirm') ? route('kpi.input.result.confirm') : '#');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const canNoticeModal = !!(
                noticeModalEl &&
                noticeCloseBtn &&
                noticeLevel1SectionEl &&
                noticeLevel2SectionEl &&
                noticeLevel3SectionEl &&
                noticeLevel1ListEl &&
                noticeLevel2ListEl &&
                noticeLevel3CardEl &&
                noticeLevel3MainEl &&
                noticeLevel3SubEl &&
                noticeLevel3HintEl &&
                noticeLevel3TableBodyEl
            );
            const canNoticeLevel3Modal = !!(
                noticeLevel3ModalEl &&
                noticeLevel3ModalTitleEl &&
                noticeLevel3ModalCloseEl &&
                noticeLevel3TableBodyEl
            );
            const canNoticeFileModal = !!(
                noticeFileModalEl &&
                noticeFileModalTitleEl &&
                noticeFileModalCloseEl &&
                noticePreviewImageEl &&
                noticePreviewIframeEl &&
                noticePreviewEmptyEl
            );
            const canNoticeAnnouncementModal = !!(
                noticeAnnouncementModalEl &&
                noticeAnnouncementModalTitleEl &&
                noticeAnnouncementModalCloseEl &&
                noticeAnnouncementModalCardEl
            );
            const canRejectDetailModal = !!(
                rejectDetailModalEl &&
                rejectDetailModalTitleEl &&
                rejectDetailModalCloseEl &&
                rejectDetailModalTextEl
            );
            let noticeFabIgnoreClick = false;
            let noticeModalFloatingState = null;
            const noticeFabPositionStorageKey = `kpi-notice-fab-pos:${viewerUserId > 0 ? viewerUserId : 'guest'}`;
            if (!listEl || !addBtn || !template || !editorZoneEl || !editorListEl) {
                return;
            }

            const monthKeys = [
                'kpiMonth01', 'kpiMonth02', 'kpiMonth03', 'kpiMonth04',
                'kpiMonth05', 'kpiMonth06', 'kpiMonth07', 'kpiMonth08',
                'kpiMonth09', 'kpiMonth10', 'kpiMonth11', 'kpiMonth12',
            ];
            const criteriaOptions = [
                { value: '>', textKey: 'kpiCriteriaGreater' },
                { value: '>=', textKey: 'kpiCriteriaGreaterEqual' },
                { value: '<=', textKey: 'kpiCriteriaLessEqual' },
                { value: '<', textKey: 'kpiCriteriaLess' },
                { value: '=', textKey: 'kpiCriteriaEqual' },
                { value: '!=', textKey: 'kpiCriteriaNotEqual' },
            ];
            const CUSTOM_UNIT_VALUE = '__custom_unit_other__';
            const toBool = (value) => (
                value === true ||
                value === 1 ||
                value === '1' ||
                String(value || '').toLowerCase() === 'true'
            );
            const normalizeDepartmentCode = (value) => String(value || '')
                .trim()
                .toUpperCase()
                .replace(/\s+/g, '');
            const normalizeDepartmentCodeList = (values) => {
                if (!Array.isArray(values)) {
                    return [];
                }

                const normalized = values
                    .map((value) => normalizeDepartmentCode(value))
                    .filter((value) => value !== '');
                return [...new Set(normalized)];
            };
            const departmentTargetPolicy = (
                kpiDepartmentTargetPolicy &&
                typeof kpiDepartmentTargetPolicy === 'object'
            ) ? kpiDepartmentTargetPolicy : {};
            const canSelectTargetDepartments = toBool(departmentTargetPolicy.enabled);
            const targetDepartmentOptions = normalizeDepartmentCodeList(
                Array.isArray(departmentTargetPolicy.options)
                    ? departmentTargetPolicy.options
                    : []
            );
            const viewerDepartmentCode = normalizeDepartmentCode(
                departmentTargetPolicy && departmentTargetPolicy.viewer_department
                    ? departmentTargetPolicy.viewer_department
                    : ''
            );
            const targetDepartmentResultPositions = [
                'assist manager',
                'assistant manager',
                'manager',
                'supervisor',
                'senior staff',
                'staff',
                'engineer',
                'senior engineer',
            ];
            const canShowDepartmentKpiAverageBySelectedDepartment = targetDepartmentResultPositions.includes(
                String(kpiInputPolicy && kpiInputPolicy.position ? kpiInputPolicy.position : '')
                    .trim()
                    .toLowerCase()
            );
            const kpiModeTabPositions = ['assist manager', 'assistant manager', 'manager'];
            const isKpiModePosition = kpiModeTabPositions.includes(
                String(kpiInputPolicy && kpiInputPolicy.position ? kpiInputPolicy.position : '')
                    .trim()
                    .toLowerCase()
            );
            const hasTargetAssignment = toBool(departmentTargetPolicy.has_target_assignment);
            const hasReviewerAssignment = toBool(departmentTargetPolicy.has_reviewer_assignment);
            const showKpiModeTabs = isKpiModePosition || hasTargetAssignment;
            const canUseTargetMode = showKpiModeTabs && hasTargetAssignment;
            const canUseReportDepartmentSubtabs = hasTargetAssignment;
            const canShowCardModeSelection = true;
            const canUseCardModeSelection = canUseTargetMode && canSelectTargetDepartments;
            const kpiModeCycleId = Number(cycleContext && cycleContext.cycle_id ? cycleContext.cycle_id : 0) || 0;
            const normalizeNotificationFocusDecision = (value) => {
                const decision = String(value || '').trim().toLowerCase();
                if (decision === 'approved') return 'approved';
                if (decision === 'rejected') return 'rejected';
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
                    };
                }

                const scoreId = Number(params.get('focus_score_id') || 0);
                const monthNo = Number(params.get('focus_month_no') || 0);
                const decision = normalizeNotificationFocusDecision(params.get('focus_decision') || '');
                const safeScoreId = Number.isFinite(scoreId) && scoreId > 0 ? scoreId : 0;
                const safeMonthNo = Number.isFinite(monthNo) && monthNo >= 1 && monthNo <= 12 ? monthNo : 0;

                return {
                    active: safeScoreId > 0 || safeMonthNo > 0,
                    scoreId: safeScoreId,
                    monthNo: safeMonthNo,
                    decision,
                };
            })();
            const shouldOpenNoticeFromQuery = (() => {
                const params = new URLSearchParams(window.location.search);
                return String(params.get('open_notice') || '').trim() === '1';
            })();
            const clearOpenNoticeQueryFlag = () => {
                if (!shouldOpenNoticeFromQuery || !window.history || typeof window.history.replaceState !== 'function') {
                    return;
                }
                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.delete('open_notice');
                window.history.replaceState(null, '', nextUrl.toString());
            };
            let currentKpiMode = canUseTargetMode ? 'target' : 'report';
            const kpiModeStorageKey = `kpi-mode:${viewerUserId}:${kpiModeCycleId > 0 ? kpiModeCycleId : 'none'}`;
            const kpiTargetDepartmentStorageKey = `kpi-mode-target-dept:${viewerUserId}:${kpiModeCycleId > 0 ? kpiModeCycleId : 'none'}`;
            const kpiReportDepartmentStorageKey = `kpi-mode-report-dept:${viewerUserId}:${kpiModeCycleId > 0 ? kpiModeCycleId : 'none'}`;
            const kpiTargetHierarchyStorageKey = `kpi-mode-target-hierarchy:${viewerUserId}:${kpiModeCycleId > 0 ? kpiModeCycleId : 'none'}`;
            const kpiReportHierarchyStorageKey = `kpi-mode-report-hierarchy:${viewerUserId}:${kpiModeCycleId > 0 ? kpiModeCycleId : 'none'}`;
            const normalizeStoredHierarchyKey = (value) => {
                const raw = String(value || '').trim();
                const objMatch = raw.match(/^obj:(\d+)$/);
                if (objMatch) {
                    const id = Number(objMatch[1]);
                    return Number.isFinite(id) && id > 0 ? `obj:${Math.trunc(id)}` : '';
                }
                const parts = raw.split(':');
                if (parts.length !== 2) {
                    return '';
                }
                const objectiveId = Number(parts[0]);
                const keyResultId = Number(parts[1]);
                if (
                    !Number.isFinite(objectiveId) ||
                    !Number.isFinite(keyResultId) ||
                    objectiveId < 1 ||
                    keyResultId < 1
                ) {
                    return '';
                }
                return `${Math.trunc(objectiveId)}:${Math.trunc(keyResultId)}`;
            };
            const buildOkrLevelThreeFilterKey = (entryId) => {
                const safeEntryId = Number(entryId || 0);
                return Number.isFinite(safeEntryId) && safeEntryId > 0
                    ? `level3:${Math.trunc(safeEntryId)}`
                    : '';
            };
            const normalizeOkrLevelThreeFilterKey = (value) => {
                const raw = String(value || '').trim();
                const match = raw.match(/^level3:(\d+)$/);
                if (!match) {
                    return '';
                }
                return buildOkrLevelThreeFilterKey(Number(match[1]));
            };
            const readSavedKpiMode = () => {
                try {
                    if (!window.localStorage) {
                        return '';
                    }
                    const raw = String(window.localStorage.getItem(kpiModeStorageKey) || '')
                        .trim()
                        .toLowerCase();
                    return raw === 'target' || raw === 'report' ? raw : '';
                } catch (error) {
                    return '';
                }
            };
            const saveCurrentKpiMode = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const safeMode = currentKpiMode === 'target' && canUseTargetMode
                        ? 'target'
                        : 'report';
                    window.localStorage.setItem(kpiModeStorageKey, safeMode);
                } catch (error) {
                    // no-op
                }
            };
            const readSavedTargetDepartment = () => {
                try {
                    if (!window.localStorage) {
                        return '';
                    }
                    return normalizeDepartmentCode(window.localStorage.getItem(kpiTargetDepartmentStorageKey) || '');
                } catch (error) {
                    return '';
                }
            };
            const saveCurrentTargetDepartment = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const safeDepartment = normalizeDepartmentCode(currentTargetDepartment || '');
                    if (safeDepartment === '') {
                        window.localStorage.removeItem(kpiTargetDepartmentStorageKey);
                    } else {
                        window.localStorage.setItem(kpiTargetDepartmentStorageKey, safeDepartment);
                    }
                } catch (error) {
                    // no-op
                }
            };
            const readSavedReportDepartment = () => {
                try {
                    if (!window.localStorage) {
                        return '';
                    }
                    return normalizeDepartmentCode(window.localStorage.getItem(kpiReportDepartmentStorageKey) || '');
                } catch (error) {
                    return '';
                }
            };
            const saveCurrentReportDepartment = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const safeDepartment = normalizeDepartmentCode(currentReportDepartment || '');
                    if (safeDepartment === '') {
                        window.localStorage.removeItem(kpiReportDepartmentStorageKey);
                    } else {
                        window.localStorage.setItem(kpiReportDepartmentStorageKey, safeDepartment);
                    }
                } catch (error) {
                    // no-op
                }
            };
            const readSavedTargetHierarchyKey = () => {
                try {
                    if (!window.localStorage) {
                        return '';
                    }
                    return normalizeStoredHierarchyKey(window.localStorage.getItem(kpiTargetHierarchyStorageKey) || '');
                } catch (error) {
                    return '';
                }
            };
            const saveCurrentTargetHierarchyKey = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const safeKey = normalizeStoredHierarchyKey(currentTargetHierarchyKey || '');
                    if (safeKey === '') {
                        window.localStorage.removeItem(kpiTargetHierarchyStorageKey);
                    } else {
                        window.localStorage.setItem(kpiTargetHierarchyStorageKey, safeKey);
                    }
                } catch (error) {
                    // no-op
                }
            };
            const readSavedReportHierarchyKey = () => {
                try {
                    if (!window.localStorage) {
                        return '';
                    }
                    return normalizeOkrLevelThreeFilterKey(window.localStorage.getItem(kpiReportHierarchyStorageKey) || '');
                } catch (error) {
                    return '';
                }
            };
            const saveCurrentReportHierarchyKey = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const safeKey = normalizeOkrLevelThreeFilterKey(currentReportHierarchyKey || '');
                    if (safeKey === '') {
                        window.localStorage.removeItem(kpiReportHierarchyStorageKey);
                    } else {
                        window.localStorage.setItem(kpiReportHierarchyStorageKey, safeKey);
                    }
                } catch (error) {
                    // no-op
                }
            };
            let currentTargetDepartment = '';
            let currentReportDepartment = '';
            let currentTargetDepartmentFilterAll = true;
            let currentReportDepartmentFilterAll = true;
            let currentTargetHierarchyKey = '';
            let currentReportHierarchyKey = '';
            const savedKpiMode = readSavedKpiMode();
            if (savedKpiMode === 'target' && canUseTargetMode) {
                currentKpiMode = 'target';
            } else if (savedKpiMode === 'report') {
                currentKpiMode = 'report';
            }
            if (notificationFocusState.active) {
                currentKpiMode = 'report';
            }
            if (!canUseTargetMode && currentKpiMode === 'target') {
                currentKpiMode = 'report';
            }
            saveCurrentKpiMode();
            const hideKpiCreateUiPositions = ['deputy general manager', 'general manager'];
            const shouldHideKpiCreateUi = hideKpiCreateUiPositions.includes(
                String(kpiInputPolicy && kpiInputPolicy.position ? kpiInputPolicy.position : '')
                    .trim()
                    .toLowerCase()
            );
            const resolveCurrentLang = () => {
                if (window.AppModal && typeof window.AppModal.getLang === 'function') {
                    return window.AppModal.getLang() === 'th' ? 'th' : 'en';
                }
                return new URLSearchParams(window.location.search).get('lang') === 'th' ? 'th' : 'en';
            };
            const getKpiAverageDepartmentLabelPrefix = () => (
                resolveCurrentLang() === 'th'
                    ? '\u0e1c\u0e25\u0e25\u0e31\u0e1e\u0e18\u0e4c KPIs \u0e40\u0e09\u0e25\u0e35\u0e48\u0e22\u0e41\u0e1c\u0e19\u0e01'
                    : 'Average KPI Result Department'
            );
            const getKpiAverageSummaryDepartmentLabelPrefix = () => (
                resolveCurrentLang() === 'th'
                    ? '\u0e04\u0e48\u0e32\u0e40\u0e09\u0e25\u0e35\u0e48\u0e22\u0e1c\u0e25\u0e25\u0e31\u0e1e\u0e18\u0e4c KPI \u0e41\u0e1c\u0e19\u0e01'
                    : 'Average KPI Result Department'
            );
            const getKpiModeReportTabLabel = (departmentCode = '') => {
                const safeDepartment = normalizeDepartmentCode(departmentCode);
                const lang = resolveCurrentLang();
                if (safeDepartment === '') {
                    return lang === 'th' ? '\u0e23\u0e32\u0e22\u0e07\u0e32\u0e19' : 'Report';
                }

                return lang === 'th'
                    ? `\u0e23\u0e32\u0e22\u0e07\u0e32\u0e19 ${safeDepartment}`
                    : `Report ${safeDepartment}`;
            };
            const getKpiModeTargetTabLabel = (departmentCode = '') => {
                const safeDepartment = normalizeDepartmentCode(departmentCode);
                const lang = resolveCurrentLang();
                if (safeDepartment === '') {
                    return lang === 'th' ? '\u0e40\u0e1b\u0e49\u0e32\u0e2b\u0e21\u0e32\u0e22' : 'Target';
                }

                return lang === 'th'
                    ? `\u0e40\u0e1b\u0e49\u0e32\u0e2b\u0e21\u0e32\u0e22 ${safeDepartment}`
                    : `Target ${safeDepartment}`;
            };
            const getKpiAverageSummaryByDepartmentLabel = (departmentCode = '') => {
                const safeDepartment = normalizeDepartmentCode(departmentCode);
                if (safeDepartment === '') {
                    return getKpiAverageSummaryLabel();
                }

                return resolveCurrentLang() === 'th'
                    ? `\u0e04\u0e48\u0e32\u0e40\u0e09\u0e25\u0e35\u0e48\u0e22\u0e1c\u0e25\u0e25\u0e31\u0e1e\u0e18\u0e4c KPI \u0e41\u0e1c\u0e19\u0e01 ${safeDepartment}`
                    : `Average KPI Result Department ${safeDepartment}`;
            };
            const renderDepartmentLabelWithStrongSuffix = (containerEl, prefixText, departmentCode, suffixText = '') => {
                if (!containerEl) {
                    return;
                }

                const safePrefix = String(prefixText || '').trim();
                const safeDepartment = normalizeDepartmentCode(departmentCode);
                const safeSuffix = String(suffixText || '');
                containerEl.innerHTML = '';

                if (safePrefix !== '') {
                    containerEl.append(document.createTextNode(safePrefix));
                }

                if (safeDepartment !== '') {
                    containerEl.append(document.createTextNode(' '));
                    const strongEl = document.createElement('strong');
                    strongEl.textContent = safeDepartment;
                    containerEl.append(strongEl);
                }

                if (safeSuffix !== '') {
                    containerEl.append(document.createTextNode(safeSuffix));
                }
            };
            const getKpiModeTabLabel = (mode) => {
                const lang = resolveCurrentLang();
                if (mode === 'target') {
                    return lang === 'th' ? '\u0e40\u0e1b\u0e49\u0e32\u0e2b\u0e21\u0e32\u0e22' : 'Target';
                }

                return lang === 'th' ? '\u0e23\u0e32\u0e22\u0e07\u0e32\u0e19' : 'Report';
            };
            const getKpiAverageSummaryLabel = () => (
                resolveCurrentLang() === 'th'
                    ? '\u0e04\u0e48\u0e32\u0e40\u0e09\u0e25\u0e35\u0e48\u0e22\u0e1c\u0e25\u0e25\u0e31\u0e1e\u0e18\u0e4c KPI'
                    : 'Average KPI Result'
            );
            const getKpiAveragePendingText = () => (
                resolveCurrentLang() === 'th'
                    ? '\u0e40\u0e1b\u0e49\u0e32\u0e2b\u0e21\u0e32\u0e22\u0e15\u0e31\u0e27\u0e0a\u0e35\u0e49\u0e27\u0e31\u0e14'
                    : 'Waiting for confirmation'
            );
            const defaultTargetDepartments = normalizeDepartmentCodeList(
                Array.isArray(departmentTargetPolicy.default_departments)
                    ? departmentTargetPolicy.default_departments
                    : []
            );
            const canSkipEvidenceUpload = toBool(kpiInputPolicy && kpiInputPolicy.can_skip_evidence);
            const requireActionPlanOnFail = toBool(kpiInputPolicy && kpiInputPolicy.require_action_plan_on_fail);
            const showActionPlanOnFail = toBool(kpiInputPolicy && kpiInputPolicy.show_action_plan_on_fail) || requireActionPlanOnFail;
            const activeCycleId = kpiModeCycleId;
            const hiddenCardStorageKey = `kpi-hidden-cards:${viewerUserId}:${activeCycleId > 0 ? activeCycleId : 'none'}`;
            const hiddenCardItemIds = (() => {
                try {
                    const raw = window.localStorage ? window.localStorage.getItem(hiddenCardStorageKey) : null;
                    if (!raw) {
                        return new Set();
                    }
                    const parsed = JSON.parse(raw);
                    if (!Array.isArray(parsed)) {
                        return new Set();
                    }
                    return new Set(
                        parsed
                            .map((value) => Number(value))
                            .filter((value) => Number.isFinite(value) && value > 0)
                    );
                } catch (error) {
                    return new Set();
                }
            })();

            const saveHiddenCardItemIds = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const payload = [...hiddenCardItemIds]
                        .map((value) => Number(value))
                        .filter((value) => Number.isFinite(value) && value > 0)
                        .sort((a, b) => a - b);
                    window.localStorage.setItem(hiddenCardStorageKey, JSON.stringify(payload));
                } catch (error) {
                    // no-op
                }
            };

            const getCardItemId = (cardEl) => {
                if (!cardEl) {
                    return 0;
                }
                const id = Number(cardEl.dataset.itemId || 0);
                return Number.isFinite(id) && id > 0 ? id : 0;
            };

            const setCardHiddenPreference = (cardEl, hidden) => {
                const itemId = getCardItemId(cardEl);
                if (itemId < 1) {
                    return;
                }
                if (hidden) {
                    hiddenCardItemIds.add(itemId);
                } else {
                    hiddenCardItemIds.delete(itemId);
                }
                saveHiddenCardItemIds();
            };

            const isCardHiddenByPreference = (cardEl) => {
                const itemId = getCardItemId(cardEl);
                if (itemId < 1) {
                    return false;
                }
                return hiddenCardItemIds.has(itemId);
            };

            const cleanupHiddenCardPreferences = () => {
                const validItemIds = new Set(
                    (Array.isArray(savedCards) ? savedCards : [])
                        .map((card) => Number(card && card.item_id ? card.item_id : 0))
                        .filter((id) => Number.isFinite(id) && id > 0)
                );

                let changed = false;
                [...hiddenCardItemIds].forEach((itemId) => {
                    if (!validItemIds.has(itemId)) {
                        hiddenCardItemIds.delete(itemId);
                        changed = true;
                    }
                });

                if (changed) {
                    saveHiddenCardItemIds();
                }
            };

            cleanupHiddenCardPreferences();
            const normalizeCardModeType = (value) => {
                return String(value || '').trim().toLowerCase() === 'target'
                    ? 'target'
                    : 'report';
            };
            const resolveAllowedCardModeType = (value) => {
                const normalized = normalizeCardModeType(value);
                if (normalized === 'target' && !canUseCardModeSelection) {
                    return 'report';
                }
                return normalized;
            };
            const cardModeStorageKey = `kpi-card-mode:${viewerUserId}:${activeCycleId > 0 ? activeCycleId : 'none'}`;
            const cardModeByItemId = (() => {
                try {
                    const raw = window.localStorage ? window.localStorage.getItem(cardModeStorageKey) : null;
                    if (!raw) {
                        return {};
                    }
                    const parsed = JSON.parse(raw);
                    if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                        return {};
                    }

                    const normalized = {};
                    Object.entries(parsed).forEach(([rawItemId, rawMode]) => {
                        const itemId = Number(rawItemId);
                        if (!Number.isFinite(itemId) || itemId < 1) {
                            return;
                        }
                        normalized[String(itemId)] = resolveAllowedCardModeType(rawMode);
                    });
                    return normalized;
                } catch (error) {
                    return {};
                }
            })();
            const saveCardModePreferences = () => {
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    window.localStorage.setItem(cardModeStorageKey, JSON.stringify(cardModeByItemId));
                } catch (error) {
                    // no-op
                }
            };
            const getStoredCardModeType = (itemId) => {
                const safeId = Number(itemId);
                if (!Number.isFinite(safeId) || safeId < 1) {
                    return '';
                }
                const stored = String(cardModeByItemId[String(safeId)] || '').trim().toLowerCase();
                if (stored !== 'report' && stored !== 'target') {
                    return '';
                }
                return resolveAllowedCardModeType(stored);
            };
            const setStoredCardModeType = (itemId, modeType) => {
                const safeId = Number(itemId);
                if (!Number.isFinite(safeId) || safeId < 1) {
                    return;
                }
                cardModeByItemId[String(safeId)] = resolveAllowedCardModeType(modeType);
                saveCardModePreferences();
            };
            const clearStoredCardModeType = (itemId) => {
                const safeId = Number(itemId);
                if (!Number.isFinite(safeId) || safeId < 1) {
                    return;
                }
                if (!Object.prototype.hasOwnProperty.call(cardModeByItemId, String(safeId))) {
                    return;
                }
                delete cardModeByItemId[String(safeId)];
                saveCardModePreferences();
            };
            const cleanupCardModePreferences = () => {
                const validItemIds = new Set(
                    (Array.isArray(savedCards) ? savedCards : [])
                        .map((card) => Number(card && card.item_id ? card.item_id : 0))
                        .filter((id) => Number.isFinite(id) && id > 0)
                );

                let changed = false;
                Object.keys(cardModeByItemId).forEach((rawItemId) => {
                    const itemId = Number(rawItemId);
                    if (!Number.isFinite(itemId) || !validItemIds.has(itemId)) {
                        delete cardModeByItemId[rawItemId];
                        changed = true;
                    }
                });

                if (changed) {
                    saveCardModePreferences();
                }
            };

            cleanupCardModePreferences();
            let noticePreviewObjectUrl = '';

            let cardSeq = 0;
            const fallbackTexts = {
                kpiAddCard: 'Add KPI Report',
                kpiEditorZoneTitle: 'Add/Edit KPI Report',
                kpiCardTitle: 'KPI Item',
                kpiBtnRemoveCard: 'Remove',
                kpiLabelTargetDepartments: 'Select Department',
                kpiLabelCardMode: 'KPI Type',
                kpiCardModeReport: 'Report',
                kpiCardModeTarget: 'Target',
                kpiSelectPlaceholder: 'Select',
                kpiLabelOkrHierarchy: 'Select Topic Level 1 / Topic Level 2',
                kpiLabelOkrLevelOne: 'Level 1',
                kpiLabelOkrLevelTwo: 'Level 2',
                kpiLabelOkrLevelThree: 'Level 3',
                kpiLabelReportLevelThree: 'Select Topic Level 3',
                kpiLabelTargetLevelOne: 'Topic 1',
                kpiLabelTargetLevelTwo: 'Topic 2',
                kpiCompactSectionTarget: 'Topic 3',
                kpiCompactSectionReport: 'Topic 4',
                kpiOkrHierarchyPlaceholder: 'Select',
                kpiOkrHierarchyNoData: 'No Topic 1 and 2 available',
                kpiOkrLevelThreePlaceholder: 'Select',
                kpiOkrLevelThreeNoData: 'No Level 3 target available',
                kpiFilterAll: 'All',
                kpiLabelObjective: 'Objective',
                kpiLabelDetail: 'Detail',
                kpiDocumentLabel: 'Document',
                kpiBtnNext: 'Save',
                kpiLabelTarget: 'Target',
                kpiTargetPlaceholder: 'Example: 2.30',
                kpiLabelUnit: 'Unit',
                kpiUnitPlaceholder: 'Select Unit',
                kpiCriteriaSectionTitle: 'Define Criteria',
                kpiHasCriteriaQuestion: 'Do you want to define criteria?',
                kpiHasCriteriaYes: 'Yes',
                kpiHasCriteriaNo: 'No',
                kpiLabelCriteria: 'Criteria',
                kpiCriteriaGreater: '>         (Greater than)',
                kpiCriteriaGreaterEqual: '\u2265         (Greater than or equal)',
                kpiCriteriaLessEqual: '\u2264         (Less than or equal)',
                kpiCriteriaLess: '<         (Less than)',
                kpiCriteriaEqual: '=         (Equal)',
                kpiCriteriaNotEqual: '\u2260         (Not equal)',
                kpiScoreTitle: 'KPI Score (12 Months)',
                kpiResultLabel: 'Average KPI Result',
                kpiResultPending: 'Waiting for KPI formula',
                kpiResultSummary: 'Passed {pass} of {total} months',
                kpiBtnConfirmResult: 'Confirm Result',
                kpiResultApprovedAverageLabel: 'Approved KPI Item Average',
                kpiResultPendingAverageLabel: 'Pending KPI Items',
                kpiResultOverallAverageLabel: 'All Saved KPI Item Average',
                kpiStatusDraft: 'Draft',
                kpiStatusConfirmed: 'Confirmed',
                kpiMonthScoreLabel: 'Score',
                kpiMonthEvidenceLabel: 'Evidence Files',
                kpiMonthEvidenceHint: 'Accepted: Word, PDF, PNG, JPG, Excel (up to 1GB - placeholder)',
                kpiMonthEvidenceNotSelected: 'No files selected',
                kpiMonthEvidenceSelected: '{count} file(s) selected',
                kpiMonthActionPlanLabel: 'Action Plan Files',
                kpiMonthActionPlanNotSelected: 'No files selected',
                kpiMonthActionPlanSelected: '{count} file(s) selected',
                kpiMonthSaveBtn: 'Save',
                kpiMonthEditBtn: 'Edit',
                kpiMonthNotSaved: 'Not saved',
                kpiMonthSavedPass: 'Pass criteria',
                kpiMonthSavedFail: 'Not pass criteria',
                kpiMonth01: 'January',
                kpiMonth02: 'February',
                kpiMonth03: 'March',
                kpiMonth04: 'April',
                kpiMonth05: 'May',
                kpiMonth06: 'June',
                kpiMonth07: 'July',
                kpiMonth08: 'August',
                kpiMonth09: 'September',
                kpiMonth10: 'October',
                kpiMonth11: 'November',
                kpiMonth12: 'December',
                confirmAddKpiItem: 'Do you want to add a new KPI report?',
                confirmRemoveKpiItem: 'Do you want to remove this KPI card?',
                confirmOpenTargetSection: 'Do you want to continue to Target?',
                confirmOpenScoreSection: 'Do you want to continue to KPI score input?',
                confirmKpiResult: 'Do you want to confirm this KPI result?',
                confirmSaveMonthData: 'Do you want to save this month score?',
                kpiAlertNeedObjectiveDetail: 'Please complete Objective and Detail first.',
                kpiAlertNeedOkrHierarchy: 'Please select Level 1 and 2 first.',
                kpiAlertNeedOkrLevelThree: 'Please select Level 3 first.',
                kpiAlertNeedTargetAndCriteria: 'Please complete Target and Criteria first.',
                kpiAlertNeedTargetCriteriaAndUnit: 'Please complete Target, Unit, and Criteria first.',
                kpiAlertNeedCriteriaChoice: 'Please choose whether you want to define criteria.',
                kpiAlertNeedTarget: 'Please complete Target first.',
                kpiAlertNeedNumericTarget: 'Target must be a valid number.',
                kpiAlertNeedMonthScore: 'Please enter month score.',
                kpiAlertNeedMonthEvidence: 'Please attach at least one evidence file.',
                kpiAlertNeedActionPlan: 'Please attach Action Plan.',
                kpiAlertNeedAllMonths: 'Please save scores for all 12 months first.',
                kpiAlertMonthSaved: 'Month score saved.',
                kpiAlertMinimumOneCard: 'At least one KPI card is required.',
                kpiAlertResultConfirmed: 'KPI report submitted for review successfully.',
            };
            Object.assign(fallbackTexts, {
                kpiBtnEditCard: 'Edit',
                kpiBtnHideCard: 'Hide \u2191',
                kpiCriteriaGreaterEqual: '\u2265         (Greater than or equal)',
                kpiCriteriaLessEqual: '\u2264         (Less than or equal)',
                kpiCriteriaNotEqual: '\u2260         (Not equal)',
                kpiResultLabel: 'Average KPI Result',
                kpiResultPending: 'Waiting for approval',
                kpiResultSummary: '{percent}% ({point}/{max})',
                kpiResultCompactSummary: 'OKR Summary: {percent}% ({point}/{max})',
                kpiResultApprovedAverageLabel: 'Approved KPI Item Average',
                kpiResultPendingAverageLabel: 'Pending KPI Items',
                kpiResultOverallAverageLabel: 'All Saved KPI Item Average',
                kpiMonthStatusLabel: 'Status',
                kpiMonthCycleOpenLabel: 'Cycle Open Date',
                kpiMonthCycleOpenPending: '-',
                kpiSummaryTitle: 'KPI Monthly Summary',
                kpiSummaryColMonth: 'Month',
                kpiSummaryColScore: 'Score',
                kpiSummaryColCriteria: 'Criteria',
                kpiSummaryColResult: 'Result',
                kpiSummaryNetAverage: 'Net Average Score',
                kpiCycleActiveLabel: 'Active Cycle',
                kpiCyclePeriodLabel: 'Period',
                kpiCycleHelp: 'You can save month score only for months that admin opened.',
                kpiCycleNoActive: 'No active cycle. Please contact admin to activate cycle first.',
                kpiMonthLocked: 'Locked (month not opened)',
                kpiSummaryTotalPending: 'No month saved yet.',
                kpiSummaryTotalLine: 'Saved {saved}/{total} months | Pass {pass} | Score {point}/{max} ({percent}%)',
                kpiMonthReviewPending: 'Pending action',
                kpiMonthReviewApproved: 'Approved',
                kpiMonthReviewRejected: 'Rejected',
                kpiMonthReviewDetailLabel: 'Reject detail',
                kpiCardReviewStatusLabel: 'Report status',
                kpiCardReportStatusTitle: 'Status',
                kpiCardReportStatusMonthsTitle: '12-Month Status',
                kpiCardReportStatusPending: 'Pending action',
                kpiCardReportStatusApproved: 'Approved',
                kpiCardReportStatusRejected: 'Rejected',
                kpiCardReportStatusWaitingScore: 'Waiting report score',
                confirmHideKpiItem: 'Do you want to hide this KPI card?',
                confirmEditKpiItem: 'Do you want to edit this KPI card?',
                kpiAlertNeedConfirmResult: 'Please confirm result first.',
                kpiAlertNeedSavedMonth: 'Please save at least 1 month first.',
                kpiAlertNeedAllMonthEvidence: 'Please attach evidence files for all 12 months.',
                kpiAlertCardHidden: 'KPI card hidden.',
                kpiAlertCardEditMode: 'Edit mode is now enabled.',
                kpiAlertNeedActiveCycle: 'No active cycle. Please contact admin first.',
                kpiAlertNeedStepOne: 'Please save Objective and Detail first.',
                kpiAlertNeedStepTwo: 'Please save Target and Criteria first.',
                kpiAlertNeedOkrHierarchy: 'Please select Level 1 and 2 first.',
                kpiAlertNeedOkrLevelThree: 'Please select Level 3 first.',
                kpiAlertStepOneSaveFailed: 'Unable to save Objective and Detail.',
                kpiAlertStepTwoSaveFailed: 'Unable to save Target and Criteria.',
                kpiAlertDeleteFailed: 'Unable to delete this KPI card.',
                kpiAlertMonthLocked: 'This month is not opened yet by admin.',
                kpiAlertMonthSaveFailed: 'Unable to save this month score.',
                kpiAlertResultSaveFailed: 'Unable to confirm KPI result.',
                kpiAlertCardDeleted: 'KPI card deleted.',
                kpiSaving: 'Saving...',
                kpiNoticeTitle: 'Review Organization Targets',
                kpiNoticeHint: 'Review Organization Targets',
                kpiNoticeModalTitle: 'Review Organization Targets',
                kpiNoticeModalClose: 'Close',
                kpiNoticeSectionLevel1: 'Level 1',
                kpiNoticeSectionLevel2: 'Level 2',
                kpiNoticeSectionLevel3: 'Level 3',
                kpiNoticeDeptLabel: 'Department',
                kpiNoticeTitleLabel: 'Title',
                kpiNoticeDetailLabel: 'Detail',
                kpiNoticeFilesLabel: 'Attached Files',
                kpiNoticePostedLabel: 'Posted at',
                kpiNoticeNoData: 'No announcement data found.',
                kpiNoticeNoFile: '-',
                kpiNoticeLevel3DeptLabel: 'Department',
                kpiNoticeLevel3Hint: 'Click to review information',
                kpiNoticeLevel3ModalTitleTpl: 'Department {dept} KPI Table',
                kpiNoticeTableNo: 'No.',
                kpiNoticeTableEmployeeCode: 'Employee Code',
                kpiNoticeTableFullName: 'Full Name',
                kpiNoticeTablePosition: 'Position',
                kpiNoticeTableObjective: 'Objective',
                kpiNoticeTableDetail: 'Detail',
                kpiNoticeTableTargetValue: 'Target Value',
                kpiNoticeTableGoal: 'Goal',
                kpiNoticeTableUnit: 'Unit',
                kpiNoticeTableEmpty: 'No KPI rows found for this department.',
                kpiNoticeFilePreviewFail: 'Unable to preview this file.',
                kpiNoticeFilePreviewUnsupported: 'This file type is not supported for in-page preview.',
            });

            const getText = (key) => {
                if (window.AppModal && typeof window.AppModal.getText === 'function') {
                    return window.AppModal.getText(key);
                }

                return fallbackTexts[key] || key;
            };

            const tpl = (text, vars = {}) => {
                return Object.keys(vars).reduce((out, key) => {
                    return out.replace(`{${key}}`, String(vars[key]));
                }, String(text || ''));
            };

            const normalizeOkrHierarchy = (rows) => {
                if (!Array.isArray(rows)) {
                    return [];
                }

                return rows
                    .map((rawObjective) => {
                        const objective = rawObjective && typeof rawObjective === 'object' ? rawObjective : {};
                        const objectiveId = Number(objective.id || 0);
                        if (!Number.isFinite(objectiveId) || objectiveId < 1) {
                            return null;
                        }

                        const keyResults = Array.isArray(objective.key_results)
                            ? objective.key_results
                            : [];
                        const normalizedKeyResults = keyResults
                            .map((rawKeyResult) => {
                                const keyResult = rawKeyResult && typeof rawKeyResult === 'object' ? rawKeyResult : {};
                                const keyResultId = Number(keyResult.id || 0);
                                if (!Number.isFinite(keyResultId) || keyResultId < 1) {
                                    return null;
                                }

                                const kpiEntries = Array.isArray(keyResult.kpi_entries)
                                    ? keyResult.kpi_entries
                                    : [];
                                const normalizedKpiEntries = kpiEntries
                                    .map((rawEntry) => {
                                        const entry = rawEntry && typeof rawEntry === 'object' ? rawEntry : {};
                                        const entryId = Number(entry.id || 0);
                                        if (!Number.isFinite(entryId) || entryId < 1) {
                                            return null;
                                        }

                                        const targetDepartments = normalizeDepartmentCodeList(
                                            Array.isArray(entry.target_departments)
                                                ? entry.target_departments
                                                : []
                                        );
                                        const fallbackDepartment = normalizeDepartmentCode(entry.dept_abbr_hr || '');
                                        if (targetDepartments.length < 1 && fallbackDepartment !== '') {
                                            targetDepartments.push(fallbackDepartment);
                                        }

                                        return {
                                            id: Math.trunc(entryId),
                                            objective: String(entry.objective || '').trim(),
                                            detail: String(entry.detail || '').trim(),
                                            dept_abbr_hr: fallbackDepartment,
                                            target_departments: targetDepartments,
                                            employee_code: String(entry.employee_code || '').trim(),
                                            full_name_th: String(entry.full_name_th || '').trim(),
                                            full_name_en: String(entry.full_name_en || '').trim(),
                                        };
                                    })
                                    .filter(Boolean);

                                return {
                                    id: Math.trunc(keyResultId),
                                    sort_no: Number(keyResult.sort_no || 0) || 0,
                                    title: String(keyResult.title || '').trim(),
                                    dept_abbr_hr: normalizeDepartmentCode(keyResult.dept_abbr_hr || ''),
                                    kpi_entries: normalizedKpiEntries,
                                };
                            })
                            .filter(Boolean);

                        return {
                            id: Math.trunc(objectiveId),
                            sort_no: Number(objective.sort_no || 0) || 0,
                            title: String(objective.title || '').trim(),
                            key_results: normalizedKeyResults,
                        };
                    })
                    .filter((objective) => objective && objective.key_results.length > 0);
            };
            const okrHierarchyOptions = normalizeOkrHierarchy(okrHierarchyRaw);
            const hasOkrHierarchyOptions = () => okrHierarchyOptions.some((objective) => {
                return objective && Array.isArray(objective.key_results) && objective.key_results.length > 0;
            });
            const buildOkrSelectionValue = (objectiveId, keyResultId) => {
                const safeObjectiveId = Number(objectiveId || 0);
                const safeKeyResultId = Number(keyResultId || 0);
                if (
                    !Number.isFinite(safeObjectiveId) ||
                    !Number.isFinite(safeKeyResultId) ||
                    safeObjectiveId < 1 ||
                    safeKeyResultId < 1
                ) {
                    return '';
                }

                return `${Math.trunc(safeObjectiveId)}:${Math.trunc(safeKeyResultId)}`;
            };
            const buildOkrHierarchyFilterKey = (objectiveId, keyResultId) => {
                return buildOkrSelectionValue(objectiveId, keyResultId);
            };
            const getOkrHierarchyDeptOptions = () => {
                const seen = new Set();
                const depts = [];
                okrHierarchyOptions.forEach((objective) => {
                    (objective.key_results || []).forEach((kr) => {
                        const dept = String(kr.dept_abbr_hr || '').trim().toUpperCase();
                        if (dept !== '' && !seen.has(dept)) {
                            seen.add(dept);
                            depts.push(dept);
                        }
                    });
                });
                return depts.sort();
            };
            const buildOkrObjectiveFilterKey = (objectiveId) => {
                const id = Number(objectiveId || 0);
                return Number.isFinite(id) && id > 0 ? `obj:${Math.trunc(id)}` : '';
            };
            const parseOkrObjectiveFilterKey = (value) => {
                const match = String(value || '').trim().match(/^obj:(\d+)$/);
                return match ? Number(match[1]) : null;
            };
            const parseOkrSelectionValue = (value) => {
                const parts = String(value || '').split(':');
                if (parts.length !== 2) {
                    return null;
                }

                const objectiveId = Number(parts[0]);
                const keyResultId = Number(parts[1]);
                if (
                    !Number.isFinite(objectiveId) ||
                    !Number.isFinite(keyResultId) ||
                    objectiveId < 1 ||
                    keyResultId < 1
                ) {
                    return null;
                }

                return {
                    objectiveId: Math.trunc(objectiveId),
                    keyResultId: Math.trunc(keyResultId),
                };
            };
            const formatOkrObjectiveLabel = (objective) => {
                const sortNo = Number(objective && objective.sort_no ? objective.sort_no : 0) || 0;
                const title = String(objective && objective.title ? objective.title : '').trim();
                const prefix = sortNo > 0 ? `${sortNo}. ` : '';
                return `${prefix}${title || (resolveCurrentLang() === 'th' ? 'ลำดับชั้น 1' : 'Level 1')}`;
            };
            const formatOkrKeyResultLabel = (keyResult) => {
                const title = String(keyResult && keyResult.title ? keyResult.title : '').trim();
                const department = normalizeDepartmentCode(keyResult && keyResult.dept_abbr_hr ? keyResult.dept_abbr_hr : '');
                const base = `- ${title || (resolveCurrentLang() === 'th' ? 'ลำดับชั้น 2' : 'Level 2')}`;
                return department !== '' ? `${base} (${department})` : base;
            };
            const formatOkrKeyResultSummaryLabel = (keyResult) => {
                const title = String(keyResult && keyResult.title ? keyResult.title : '').trim();
                return title || (resolveCurrentLang() === 'th' ? 'ลำดับชั้น 2' : 'Level 2');
            };
            const formatOkrLevelThreeLabel = (entry) => {
                const title = String(entry && entry.objective ? entry.objective : '').trim();
                return title || (resolveCurrentLang() === 'th' ? 'ลำดับชั้นที่ 3' : 'Level 3');
            };
            const flattenOkrLevelThreeEntries = () => {
                const allowedDepartments = normalizeDepartmentCodeList(targetDepartmentOptions);
                const rows = [];
                okrHierarchyOptions.forEach((objective) => {
                    const keyResults = Array.isArray(objective.key_results) ? objective.key_results : [];
                    keyResults.forEach((keyResult) => {
                        const entries = Array.isArray(keyResult.kpi_entries) ? keyResult.kpi_entries : [];
                        entries.forEach((entry) => {
                            const entryDepartments = normalizeDepartmentCodeList(
                                Array.isArray(entry.target_departments) ? entry.target_departments : []
                            );
                            if (
                                allowedDepartments.length > 0 &&
                                entryDepartments.length > 0 &&
                                !entryDepartments.some((departmentCode) => allowedDepartments.includes(departmentCode))
                            ) {
                                return;
                            }

                            rows.push({
                                ...entry,
                                objective_id: Number(objective.id || 0),
                                key_result_id: Number(keyResult.id || 0),
                                objective_label: formatOkrObjectiveLabel(objective),
                                key_result_label: formatOkrKeyResultSummaryLabel(keyResult),
                                hierarchy_key: buildOkrHierarchyFilterKey(objective.id, keyResult.id),
                            });
                        });
                    });
                });
                return rows;
            };
            const resolveOkrLevelThreeEntryById = (entryId) => {
                const safeEntryId = Number(entryId || 0);
                if (!Number.isFinite(safeEntryId) || safeEntryId < 1) {
                    return null;
                }

                return flattenOkrLevelThreeEntries().find((entry) => Number(entry.id) === Math.trunc(safeEntryId)) || null;
            };
            const resolveOkrLevelThreeEntryByFilterKey = (filterKey) => {
                const safeKey = normalizeOkrLevelThreeFilterKey(filterKey || '');
                if (safeKey === '') {
                    return null;
                }

                return flattenOkrLevelThreeEntries()
                    .find((entry) => buildOkrLevelThreeFilterKey(entry && entry.id ? entry.id : 0) === safeKey) || null;
            };
            const resolveOkrHierarchySummaryBySelection = (selection) => {
                if (!selection) {
                    return null;
                }

                const objective = okrHierarchyOptions.find((item) => Number(item.id) === Number(selection.objectiveId));
                if (!objective) {
                    return null;
                }

                const keyResult = Array.isArray(objective.key_results)
                    ? objective.key_results.find((item) => Number(item.id) === Number(selection.keyResultId))
                    : null;

                if (!keyResult) {
                    return null;
                }

                return {
                    objectiveLabel: formatOkrObjectiveLabel(objective),
                    keyResultLabel: formatOkrKeyResultSummaryLabel(keyResult),
                    hierarchyKey: buildOkrHierarchyFilterKey(objective.id, keyResult.id),
                };
            };
            const renderCardOkrHierarchySelection = (cardEl, preferredSelection = null) => {
                if (!cardEl) {
                    return;
                }

                const wrap = cardEl.querySelector('[data-role="okr-hierarchy-wrap"]');
                const label = cardEl.querySelector('[data-role="label-okr-hierarchy"]');
                const select = cardEl.querySelector('[data-role="okr-hierarchy"]');
                const deptFilterSelect = cardEl.querySelector('[data-role="okr-hierarchy-dept-filter"]');
                if (!wrap || !select) {
                    return;
                }

                if (label) {
                    label.textContent = getText('kpiLabelOkrHierarchy');
                }

                const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                const visible = modeType === 'target' && hasOkrHierarchyOptions();
                wrap.hidden = !visible;
                select.disabled = !visible;

                // Populate dept filter select
                if (deptFilterSelect) {
                    const deptOptions = getOkrHierarchyDeptOptions();
                    const prevDept = String(deptFilterSelect.value || '');
                    deptFilterSelect.textContent = '';
                    const allOpt = document.createElement('option');
                    allOpt.value = '';
                    allOpt.textContent = 'ทั้งหมด';
                    deptFilterSelect.appendChild(allOpt);
                    deptOptions.forEach((dept) => {
                        const opt = document.createElement('option');
                        opt.value = dept;
                        opt.textContent = dept;
                        deptFilterSelect.appendChild(opt);
                    });
                    // Restore previous selection if still valid
                    if (prevDept !== '' && deptOptions.includes(prevDept)) {
                        deptFilterSelect.value = prevDept;
                    }
                    deptFilterSelect.classList.toggle('has-filter', deptFilterSelect.value !== '');
                }

                const activeDept = deptFilterSelect ? String(deptFilterSelect.value || '').trim().toUpperCase() : '';

                const preferredValue = preferredSelection && typeof preferredSelection === 'object'
                    ? buildOkrSelectionValue(preferredSelection.objectiveId, preferredSelection.keyResultId)
                    : '';
                const previousValue = preferredValue || String(select.value || '');
                select.textContent = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = hasOkrHierarchyOptions()
                    ? getText('kpiOkrHierarchyPlaceholder')
                    : getText('kpiOkrHierarchyNoData');
                placeholder.disabled = hasOkrHierarchyOptions();
                placeholder.selected = true;
                select.appendChild(placeholder);

                okrHierarchyOptions.forEach((objective) => {
                    const filteredKrs = (objective.key_results || []).filter((kr) => {
                        if (activeDept === '') {
                            return true;
                        }
                        return String(kr.dept_abbr_hr || '').trim().toUpperCase() === activeDept;
                    });
                    if (filteredKrs.length === 0) {
                        return;
                    }
                    const group = document.createElement('optgroup');
                    group.label = formatOkrObjectiveLabel(objective);

                    filteredKrs.forEach((keyResult) => {
                        const option = document.createElement('option');
                        option.value = buildOkrSelectionValue(objective.id, keyResult.id);
                        option.textContent = formatOkrKeyResultLabel(keyResult);
                        group.appendChild(option);
                    });

                    select.appendChild(group);
                });

                const hasPreviousValue = previousValue !== '' && [...select.options]
                    .some((option) => option.value === previousValue);
                if (hasPreviousValue) {
                    select.value = previousValue;
                }
            };
            const getCardOkrHierarchySelection = (cardEl) => {
                const select = cardEl ? cardEl.querySelector('[data-role="okr-hierarchy"]') : null;
                return select ? parseOkrSelectionValue(select.value) : null;
            };
            const setL3DropdownValue = (cardEl, valueStr) => {
                const select = cardEl.querySelector('[data-role="okr-level-three"]');
                const displayEl = cardEl.querySelector('[data-role="okr-level-three-display"]');
                const dropdownEl = cardEl.querySelector('[data-role="okr-level-three-dropdown"]');
                const optionsEl = cardEl.querySelector('[data-role="okr-level-three-options"]');
                if (!select) {
                    return;
                }
                select.value = valueStr;
                if (optionsEl) {
                    [...optionsEl.querySelectorAll('.kpi-l3-option')].forEach((opt) => {
                        opt.classList.toggle('is-selected', opt.dataset.value === valueStr);
                    });
                }
                if (displayEl) {
                    const selectedOpt = optionsEl
                        ? optionsEl.querySelector(`.kpi-l3-option[data-value="${CSS.escape(valueStr)}"]`)
                        : null;
                    if (selectedOpt && valueStr !== '') {
                        displayEl.textContent = selectedOpt.dataset.label || valueStr;
                        displayEl.classList.add('has-value');
                    } else {
                        displayEl.textContent = getText('kpiOkrLevelThreePlaceholder');
                        displayEl.classList.remove('has-value');
                    }
                }
                if (dropdownEl) {
                    dropdownEl.classList.remove('open');
                    const opts = dropdownEl.querySelector('[data-role="okr-level-three-options"]');
                    if (opts) {
                        opts.hidden = true;
                    }
                }
            };
            const renderCardReportLevelThreeSelection = (cardEl, preferredParentTargetId = null) => {
                if (!cardEl) {
                    return;
                }

                const wrap = cardEl.querySelector('[data-role="okr-level-three-wrap"]');
                const label = cardEl.querySelector('[data-role="label-okr-level-three"]');
                const select = cardEl.querySelector('[data-role="okr-level-three"]');
                const dropdownEl = cardEl.querySelector('[data-role="okr-level-three-dropdown"]');
                const triggerEl = cardEl.querySelector('[data-role="okr-level-three-trigger"]');
                const displayEl = cardEl.querySelector('[data-role="okr-level-three-display"]');
                const optionsEl = cardEl.querySelector('[data-role="okr-level-three-options"]');
                if (!wrap || !select) {
                    cardEl.classList.remove('has-report-level-three');
                    return;
                }

                if (label) {
                    label.textContent = getText('kpiLabelReportLevelThree');
                }

                const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                const currentItemId = Number(cardEl.dataset.itemId || 0);
                const cardSelectedDepts = getCardSelectedTargetDepartments(cardEl);
                const filterDeptCode = cardSelectedDepts.length > 0
                    ? normalizeDepartmentCode(cardSelectedDepts[0])
                    : viewerDepartmentCode;
                const levelThreeEntries = flattenOkrLevelThreeEntries()
                    .filter((entry) => Number(entry && entry.id ? entry.id : 0) !== currentItemId)
                    .filter((entry) => {
                        if (filterDeptCode === '') {
                            return true;
                        }
                        const entryDepts = normalizeDepartmentCodeList(
                            Array.isArray(entry && entry.target_departments ? entry.target_departments : [])
                                ? entry.target_departments : []
                        );
                        if (entryDepts.length > 0) {
                            return entryDepts.includes(filterDeptCode);
                        }
                        return normalizeDepartmentCode(entry && entry.dept_abbr_hr ? entry.dept_abbr_hr : '') === filterDeptCode;
                    });
                const visible = modeType === 'report' && levelThreeEntries.length > 0;
                wrap.hidden = !visible;
                cardEl.classList.toggle('has-report-level-three', visible);

                const preferredId = Number(preferredParentTargetId || 0) > 0
                    ? Number(preferredParentTargetId)
                    : Number(cardEl.dataset.parentTargetKpiId || 0);
                const previousValue = preferredId > 0 ? String(preferredId) : String(select.value || '');

                select.textContent = '';
                const placeholderOpt = document.createElement('option');
                placeholderOpt.value = '';
                placeholderOpt.textContent = levelThreeEntries.length > 0
                    ? getText('kpiOkrLevelThreePlaceholder')
                    : getText('kpiOkrLevelThreeNoData');
                placeholderOpt.disabled = levelThreeEntries.length > 0;
                placeholderOpt.selected = true;
                select.appendChild(placeholderOpt);
                levelThreeEntries.forEach((entry) => {
                    const opt = document.createElement('option');
                    opt.value = String(entry.id);
                    opt.textContent = String(entry.id);
                    select.appendChild(opt);
                });

                if (optionsEl) {
                    optionsEl.textContent = '';
                    if (levelThreeEntries.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'kpi-l3-option-placeholder';
                        empty.textContent = getText('kpiOkrLevelThreeNoData');
                        optionsEl.appendChild(empty);
                    } else {
                        levelThreeEntries.forEach((entry) => {
                            const l3Label = formatOkrLevelThreeLabel(entry);
                            const plainL1 = String(entry.objective_label || '').replace(/^\d+\.\s*/, '');
                            const shortLabel = `${plainL1} › ${l3Label}`;
                            const optDiv = document.createElement('div');
                            optDiv.className = 'kpi-l3-option';
                            optDiv.dataset.value = String(entry.id);
                            optDiv.dataset.label = shortLabel;

                            const l1El = document.createElement('div');
                            l1El.className = 'kpi-l3-opt-l1';
                            l1El.textContent = plainL1;

                            const l2El = document.createElement('div');
                            l2El.className = 'kpi-l3-opt-l2';
                            l2El.textContent = `- ${entry.key_result_label}`;

                            const l3El = document.createElement('div');
                            l3El.className = 'kpi-l3-opt-l3';
                            l3El.textContent = `- ${l3Label}`;

                            optDiv.appendChild(l1El);
                            optDiv.appendChild(l2El);
                            optDiv.appendChild(l3El);

                            optDiv.addEventListener('click', () => {
                                select.value = String(entry.id);
                                cardEl.dataset.parentTargetKpiId = String(entry.id);
                                setL3DropdownValue(cardEl, String(entry.id));
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            });

                            optionsEl.appendChild(optDiv);
                        });
                    }
                }

                if (triggerEl && !triggerEl.dataset.l3Bound) {
                    triggerEl.dataset.l3Bound = '1';
                    triggerEl.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (!optionsEl) {
                            return;
                        }
                        const isOpen = !optionsEl.hidden;
                        document.querySelectorAll('[data-role="okr-level-three-options"]').forEach((el) => {
                            el.hidden = true;
                        });
                        document.querySelectorAll('[data-role="okr-level-three-dropdown"]').forEach((el) => {
                            el.classList.remove('open');
                        });
                        if (!isOpen) {
                            optionsEl.hidden = false;
                            if (dropdownEl) {
                                dropdownEl.classList.add('open');
                            }
                        }
                    });
                }

                const hasPreviousValue = previousValue !== '' && levelThreeEntries.some(
                    (entry) => String(entry.id) === previousValue
                );
                if (hasPreviousValue) {
                    cardEl.dataset.parentTargetKpiId = previousValue;
                    setL3DropdownValue(cardEl, previousValue);
                } else {
                    delete cardEl.dataset.parentTargetKpiId;
                    setL3DropdownValue(cardEl, '');
                }
            };
            const getCardReportLevelThreeSelection = (cardEl) => {
                if (!cardEl) {
                    return null;
                }

                const select = cardEl.querySelector('[data-role="okr-level-three"]');
                const selectedId = select ? Number(select.value || 0) : Number(cardEl.dataset.parentTargetKpiId || 0);
                const entry = resolveOkrLevelThreeEntryById(selectedId);
                if (!entry) {
                    return null;
                }

                return {
                    parentTargetKpiId: Number(entry.id),
                    objectiveId: Number(entry.objective_id),
                    keyResultId: Number(entry.key_result_id),
                    hierarchyKey: entry.hierarchy_key,
                    objectiveLabel: entry.objective_label,
                    keyResultLabel: entry.key_result_label,
                    levelThreeLabel: formatOkrLevelThreeLabel(entry),
                    targetDepartments: normalizeDepartmentCodeList(entry.target_departments),
                };
            };
            const upsertOkrLevelThreeEntryFromTargetCard = (cardEl) => {
                if (!cardEl || resolveAllowedCardModeType(cardEl.dataset.modeType || 'report') !== 'target') {
                    return;
                }

                const itemId = Number(cardEl.dataset.itemId || 0);
                const selection = getCardOkrHierarchySelection(cardEl) || (
                    Number(cardEl.dataset.okrObjectiveId || 0) > 0 && Number(cardEl.dataset.okrKeyResultId || 0) > 0
                        ? {
                            objectiveId: Number(cardEl.dataset.okrObjectiveId),
                            keyResultId: Number(cardEl.dataset.okrKeyResultId),
                        }
                        : null
                );
                if (!Number.isFinite(itemId) || itemId < 1 || !selection) {
                    return;
                }

                const objective = okrHierarchyOptions.find((item) => Number(item.id) === Number(selection.objectiveId));
                if (!objective || !Array.isArray(objective.key_results)) {
                    return;
                }

                const keyResult = objective.key_results.find((item) => Number(item.id) === Number(selection.keyResultId));
                if (!keyResult) {
                    return;
                }

                okrHierarchyOptions.forEach((sourceObjective) => {
                    const keyResults = Array.isArray(sourceObjective.key_results) ? sourceObjective.key_results : [];
                    keyResults.forEach((sourceKeyResult) => {
                        sourceKeyResult.kpi_entries = (Array.isArray(sourceKeyResult.kpi_entries) ? sourceKeyResult.kpi_entries : [])
                            .filter((entry) => Number(entry && entry.id ? entry.id : 0) !== itemId);
                    });
                });

                const objectiveInput = cardEl.querySelector('[data-role="objective"]');
                const detailInput = cardEl.querySelector('[data-role="detail"]');
                keyResult.kpi_entries = Array.isArray(keyResult.kpi_entries) ? keyResult.kpi_entries : [];
                keyResult.kpi_entries.push({
                    id: Math.trunc(itemId),
                    objective: objectiveInput ? String(objectiveInput.value || '').trim() : '',
                    detail: detailInput ? String(detailInput.value || '').trim() : '',
                    dept_abbr_hr: '',
                    target_departments: getCardSelectedTargetDepartments(cardEl),
                    employee_code: '',
                    full_name_th: '',
                    full_name_en: '',
                });
            };
            const resolveCardOkrHierarchySummary = (cardEl) => {
                const modeType = resolveAllowedCardModeType(cardEl && cardEl.dataset ? cardEl.dataset.modeType : 'report');
                if (modeType === 'report') {
                    const levelThreeSelection = getCardReportLevelThreeSelection(cardEl);
                    if (levelThreeSelection) {
                        return levelThreeSelection;
                    }
                }

                const selection = getCardOkrHierarchySelection(cardEl) || (
                    cardEl && Number(cardEl.dataset.okrObjectiveId || 0) > 0 && Number(cardEl.dataset.okrKeyResultId || 0) > 0
                        ? {
                            objectiveId: Number(cardEl.dataset.okrObjectiveId),
                            keyResultId: Number(cardEl.dataset.okrKeyResultId),
                        }
                        : null
                );
                return resolveOkrHierarchySummaryBySelection(selection);
            };

            const normalizeNoticeLevel = (value) => {
                const level = Number(value);
                if (!Number.isFinite(level) || level < 1) {
                    return 0;
                }
                return Math.trunc(level);
            };

            const normalizeNoticeAnnouncement = (raw) => {
                const item = raw && typeof raw === 'object' ? raw : {};
                return {
                    id: Number(item.id) || 0,
                    level_no: normalizeNoticeLevel(item.level_no),
                    dept_abbr_hr: String(item.dept_abbr_hr || '').trim().toUpperCase(),
                    title: String(item.title || '').trim(),
                    detail: String(item.detail || '').trim(),
                    posted_at: String(item.posted_at || '').trim(),
                    files: Array.isArray(item.files)
                        ? item.files.map((file) => ({
                            id: Number(file && file.id ? file.id : 0),
                            name: String(file && file.name ? file.name : '').trim(),
                            size_text: String(file && file.size_text ? file.size_text : '').trim(),
                            url: String(file && file.url ? file.url : '').trim(),
                            preview_url: String(file && file.preview_url ? file.preview_url : '').trim(),
                        }))
                        : [],
                };
            };

            const normalizeNoticeRows = (rows) => {
                if (!Array.isArray(rows)) {
                    return [];
                }
                return rows.map((row) => {
                    const item = row && typeof row === 'object' ? row : {};
                    return {
                        employee_code: String(item.employee_code || '').trim(),
                        full_name_th: String(item.full_name_th || '').trim(),
                        full_name_en: String(item.full_name_en || '').trim(),
                        position: String(item.position || '').trim(),
                        objective: String(item.objective || '').trim(),
                        detail: String(item.detail || '').trim(),
                        target_value: String(item.target_value || '').trim(),
                        target_goal: String(item.target_goal || '').trim(),
                        unit: String(item.unit || '').trim(),
                    };
                });
            };

            const noticeAnnouncements = {
                1: [],
                2: [],
            };

            if (noticeAnnouncementsByLevel && typeof noticeAnnouncementsByLevel === 'object') {
                [1, 2].forEach((level) => {
                    const source = noticeAnnouncementsByLevel[String(level)] || noticeAnnouncementsByLevel[level] || [];
                    noticeAnnouncements[level] = Array.isArray(source)
                        ? source.map((item) => normalizeNoticeAnnouncement(item))
                        : [];
                });
            }

            const noticeLevelThreeCardData = noticeLevelThreeCard && typeof noticeLevelThreeCard === 'object'
                ? {
                    dept_abbr_hr: String(noticeLevelThreeCard.dept_abbr_hr || '').trim().toUpperCase(),
                    rows_count: Number(noticeLevelThreeCard.rows_count) || 0,
                }
                : null;
            const getViewerDepartmentFallback = () => normalizeDepartmentCode(
                (noticeLevelThreeCardData && noticeLevelThreeCardData.dept_abbr_hr)
                    ? noticeLevelThreeCardData.dept_abbr_hr
                    : viewerDepartmentCode
            );
            const noticeLevelThreeRowsData = normalizeNoticeRows(noticeLevelThreeRows);

            const resolveGlobalNoticeDepartmentFromKpiPage = () => {
                const activeDepartment = currentKpiMode === 'target'
                    ? currentTargetDepartment
                    : currentReportDepartment;
                const normalizedActive = normalizeDepartmentCode(activeDepartment || '');
                if (normalizedActive !== '') {
                    return normalizedActive;
                }

                const fallbackReport = normalizeDepartmentCode(currentReportDepartment || '');
                if (fallbackReport !== '') {
                    return fallbackReport;
                }

                const fallbackTarget = normalizeDepartmentCode(currentTargetDepartment || '');
                if (fallbackTarget !== '') {
                    return fallbackTarget;
                }

                return getViewerDepartmentFallback();
            };
            window.resolveGlobalNoticeDepartment = resolveGlobalNoticeDepartmentFromKpiPage;

            const showRawAlert = (message, type = 'info') => {
                const safeMessage = String(message || '').trim() || 'Unexpected error.';
                if (window.AppModal && typeof window.AppModal.alert === 'function') {
                    window.AppModal.alert(safeMessage, type);
                    return;
                }
                window.alert(safeMessage);
            };

            const showConfirm = (messageKey, callback) => {
                const message = getText(messageKey);
                if (window.AppModal && typeof window.AppModal.confirm === 'function') {
                    window.AppModal.confirm(message, callback);
                    return;
                }

                if (window.confirm(message)) {
                    callback();
                }
            };

            const showAlert = (messageKey, type = 'info') => {
                const message = getText(messageKey);
                if (window.AppModal && typeof window.AppModal.alert === 'function') {
                    window.AppModal.alert(message, type);
                    return;
                }

                window.alert(message);
            };

            const closeRejectDetailModal = () => {
                if (!canRejectDetailModal || !rejectDetailModalEl || !rejectDetailModalTextEl) {
                    return;
                }
                rejectDetailModalEl.classList.remove('show');
                rejectDetailModalEl.setAttribute('aria-hidden', 'true');
                rejectDetailModalTextEl.textContent = '-';
            };

            const openRejectDetailModal = (rejectDetail) => {
                if (!canRejectDetailModal || !rejectDetailModalEl || !rejectDetailModalTitleEl || !rejectDetailModalTextEl) {
                    return;
                }

                const activeLang = resolveActiveLang();
                const detailText = String(rejectDetail || '').trim();
                const titleText = activeLang === 'th' ? 'รายละเอียดการปฏิเสธ' : 'Rejection detail';
                const detailLabel = activeLang === 'th' ? 'รายละเอียด' : 'Detail';

                rejectDetailModalTitleEl.textContent = titleText;
                rejectDetailModalTextEl.textContent = `${detailLabel} : ${detailText !== '' ? detailText : '-'}`;
                rejectDetailModalEl.classList.add('show');
                rejectDetailModalEl.setAttribute('aria-hidden', 'false');
            };

            const postForm = async (url, formData, fallbackMessageKey) => {
                if (!url || url === '#' || !csrfToken) {
                    throw new Error(getText(fallbackMessageKey));
                }

                const activeLang = window.AppModal && typeof window.AppModal.getLang === 'function'
                    ? window.AppModal.getLang()
                    : (new URLSearchParams(window.location.search).get('lang') === 'th' ? 'th' : 'en');
                if (formData instanceof FormData && !formData.has('lang')) {
                    formData.append('lang', activeLang);
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                let data = {};
                try {
                    data = await res.json();
                } catch (jsonErr) {
                    data = {};
                }

                if (!res.ok || !data || data.ok !== true) {
                    const err = new Error((data && data.message) ? data.message : getText(fallbackMessageKey));
                    if (data && typeof data === 'object') {
                        err.payload = data;
                    }
                    throw err;
                }

                return data;
            };

            const monthMap = cycleContext && typeof cycleContext === 'object' && cycleContext.months && typeof cycleContext.months === 'object'
                ? cycleContext.months
                : {};

            const resolveMonthConfig = (monthNo) => {
                return monthMap[String(monthNo)] || monthMap[monthNo] || null;
            };

            const isMonthOpen = (monthNo) => {
                if (!cycleContext || !cycleContext.has_active_cycle) {
                    return false;
                }
                const monthConfig = resolveMonthConfig(monthNo);
                return !!(monthConfig && (monthConfig.is_active === true || monthConfig.is_active === 1 || monthConfig.is_active === '1'));
            };

            const parseNumeric = (value) => {
                if (value === null || value === undefined) {
                    return Number.NaN;
                }

                const normalized = String(value).trim();
                if (normalized === '') {
                    return Number.NaN;
                }

                const parsed = Number(normalized);
                return Number.isFinite(parsed) ? parsed : Number.NaN;
            };

            const formatDateDmy = (rawValue) => {
                const value = String(rawValue || '').trim();
                if (!value) {
                    return '';
                }

                const dmyMatch = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (dmyMatch) {
                    return value;
                }

                const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})(?:\s.*)?$/);
                if (isoMatch) {
                    return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
                }

                const parsed = new Date(value);
                if (Number.isNaN(parsed.getTime())) {
                    return '';
                }

                const day = String(parsed.getDate()).padStart(2, '0');
                const month = String(parsed.getMonth() + 1).padStart(2, '0');
                const year = String(parsed.getFullYear());
                return `${day}/${month}/${year}`;
            };

            const escapeHtml = (value) => {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            const escapeHtmlWithLineBreaks = (value) => {
                return escapeHtml(String(value || '')).replace(/\r\n|\r|\n/g, '<br>');
            };

            const formatNoticePostedAt = (isoText) => {
                const value = String(isoText || '').trim();
                if (value === '') {
                    return '-';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '-';
                }

                const lang = window.AppModal && typeof window.AppModal.getLang === 'function'
                    ? window.AppModal.getLang()
                    : (new URLSearchParams(window.location.search).get('lang') === 'th' ? 'th' : 'en');

                return date.toLocaleString(lang === 'th' ? 'th-TH' : 'en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            };

            const truncateNoticeSummaryText = (value, maxLength = 180) => {
                const rawText = String(value || '').replace(/\s+/g, ' ').trim();
                if (rawText === '') {
                    return '-';
                }
                if (rawText.length <= maxLength) {
                    return rawText;
                }
                return `${rawText.slice(0, maxLength).trimEnd()}....`;
            };

            const applyNoticeClampToRow = (rowEl) => {
                if (!(rowEl instanceof HTMLElement)) {
                    return;
                }
                const valueEl = rowEl.querySelector('.kpi-notice-item-value');
                if (valueEl) {
                    valueEl.classList.add('is-clamp-3');
                }
            };

            const createNoticeRow = (labelText, valueNodeOrText) => {
                const rowEl = document.createElement('div');
                rowEl.className = 'kpi-notice-item-row';

                const labelEl = document.createElement('span');
                labelEl.className = 'kpi-notice-item-label';
                labelEl.textContent = labelText;
                rowEl.appendChild(labelEl);

                if (valueNodeOrText instanceof HTMLElement) {
                    valueNodeOrText.classList.add('kpi-notice-item-value');
                    rowEl.appendChild(valueNodeOrText);
                } else {
                    const valueEl = document.createElement('div');
                    valueEl.className = 'kpi-notice-item-value';
                    const text = String(valueNodeOrText || '').trim();
                    valueEl.textContent = text !== '' ? text : '-';
                    rowEl.appendChild(valueEl);
                }

                return rowEl;
            };

            const buildNoticeFileOpenUrl = (fileUrl) => {
                const rawUrl = String(fileUrl || '').trim();
                if (rawUrl === '') {
                    return '';
                }
                return rawUrl;
            };

            const createNoticeFilesNode = (files) => {
                if (!Array.isArray(files) || files.length === 0) {
                    return getText('kpiNoticeNoFile');
                }

                const list = document.createElement('ul');
                list.className = 'kpi-notice-file-list';
                files.forEach((file) => {
                    const item = file && typeof file === 'object' ? file : {};
                    const fileName = String(item.name || '').trim();
                    const sizeText = String(item.size_text || '').trim();
                    const fileUrl = String(item.url || '').trim();
                    if (fileName === '') {
                        return;
                    }

                    const li = document.createElement('li');
                    if (fileUrl !== '') {
                        const link = document.createElement('a');
                        link.href = buildNoticeFileOpenUrl(fileUrl);
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = sizeText !== '' ? `${fileName} (${sizeText})` : fileName;
                        li.appendChild(link);
                    } else {
                        li.textContent = sizeText !== '' ? `${fileName} (${sizeText})` : fileName;
                    }
                    list.appendChild(li);
                });

                if (list.children.length < 1) {
                    return getText('kpiNoticeNoFile');
                }

                return list;
            };

            const createNoticeAnnouncementCard = (announcement, options = {}) => {
                const settings = options && typeof options === 'object' ? options : {};
                const expandable = !!settings.expandable;
                const compactSummary = !!settings.compactSummary;
                const cardEl = document.createElement('article');
                cardEl.className = 'kpi-notice-item';
                if (expandable && canNoticeAnnouncementModal) {
                    cardEl.classList.add('is-expandable');
                    cardEl.setAttribute('tabindex', '0');
                    const openExpandedCard = () => {
                        openNoticeAnnouncementModal(announcement);
                    };
                    cardEl.addEventListener('click', (event) => {
                        const target = event.target;
                        if (target instanceof HTMLElement && target.closest('a')) {
                            return;
                        }
                        openExpandedCard();
                    });
                    cardEl.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }
                        event.preventDefault();
                        openExpandedCard();
                    });
                }
                const titleText = compactSummary
                    ? truncateNoticeSummaryText(announcement.title || '-', 120)
                    : (announcement.title || '-');
                const detailText = compactSummary
                    ? truncateNoticeSummaryText(announcement.detail || '-', 180)
                    : (announcement.detail || '-');

                const titleRowEl = createNoticeRow(getText('kpiNoticeTitleLabel'), titleText);
                const detailRowEl = createNoticeRow(getText('kpiNoticeDetailLabel'), detailText);

                if (compactSummary) {
                    applyNoticeClampToRow(titleRowEl);
                    applyNoticeClampToRow(detailRowEl);
                }

                cardEl.appendChild(createNoticeRow(getText('kpiNoticeDeptLabel'), announcement.dept_abbr_hr || '-'));
                cardEl.appendChild(titleRowEl);
                cardEl.appendChild(detailRowEl);
                cardEl.appendChild(createNoticeRow(getText('kpiNoticePostedLabel'), formatNoticePostedAt(announcement.posted_at)));
                cardEl.appendChild(createNoticeRow(getText('kpiNoticeFilesLabel'), createNoticeFilesNode(announcement.files)));
                return cardEl;
            };

            const closeNoticeAnnouncementModal = () => {
                if (!canNoticeAnnouncementModal || !noticeAnnouncementModalEl) {
                    return;
                }
                noticeAnnouncementModalEl.classList.remove('show');
                noticeAnnouncementModalEl.setAttribute('aria-hidden', 'true');
                if (noticeAnnouncementModalCardEl) {
                    noticeAnnouncementModalCardEl.innerHTML = '';
                }
            };

            const openNoticeAnnouncementModal = (announcement) => {
                if (!canNoticeAnnouncementModal || !noticeAnnouncementModalEl || !noticeAnnouncementModalTitleEl || !noticeAnnouncementModalCardEl) {
                    return;
                }
                const safeAnnouncement = announcement && typeof announcement === 'object' ? announcement : {};
                noticeAnnouncementModalTitleEl.textContent = String(safeAnnouncement.title || '').trim() || getText('kpiNoticeModalTitle');
                noticeAnnouncementModalCardEl.innerHTML = '';
                noticeAnnouncementModalCardEl.appendChild(createNoticeAnnouncementCard(safeAnnouncement, { expandable: false }));
                noticeAnnouncementModalEl.classList.add('show');
                noticeAnnouncementModalEl.setAttribute('aria-hidden', 'false');
            };

            const renderNoticeLevel = (levelNo, listContainerEl) => {
                if (!listContainerEl) {
                    return;
                }

                listContainerEl.innerHTML = '';
                const items = Array.isArray(noticeAnnouncements[levelNo]) ? noticeAnnouncements[levelNo] : [];
                if (items.length < 1) {
                    const emptyEl = document.createElement('p');
                    emptyEl.className = 'kpi-notice-empty';
                    emptyEl.textContent = getText('kpiNoticeNoData');
                    listContainerEl.appendChild(emptyEl);
                    return;
                }

                items.forEach((item) => {
                    listContainerEl.appendChild(createNoticeAnnouncementCard(item, {
                        expandable: true,
                        compactSummary: levelNo === 2,
                    }));
                });
            };

            const renderNoticeLevelThreeTable = () => {
                if (!noticeLevel3TableBodyEl) {
                    return;
                }

                noticeLevel3TableBodyEl.innerHTML = '';
                if (noticeLevelThreeRowsData.length < 1) {
                    const trEl = document.createElement('tr');
                    const tdEl = document.createElement('td');
                    tdEl.colSpan = 9;
                    tdEl.textContent = getText('kpiNoticeTableEmpty');
                    trEl.appendChild(tdEl);
                    noticeLevel3TableBodyEl.appendChild(trEl);
                } else {
                    const activeLang = resolveActiveLang() === 'th' ? 'th' : 'en';
                    noticeLevelThreeRowsData.forEach((row, index) => {
                        const fullName = activeLang === 'th'
                            ? (row.full_name_th || row.full_name_en || '-')
                            : (row.full_name_en || row.full_name_th || '-');
                        const trEl = document.createElement('tr');
                        [
                            String(index + 1),
                            row.employee_code || '-',
                            fullName,
                            row.position || '-',
                            row.objective || '-',
                            row.detail || '-',
                            row.target_goal || '-',
                            row.target_value || '-',
                            row.unit || '-',
                        ].forEach((value) => {
                            const tdEl = document.createElement('td');
                            tdEl.textContent = value;
                            trEl.appendChild(tdEl);
                        });
                        noticeLevel3TableBodyEl.appendChild(trEl);
                    });
                }
            };

            const openNoticeLevelThreeInNewTab = () => {
                const deptCode = noticeLevelThreeCardData && noticeLevelThreeCardData.dept_abbr_hr
                    ? noticeLevelThreeCardData.dept_abbr_hr
                    : '-';
                const titleText = tpl(getText('kpiNoticeLevel3ModalTitleTpl'), { dept: deptCode });
                const activeLang = resolveActiveLang() === 'th' ? 'th' : 'en';
                const rows = Array.isArray(noticeLevelThreeRowsData) ? noticeLevelThreeRowsData : [];

                const headers = [
                    getText('kpiNoticeTableNo'),
                    getText('kpiNoticeTableEmployeeCode'),
                    getText('kpiNoticeTableFullName'),
                    getText('kpiNoticeTablePosition'),
                    getText('kpiNoticeTableObjective'),
                    getText('kpiNoticeTableDetail'),
                    getText('kpiNoticeTableGoal'),
                    getText('kpiNoticeTableTargetValue'),
                    getText('kpiNoticeTableUnit'),
                ];

                const tableHeadHtml = headers
                    .map((header) => `<th>${escapeHtml(header)}</th>`)
                    .join('');

                let tableBodyHtml = '';
                if (rows.length < 1) {
                    tableBodyHtml = `
                        <tr>
                            <td colspan="9">${escapeHtml(getText('kpiNoticeTableEmpty'))}</td>
                        </tr>
                    `;
                } else {
                    tableBodyHtml = rows.map((row, index) => {
                        const fullName = activeLang === 'th'
                            ? (row.full_name_th || row.full_name_en || '-')
                            : (row.full_name_en || row.full_name_th || '-');
                        const cells = [
                            String(index + 1),
                            row.employee_code || '-',
                            fullName,
                            row.position || '-',
                            row.objective || '-',
                            row.detail || '-',
                            row.target_goal || '-',
                            row.target_value || '-',
                            row.unit || '-',
                        ];

                        return `<tr>${cells.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`;
                    }).join('');
                }

                const popup = window.open('', '_blank');
                if (!popup) {
                    showRawAlert(
                        activeLang === 'th'
                            ? 'เบราว์เซอร์บล็อกการเปิดแท็บใหม่ กรุณาอนุญาตป๊อปอัป'
                            : 'Browser blocked opening a new tab. Please allow pop-ups.',
                        'error'
                    );
                    return;
                }

                popup.document.open();
                popup.document.write(`
<!doctype html>
<html lang="${activeLang}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${escapeHtml(titleText)}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #fffafc; color: #111827; }
        .wrap { padding: 16px; }
        h1 { margin: 0 0 12px; color: #9d174d; font-size: 22px; }
        .table-wrap { border: 1px solid #f3f4f6; overflow: auto; background: #ffffff; }
        table { width: 100%; border-collapse: collapse; min-width: 980px; }
        th, td { border: 1px solid #e5e7eb; padding: 10px 8px; font-size: 13px; text-align: left; vertical-align: top; }
        th { background: #fdf2f8; color: #9d174d; font-weight: 700; white-space: nowrap; }
        td:nth-child(6) { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>${escapeHtml(titleText)}</h1>
        <div class="table-wrap">
            <table>
                <thead><tr>${tableHeadHtml}</tr></thead>
                <tbody>${tableBodyHtml}</tbody>
            </table>
        </div>
    </main>
</body>
</html>`);
                popup.document.close();
                try {
                    popup.focus();
                } catch (error) {
                    // no-op
                }
            };

            const renderNoticeLevelThreeCard = () => {
                if (!noticeLevel3MainEl || !noticeLevel3SubEl || !noticeLevel3HintEl) {
                    return;
                }

                const deptCode = noticeLevelThreeCardData && noticeLevelThreeCardData.dept_abbr_hr
                    ? noticeLevelThreeCardData.dept_abbr_hr
                    : '';
                noticeLevel3MainEl.textContent = getText('kpiNoticeLevel3DeptLabel');
                noticeLevel3SubEl.textContent = deptCode !== '' ? deptCode : '-';
                noticeLevel3HintEl.textContent = getText('kpiNoticeLevel3Hint');
            };

            const closeNoticeLevel3Modal = () => {
                if (!canNoticeLevel3Modal || !noticeLevel3ModalEl) {
                    return;
                }
                noticeLevel3ModalEl.classList.remove('show');
                noticeLevel3ModalEl.setAttribute('aria-hidden', 'true');
            };

            const openNoticeLevel3Modal = () => {
                if (!canNoticeLevel3Modal || !noticeLevel3ModalEl || !noticeLevel3ModalTitleEl) {
                    return;
                }
                const deptCode = noticeLevelThreeCardData && noticeLevelThreeCardData.dept_abbr_hr
                    ? noticeLevelThreeCardData.dept_abbr_hr
                    : '-';
                noticeLevel3ModalTitleEl.textContent = tpl(getText('kpiNoticeLevel3ModalTitleTpl'), { dept: deptCode });
                renderNoticeLevelThreeTable();
                noticeLevel3ModalEl.classList.add('show');
                noticeLevel3ModalEl.setAttribute('aria-hidden', 'false');
            };

            const resetNoticeFilePreview = () => {
                if (!canNoticeFileModal) {
                    return;
                }
                if (noticePreviewObjectUrl) {
                    URL.revokeObjectURL(noticePreviewObjectUrl);
                    noticePreviewObjectUrl = '';
                }
                if (noticePreviewImageEl) {
                    noticePreviewImageEl.hidden = true;
                    noticePreviewImageEl.removeAttribute('src');
                }
                if (noticePreviewIframeEl) {
                    noticePreviewIframeEl.hidden = true;
                    noticePreviewIframeEl.src = 'about:blank';
                }
                if (noticePreviewEmptyEl) {
                    noticePreviewEmptyEl.hidden = true;
                    noticePreviewEmptyEl.textContent = '';
                }
            };

            const closeNoticeFileModal = () => {
                if (!canNoticeFileModal || !noticeFileModalEl) {
                    return;
                }
                noticeFileModalEl.classList.remove('show');
                noticeFileModalEl.setAttribute('aria-hidden', 'true');
                resetNoticeFilePreview();
            };

            const getNoticeFileExt = (name, url) => {
                const fromName = String(name || '').trim();
                if (fromName.includes('.')) {
                    return fromName.split('.').pop().toLowerCase();
                }
                try {
                    const parsed = new URL(String(url || ''), window.location.origin);
                    const path = parsed.pathname || '';
                    if (path.includes('.')) {
                        return path.split('.').pop().toLowerCase();
                    }
                } catch (err) {
                    // ignore
                }
                return '';
            };

            const openNoticeFilePreview = (file) => {
                if (!canNoticeFileModal || !noticeFileModalEl || !noticeFileModalTitleEl) {
                    return;
                }

                const name = String(file && file.name ? file.name : '').trim() || 'File';
                const url = String(file && file.url ? file.url : '').trim();
                const ext = getNoticeFileExt(name, url);
                const isImage = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext);
                const isPdf = ext === 'pdf';
                const isText = ['txt', 'csv', 'json', 'xml', 'html', 'htm'].includes(ext);
                const isOfficeFile = ['ppt', 'pptx', 'xls', 'xlsx', 'doc', 'docx'].includes(ext);

                noticeFileModalTitleEl.textContent = name;
                resetNoticeFilePreview();
                noticeFileModalEl.classList.add('show');
                noticeFileModalEl.setAttribute('aria-hidden', 'false');

                if (url === '') {
                    if (noticePreviewEmptyEl) {
                        noticePreviewEmptyEl.hidden = false;
                        noticePreviewEmptyEl.textContent = getText('kpiNoticeFilePreviewFail');
                    }
                    return;
                }

                if (isImage) {
                    if (noticePreviewImageEl) {
                        noticePreviewImageEl.hidden = false;
                        noticePreviewImageEl.src = url;
                        noticePreviewImageEl.alt = name;
                    }
                    return;
                }

                if (isPdf || isText || isOfficeFile) {
                    fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }
                            return response.blob();
                        })
                        .then((blob) => {
                            if (!noticePreviewIframeEl) {
                                throw new Error('iframe-not-ready');
                            }
                            noticePreviewObjectUrl = URL.createObjectURL(blob);
                            const previewUrl = isPdf ? `${noticePreviewObjectUrl}#toolbar=1` : noticePreviewObjectUrl;
                            noticePreviewIframeEl.hidden = false;
                            noticePreviewIframeEl.src = previewUrl;
                        })
                        .catch(() => {
                            resetNoticeFilePreview();
                            if (noticePreviewEmptyEl) {
                                noticePreviewEmptyEl.hidden = false;
                                noticePreviewEmptyEl.textContent = getText('kpiNoticeFilePreviewFail');
                            }
                        });
                    return;
                }

                if (noticePreviewEmptyEl) {
                    noticePreviewEmptyEl.hidden = false;
                    noticePreviewEmptyEl.textContent = getText('kpiNoticeFilePreviewUnsupported');
                }

                if (noticePreviewIframeEl) {
                    noticePreviewIframeEl.onload = null;
                    noticePreviewIframeEl.onerror = null;
                }

                if (noticePreviewImageEl) {
                    noticePreviewImageEl.onload = null;
                    noticePreviewImageEl.onerror = null;
                }
            };

            const ensureNoticePreviewElementErrorFallback = () => {
                if (noticePreviewIframeEl) {
                    noticePreviewIframeEl.addEventListener('error', () => {
                        resetNoticeFilePreview();
                        if (noticePreviewEmptyEl) {
                            noticePreviewEmptyEl.hidden = false;
                            noticePreviewEmptyEl.textContent = getText('kpiNoticeFilePreviewFail');
                        }
                    });
                }
                if (noticePreviewImageEl) {
                    noticePreviewImageEl.addEventListener('error', () => {
                        resetNoticeFilePreview();
                        if (noticePreviewEmptyEl) {
                            noticePreviewEmptyEl.hidden = false;
                            noticePreviewEmptyEl.textContent = getText('kpiNoticeFilePreviewFail');
                        }
                    });
                }
            };

            ensureNoticePreviewElementErrorFallback();

            const renderNoticeModalContent = () => {
                if (!canNoticeModal) {
                    return;
                }

                if (noticeLevel1SectionEl) {
                    noticeLevel1SectionEl.hidden = noticeVisibilityLevel < 1;
                }
                if (noticeLevel2SectionEl) {
                    noticeLevel2SectionEl.hidden = noticeVisibilityLevel < 2;
                }

                renderNoticeLevel(1, noticeLevel1ListEl);
                renderNoticeLevel(2, noticeLevel2ListEl);

                const canSeeLevelThree = (
                    noticeVisibilityLevel >= 3 &&
                    noticeLevelThreeCardData &&
                    String(noticeLevelThreeCardData.dept_abbr_hr || '').trim() !== ''
                );
                if (noticeLevel3SectionEl) {
                    noticeLevel3SectionEl.hidden = !canSeeLevelThree;
                }
                if (!canSeeLevelThree) {
                    return;
                }

                renderNoticeLevelThreeCard();
            };

            const clampNoticeFabPosition = (left, top) => {
                if (!noticeFabEl) {
                    return { left: 0, top: 0 };
                }
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const fabWidth = noticeFabEl.offsetWidth || 62;
                const fabHeight = noticeFabEl.offsetHeight || 62;
                const minGap = 8;
                const minLeft = minGap;
                const minTop = minGap;
                const maxLeft = Math.max(minLeft, viewportWidth - fabWidth - minGap);
                const maxTop = Math.max(minTop, viewportHeight - fabHeight - minGap);
                const nextLeft = Math.min(maxLeft, Math.max(minLeft, Number(left) || 0));
                const nextTop = Math.min(maxTop, Math.max(minTop, Number(top) || 0));
                return { left: nextLeft, top: nextTop };
            };

            const setNoticeFabPosition = (left, top, persist = false) => {
                if (!noticeFabEl) {
                    return;
                }
                const clamped = clampNoticeFabPosition(left, top);
                noticeFabEl.style.left = `${clamped.left}px`;
                noticeFabEl.style.top = `${clamped.top}px`;
                noticeFabEl.style.right = 'auto';
                noticeFabEl.style.bottom = 'auto';
                if (!persist) {
                    return;
                }
                try {
                    if (window.localStorage) {
                        window.localStorage.setItem(noticeFabPositionStorageKey, JSON.stringify(clamped));
                    }
                } catch (error) {
                    // Ignore storage errors
                }
            };

            const restoreNoticeFabPosition = () => {
                if (!noticeFabEl) {
                    return;
                }
                try {
                    if (!window.localStorage) {
                        return;
                    }
                    const raw = window.localStorage.getItem(noticeFabPositionStorageKey);
                    if (!raw) {
                        return;
                    }
                    const parsed = JSON.parse(raw);
                    if (!parsed || typeof parsed !== 'object') {
                        return;
                    }
                    const left = Number(parsed.left);
                    const top = Number(parsed.top);
                    if (!Number.isFinite(left) || !Number.isFinite(top)) {
                        return;
                    }
                    setNoticeFabPosition(left, top, false);
                } catch (error) {
                    // Ignore storage errors
                }
            };

            const saveNoticePanelFloatingState = () => {
                if (!noticePanelEl) {
                    return;
                }
                const rect = noticePanelEl.getBoundingClientRect();
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                if (viewportWidth < 1 || viewportHeight < 1) {
                    return;
                }
                const maxWidth = Math.max(340, viewportWidth - 16);
                const maxHeight = Math.max(320, viewportHeight - 16);
                const width = Math.min(maxWidth, Math.max(340, rect.width));
                const height = Math.min(maxHeight, Math.max(320, rect.height));
                const maxLeft = Math.max(8, viewportWidth - width - 8);
                const maxTop = Math.max(8, viewportHeight - height - 8);
                const left = Math.min(maxLeft, Math.max(8, rect.left));
                const top = Math.min(maxTop, Math.max(8, rect.top));
                noticeModalFloatingState = { left, top, width, height };
            };

            const applyNoticePanelFloatingState = () => {
                if (!noticePanelEl) {
                    return;
                }
                if (!noticeModalFloatingState) {
                    noticePanelEl.style.position = '';
                    noticePanelEl.style.left = '';
                    noticePanelEl.style.top = '';
                    noticePanelEl.style.width = '';
                    noticePanelEl.style.height = '';
                    return;
                }
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const maxWidth = Math.max(340, viewportWidth - 16);
                const maxHeight = Math.max(320, viewportHeight - 16);
                const width = Math.min(maxWidth, Math.max(340, Number(noticeModalFloatingState.width) || 340));
                const height = Math.min(maxHeight, Math.max(320, Number(noticeModalFloatingState.height) || 320));
                const maxLeft = Math.max(8, viewportWidth - width - 8);
                const maxTop = Math.max(8, viewportHeight - height - 8);
                const left = Math.min(maxLeft, Math.max(8, Number(noticeModalFloatingState.left) || 8));
                const top = Math.min(maxTop, Math.max(8, Number(noticeModalFloatingState.top) || 8));
                noticePanelEl.style.position = 'absolute';
                noticePanelEl.style.left = `${left}px`;
                noticePanelEl.style.top = `${top}px`;
                noticePanelEl.style.width = `${width}px`;
                noticePanelEl.style.height = `${height}px`;
                noticeModalFloatingState = { left, top, width, height };
            };

            const openNoticeModal = () => {
                if (!canNoticeModal || !noticeModalEl) {
                    return;
                }

                closeNoticeAnnouncementModal();
                closeNoticeLevel3Modal();
                closeNoticeFileModal();
                renderNoticeModalContent();
                noticeModalEl.classList.add('show');
                noticeModalEl.setAttribute('aria-hidden', 'false');
                applyNoticePanelFloatingState();
            };

            const closeNoticeModal = () => {
                if (!canNoticeModal || !noticeModalEl) {
                    return;
                }

                saveNoticePanelFloatingState();
                closeNoticeAnnouncementModal();
                closeNoticeLevel3Modal();
                closeNoticeFileModal();
                noticeModalEl.classList.remove('show');
                noticeModalEl.setAttribute('aria-hidden', 'true');
            };
            if (canNoticeModal) {
                window.openKpiOrganizationNoticeModal = () => {
                    openNoticeModal();
                };
            }

            const evaluateScoreByCriteria = (score, target, criteria) => {
                switch (criteria) {
                    case '>':
                        return score > target;
                    case '>=':
                        return score >= target;
                    case '<=':
                        return score <= target;
                    case '<':
                        return score < target;
                    case '=':
                        return score === target;
                    case '!=':
                        return score !== target;
                    default:
                        return false;
                }
            };

            const displayCriteriaSymbol = (operator) => {
                switch (operator) {
                    case '>=':
                        return '\u2265';
                    case '<=':
                        return '\u2264';
                    case '!=':
                        return '\u2260';
                    default:
                        return operator || '-';
                }
            };

            const formatDisplayNumber = (value) => {
                if (!Number.isFinite(value)) {
                    return '-';
                }

                const rounded = Math.round(value * 100) / 100;
                const hasDecimal = Math.abs(rounded - Math.round(rounded)) > Number.EPSILON;
                return rounded.toLocaleString(undefined, {
                    minimumFractionDigits: hasDecimal ? 2 : 0,
                    maximumFractionDigits: 2,
                });
            };

            const resolveKpiAvgDisplayDepartments = (valueByDepartment) => {
                const optionDepartments = normalizeDepartmentCodeList(targetDepartmentOptions);
                const source = (
                    valueByDepartment &&
                    typeof valueByDepartment === 'object' &&
                    !Array.isArray(valueByDepartment)
                ) ? valueByDepartment : {};
                const sourceDepartments = normalizeDepartmentCodeList(Object.keys(source));
                const merged = [...optionDepartments];

                sourceDepartments.forEach((departmentCode) => {
                    if (!merged.includes(departmentCode)) {
                        merged.push(departmentCode);
                    }
                });

                if (viewerDepartmentCode !== '' && !merged.includes(viewerDepartmentCode)) {
                    merged.push(viewerDepartmentCode);
                }

                return merged;
            };

            const resolveCardSelectedDepartmentFromElement = (cardEl) => {
                if (!cardEl) {
                    return '';
                }

                const targetDepartmentsSelect = cardEl.querySelector('[data-role="target-departments"]');
                if (!targetDepartmentsSelect) {
                    return '';
                }

                const selectedOption = [...targetDepartmentsSelect.options].find((optionEl) => optionEl.selected);
                return normalizeDepartmentCode(selectedOption ? selectedOption.value : '');
            };

            const resolveCardReportDepartment = (cardEl) => {
                if (!cardEl || !isCardReportType(cardEl)) {
                    return '';
                }

                const selectedDepartment = resolveCardSelectedDepartmentFromElement(cardEl);
                if (selectedDepartment !== '') {
                    return selectedDepartment;
                }

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '') {
                    return defaultDepartment;
                }

                return getViewerDepartmentFallback();
            };

            const resolveCardTargetDepartment = (cardEl) => {
                if (!cardEl) {
                    return '';
                }

                if (resolveAllowedCardModeType(cardEl.dataset.modeType || '') !== 'target') {
                    return '';
                }

                const selectedDepartment = resolveCardSelectedDepartmentFromElement(cardEl);
                if (selectedDepartment !== '') {
                    return selectedDepartment;
                }

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '') {
                    return defaultDepartment;
                }

                return getViewerDepartmentFallback();
            };

            const resolveTargetTabDepartments = () => {
                const departments = normalizeDepartmentCodeList(targetDepartmentOptions);

                const cardNodes = [
                    ...listEl.querySelectorAll('.kpi-card'),
                    ...editorListEl.querySelectorAll('.kpi-card'),
                ];
                cardNodes.forEach((cardEl) => {
                    const department = resolveCardTargetDepartment(cardEl);
                    if (department !== '' && !departments.includes(department)) {
                        departments.push(department);
                    }
                });

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '' && !departments.includes(defaultDepartment)) {
                    departments.unshift(defaultDepartment);
                }

                const ownDepartment = getViewerDepartmentFallback();
                if (departments.length < 1 && ownDepartment !== '') {
                    departments.push(ownDepartment);
                }

                return normalizeDepartmentCodeList(departments);
            };

            const resolveReportTabDepartments = (valueByDepartment = null) => {
                const normalizedByAverage = (
                    valueByDepartment &&
                    typeof valueByDepartment === 'object' &&
                    !Array.isArray(valueByDepartment)
                )
                    ? valueByDepartment
                    : kpiAverageByDepartmentState;
                const departments = resolveKpiAvgDisplayDepartments(normalizedByAverage);

                const cardNodes = [
                    ...listEl.querySelectorAll('.kpi-card'),
                    ...editorListEl.querySelectorAll('.kpi-card'),
                ];
                cardNodes.forEach((cardEl) => {
                    const department = resolveCardReportDepartment(cardEl);
                    if (department !== '' && !departments.includes(department)) {
                        departments.push(department);
                    }
                });

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '' && !departments.includes(defaultDepartment)) {
                    departments.unshift(defaultDepartment);
                }

                const ownDepartment = getViewerDepartmentFallback();
                if (departments.length < 1 && ownDepartment !== '') {
                    departments.push(ownDepartment);
                }

                return normalizeDepartmentCodeList(departments);
            };

            const resolveCardHierarchyFilterKey = (cardEl) => {
                if (!cardEl) {
                    return '';
                }

                const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                if (modeType === 'report') {
                    const levelThreeSelection = getCardReportLevelThreeSelection(cardEl);
                    if (levelThreeSelection && Number(levelThreeSelection.parentTargetKpiId || 0) > 0) {
                        return buildOkrLevelThreeFilterKey(levelThreeSelection.parentTargetKpiId);
                    }
                }

                const selection = getCardOkrHierarchySelection(cardEl) || (
                    Number(cardEl.dataset.okrObjectiveId || 0) > 0 && Number(cardEl.dataset.okrKeyResultId || 0) > 0
                        ? {
                            objectiveId: Number(cardEl.dataset.okrObjectiveId),
                            keyResultId: Number(cardEl.dataset.okrKeyResultId),
                        }
                        : null
                );
                return selection
                    ? buildOkrHierarchyFilterKey(selection.objectiveId, selection.keyResultId)
                    : '';
            };

            const resolveHierarchyFilterLabel = (hierarchyKey) => {
                const selection = parseOkrSelectionValue(hierarchyKey);
                const summary = resolveOkrHierarchySummaryBySelection(selection);
                if (!summary) {
                    return '';
                }

                return `${summary.objectiveLabel} / ${summary.keyResultLabel}`;
            };

            const resolveHierarchyTabOptions = (modeType) => {
                const normalizedMode = resolveAllowedCardModeType(modeType || 'report');
                const optionsByKey = new Map();
                const cardNodes = [
                    ...listEl.querySelectorAll('.kpi-card'),
                    ...editorListEl.querySelectorAll('.kpi-card'),
                ];

                cardNodes.forEach((cardEl) => {
                    const isReportType = isCardReportType(cardEl);
                    if (normalizedMode === 'report' && !isReportType) {
                        return;
                    }
                    if (normalizedMode === 'target' && isReportType) {
                        return;
                    }

                    const levelThreeSelection = normalizedMode === 'report'
                        ? getCardReportLevelThreeSelection(cardEl)
                        : null;
                    const key = normalizedMode === 'report' && levelThreeSelection
                        ? buildOkrLevelThreeFilterKey(levelThreeSelection.parentTargetKpiId)
                        : resolveCardHierarchyFilterKey(cardEl);
                    if (key === '' || optionsByKey.has(key)) {
                        return;
                    }

                    const label = normalizedMode === 'report' && levelThreeSelection
                        ? (levelThreeSelection.levelThreeLabel || '')
                        : resolveHierarchyFilterLabel(key);
                    if (label === '') {
                        return;
                    }

                    optionsByKey.set(key, {
                        key,
                        label,
                    });
                });

                const l2Options = [...optionsByKey.values()];

                if (normalizedMode === 'target') {
                    const l1ByObjId = new Map();
                    l2Options.forEach((opt) => {
                        const sel = parseOkrSelectionValue(opt.key);
                        if (!sel) return;
                        const l1Key = buildOkrObjectiveFilterKey(sel.objectiveId);
                        if (l1Key === '' || l1ByObjId.has(l1Key)) return;
                        const objective = okrHierarchyOptions.find((o) => Number(o.id) === sel.objectiveId);
                        if (!objective) return;
                        l1ByObjId.set(l1Key, { key: l1Key, label: formatOkrObjectiveLabel(objective) });
                    });
                    const result = [];
                    l1ByObjId.forEach((l1Option, l1Key) => {
                        result.push(l1Option);
                        l2Options.forEach((l2Option) => {
                            const sel = parseOkrSelectionValue(l2Option.key);
                            if (sel && buildOkrObjectiveFilterKey(sel.objectiveId) === l1Key) {
                                const obj2 = okrHierarchyOptions.find((o) => Number(o.id) === sel.objectiveId);
                                const kr2 = obj2 ? (obj2.key_results || []).find((k) => Number(k.id) === sel.keyResultId) : null;
                                result.push({ ...l2Option, label: kr2 ? formatOkrKeyResultSummaryLabel(kr2) : l2Option.label });
                            }
                        });
                    });
                    return result;
                }

                return l2Options;
            };

            const ensureCurrentTargetDepartment = (departments = []) => {
                const availableDepartments = normalizeDepartmentCodeList(departments);
                if (availableDepartments.length < 1) {
                    currentTargetDepartmentFilterAll = false;
                    currentTargetDepartment = '';
                    saveCurrentTargetDepartment();
                    return currentTargetDepartment;
                }

                if (currentTargetDepartmentFilterAll) {
                    currentTargetDepartment = '';
                    saveCurrentTargetDepartment();
                    return currentTargetDepartment;
                }

                const currentDepartment = normalizeDepartmentCode(currentTargetDepartment || '');
                if (currentDepartment !== '' && availableDepartments.includes(currentDepartment)) {
                    currentTargetDepartment = currentDepartment;
                    saveCurrentTargetDepartment();
                    return currentTargetDepartment;
                }

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '' && availableDepartments.includes(defaultDepartment)) {
                    currentTargetDepartment = defaultDepartment;
                    saveCurrentTargetDepartment();
                    return currentTargetDepartment;
                }

                currentTargetDepartment = availableDepartments[0];
                saveCurrentTargetDepartment();
                return currentTargetDepartment;
            };

            const ensureCurrentReportDepartment = (departments = []) => {
                const availableDepartments = normalizeDepartmentCodeList(departments);
                if (availableDepartments.length < 1) {
                    currentReportDepartmentFilterAll = false;
                    currentReportDepartment = '';
                    saveCurrentReportDepartment();
                    return currentReportDepartment;
                }

                if (currentReportDepartmentFilterAll) {
                    currentReportDepartment = '';
                    saveCurrentReportDepartment();
                    return currentReportDepartment;
                }

                const currentDepartment = normalizeDepartmentCode(currentReportDepartment || '');
                if (currentDepartment !== '' && availableDepartments.includes(currentDepartment)) {
                    currentReportDepartment = currentDepartment;
                    saveCurrentReportDepartment();
                    return currentReportDepartment;
                }

                const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                if (defaultDepartment !== '' && availableDepartments.includes(defaultDepartment)) {
                    currentReportDepartment = defaultDepartment;
                    saveCurrentReportDepartment();
                    return currentReportDepartment;
                }

                currentReportDepartment = availableDepartments[0];
                saveCurrentReportDepartment();
                return currentReportDepartment;
            };

            const ensureCurrentTargetHierarchyKey = (options = []) => {
                const availableKeys = Array.isArray(options)
                    ? options.map((option) => normalizeStoredHierarchyKey(option && option.key ? option.key : '')).filter((key) => key !== '')
                    : [];
                if (availableKeys.length < 1) {
                    currentTargetHierarchyKey = '';
                    saveCurrentTargetHierarchyKey();
                    return currentTargetHierarchyKey;
                }

                const currentKey = normalizeStoredHierarchyKey(currentTargetHierarchyKey || '');
                if (currentKey !== '' && availableKeys.includes(currentKey)) {
                    currentTargetHierarchyKey = currentKey;
                    saveCurrentTargetHierarchyKey();
                    return currentTargetHierarchyKey;
                }

                currentTargetHierarchyKey = '';
                saveCurrentTargetHierarchyKey();
                return currentTargetHierarchyKey;
            };

            const ensureCurrentReportHierarchyKey = (options = []) => {
                const availableKeys = Array.isArray(options)
                    ? options.map((option) => normalizeOkrLevelThreeFilterKey(option && option.key ? option.key : '')).filter((key) => key !== '')
                    : [];
                if (availableKeys.length < 1) {
                    currentReportHierarchyKey = '';
                    saveCurrentReportHierarchyKey();
                    return currentReportHierarchyKey;
                }

                const currentKey = normalizeOkrLevelThreeFilterKey(currentReportHierarchyKey || '');
                if (currentKey !== '' && availableKeys.includes(currentKey)) {
                    currentReportHierarchyKey = currentKey;
                    saveCurrentReportHierarchyKey();
                    return currentReportHierarchyKey;
                }

                currentReportHierarchyKey = '';
                saveCurrentReportHierarchyKey();
                return currentReportHierarchyKey;
            };

            const handleTargetDepartmentTabSelect = (departmentCode) => {
                const notifyGlobalNoticeDepartmentChanged = () => {
                    document.dispatchEvent(new CustomEvent('app:notice-department-changed'));
                };
                const nextDepartment = normalizeDepartmentCode(departmentCode || '');
                if (nextDepartment === '') {
                    return;
                }

                const sameDepartment = normalizeDepartmentCode(currentTargetDepartment || '') === nextDepartment;
                if (currentKpiMode === 'target' && sameDepartment) {
                    notifyGlobalNoticeDepartmentChanged();
                    keepKpiViewportAtTabs('smooth');
                    return;
                }

                currentKpiMode = 'target';
                currentTargetDepartmentFilterAll = false;
                currentTargetDepartment = nextDepartment;
                currentTargetHierarchyKey = '';
                saveCurrentKpiMode();
                saveCurrentTargetDepartment();
                saveCurrentTargetHierarchyKey();
                renderKpiModeTabs();
                renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                applyKpiModeFilterToCards();
                ensureCardOrder();
                notifyGlobalNoticeDepartmentChanged();
                keepKpiViewportAtTabs('smooth');
            };

            const handleReportDepartmentTabSelect = (departmentCode) => {
                const notifyGlobalNoticeDepartmentChanged = () => {
                    document.dispatchEvent(new CustomEvent('app:notice-department-changed'));
                };
                const nextDepartment = normalizeDepartmentCode(departmentCode || '');
                if (nextDepartment === '') {
                    return;
                }

                const sameDepartment = normalizeDepartmentCode(currentReportDepartment || '') === nextDepartment;
                if (currentKpiMode === 'report' && sameDepartment) {
                    notifyGlobalNoticeDepartmentChanged();
                    keepKpiViewportAtTabs('smooth');
                    return;
                }

                currentKpiMode = 'report';
                currentReportDepartmentFilterAll = false;
                currentReportDepartment = nextDepartment;
                currentReportHierarchyKey = '';
                saveCurrentKpiMode();
                saveCurrentReportDepartment();
                saveCurrentReportHierarchyKey();
                renderKpiModeTabs();
                renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                applyKpiModeFilterToCards();
                ensureCardOrder();
                notifyGlobalNoticeDepartmentChanged();
                keepKpiViewportAtTabs('smooth');
            };

            const handleHierarchyTabSelect = (modeType, hierarchyKey) => {
                const normalizedMode = resolveAllowedCardModeType(modeType || 'report');
                const isAllSelection = String(hierarchyKey || '').trim() === '';
                const safeKey = isAllSelection
                    ? ''
                    : (
                        normalizedMode === 'report'
                            ? normalizeOkrLevelThreeFilterKey(hierarchyKey || '')
                            : normalizeStoredHierarchyKey(hierarchyKey || '')
                    );
                if (!isAllSelection && safeKey === '') {
                    return;
                }

                if (normalizedMode === 'target') {
                    if (!canUseTargetMode) {
                        return;
                    }
                    currentKpiMode = 'target';
                    currentTargetDepartmentFilterAll = isAllSelection;
                    if (isAllSelection) {
                        currentTargetDepartment = '';
                        saveCurrentTargetDepartment();
                    }
                    currentTargetHierarchyKey = safeKey;
                    saveCurrentTargetHierarchyKey();
                } else {
                    currentKpiMode = 'report';
                    currentReportDepartmentFilterAll = isAllSelection;
                    if (isAllSelection) {
                        currentReportDepartment = '';
                        saveCurrentReportDepartment();
                    }
                    currentReportHierarchyKey = safeKey;
                    saveCurrentReportHierarchyKey();
                    const levelThreeEntry = resolveOkrLevelThreeEntryByFilterKey(safeKey);
                    const levelThreeDepartment = normalizeDepartmentCodeList(
                        levelThreeEntry && Array.isArray(levelThreeEntry.target_departments)
                            ? levelThreeEntry.target_departments
                            : []
                    )[0] || '';
                    if (levelThreeDepartment !== '') {
                        currentReportDepartmentFilterAll = false;
                        currentReportDepartment = levelThreeDepartment;
                        saveCurrentReportDepartment();
                    }
                }

                saveCurrentKpiMode();
                renderKpiModeTabs();
                renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                applyKpiModeFilterToCards();
                ensureCardOrder();
                document.dispatchEvent(new CustomEvent('app:notice-department-changed'));
                keepKpiViewportAtTabs('smooth');
            };

            const renderKpiModeTabs = () => {
                if (
                    !kpiModeTabsEl ||
                    !kpiModeTargetBtn ||
                    !kpiModeReportBtn ||
                    !kpiModeTargetSubtabsEl ||
                    !kpiModeReportSubtabsEl
                ) {
                    if (noticeFabEl) {
                        noticeFabEl.hidden = !canNoticeModal;
                    }
                    return;
                }

                if (!showKpiModeTabs || !kpiAvgDeptListEl) {
                    kpiModeTabsEl.hidden = true;
                    if (noticeFabEl) {
                        noticeFabEl.hidden = !canNoticeModal;
                    }
                    return;
                }

                kpiModeTabsEl.hidden = false;
                const targetDepartments = canUseTargetMode ? resolveTargetTabDepartments() : [];
                const reportDepartments = resolveReportTabDepartments();
                ensureCurrentTargetDepartment(targetDepartments);
                ensureCurrentReportDepartment(reportDepartments);

                kpiModeTargetBtn.textContent = getKpiModeTabLabel('target');
                kpiModeReportBtn.textContent = getKpiModeTabLabel('report');
                if (kpiModeTargetGroup) {
                    kpiModeTargetGroup.hidden = !canUseTargetMode;
                    kpiModeTargetGroup.classList.toggle('is-active', canUseTargetMode && currentKpiMode === 'target');
                }
                if (kpiModeReportGroup) {
                    kpiModeReportGroup.hidden = false;
                    kpiModeReportGroup.classList.toggle('is-active', currentKpiMode === 'report');
                }
                kpiModeTargetBtn.hidden = !canUseTargetMode;
                kpiModeTargetBtn.classList.toggle('is-active', canUseTargetMode && currentKpiMode === 'target');
                kpiModeReportBtn.classList.toggle('is-active', currentKpiMode === 'report');

                kpiModeTargetBtn.onclick = () => {
                    const notifyGlobalNoticeDepartmentChanged = () => {
                        document.dispatchEvent(new CustomEvent('app:notice-department-changed'));
                    };
                    if (!canUseTargetMode) {
                        return;
                    }
                    kpiModeTargetBtn.blur();
                    if (currentKpiMode === 'target') {
                        notifyGlobalNoticeDepartmentChanged();
                        keepKpiViewportAtTabs('smooth');
                        return;
                    }

                    currentKpiMode = 'target';
                    saveCurrentKpiMode();
                    renderKpiModeTabs();
                    renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                    applyKpiModeFilterToCards();
                    ensureCardOrder();
                    notifyGlobalNoticeDepartmentChanged();
                    keepKpiViewportAtTabs('smooth');
                };
                kpiModeReportBtn.onclick = () => {
                    const notifyGlobalNoticeDepartmentChanged = () => {
                        document.dispatchEvent(new CustomEvent('app:notice-department-changed'));
                    };
                    kpiModeReportBtn.blur();
                    if (currentKpiMode === 'report') {
                        notifyGlobalNoticeDepartmentChanged();
                        keepKpiViewportAtTabs('smooth');
                        return;
                    }
                    currentKpiMode = 'report';
                    saveCurrentKpiMode();
                    renderKpiModeTabs();
                    renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                    applyKpiModeFilterToCards();
                    ensureCardOrder();
                    notifyGlobalNoticeDepartmentChanged();
                    keepKpiViewportAtTabs('smooth');
                };

                const renderSubtabs = (
                    containerEl,
                    departments,
                    modeType,
                    activeDepartment,
                    labelResolver,
                    onDepartmentSelect
                ) => {
                    containerEl.innerHTML = '';
                    const safeDepartments = Array.isArray(departments) ? departments : [];
                    containerEl.hidden = false;
                    const activeSafeDepartment = normalizeDepartmentCode(activeDepartment || '');
                    const allTabBtn = document.createElement('button');
                    allTabBtn.type = 'button';
                    allTabBtn.className = 'kpi-mode-subtab-btn';
                    allTabBtn.dataset.modeType = modeType;
                    allTabBtn.dataset.filterValue = 'all';
                    allTabBtn.dataset.hierarchyKey = '';
                    allTabBtn.textContent = getText('kpiFilterAll');
                    allTabBtn.classList.toggle(
                        'is-active',
                        currentKpiMode === modeType
                            && activeSafeDepartment === ''
                    );
                    allTabBtn.onclick = (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        handleHierarchyTabSelect(modeType, '');
                        allTabBtn.blur();
                    };
                    containerEl.appendChild(allTabBtn);

                    if (safeDepartments.length > 0) {
                        const sectionEl = document.createElement('div');
                        sectionEl.className = 'kpi-mode-subtab-section';
                        const titleEl = document.createElement('p');
                        titleEl.className = 'kpi-mode-subtab-section-title';
                        titleEl.textContent = getText('kpiLabelTargetDepartments');
                        sectionEl.appendChild(titleEl);
                        safeDepartments.forEach((departmentCode) => {
                            const tabBtn = document.createElement('button');
                            tabBtn.type = 'button';
                            tabBtn.className = 'kpi-mode-subtab-btn';
                            tabBtn.dataset.modeType = modeType;
                            tabBtn.dataset.department = departmentCode;
                            tabBtn.textContent = labelResolver(departmentCode);
                            tabBtn.classList.toggle(
                                'is-active',
                                currentKpiMode === modeType
                                    && normalizeDepartmentCode(activeDepartment || '') === departmentCode
                            );
                            tabBtn.onclick = (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                onDepartmentSelect(departmentCode);
                                tabBtn.blur();
                            };
                            sectionEl.appendChild(tabBtn);
                        });
                        containerEl.appendChild(sectionEl);
                    }
                };

                renderSubtabs(
                    kpiModeTargetSubtabsEl,
                    targetDepartments,
                    'target',
                    currentTargetDepartment,
                    getKpiModeTargetTabLabel,
                    handleTargetDepartmentTabSelect
                );
                renderSubtabs(
                    kpiModeReportSubtabsEl,
                    canUseReportDepartmentSubtabs ? reportDepartments : [],
                    'report',
                    currentReportDepartment,
                    getKpiModeReportTabLabel,
                    handleReportDepartmentTabSelect
                );

                if (noticeFabEl) {
                    noticeFabEl.hidden = !canNoticeModal;
                }
            };

            const isCardReportType = (cardEl) => {
                if (!cardEl) {
                    return false;
                }

                if (resolveAllowedCardModeType(cardEl.dataset.modeType || '') === 'report') {
                    return true;
                }

                const monthItems = [...cardEl.querySelectorAll('[data-role="month-item"]')];
                if (monthItems.some((monthItem) => monthItem.dataset.saved === '1')) {
                    return true;
                }

                const resultInput = cardEl.querySelector('[data-role="result"]');
                const numericResult = resultInput
                    ? parseNumeric(resultInput.dataset.resultValue)
                    : Number.NaN;

                return Number.isFinite(numericResult);
            };

            const resolveCardPendingResultText = (cardEl) => {
                if (resolveAllowedCardModeType(cardEl && cardEl.dataset ? cardEl.dataset.modeType : '') === 'target') {
                    return getKpiAveragePendingText();
                }
                return getText('kpiResultPending');
            };

            const resolveCardResultLabelText = (cardEl) => {
                if (resolveAllowedCardModeType(cardEl && cardEl.dataset ? cardEl.dataset.modeType : '') === 'target') {
                    return `${getText('kpiDocumentLabel')} :`;
                }

                return getText('kpiResultLabel');
            };

            const canSwitchCardModeType = (cardEl) => {
                if (!canUseCardModeSelection || !cardEl) {
                    return false;
                }

                const monthItems = [...cardEl.querySelectorAll('[data-role="month-item"]')];
                const hasSavedMonth = monthItems.some((monthItem) => monthItem.dataset.saved === '1');
                const resultInput = cardEl.querySelector('[data-role="result"]');
                const isResultConfirmed = !!(resultInput && resultInput.dataset.confirmed === '1');
                return !hasSavedMonth && !isResultConfirmed;
            };

            const syncCardModePicker = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const modeWrap = cardEl.querySelector('[data-role="card-mode-wrap"]');
                const modeReportBtn = cardEl.querySelector('[data-role="card-mode-report"]');
                const modeTargetBtn = cardEl.querySelector('[data-role="card-mode-target"]');
                if (!modeWrap || !modeReportBtn || !modeTargetBtn) {
                    return;
                }

                const canShowCardMode = canShowCardModeSelection;
                cardEl.classList.toggle('has-card-mode', canShowCardMode);
                if (!canShowCardMode) {
                    modeWrap.hidden = true;
                    return;
                }

                modeWrap.hidden = false;
                const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || '');
                modeReportBtn.classList.toggle('is-active', modeType === 'report');
                modeTargetBtn.classList.toggle('is-active', modeType === 'target');
                modeTargetBtn.hidden = !canUseTargetMode;

                const allowSwitch = canSwitchCardModeType(cardEl);
                modeReportBtn.disabled = !allowSwitch;
                modeTargetBtn.disabled = !allowSwitch;
            };

            const resolveCardHasCriteriaChoice = (cardEl) => {
                if (!cardEl || resolveAllowedCardModeType(cardEl.dataset.modeType || '') !== 'target') {
                    return true;
                }

                const selectedChoice = cardEl.querySelector('[data-role="has-criteria"]:checked');
                if (!selectedChoice) {
                    return null;
                }

                return selectedChoice.value === '1';
            };

            const syncCardCriteriaChoice = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const isTargetMode = resolveAllowedCardModeType(cardEl.dataset.modeType || '') === 'target';
                const choiceWrap = cardEl.querySelector('[data-role="criteria-choice"]');
                const criteriaFields = cardEl.querySelector('[data-role="criteria-fields"]');
                if (choiceWrap) {
                    choiceWrap.hidden = !isTargetMode;
                }
                if (criteriaFields) {
                    criteriaFields.hidden = isTargetMode && resolveCardHasCriteriaChoice(cardEl) !== true;
                }
            };

            const syncCardModeSections = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || '');
                cardEl.classList.toggle('is-target-mode', modeType === 'target');
                cardEl.classList.toggle('is-report-mode', modeType === 'report');
                syncCardCriteriaChoice(cardEl);
                const scoreTitle = cardEl.querySelector('[data-role="label-score-title"]');
                const monthGrid = cardEl.querySelector('[data-role="month-grid"]');
                const summaryBox = cardEl.querySelector('[data-role="summary-box"]');
                if (scoreTitle) {
                    scoreTitle.style.display = modeType === 'target' ? 'none' : '';
                }
                if (monthGrid) {
                    monthGrid.style.display = modeType === 'target' ? 'none' : '';
                }
                if (summaryBox) {
                    summaryBox.style.display = modeType === 'target' ? 'none' : '';
                }

                const resultLabel = cardEl.querySelector('[data-role="label-result"]');
                if (resultLabel) {
                    resultLabel.textContent = resolveCardResultLabelText(cardEl);
                }

                renderCardOkrHierarchySelection(cardEl);
                renderCardReportLevelThreeSelection(cardEl);

                const resultInput = cardEl.querySelector('[data-role="result"]');
                if (resultInput && !resultInput.dataset.confirmed) {
                    resultInput.textContent = resolveCardPendingResultText(cardEl);
                }
            };

            const setCardModeType = (cardEl, modeType, options = {}) => {
                if (!cardEl) {
                    return;
                }

                const settings = options && typeof options === 'object' ? options : {};
                const normalizedMode = resolveAllowedCardModeType(modeType);
                cardEl.dataset.modeType = normalizedMode;

                if (settings.persist !== false) {
                    const itemId = getCardItemId(cardEl);
                    if (itemId > 0) {
                        setStoredCardModeType(itemId, normalizedMode);
                    }
                }

                syncCardModePicker(cardEl);
                syncCardModeSections(cardEl);

                if (settings.applyFilter !== false) {
                    applyKpiModeFilterToCards();
                }
            };

            const keepKpiViewportAtTabs = (behavior = 'auto') => {
                const anchor = (editorZoneEl && !editorZoneEl.hidden)
                    ? editorZoneEl
                    : (kpiModeTabsEl || listEl);
                if (!anchor || typeof window.scrollTo !== 'function') {
                    return;
                }

                const rect = anchor.getBoundingClientRect();
                const targetTop = Math.max(0, window.scrollY + rect.top - 14);
                window.scrollTo({
                    top: targetTop,
                    behavior,
                });
            };

            const updateEditorZoneState = () => {
                const hasEditorCard = editorListEl.querySelector('.kpi-card') !== null;
                editorZoneEl.hidden = !hasEditorCard;
                if (editorZoneTitleEl) {
                    editorZoneTitleEl.textContent = getText('kpiEditorZoneTitle');
                }
            };

            const pinCardToEditorZone = (cardEl) => {
                if (!cardEl) {
                    return false;
                }

                const existingEditorCard = editorListEl.querySelector('.kpi-card');
                if (existingEditorCard && existingEditorCard !== cardEl) {
                    keepKpiViewportAtTabs('smooth');
                    return false;
                }

                if (cardEl.parentElement === editorListEl) {
                    updateEditorZoneState();
                    return true;
                }

                if (cardEl.parentElement === listEl) {
                    const marker = document.createElement('div');
                    marker.hidden = true;
                    marker.className = 'kpi-card-anchor';
                    listEl.insertBefore(marker, cardEl.nextSibling);
                    cardEl._restoreMarker = marker;
                }

                editorListEl.appendChild(cardEl);
                cardEl.dataset.pinned = '1';
                setCardCollapsed(cardEl, false);
                updateEditorZoneState();
                keepKpiViewportAtTabs('smooth');
                return true;
            };

            const restoreCardFromEditorZone = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                if (cardEl.parentElement !== editorListEl && !cardEl._restoreMarker) {
                    delete cardEl.dataset.pinned;
                    updateEditorZoneState();
                    return;
                }

                const marker = cardEl._restoreMarker;
                if (marker && marker.parentElement === listEl) {
                    listEl.insertBefore(cardEl, marker);
                    marker.remove();
                } else {
                    listEl.appendChild(cardEl);
                }

                delete cardEl._restoreMarker;
                delete cardEl.dataset.pinned;
                updateEditorZoneState();
            };

            const applyKpiModeFilterToCards = () => {
                if (!listEl) {
                    return;
                }

                const cards = [...listEl.querySelectorAll('.kpi-card')];
                if (!canUseTargetMode) {
                    cards.forEach((cardEl) => {
                        cardEl.hidden = false;
                    });
                    return;
                }

                const activeReportDepartment = normalizeDepartmentCode(currentReportDepartment || '');
                const activeTargetDepartment = normalizeDepartmentCode(currentTargetDepartment || '');
                cards.forEach((cardEl) => {
                    const isReportType = isCardReportType(cardEl);
                    if (currentKpiMode === 'report') {
                        if (!isReportType) {
                            cardEl.hidden = true;
                            return;
                        }

                        if (!canSelectTargetDepartments || activeReportDepartment === '') {
                            cardEl.hidden = false;
                            return;
                        }

                        const cardDepartment = resolveCardReportDepartment(cardEl);
                        cardEl.hidden = cardDepartment !== '' && cardDepartment !== activeReportDepartment;
                        return;
                    }

                    if (isReportType) {
                        cardEl.hidden = true;
                        return;
                    }

                    if (!canSelectTargetDepartments || activeTargetDepartment === '') {
                        cardEl.hidden = false;
                        return;
                    }

                    const cardDepartment = resolveCardTargetDepartment(cardEl);
                    cardEl.hidden = cardDepartment !== '' && cardDepartment !== activeTargetDepartment;
                });
            };

            const resolveLiveDepartmentAverageFromCards = (departmentCode) => {
                const targetDepartment = normalizeDepartmentCode(departmentCode || '');
                if (targetDepartment === '') {
                    return Number.NaN;
                }

                const allCards = [
                    ...listEl.querySelectorAll('.kpi-card'),
                    ...editorListEl.querySelectorAll('.kpi-card'),
                ];
                const values = [];

                allCards.forEach((cardEl) => {
                    if (!isCardReportType(cardEl)) {
                        return;
                    }
                    if (resolveCardReportDepartment(cardEl) !== targetDepartment) {
                        return;
                    }

                    const resultInput = cardEl.querySelector('[data-role="result"]');
                    if (!resultInput || resultInput.dataset.confirmed !== '1') {
                        return;
                    }

                    const numeric = parseNumeric(resultInput.dataset.resultValue);
                    if (Number.isFinite(numeric)) {
                        values.push(numeric);
                    }
                });

                if (values.length < 1) {
                    return Number.NaN;
                }

                const average = values.reduce((sum, value) => sum + value, 0) / values.length;
                return Number.isFinite(average) ? average : Number.NaN;
            };

            const renderKpiAvgByDepartment = (valueByDepartment) => {
                if (!kpiAvgDeptListEl) {
                    return;
                }

                const source = (
                    valueByDepartment &&
                    typeof valueByDepartment === 'object' &&
                    !Array.isArray(valueByDepartment)
                ) ? valueByDepartment : {};

                const normalizedValueMap = {};
                Object.entries(source).forEach(([departmentCode, value]) => {
                    const department = normalizeDepartmentCode(departmentCode);
                    if (department === '') {
                        return;
                    }

                    const numeric = (
                        value === null ||
                        typeof value === 'undefined' ||
                        String(value).trim() === ''
                    )
                        ? Number.NaN
                        : Number(value);
                    if (Number.isFinite(numeric)) {
                        normalizedValueMap[department] = numeric;
                    }
                });

                if (showKpiModeTabs) {
                    if (kpiAvgDeptCardEl) {
                        kpiAvgDeptCardEl.hidden = false;
                    }
                    kpiAvgDeptListEl.hidden = false;
                    kpiAvgDeptListEl.innerHTML = '';

                    const cardEl = document.createElement('article');
                    cardEl.className = 'kpi-avg-dept-card';

                    const reportDepartments = resolveReportTabDepartments(source);
                    const activeDepartment = ensureCurrentReportDepartment(reportDepartments);
                    const targetDepartments = resolveTargetTabDepartments();
                    const activeTargetDepartment = ensureCurrentTargetDepartment(targetDepartments);
                    const displayDepartment = currentKpiMode === 'target'
                        ? activeTargetDepartment
                        : activeDepartment;
                    if (!(canUseTargetMode && currentKpiMode === 'target')) {
                        const textEl = document.createElement('p');
                        textEl.className = 'kpi-avg-dept-text';
                        if (normalizeDepartmentCode(displayDepartment) === '') {
                            textEl.textContent = `${getKpiAverageSummaryByDepartmentLabel(displayDepartment)}:`;
                        } else {
                            renderDepartmentLabelWithStrongSuffix(
                                textEl,
                                getKpiAverageSummaryDepartmentLabelPrefix(),
                                displayDepartment,
                                ':'
                            );
                        }
                        cardEl.appendChild(textEl);
                    }

                    const valueEl = document.createElement('p');
                    valueEl.className = 'kpi-avg-dept-value';
                    if (canUseTargetMode && currentKpiMode === 'target') {
                        valueEl.classList.add('is-target-pending');
                        valueEl.textContent = getKpiAveragePendingText();
                    } else {
                        let summaryNumeric = Number.NaN;
                        if (
                            activeDepartment !== '' &&
                            Object.prototype.hasOwnProperty.call(normalizedValueMap, activeDepartment)
                        ) {
                            const numeric = Number(normalizedValueMap[activeDepartment]);
                            if (Number.isFinite(numeric)) {
                                summaryNumeric = numeric;
                            }
                        }

                        if (!Number.isFinite(summaryNumeric) && activeDepartment !== '') {
                            summaryNumeric = resolveLiveDepartmentAverageFromCards(activeDepartment);
                        }

                        valueEl.textContent = Number.isFinite(summaryNumeric)
                            ? `${formatDisplayNumber(summaryNumeric)}%`
                            : '-';
                    }
                    cardEl.appendChild(valueEl);

                    kpiAvgDeptListEl.appendChild(cardEl);
                    return;
                }

                const entries = resolveKpiAvgDisplayDepartments(source).map((department) => ({
                    department,
                    numeric: Object.prototype.hasOwnProperty.call(normalizedValueMap, department)
                        ? Number(normalizedValueMap[department])
                        : Number.NaN,
                }));

                const shouldShow = entries.length > 0 && canShowDepartmentKpiAverageBySelectedDepartment;
                if (!shouldShow) {
                    if (kpiAvgDeptCardEl) {
                        kpiAvgDeptCardEl.hidden = true;
                    }
                    kpiAvgDeptListEl.hidden = true;
                    kpiAvgDeptListEl.innerHTML = '';
                    return;
                }

                if (kpiAvgDeptCardEl) {
                    kpiAvgDeptCardEl.hidden = false;
                }
                kpiAvgDeptListEl.hidden = false;
                kpiAvgDeptListEl.innerHTML = '';
                entries.forEach((entry) => {
                    const cardEl = document.createElement('article');
                    cardEl.className = 'kpi-avg-dept-card';

                    const textEl = document.createElement('p');
                    textEl.className = 'kpi-avg-dept-text';
                    if (normalizeDepartmentCode(entry.department) === '') {
                        textEl.textContent = getKpiAverageDepartmentLabelPrefix();
                    } else {
                        renderDepartmentLabelWithStrongSuffix(
                            textEl,
                            getKpiAverageDepartmentLabelPrefix(),
                            entry.department
                        );
                    }
                    cardEl.appendChild(textEl);

                    const valueEl = document.createElement('p');
                    valueEl.className = 'kpi-avg-dept-value';
                    valueEl.textContent = Number.isFinite(entry.numeric)
                        ? `${formatDisplayNumber(entry.numeric)}%`
                        : '-';
                    cardEl.appendChild(valueEl);

                    kpiAvgDeptListEl.appendChild(cardEl);
                });
            };
            renderKpiModeTabs();
            renderKpiAvgByDepartment(kpiAverageByDepartmentState);
            applyKpiModeFilterToCards();

            const calculateCardScore = (cardEl) => {
                const monthItems = [...cardEl.querySelectorAll('[data-role="month-item"]')];
                let submittedCount = 0;
                let approvedCount = 0;
                let pendingCount = 0;
                let rejectedCount = 0;
                let waitingScoreCount = 0;
                let passCount = 0;
                let point = 0;
                let allSavedCount = 0;
                let allSavedPassCount = 0;

                monthItems.forEach((monthItem) => {
                    if (monthItem.dataset.saved === '1') {
                        submittedCount += 1;
                        allSavedCount += 1;
                        if (monthItem.dataset.passed === '1') {
                            allSavedPassCount += 1;
                        }
                        const reviewStatus = normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                        if (reviewStatus === 'approved') {
                            approvedCount += 1;
                            const passed = monthItem.dataset.passed === '1';
                            if (passed) {
                                passCount += 1;
                                point += 100;
                            }
                        } else if (reviewStatus === 'rejected') {
                            rejectedCount += 1;
                        } else {
                            pendingCount += 1;
                        }
                    } else if (monthItem.dataset.locked !== '1') {
                        waitingScoreCount += 1;
                    }
                });

                const max = approvedCount * 100;
                const percent = max > 0 ? (point / max) * 100 : 0;
                const allSavedPercent = allSavedCount > 0
                    ? (allSavedPassCount / allSavedCount) * 100
                    : Number.NaN;

                return {
                    monthItems,
                    submittedCount,
                    approvedCount,
                    pendingCount,
                    rejectedCount,
                    waitingScoreCount,
                    savedCount: approvedCount,
                    passCount,
                    point,
                    max,
                    percent,
                    allSavedCount,
                    allSavedPassCount,
                    allSavedPercent,
                    total: monthItems.length,
                };
            };

            const buildResultSummary = (stat) => {
                return getText('kpiResultSummary')
                    .replace('{percent}', stat.percent.toFixed(2))
                    .replace('{point}', String(stat.point))
                    .replace('{max}', String(stat.max || 0));
            };

            const buildStoredResultText = (value) => {
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    return getText('kpiResultPending');
                }
                return `${formatDisplayNumber(numeric)}%`;
            };

            const buildCardResultBreakdown = (cardEl, scoreStat) => {
                const breakdownEl = cardEl.querySelector('[data-role="result-breakdown"]');
                if (!breakdownEl) {
                    return;
                }

                if (!isCardReportType(cardEl)) {
                    breakdownEl.hidden = true;
                    breakdownEl.innerHTML = '';
                    return;
                }
                breakdownEl.hidden = false;

                const approvedAvgText = scoreStat.approvedCount > 0
                    ? `${formatDisplayNumber(scoreStat.percent)}%`
                    : '-';
                const pendingText = scoreStat.pendingCount > 0
                    ? `${getText('kpiCardReportStatusPending')} (${scoreStat.pendingCount})`
                    : '-';
                const overallAvgText = scoreStat.allSavedCount > 0 && Number.isFinite(scoreStat.allSavedPercent)
                    ? `${formatDisplayNumber(scoreStat.allSavedPercent)}%`
                    : '-';

                breakdownEl.innerHTML = `
                    <div class="kpi-result-breakdown-row">
                        <span>${escapeHtml(getText('kpiResultApprovedAverageLabel'))} :&nbsp;&nbsp;&nbsp;</span>
                        <span class="kpi-result-breakdown-value">${escapeHtml(approvedAvgText)}</span>
                    </div>
                    <div class="kpi-result-breakdown-row">
                        <span>${escapeHtml(getText('kpiResultPendingAverageLabel'))} :&nbsp;&nbsp;&nbsp;</span>
                        <span class="kpi-result-breakdown-value pending">${escapeHtml(pendingText)}</span>
                    </div>
                    <div class="kpi-result-breakdown-row">
                        <span>${escapeHtml(getText('kpiResultOverallAverageLabel'))} :&nbsp;&nbsp;&nbsp;</span>
                        <span class="kpi-result-breakdown-value">${escapeHtml(overallAvgText)}</span>
                    </div>
                `;
            };

            const resolveCompactMonthReviewStatus = (monthItem) => {
                if (!monthItem) {
                    return 'not-open';
                }

                if (monthItem.dataset.locked === '1' && monthItem.dataset.saved !== '1') {
                    return 'not-open';
                }

                if (!monthItem || monthItem.dataset.saved !== '1') {
                    return 'waiting-score';
                }
                const reviewStatus = normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                if (reviewStatus === 'approved') {
                    return 'approved';
                }
                if (reviewStatus === 'rejected') {
                    return 'rejected';
                }
                return 'pending';
            };

            const buildCompactMonthStatusRows = (scoreStat) => {
                const monthItems = Array.isArray(scoreStat && scoreStat.monthItems) ? scoreStat.monthItems : [];
                return monthItems.map((monthItem, index) => {
                    const monthLabel = getText(monthKeys[index] || monthKeys[0]);
                    const status = resolveCompactMonthReviewStatus(monthItem);
                    const statusLabel = status === 'approved'
                        ? getText('kpiCardReportStatusApproved')
                        : (status === 'rejected'
                            ? getText('kpiCardReportStatusRejected')
                            : (status === 'waiting-score'
                                ? getText('kpiCardReportStatusWaitingScore')
                            : (status === 'not-open'
                                ? '-'
                                : getText('kpiCardReportStatusPending'))));
                    return `
                        <div class="kpi-compact-status-month-row">
                            <span class="kpi-compact-status-month-label">${escapeHtml(monthLabel)}</span>
                            <span class="kpi-compact-status-colon">:</span>
                            <span class="kpi-compact-status-month-value status-${escapeHtml(status)}">${escapeHtml(statusLabel)}</span>
                        </div>
                    `;
                }).join('');
            };

            const setCompactSummary = (cardEl, stat = null) => {
                const compactSummary = cardEl.querySelector('[data-role="compact-summary"]');
                if (!compactSummary) {
                    return;
                }

                const objectiveInput = cardEl.querySelector('[data-role="objective"]');
                const detailInput = cardEl.querySelector('[data-role="detail"]');
                const objectiveText = objectiveInput && objectiveInput.value.trim() ? objectiveInput.value.trim() : '-';
                const detailText = detailInput && detailInput.value.trim() ? detailInput.value.trim() : '-';
                const selectedDepartments = canSelectTargetDepartments
                    ? getCardSelectedTargetDepartments(cardEl)
                    : [];
                const selectedDepartmentText = selectedDepartments[0] || getText('kpiSelectPlaceholder');
                const cardModeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                const okrSummary = resolveCardOkrHierarchySummary(cardEl);
                const l1Key = cardModeType === 'target' ? 'kpiLabelTargetLevelOne' : 'kpiLabelOkrLevelOne';
                const l2Key = cardModeType === 'target' ? 'kpiLabelTargetLevelTwo' : 'kpiLabelOkrLevelTwo';
                const buildCompactHierarchyRow = (level, label, value) => `
                    <div class="kpi-card-compact-hierarchy-row is-level-${escapeHtml(level)}">
                        <strong class="kpi-card-compact-hierarchy-label">${escapeHtml(label)}:</strong>
                        <div class="kpi-card-compact-hierarchy-value">${escapeHtmlWithLineBreaks(value || '-')}</div>
                    </div>
                `;
                const buildCompactDetailRow = (label, value) => `
                    <div class="kpi-card-compact-detail-row">
                        <strong class="kpi-card-compact-detail-label">${escapeHtml(label)}:</strong>
                        <div class="kpi-card-compact-detail-value">${escapeHtmlWithLineBreaks(value || '-')}</div>
                    </div>
                `;
                const compactDepartmentRow = canSelectTargetDepartments
                    ? buildCompactDetailRow(getText('kpiLabelTargetDepartments'), selectedDepartmentText)
                    : '';
                const compactOkrRows = okrSummary
                    ? `
                        <div class="kpi-card-compact-hierarchy">
                            ${buildCompactHierarchyRow('1', getText(l1Key), okrSummary.objectiveLabel)}
                            ${buildCompactHierarchyRow('2', getText(l2Key), okrSummary.keyResultLabel)}
                        ${cardModeType === 'report'
                            ? buildCompactHierarchyRow('3', getText('kpiLabelOkrLevelThree'), okrSummary.levelThreeLabel)
                            : ''}
                        </div>
                    `
                    : '';
                const compactOkrSpacer = compactOkrRows !== ''
                    ? '<div class="kpi-card-compact-spacer" aria-hidden="true"></div>'
                    : '';
                const sectionHeaderKey = cardModeType === 'target' ? 'kpiCompactSectionTarget' : 'kpiCompactSectionReport';

                const resultInput = cardEl.querySelector('[data-role="result"]');
                const storedResult = resultInput && resultInput.dataset.confirmed === '1'
                    ? parseNumeric(resultInput.dataset.resultValue)
                    : Number.NaN;

                const scoreStat = (stat && typeof stat === 'object') ? stat : calculateCardScore(cardEl);
                const pendingResultText = resolveCardPendingResultText(cardEl);
                const hasReportPreviewAverage = (
                    isCardReportType(cardEl)
                    && scoreStat
                    && Number(scoreStat.allSavedCount || 0) > 0
                    && Number.isFinite(scoreStat.allSavedPercent)
                );
                const hasApprovedSummary = !!(
                    scoreStat
                    && Number(scoreStat.savedCount || 0) > 0
                );
                const resultText = Number.isFinite(storedResult)
                    ? buildStoredResultText(storedResult)
                    : (hasReportPreviewAverage
                        ? `${formatDisplayNumber(scoreStat.allSavedPercent)}%`
                        : (!hasApprovedSummary
                            ? pendingResultText
                            : getText('kpiResultCompactSummary')
                                .replace('{percent}', scoreStat.percent.toFixed(2))
                                .replace('{point}', String(scoreStat.point))
                                .replace('{max}', String(scoreStat.max))));
                const reviewCountsAllZero = !scoreStat || (
                    Number(scoreStat.approvedCount || 0) < 1
                    && Number(scoreStat.pendingCount || 0) < 1
                    && Number(scoreStat.rejectedCount || 0) < 1
                );
                const isReportCard = isCardReportType(cardEl);
                const hideReportStatusRow = (
                    !isReportCard
                    && reviewCountsAllZero
                    && resultText === pendingResultText
                );
                const compactReportStatusSection = hideReportStatusRow
                    ? ''
                    : `
                        <details class="kpi-compact-status">
                            <summary class="kpi-compact-status-summary">
                                <div>
                                    <p class="kpi-compact-status-title">${escapeHtml(getText('kpiCardReportStatusTitle'))}</p>
                                    <div class="kpi-compact-status-counts">
                                        <div class="kpi-compact-status-count-line status-approved">
                                            <span>${escapeHtml(getText('kpiCardReportStatusApproved'))}</span>
                                            <span class="kpi-compact-status-colon">:</span>
                                            <span class="kpi-compact-status-count-value">${escapeHtml(String(scoreStat.approvedCount || 0))}</span>
                                        </div>
                                        <div class="kpi-compact-status-count-line status-pending">
                                            <span>${escapeHtml(getText('kpiCardReportStatusPending'))}</span>
                                            <span class="kpi-compact-status-colon">:</span>
                                            <span class="kpi-compact-status-count-value">${escapeHtml(String(scoreStat.pendingCount || 0))}</span>
                                        </div>
                                        <div class="kpi-compact-status-count-line status-rejected">
                                            <span>${escapeHtml(getText('kpiCardReportStatusRejected'))}</span>
                                            <span class="kpi-compact-status-colon">:</span>
                                            <span class="kpi-compact-status-count-value">${escapeHtml(String(scoreStat.rejectedCount || 0))}</span>
                                        </div>
                                        <div class="kpi-compact-status-count-line status-waiting-score">
                                            <span>${escapeHtml(getText('kpiCardReportStatusWaitingScore'))}</span>
                                            <span class="kpi-compact-status-colon">:</span>
                                            <span class="kpi-compact-status-count-value">${escapeHtml(String(scoreStat.waitingScoreCount || 0))}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="kpi-compact-status-chevron" aria-hidden="true">V</span>
                            </summary>
                            <div class="kpi-compact-status-panel">
                                <p class="kpi-compact-status-panel-title">${escapeHtml(getText('kpiCardReportStatusMonthsTitle'))}</p>
                                <div class="kpi-compact-status-month-list">
                                    ${buildCompactMonthStatusRows(scoreStat)}
                                </div>
                            </div>
                        </details>
                    `;
                const compactResultLabel = cardModeType === 'target'
                    ? getText('kpiDocumentLabel')
                    : getText('kpiResultLabel');
                const compactResultRow = buildCompactDetailRow(compactResultLabel, resultText);

                compactSummary.innerHTML = `
                    ${compactOkrRows}
                    ${compactOkrSpacer}
                    <div class="kpi-card-compact-section-header">${escapeHtml(getText(sectionHeaderKey))} :</div>
                    <div class="kpi-card-compact-section-body">
                        ${compactDepartmentRow}
                        ${buildCompactDetailRow(getText('kpiLabelObjective'), objectiveText)}
                        ${buildCompactDetailRow(getText('kpiLabelDetail'), detailText)}
                        ${compactResultRow}
                    </div>
                    ${compactReportStatusSection}
                `;
                cardEl.classList.remove('has-report-status');
                const cardReportStatusEl = cardEl.querySelector('[data-role="card-report-status"]');
                if (cardReportStatusEl) {
                    cardReportStatusEl.hidden = true;
                    cardReportStatusEl.textContent = '';
                    cardReportStatusEl.classList.remove('status-pending', 'status-rejected', 'status-approved');
                }
            };

            const setCardCollapsed = (cardEl, collapsed) => {
                const editBtn = cardEl.querySelector('[data-role="edit-card"]');
                cardEl.classList.toggle('is-collapsed', collapsed);
                if (editBtn) {
                    editBtn.style.display = collapsed ? 'inline-block' : 'none';
                }
            };

            const focusAddedCard = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                cardEl.classList.add('kpi-card-just-added');
                try {
                    cardEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                } catch (error) {
                    cardEl.scrollIntoView();
                }

                const firstInput = cardEl.querySelector('[data-role="objective"]');
                if (firstInput && typeof firstInput.focus === 'function') {
                    setTimeout(() => {
                        try {
                            firstInput.focus({ preventScroll: true });
                        } catch (error) {
                            firstInput.focus();
                        }
                    }, 260);
                }

                setTimeout(() => {
                    cardEl.classList.remove('kpi-card-just-added');
                }, 1800);
            };

            const renderCardSummary = (cardEl) => {
                const summaryBody = cardEl.querySelector('[data-role="summary-body"]');
                if (!summaryBody) {
                    return;
                }

                const targetInput = cardEl.querySelector('[data-role="target"]');
                const criteriaSelect = cardEl.querySelector('[data-role="criteria"]');
                const targetNumber = parseNumeric(targetInput ? targetInput.value : '');
                const unitText = resolveCardUnitText(cardEl);
                const criteriaRaw = criteriaSelect ? String(criteriaSelect.value || '').trim() : '';
                const hasCriteria = criteriaRaw !== '' && !Number.isNaN(targetNumber);
                const criteriaText = hasCriteria
                    ? `${displayCriteriaSymbol(criteriaRaw)} ${formatDisplayNumber(targetNumber)}${unitText !== '' ? ` ${unitText}` : ''}`
                    : '-';

                const stat = calculateCardScore(cardEl);
                summaryBody.innerHTML = '';
                let enteredScoreTotal = 0;
                let enteredScoreCount = 0;
                stat.monthItems.forEach((monthItem, index) => {
                    const scoreInput = monthItem.querySelector('[data-role="month-input"]');
                    const scoreValue = parseNumeric(scoreInput ? scoreInput.value : '');
                    const scoreText = Number.isNaN(scoreValue) ? '-' : formatDisplayNumber(scoreValue);
                    const reviewStatus = normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                    let resultText = getText('kpiMonthNotSaved');
                    let resultClass = 'kpi-summary-result-not-saved';
                    if (monthItem.dataset.saved === '1') {
                        if (reviewStatus === 'approved') {
                            if (monthItem.dataset.passed === '1') {
                                resultText = getText('kpiMonthSavedPass');
                                resultClass = 'kpi-summary-result-pass';
                            } else {
                                resultText = getText('kpiMonthSavedFail');
                                resultClass = 'kpi-summary-result-fail';
                            }
                            if (Number.isFinite(scoreValue)) {
                                enteredScoreTotal += scoreValue;
                                enteredScoreCount += 1;
                            }
                        } else if (reviewStatus === 'rejected') {
                            resultText = getText('kpiMonthReviewRejected');
                            resultClass = 'kpi-summary-result-fail';
                        } else {
                            const pendingBaseText = monthItem.dataset.passed === '1'
                                ? getText('kpiMonthSavedPass')
                                : (monthItem.dataset.passed === '0'
                                    ? getText('kpiMonthSavedFail')
                                    : '');
                            resultText = pendingBaseText !== ''
                                ? `${pendingBaseText} (${getText('kpiMonthReviewPending')})`
                                : getText('kpiMonthReviewPending');
                            resultClass = monthItem.dataset.passed === '1'
                                ? 'kpi-summary-result-pass'
                                : (monthItem.dataset.passed === '0'
                                    ? 'kpi-summary-result-fail'
                                    : 'kpi-summary-result-pending');
                        }
                    }

                    const row = document.createElement('tr');

                    const monthCell = document.createElement('td');
                    monthCell.textContent = getText(monthKeys[index]);
                    row.appendChild(monthCell);

                    const scoreCell = document.createElement('td');
                    scoreCell.textContent = scoreText;
                    row.appendChild(scoreCell);

                    const criteriaCell = document.createElement('td');
                    criteriaCell.textContent = criteriaText;
                    row.appendChild(criteriaCell);

                    const resultCell = document.createElement('td');
                    resultCell.className = resultClass;
                    resultCell.textContent = resultText;
                    row.appendChild(resultCell);

                    summaryBody.appendChild(row);
                });

                const netAverageScore = enteredScoreCount > 0
                    ? (enteredScoreTotal / enteredScoreCount)
                    : Number.NaN;

                const netAverageRow = document.createElement('tr');
                netAverageRow.className = 'kpi-summary-net-average';

                const netAverageLabelCell = document.createElement('td');
                netAverageLabelCell.textContent = getText('kpiSummaryNetAverage');
                netAverageRow.appendChild(netAverageLabelCell);

                const netAverageScoreCell = document.createElement('td');
                netAverageScoreCell.textContent = Number.isFinite(netAverageScore)
                    ? formatDisplayNumber(netAverageScore)
                    : '-';
                netAverageRow.appendChild(netAverageScoreCell);

                const netAverageCriteriaCell = document.createElement('td');
                netAverageCriteriaCell.textContent = '';
                netAverageRow.appendChild(netAverageCriteriaCell);

                const netAverageResultCell = document.createElement('td');
                netAverageResultCell.textContent = '';
                netAverageRow.appendChild(netAverageResultCell);

                summaryBody.appendChild(netAverageRow);

                const resultInput = cardEl.querySelector('[data-role="result"]');
                if (resultInput) {
                    if (resultInput.dataset.confirmed === '1') {
                        const storedResult = parseNumeric(resultInput.dataset.resultValue);
                        if (Number.isFinite(storedResult)) {
                            resultInput.textContent = buildStoredResultText(storedResult);
                        } else if (isCardReportType(cardEl) && stat.allSavedCount > 0 && Number.isFinite(stat.allSavedPercent)) {
                            resultInput.textContent = `${formatDisplayNumber(stat.allSavedPercent)}%`;
                        } else {
                            resultInput.textContent = resolveCardPendingResultText(cardEl);
                        }
                    } else if (isCardReportType(cardEl) && stat.allSavedCount > 0 && Number.isFinite(stat.allSavedPercent)) {
                        resultInput.textContent = `${formatDisplayNumber(stat.allSavedPercent)}%`;
                    } else {
                        resultInput.textContent = resolveCardPendingResultText(cardEl);
                    }
                }

                buildCardResultBreakdown(cardEl, stat);
                setCompactSummary(cardEl, stat);
            };

            const setCardDraft = (cardEl) => {
                const resultInput = cardEl.querySelector('[data-role="result"]');
                if (resultInput) {
                    delete resultInput.dataset.confirmed;
                    delete resultInput.dataset.resultValue;
                    resultInput.textContent = resolveCardPendingResultText(cardEl);
                }
                applyKpiModeFilterToCards();
            };

            const evaluateMonthPassState = (cardEl, monthItem) => {
                if (!cardEl || !monthItem) {
                    return null;
                }

                const scoreInput = monthItem.querySelector('[data-role="month-input"]');
                const targetInput = cardEl.querySelector('[data-role="target"]');
                const criteriaSelect = cardEl.querySelector('[data-role="criteria"]');
                const scoreValue = parseNumeric(scoreInput ? scoreInput.value : '');
                const targetValue = parseNumeric(targetInput ? targetInput.value : '');
                const criteria = criteriaSelect ? String(criteriaSelect.value || '').trim() : '';

                if (Number.isNaN(scoreValue) || Number.isNaN(targetValue) || criteria === '') {
                    return null;
                }

                return evaluateScoreByCriteria(scoreValue, targetValue, criteria);
            };

            const renderFileSelection = (monthItem, options, fileCount = null) => {
                if (!monthItem || !options || typeof options !== 'object') {
                    return;
                }

                const fileInput = monthItem.querySelector(`[data-role="${options.inputRole}"]`);
                const selectedInfo = monthItem.querySelector(`[data-role="${options.selectedRole}"]`);
                const fileList = monthItem.querySelector(`[data-role="${options.listRole}"]`);
                if (!fileInput || !selectedInfo || !fileList) {
                    return;
                }

                const previewKey = options.previewKey || '_filePreviewUrls';
                if (Array.isArray(monthItem[previewKey])) {
                    monthItem[previewKey].forEach((url) => URL.revokeObjectURL(url));
                }
                monthItem[previewKey] = [];

                const persistedKey = options.persistedKey || '_persistedFiles';
                const persistedFiles = Array.isArray(monthItem[persistedKey]) ? monthItem[persistedKey] : [];
                const hasLocalFiles = !!(fileInput.files && fileInput.files.length > 0);

                let count = fileCount;
                if (count === null || count === undefined) {
                    count = hasLocalFiles ? fileInput.files.length : 0;
                    if ((!count || count < 0) && persistedFiles.length > 0) {
                        count = persistedFiles.length;
                    }
                    if ((!count || count < 0) && monthItem.dataset[options.datasetCountKey]) {
                        count = Number(monthItem.dataset[options.datasetCountKey]);
                    }
                }

                const safeCount = Number.isFinite(Number(count)) && Number(count) > 0 ? Number(count) : 0;
                monthItem.dataset[options.datasetCountKey] = String(safeCount);
                selectedInfo.textContent = safeCount > 0
                    ? getText(options.selectedTextKey).replace('{count}', String(safeCount))
                    : getText(options.notSelectedTextKey);

                fileList.innerHTML = '';
                if (safeCount > 0 && hasLocalFiles) {
                    [...fileInput.files].forEach((file) => {
                        const objectUrl = URL.createObjectURL(file);
                        monthItem[previewKey].push(objectUrl);

                        const item = document.createElement('li');
                        const link = document.createElement('a');
                        link.className = 'kpi-file-link';
                        link.href = objectUrl;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = file.name;
                        item.appendChild(link);

                        fileList.appendChild(item);
                    });
                } else if (persistedFiles.length > 0) {
                    persistedFiles.forEach((fileItem) => {
                        if (!fileItem || typeof fileItem !== 'object') {
                            return;
                        }

                        const url = typeof fileItem.url === 'string' ? fileItem.url : '';
                        const name = typeof fileItem.name === 'string' ? fileItem.name : '';
                        if (!url || !name) {
                            return;
                        }

                        const item = document.createElement('li');
                        const link = document.createElement('a');
                        link.className = 'kpi-file-link';
                        link.href = url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = name;
                        item.appendChild(link);

                        fileList.appendChild(item);
                    });
                }
            };

            const renderEvidenceSelection = (monthItem, fileCount = null) => {
                renderFileSelection(monthItem, {
                    inputRole: 'month-evidence-input',
                    selectedRole: 'month-evidence-selected',
                    listRole: 'month-evidence-files',
                    persistedKey: '_persistedFiles',
                    previewKey: '_filePreviewUrls',
                    datasetCountKey: 'fileCount',
                    selectedTextKey: 'kpiMonthEvidenceSelected',
                    notSelectedTextKey: 'kpiMonthEvidenceNotSelected',
                }, fileCount);
            };

            const renderActionPlanSelection = (monthItem, fileCount = null) => {
                renderFileSelection(monthItem, {
                    inputRole: 'month-action-plan-input',
                    selectedRole: 'month-action-plan-selected',
                    listRole: 'month-action-plan-files',
                    persistedKey: '_persistedActionPlanFiles',
                    previewKey: '_actionPlanPreviewUrls',
                    datasetCountKey: 'actionPlanCount',
                    selectedTextKey: 'kpiMonthActionPlanSelected',
                    notSelectedTextKey: 'kpiMonthActionPlanNotSelected',
                }, fileCount);
            };

            const updateActionPlanVisibility = (cardEl, monthItem, options = {}) => {
                const settings = options && typeof options === 'object' ? options : {};
                const notifyActionPlanRequired = !!settings.notifyActionPlanRequired;
                const actionPlanField = monthItem ? monthItem.querySelector('[data-role="month-action-plan-field"]') : null;
                const actionPlanInput = monthItem ? monthItem.querySelector('[data-role="month-action-plan-input"]') : null;
                const actionPlanSelected = monthItem ? monthItem.querySelector('[data-role="month-action-plan-selected"]') : null;
                const evidenceField = monthItem ? monthItem.querySelector('[data-role="month-evidence-field"]') : null;
                const evidenceInput = monthItem ? monthItem.querySelector('[data-role="month-evidence-input"]') : null;
                const evidenceSelected = monthItem ? monthItem.querySelector('[data-role="month-evidence-selected"]') : null;
                if (!actionPlanField || !actionPlanInput || !evidenceField || !evidenceInput || !actionPlanSelected || !evidenceSelected) {
                    return;
                }

                const wasRequired = monthItem.dataset.actionPlanRequired === '1';
                const locked = monthItem.dataset.locked === '1';
                const isApprovedMonth = (
                    monthItem.dataset.saved === '1'
                    && String(monthItem.dataset.reviewStatus || '').trim().toLowerCase() === 'approved'
                );
                const shouldShow = showActionPlanOnFail
                    && monthItem.dataset.saved === '1'
                    && monthItem.dataset.passed === '0';

                actionPlanField.hidden = !shouldShow;
                actionPlanInput.disabled = locked || !shouldShow;
                actionPlanInput.hidden = isApprovedMonth;
                actionPlanSelected.hidden = isApprovedMonth;
                monthItem.dataset.actionPlanRequired = shouldShow ? '1' : '0';

                evidenceField.hidden = false;
                const disableEvidenceInput = locked;
                evidenceInput.disabled = disableEvidenceInput;
                evidenceInput.hidden = isApprovedMonth;
                evidenceSelected.hidden = isApprovedMonth;
                if (disableEvidenceInput && evidenceInput.files && evidenceInput.files.length > 0) {
                    evidenceInput.value = '';
                }
                renderEvidenceSelection(monthItem);

                if (!shouldShow && actionPlanInput.files && actionPlanInput.files.length > 0) {
                    actionPlanInput.value = '';
                }
                renderActionPlanSelection(monthItem);

                if (notifyActionPlanRequired && !wasRequired && shouldShow) {
                    const selectedActionPlanCount = actionPlanInput.files ? actionPlanInput.files.length : 0;
                    const persistedActionPlanCount = Array.isArray(monthItem._persistedActionPlanFiles)
                        ? monthItem._persistedActionPlanFiles.length
                        : Number(monthItem.dataset.actionPlanCount || 0);
                    const totalActionPlanCount = selectedActionPlanCount > 0
                        ? selectedActionPlanCount
                        : persistedActionPlanCount;
                    if (!Number.isFinite(totalActionPlanCount) || totalActionPlanCount < 1) {
                        showAlert('kpiAlertNeedActionPlan', 'error');
                    }
                }
            };

            const refreshActionPlanVisibilityForCard = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                cardEl.querySelectorAll('[data-role="month-item"]').forEach((monthItem) => {
                    updateActionPlanVisibility(cardEl, monthItem);
                });
            };

            const findFirstMissingActionPlanMonth = (cardEl) => {
                if (!cardEl || !requireActionPlanOnFail) {
                    return null;
                }

                const monthItems = [...cardEl.querySelectorAll('[data-role="month-item"]')];
                for (let idx = 0; idx < monthItems.length; idx += 1) {
                    const monthItem = monthItems[idx];
                    if (monthItem.dataset.saved !== '1' || monthItem.dataset.passed !== '0') {
                        continue;
                    }

                    const selectedCount = (() => {
                        const actionPlanInput = monthItem.querySelector('[data-role="month-action-plan-input"]');
                        return actionPlanInput && actionPlanInput.files ? actionPlanInput.files.length : 0;
                    })();
                    const persistedCount = Array.isArray(monthItem._persistedActionPlanFiles)
                        ? monthItem._persistedActionPlanFiles.length
                        : Number(monthItem.dataset.actionPlanCount || 0);
                    const totalCount = selectedCount > 0 ? selectedCount : persistedCount;

                    if (!Number.isFinite(totalCount) || totalCount < 1) {
                        return {
                            monthNo: idx + 1,
                            monthItem,
                        };
                    }
                }

                return null;
            };

            const jumpToMonthForActionPlan = (cardEl, monthNo) => {
                const monthIndex = Number(monthNo || 0);
                if (!cardEl || !Number.isFinite(monthIndex) || monthIndex < 1 || monthIndex > 12) {
                    return;
                }

                const monthItems = [...cardEl.querySelectorAll('[data-role="month-item"]')];
                const monthItem = monthItems[monthIndex - 1] || null;
                if (!monthItem) {
                    return;
                }

                setCardCollapsed(cardEl, false);
                setMonthActionMode(monthItem, 'save');
                updateActionPlanVisibility(cardEl, monthItem);

                monthItem.classList.add('focus-action-plan');
                setTimeout(() => {
                    monthItem.classList.remove('focus-action-plan');
                }, 1800);

                monthItem.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                const actionPlanInput = monthItem.querySelector('[data-role="month-action-plan-input"]');
                if (actionPlanInput && !actionPlanInput.disabled) {
                    setTimeout(() => {
                        try {
                            actionPlanInput.focus();
                        } catch (e) {
                            // no-op
                        }
                    }, 220);
                }
            };

            const clearNotificationFocusQueryParams = () => {
                try {
                    const url = new URL(window.location.href);
                    const keys = ['focus_from', 'focus_score_id', 'focus_month_no', 'focus_decision'];
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

            const applyNotificationMonthHighlight = (monthItem, decision = '') => {
                if (!monthItem) {
                    return;
                }

                monthItem.classList.remove('focus-notify-approved', 'focus-notify-rejected');
                const highlightClass = decision === 'rejected'
                    ? 'focus-notify-rejected'
                    : 'focus-notify-approved';
                monthItem.classList.add(highlightClass);

                if (monthItem._notifyHighlightTimer) {
                    clearTimeout(monthItem._notifyHighlightTimer);
                }

                monthItem._notifyHighlightTimer = setTimeout(() => {
                    monthItem.classList.remove('focus-notify-approved', 'focus-notify-rejected');
                    monthItem._notifyHighlightTimer = null;
                }, 2000);
            };

            const findNotificationFocusMonthItem = () => {
                if (!notificationFocusState.active) {
                    return null;
                }

                const monthItems = [...document.querySelectorAll('.kpi-card [data-role="month-item"]')];
                if (monthItems.length < 1) {
                    return null;
                }

                if (notificationFocusState.scoreId > 0) {
                    const byScoreId = monthItems.find((monthItem) => (
                        Number(monthItem && monthItem.dataset ? monthItem.dataset.scoreId || 0 : 0) === notificationFocusState.scoreId
                    ));
                    if (byScoreId) {
                        return byScoreId;
                    }
                }

                if (notificationFocusState.monthNo > 0) {
                    const filteredByMonth = monthItems.filter((monthItem) => (
                        Number(monthItem && monthItem.dataset ? monthItem.dataset.monthNo || 0 : 0) === notificationFocusState.monthNo
                    ));
                    if (filteredByMonth.length < 1) {
                        return null;
                    }

                    const byDecision = notificationFocusState.decision !== ''
                        ? filteredByMonth.find((monthItem) => normalizeMonthReviewStatus(monthItem.dataset.reviewStatus) === notificationFocusState.decision)
                        : null;
                    return byDecision || filteredByMonth[0] || null;
                }

                return null;
            };

            const focusKpiMonthFromNotification = () => {
                if (!notificationFocusState.active) {
                    return;
                }

                const monthItem = findNotificationFocusMonthItem();
                clearNotificationFocusQueryParams();
                if (!monthItem) {
                    return;
                }

                const cardEl = monthItem.closest('.kpi-card');
                if (!cardEl) {
                    return;
                }

                const focusDepartment = resolveCardReportDepartment(cardEl);
                if (focusDepartment !== '') {
                    currentReportDepartmentFilterAll = false;
                    currentReportDepartment = focusDepartment;
                    saveCurrentReportDepartment();
                }

                if (canUseTargetMode && currentKpiMode !== 'report') {
                    currentKpiMode = 'report';
                    saveCurrentKpiMode();
                    renderKpiModeTabs();
                    renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                    applyKpiModeFilterToCards();
                    ensureCardOrder();
                }

                const pinnedToEditor = pinCardToEditorZone(cardEl);
                if (!pinnedToEditor) {
                    setCardCollapsed(cardEl, false);
                }
                setCardCollapsed(cardEl, false);

                try {
                    monthItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                } catch (error) {
                    monthItem.scrollIntoView();
                }

                const highlightDecision = notificationFocusState.decision !== ''
                    ? notificationFocusState.decision
                    : normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                applyNotificationMonthHighlight(monthItem, highlightDecision);
            };

            const normalizeMonthReviewStatus = (value) => {
                const status = String(value || '').trim().toLowerCase();
                if (status === 'approved') return 'approved';
                if (status === 'rejected') return 'rejected';
                return 'pending';
            };

            const isMonthApprovedAndSaved = (monthItem) => (
                !!monthItem
                && monthItem.dataset.saved === '1'
                && normalizeMonthReviewStatus(monthItem.dataset.reviewStatus) === 'approved'
            );

            const syncMonthInputAndActionAvailability = (monthItem) => {
                if (!monthItem) {
                    return;
                }

                const isLockedBySchedule = monthItem.dataset.locked === '1' && monthItem.dataset.saved !== '1';
                const isApprovedLocked = isMonthApprovedAndSaved(monthItem);
                const mustLockInputs = isLockedBySchedule || isApprovedLocked;

                const scoreInput = monthItem.querySelector('[data-role="month-input"]');
                const evidenceInput = monthItem.querySelector('[data-role="month-evidence-input"]');
                const actionPlanInput = monthItem.querySelector('[data-role="month-action-plan-input"]');
                if (scoreInput) {
                    scoreInput.disabled = mustLockInputs;
                }
                if (evidenceInput) {
                    evidenceInput.disabled = mustLockInputs;
                }
                if (actionPlanInput) {
                    actionPlanInput.disabled = mustLockInputs;
                }

                const saveBtn = monthItem.querySelector('[data-role="month-save-btn"]');
                if (saveBtn) {
                    if (isApprovedLocked) {
                        saveBtn.hidden = true;
                        saveBtn.disabled = true;
                    } else {
                        saveBtn.hidden = false;
                        saveBtn.disabled = isLockedBySchedule;
                    }
                }
            };

            const renderMonthReviewNote = (monthItem) => {
                const toggleEl = monthItem.querySelector('[data-role="month-review-toggle"]');
                const noteEl = monthItem.querySelector('[data-role="month-review-note"]');
                if (!noteEl || !toggleEl) {
                    return;
                }

                const rejectDetail = String(monthItem.dataset.reviewRejectDetail || '').trim();
                const reviewStatus = normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                if (reviewStatus === 'rejected' && rejectDetail !== '') {
                    toggleEl.hidden = false;
                    toggleEl.textContent = resolveActiveLang() === 'th'
                        ? '\u25be \u0e14\u0e39\u0e01\u0e32\u0e23\u0e1b\u0e0f\u0e34\u0e40\u0e2a\u0e18'
                        : '\u25be View rejection';
                    noteEl.hidden = true;
                    noteEl.textContent = '';
                } else {
                    toggleEl.hidden = true;
                    toggleEl.textContent = '';
                    noteEl.hidden = true;
                    noteEl.textContent = '';
                }
            };

            const renderMonthCriteriaStatus = (monthItem) => {
                const criteriaEl = monthItem.querySelector('[data-role="month-criteria-status"]');
                if (!criteriaEl) {
                    return;
                }

                criteriaEl.classList.remove('success', 'fail');
                if (monthItem.dataset.saved === '1') {
                    if (monthItem.dataset.passed === '1') {
                        criteriaEl.classList.add('success');
                        criteriaEl.textContent = getText('kpiMonthSavedPass');
                    } else if (monthItem.dataset.passed === '0') {
                        criteriaEl.classList.add('fail');
                        criteriaEl.textContent = getText('kpiMonthSavedFail');
                    } else {
                        criteriaEl.textContent = '-';
                    }
                    return;
                }

                criteriaEl.textContent = '-';
            };

            const renderMonthStatus = (monthItem) => {
                const statusEl = monthItem.querySelector('[data-role="month-status"]');
                if (!statusEl) {
                    return;
                }

                const reviewStatus = normalizeMonthReviewStatus(monthItem.dataset.reviewStatus);
                statusEl.classList.remove('success', 'fail', 'locked', 'not-saved', 'review-pending', 'review-approved', 'review-rejected');
                monthItem.classList.toggle('is-rejected', false);

                if (monthItem.dataset.locked === '1' && monthItem.dataset.saved !== '1') {
                    statusEl.classList.add('locked');
                    statusEl.textContent = getText('kpiMonthLocked');
                    renderMonthCriteriaStatus(monthItem);
                    renderMonthReviewNote(monthItem);
                    return;
                }

                if (monthItem.dataset.saved === '1') {
                    if (reviewStatus === 'approved') {
                        statusEl.classList.add('review-approved');
                        statusEl.textContent = getText('kpiMonthReviewApproved');
                    } else if (reviewStatus === 'rejected') {
                        statusEl.classList.add('review-rejected');
                        statusEl.textContent = getText('kpiMonthReviewRejected');
                        monthItem.classList.add('is-rejected');
                    } else {
                        statusEl.classList.add('review-pending');
                        statusEl.textContent = getText('kpiMonthReviewPending');
                    }
                } else {
                    statusEl.classList.add('not-saved');
                    statusEl.textContent = getText('kpiMonthNotSaved');
                }

                renderMonthCriteriaStatus(monthItem);
                renderMonthReviewNote(monthItem);
                syncMonthInputAndActionAvailability(monthItem);
            };

            const markMonthDraft = (monthItem) => {
                if (monthItem.dataset.locked === '1') {
                    return;
                }
                if (isMonthApprovedAndSaved(monthItem)) {
                    return;
                }
                monthItem.dataset.saved = '0';
                monthItem.dataset.passed = '';
                monthItem.dataset.reviewStatus = 'pending';
                monthItem.dataset.reviewRejectDetail = '';
                monthItem.dataset.reviewedAt = '';
                setMonthActionMode(monthItem, 'save');
                renderMonthStatus(monthItem);
            };

            const markAllMonthsDraft = (cardEl) => {
                cardEl.querySelectorAll('[data-role="month-item"]').forEach((monthItem) => {
                    markMonthDraft(monthItem);
                });
                setCardDraft(cardEl);
                renderCardSummary(cardEl);
                refreshActionPlanVisibilityForCard(cardEl);
            };

            const resolveActiveLang = () => {
                if (window.AppModal && typeof window.AppModal.getLang === 'function') {
                    return window.AppModal.getLang() === 'th' ? 'th' : 'en';
                }

                return new URLSearchParams(window.location.search).get('lang') === 'th' ? 'th' : 'en';
            };

            const getSavedHintText = () => (
                resolveActiveLang() === 'th'
                    ? '\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01\u0e41\u0e25\u0e49\u0e27'
                    : 'Saved'
            );
            const getOtherUnitOptionText = () => (
                resolveActiveLang() === 'th'
                    ? '\u0e2d\u0e37\u0e48\u0e19\u0e46'
                    : 'Other'
            );
            const getOtherUnitPlaceholderText = () => (
                resolveActiveLang() === 'th'
                    ? '\u0e23\u0e30\u0e1a\u0e38\u0e2b\u0e19\u0e48\u0e27\u0e22'
                    : 'Specify unit'
            );
            const getStepActionLabelByMode = (mode) => (mode === 'edit' ? getText('kpiBtnEditCard') : getText('kpiBtnNext'));

            const setMonthActionMode = (monthItem, mode = 'save') => {
                if (!monthItem) {
                    return;
                }

                const saveBtn = monthItem.querySelector('[data-role="month-save-btn"]');
                if (!saveBtn) {
                    return;
                }

                const normalizedMode = mode === 'edit' ? 'edit' : 'save';
                monthItem.dataset.monthActionMode = normalizedMode;
                saveBtn.dataset.mode = normalizedMode;
                saveBtn.classList.toggle('is-edit', normalizedMode === 'edit');
                saveBtn.textContent = normalizedMode === 'edit'
                    ? getText('kpiMonthEditBtn')
                    : getText('kpiMonthSaveBtn');
                syncMonthInputAndActionAvailability(monthItem);
            };

            const getMonthActionMode = (monthItem) => (
                monthItem && monthItem.dataset.monthActionMode === 'edit' ? 'edit' : 'save'
            );

            const formatUnitOptionLabel = (unit) => {
                if (!unit || typeof unit !== 'object') {
                    return '';
                }

                const th = String(unit.name_th || '').trim();
                const en = String(unit.name_en || '').trim();
                const code = String(unit.code || '').trim();
                const lang = resolveActiveLang();

                if (lang === 'th') {
                    return th || en || code;
                }

                return en || th || code;
            };

            const getUnitLabelById = (rawId) => {
                const unitId = Number(rawId || 0);
                if (!Number.isFinite(unitId) || unitId < 1 || !Array.isArray(unitOptions)) {
                    return '';
                }

                const unit = unitOptions.find((item) => Number(item && item.id ? item.id : 0) === unitId);
                return formatUnitOptionLabel(unit);
            };

            const defaultUnitId = (() => {
                if (!Array.isArray(unitOptions)) {
                    return '';
                }

                const firstUnit = unitOptions.find((item) => {
                    const unitId = Number(item && item.id ? item.id : 0);
                    return Number.isFinite(unitId) && unitId > 0;
                });

                return firstUnit ? String(Number(firstUnit.id)) : '';
            })();

            const setStepActionMode = (btn, mode = 'save') => {
                if (!btn) {
                    return;
                }

                const normalizedMode = mode === 'edit' ? 'edit' : 'save';
                btn.dataset.mode = normalizedMode;
                btn.textContent = getStepActionLabelByMode(normalizedMode);
            };

            const getStepActionMode = (btn) => (btn && btn.dataset.mode === 'edit' ? 'edit' : 'save');

            const updateStepSavedHint = (cardEl, hintRole, visible) => {
                if (!cardEl) {
                    return;
                }

                const hintEl = cardEl.querySelector(`[data-role="${hintRole}"]`);
                if (!hintEl) {
                    return;
                }

                hintEl.hidden = !visible;
                if (visible) {
                    hintEl.textContent = getSavedHintText();
                }
            };

            const getCardCustomUnitText = (cardEl) => {
                const customUnitInput = cardEl ? cardEl.querySelector('[data-role="unit-other"]') : null;
                return customUnitInput ? String(customUnitInput.value || '').trim() : '';
            };

            const resolveCardUnitText = (cardEl) => {
                if (!cardEl) {
                    return '';
                }

                const unitSelect = cardEl.querySelector('[data-role="unit"]');
                const selected = unitSelect ? String(unitSelect.value || '').trim() : '';
                if (selected === CUSTOM_UNIT_VALUE) {
                    return getCardCustomUnitText(cardEl);
                }

                return selected !== '' ? getUnitLabelById(selected) : '';
            };

            const resolveCardSubmitUnitId = (cardEl) => {
                if (!cardEl) {
                    return '';
                }

                const unitSelect = cardEl.querySelector('[data-role="unit"]');
                const selected = unitSelect ? String(unitSelect.value || '').trim() : '';
                if (selected === CUSTOM_UNIT_VALUE) {
                    const lastStandard = unitSelect ? String(unitSelect.dataset.lastStandardUnit || '').trim() : '';
                    return lastStandard !== '' ? lastStandard : (defaultUnitId !== '' ? defaultUnitId : '');
                }

                return selected;
            };

            const renderCustomUnitInput = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const unitSelect = cardEl.querySelector('[data-role="unit"]');
                const customUnitInput = cardEl.querySelector('[data-role="unit-other"]');
                if (!unitSelect || !customUnitInput) {
                    return;
                }

                const isCustomSelected = String(unitSelect.value || '').trim() === CUSTOM_UNIT_VALUE;
                customUnitInput.placeholder = getOtherUnitPlaceholderText();
                customUnitInput.style.display = isCustomSelected ? 'block' : 'none';
                customUnitInput.disabled = !isCustomSelected;
            };

            const renderUnitOptions = (unitSelect) => {
                if (!unitSelect) {
                    return;
                }

                const selected = String(unitSelect.value || '').trim();
                unitSelect.innerHTML = '';

                if (Array.isArray(unitOptions)) {
                    unitOptions.forEach((unit) => {
                        const unitId = Number(unit && unit.id ? unit.id : 0);
                        if (!Number.isFinite(unitId) || unitId < 1) {
                            return;
                        }

                        const option = document.createElement('option');
                        option.value = String(unitId);
                        option.textContent = formatUnitOptionLabel(unit);
                        unitSelect.appendChild(option);
                    });
                }

                const customOption = document.createElement('option');
                customOption.value = CUSTOM_UNIT_VALUE;
                customOption.textContent = getOtherUnitOptionText();
                unitSelect.appendChild(customOption);

                const hasSelected = [...unitSelect.options].some((option) => option.value === selected);
                unitSelect.value = hasSelected
                    ? selected
                    : (defaultUnitId !== '' ? defaultUnitId : '');
                if (unitSelect.value !== CUSTOM_UNIT_VALUE && String(unitSelect.value || '').trim() !== '') {
                    unitSelect.dataset.lastStandardUnit = String(unitSelect.value || '').trim();
                }
            };

            const renderMonthUnitLabels = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const unitText = resolveCardUnitText(cardEl);
                cardEl.querySelectorAll('[data-role="month-unit-text"]').forEach((unitLabel) => {
                    unitLabel.textContent = unitText;
                });
            };

            const renderCriteriaOptions = (criteriaSelect) => {
                if (!criteriaSelect) {
                    return;
                }

                const selected = criteriaSelect.value || '<=';
                criteriaSelect.innerHTML = '';
                criteriaOptions.forEach((optionItem) => {
                    const option = document.createElement('option');
                    option.value = optionItem.value;
                    option.textContent = getText(optionItem.textKey);
                    criteriaSelect.appendChild(option);
                });
                criteriaSelect.value = criteriaOptions.some((optionItem) => optionItem.value === selected) ? selected : '<=';
            };

            const getCardSelectedTargetDepartments = (cardEl) => {
                if (!cardEl) {
                    return [];
                }

                const targetDepartmentsSelect = cardEl.querySelector('[data-role="target-departments"]');
                if (!targetDepartmentsSelect) {
                    return [];
                }

                return normalizeDepartmentCodeList(
                    [...targetDepartmentsSelect.selectedOptions].map((option) => option.value)
                );
            };

            const setCardTargetDepartmentsPanelOpen = (cardEl, isOpen) => {
                if (!cardEl) {
                    return;
                }

                const targetDepartmentsSummary = cardEl.querySelector('[data-role="target-departments-summary"]');
                const targetDepartmentsOptions = cardEl.querySelector('[data-role="target-departments-options"]');
                if (!targetDepartmentsSummary || !targetDepartmentsOptions) {
                    return;
                }

                const open = !!isOpen;
                targetDepartmentsOptions.hidden = !open;
                targetDepartmentsSummary.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            const refreshCardTargetDepartmentsSummary = (cardEl) => {
                if (!cardEl) {
                    return;
                }

                const targetDepartmentsSummary = cardEl.querySelector('[data-role="target-departments-summary"]');
                if (!targetDepartmentsSummary) {
                    return;
                }

                const selectedDepartments = getCardSelectedTargetDepartments(cardEl);
                targetDepartmentsSummary.textContent = selectedDepartments.length > 0
                    ? selectedDepartments.join(', ')
                    : getText('kpiSelectPlaceholder');
            };

            const renderCardTargetDepartmentSelection = (cardEl, preferredDepartments = null) => {
                if (!cardEl) {
                    return;
                }

                const targetDepartmentsWrap = cardEl.querySelector('[data-role="target-departments-wrap"]');
                const targetDepartmentsLabel = cardEl.querySelector('[data-role="label-target-departments"]');
                const targetDepartmentsSelect = cardEl.querySelector('[data-role="target-departments"]');
                const targetDepartmentsOptions = cardEl.querySelector('[data-role="target-departments-options"]');
                const targetDepartmentsSummary = cardEl.querySelector('[data-role="target-departments-summary"]');

                if (targetDepartmentsLabel) {
                    targetDepartmentsLabel.textContent = getText('kpiLabelTargetDepartments');
                }
                if (!targetDepartmentsWrap || !targetDepartmentsSelect) {
                    return;
                }

                if (!canSelectTargetDepartments) {
                    targetDepartmentsWrap.hidden = true;
                    targetDepartmentsSelect.innerHTML = '';
                    targetDepartmentsSelect.disabled = true;
                    if (targetDepartmentsOptions) {
                        targetDepartmentsOptions.innerHTML = '';
                        targetDepartmentsOptions.hidden = true;
                    }
                    if (targetDepartmentsSummary) {
                        targetDepartmentsSummary.textContent = getText('kpiSelectPlaceholder');
                        targetDepartmentsSummary.setAttribute('aria-expanded', 'false');
                    }
                    return;
                }

                targetDepartmentsWrap.hidden = false;
                targetDepartmentsSelect.disabled = false;

                const selectedFromArgs = Array.isArray(preferredDepartments)
                    ? preferredDepartments
                    : [...targetDepartmentsSelect.selectedOptions].map((option) => option.value);
                const selectedDepartments = normalizeDepartmentCodeList(selectedFromArgs);
                let selectedDepartment = selectedDepartments[0] || '';

                const options = [...targetDepartmentOptions];
                if (selectedDepartment !== '' && !options.includes(selectedDepartment)) {
                    options.push(selectedDepartment);
                }

                if (options.length < 1) {
                    const fallbackOwnDept = getViewerDepartmentFallback();
                    if (fallbackOwnDept !== '') {
                        options.push(fallbackOwnDept);
                    }
                }

                if (selectedDepartment === '') {
                    const defaultDepartment = normalizeDepartmentCode(defaultTargetDepartments[0] || '');
                    if (defaultDepartment !== '' && options.includes(defaultDepartment)) {
                        selectedDepartment = defaultDepartment;
                    } else if (options.length > 0) {
                        selectedDepartment = options[0];
                    }
                }

                options.sort((a, b) => a.localeCompare(b));
                targetDepartmentsSelect.innerHTML = '';
                options.forEach((code) => {
                    const optionEl = document.createElement('option');
                    optionEl.value = code;
                    optionEl.textContent = code;
                    targetDepartmentsSelect.appendChild(optionEl);
                });

                [...targetDepartmentsSelect.options].forEach((optionEl) => {
                    optionEl.selected = normalizeDepartmentCode(optionEl.value) === selectedDepartment;
                });

                if (targetDepartmentsOptions) {
                    targetDepartmentsOptions.innerHTML = '';
                    options.forEach((code) => {
                        const isSelected = code === selectedDepartment;
                        const optionBtn = document.createElement('button');
                        optionBtn.type = 'button';
                        optionBtn.className = `kpi-department-picker-option${isSelected ? ' is-selected' : ''}`;
                        optionBtn.setAttribute('data-role', 'target-department-option');
                        optionBtn.dataset.value = code;
                        optionBtn.textContent = isSelected ? `✓ ${code}` : code;
                        targetDepartmentsOptions.appendChild(optionBtn);
                    });
                }

                setCardTargetDepartmentsPanelOpen(cardEl, false);
                refreshCardTargetDepartmentsSummary(cardEl);
            };

            const cleanupCardEvidencePreviews = (cardEl) => {
                cardEl.querySelectorAll('[data-role="month-item"]').forEach((monthItem) => {
                    if (Array.isArray(monthItem._filePreviewUrls)) {
                        monthItem._filePreviewUrls.forEach((url) => URL.revokeObjectURL(url));
                        monthItem._filePreviewUrls = [];
                    }
                    if (Array.isArray(monthItem._actionPlanPreviewUrls)) {
                        monthItem._actionPlanPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
                        monthItem._actionPlanPreviewUrls = [];
                    }
                });
            };

            const applyCardTexts = (cardEl, index) => {
                const titleEl = cardEl.querySelector('[data-role="item-title"]');
                if (titleEl) {
                    titleEl.textContent = `${getText('kpiCardTitle')} #${index}`;
                }

                renderCardTargetDepartmentSelection(cardEl);

                const cardModeLabel = cardEl.querySelector('[data-role="label-card-mode"]');
                if (cardModeLabel) {
                    cardModeLabel.textContent = getText('kpiLabelCardMode');
                }
                const cardModeReportBtn = cardEl.querySelector('[data-role="card-mode-report"]');
                if (cardModeReportBtn) {
                    cardModeReportBtn.textContent = getText('kpiCardModeReport');
                }
                const cardModeTargetBtn = cardEl.querySelector('[data-role="card-mode-target"]');
                if (cardModeTargetBtn) {
                    cardModeTargetBtn.textContent = getText('kpiCardModeTarget');
                }
                syncCardModePicker(cardEl);
                syncCardModeSections(cardEl);

                const objectiveLabel = cardEl.querySelector('[data-role="label-objective"]');
                if (objectiveLabel) {
                    objectiveLabel.textContent = getText('kpiLabelObjective');
                }

                const detailLabel = cardEl.querySelector('[data-role="label-detail"]');
                if (detailLabel) {
                    detailLabel.textContent = getText('kpiLabelDetail');
                }

                const levelThreeLabel = cardEl.querySelector('[data-role="label-okr-level-three"]');
                if (levelThreeLabel) {
                    levelThreeLabel.textContent = getText('kpiLabelReportLevelThree');
                }

                const removeBtn = cardEl.querySelector('[data-role="remove-item"]');
                if (removeBtn) {
                    removeBtn.textContent = getText('kpiBtnRemoveCard');
                }

                const editBtn = cardEl.querySelector('[data-role="edit-card"]');
                if (editBtn) {
                    editBtn.textContent = getText('kpiBtnEditCard');
                }

                const nextToTargetBtn = cardEl.querySelector('[data-role="open-target"]');
                if (nextToTargetBtn) {
                    setStepActionMode(nextToTargetBtn, getStepActionMode(nextToTargetBtn));
                }

                const targetSavedHint = cardEl.querySelector('[data-role="open-target-saved-hint"]');
                if (targetSavedHint && !targetSavedHint.hidden) {
                    targetSavedHint.textContent = getSavedHintText();
                }

                const targetLabel = cardEl.querySelector('[data-role="label-target"]');
                if (targetLabel) {
                    targetLabel.textContent = getText('kpiLabelTarget');
                }

                const targetInput = cardEl.querySelector('[data-role="target"]');
                if (targetInput) {
                    targetInput.placeholder = getText('kpiTargetPlaceholder');
                }

                const unitLabel = cardEl.querySelector('[data-role="label-unit"]');
                if (unitLabel) {
                    unitLabel.textContent = getText('kpiLabelUnit');
                }

                const unitSelect = cardEl.querySelector('[data-role="unit"]');
                renderUnitOptions(unitSelect);
                renderCustomUnitInput(cardEl);
                renderMonthUnitLabels(cardEl);

                const criteriaSectionLabel = cardEl.querySelector('[data-role="label-criteria-section"]');
                if (criteriaSectionLabel) {
                    criteriaSectionLabel.textContent = getText('kpiCriteriaSectionTitle');
                }

                const criteriaChoiceQuestion = cardEl.querySelector('[data-role="criteria-choice-question"]');
                if (criteriaChoiceQuestion) {
                    criteriaChoiceQuestion.textContent = getText('kpiHasCriteriaQuestion');
                }
                const criteriaChoiceYes = cardEl.querySelector('[data-role="criteria-choice-yes"]');
                if (criteriaChoiceYes) {
                    criteriaChoiceYes.textContent = getText('kpiHasCriteriaYes');
                }
                const criteriaChoiceNo = cardEl.querySelector('[data-role="criteria-choice-no"]');
                if (criteriaChoiceNo) {
                    criteriaChoiceNo.textContent = getText('kpiHasCriteriaNo');
                }

                const criteriaLabel = cardEl.querySelector('[data-role="label-criteria"]');
                if (criteriaLabel) {
                    criteriaLabel.textContent = getText('kpiLabelCriteria');
                }

                const criteriaSelect = cardEl.querySelector('[data-role="criteria"]');
                renderCriteriaOptions(criteriaSelect);

                const nextToScoreBtn = cardEl.querySelector('[data-role="open-score"]');
                if (nextToScoreBtn) {
                    setStepActionMode(nextToScoreBtn, getStepActionMode(nextToScoreBtn));
                }

                const scoreSavedHint = cardEl.querySelector('[data-role="open-score-saved-hint"]');
                if (scoreSavedHint && !scoreSavedHint.hidden) {
                    scoreSavedHint.textContent = getSavedHintText();
                }

                const scoreTitle = cardEl.querySelector('[data-role="label-score-title"]');
                if (scoreTitle) {
                    scoreTitle.textContent = getText('kpiScoreTitle');
                }

                const resultLabel = cardEl.querySelector('[data-role="label-result"]');
                if (resultLabel) {
                    resultLabel.textContent = resolveCardResultLabelText(cardEl);
                }

                const resultInput = cardEl.querySelector('[data-role="result"]');
                if (resultInput && !resultInput.dataset.confirmed) {
                    resultInput.textContent = resolveCardPendingResultText(cardEl);
                }

                const confirmBtn = cardEl.querySelector('[data-role="confirm-result"]');
                if (confirmBtn) {
                    confirmBtn.textContent = getText('kpiBtnConfirmResult');
                }

                const hideBtn = cardEl.querySelector('[data-role="hide-card"]');
                if (hideBtn) {
                    hideBtn.textContent = getText('kpiBtnHideCard');
                }

                const summaryTitle = cardEl.querySelector('[data-role="summary-title"]');
                if (summaryTitle) {
                    summaryTitle.textContent = getText('kpiSummaryTitle');
                }

                const summaryColMonth = cardEl.querySelector('[data-role="summary-col-month"]');
                if (summaryColMonth) {
                    summaryColMonth.textContent = getText('kpiSummaryColMonth');
                }

                const summaryColScore = cardEl.querySelector('[data-role="summary-col-score"]');
                if (summaryColScore) {
                    summaryColScore.textContent = getText('kpiSummaryColScore');
                }

                const summaryColCriteria = cardEl.querySelector('[data-role="summary-col-criteria"]');
                if (summaryColCriteria) {
                    summaryColCriteria.textContent = getText('kpiSummaryColCriteria');
                }

                const summaryColResult = cardEl.querySelector('[data-role="summary-col-result"]');
                if (summaryColResult) {
                    summaryColResult.textContent = getText('kpiSummaryColResult');
                }

                cardEl.querySelectorAll('[data-role="month-label"]').forEach((monthLabel) => {
                    const monthIndex = Number(monthLabel.dataset.monthIndex || 0);
                    if (monthIndex >= 1 && monthIndex <= 12) {
                        monthLabel.textContent = getText(monthKeys[monthIndex - 1]);
                    }
                });

                cardEl.querySelectorAll('[data-role="month-status-label"]').forEach((statusLabel) => {
                    statusLabel.textContent = getText('kpiMonthStatusLabel');
                });
                cardEl.querySelectorAll('[data-role="month-criteria-label"]').forEach((criteriaLabel) => {
                    criteriaLabel.textContent = getText('kpiLabelCriteria');
                });

                cardEl.querySelectorAll('[data-role="month-cycle-label"]').forEach((cycleLabel) => {
                    cycleLabel.textContent = getText('kpiMonthCycleOpenLabel');
                });

                cardEl.querySelectorAll('[data-role="month-cycle-date"]').forEach((cycleDate) => {
                    if (!String(cycleDate.textContent || '').trim() || cycleDate.dataset.autoLabel === '1') {
                        cycleDate.textContent = getText('kpiMonthCycleOpenPending');
                        cycleDate.dataset.autoLabel = '1';
                    }
                });

                cardEl.querySelectorAll('[data-role="month-score-label"]').forEach((scoreLabel) => {
                    scoreLabel.textContent = getText('kpiMonthScoreLabel');
                });

                cardEl.querySelectorAll('[data-role="month-evidence-label"]').forEach((evidenceLabel) => {
                    evidenceLabel.textContent = getText('kpiMonthEvidenceLabel');
                });

                cardEl.querySelectorAll('[data-role="month-action-plan-label"]').forEach((actionPlanLabel) => {
                    actionPlanLabel.textContent = getText('kpiMonthActionPlanLabel');
                });

                cardEl.querySelectorAll('[data-role="month-item"]').forEach((monthItem) => {
                    setMonthActionMode(monthItem, getMonthActionMode(monthItem));
                    renderMonthStatus(monthItem);
                    renderEvidenceSelection(monthItem);
                    renderActionPlanSelection(monthItem);
                    updateActionPlanVisibility(cardEl, monthItem);
                });

                renderCardSummary(cardEl);
            };

            const ensureCardOrder = () => {
                const cards = [...listEl.querySelectorAll('.kpi-card')];
                const reportIndexByDepartment = {};
                let targetIndex = 0;
                const nextReportIndex = (departmentCode = '') => {
                    const key = normalizeDepartmentCode(departmentCode || '') || '__report__';
                    reportIndexByDepartment[key] = (reportIndexByDepartment[key] || 0) + 1;
                    return reportIndexByDepartment[key];
                };

                cards.forEach((cardEl) => {
                    const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                    if (modeType === 'target') {
                        targetIndex += 1;
                        applyCardTexts(cardEl, targetIndex);
                        return;
                    }

                    const reportDepartment = resolveCardReportDepartment(cardEl);
                    applyCardTexts(cardEl, nextReportIndex(reportDepartment));
                });

                const editorCards = [...editorListEl.querySelectorAll('.kpi-card')];
                editorCards.forEach((cardEl) => {
                    const modeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                    const reportDepartment = resolveCardReportDepartment(cardEl);
                    const marker = cardEl._restoreMarker;
                    if (marker && marker.parentElement === listEl) {
                        let markerCount = 0;
                        const listChildren = [...listEl.children];
                        for (let i = 0; i < listChildren.length; i += 1) {
                            const node = listChildren[i];
                            if (node === marker) {
                                break;
                            }
                            if (
                                node &&
                                node.classList &&
                                node.classList.contains('kpi-card') &&
                                resolveAllowedCardModeType(node.dataset.modeType || 'report') === modeType &&
                                (
                                    modeType !== 'report' ||
                                    resolveCardReportDepartment(node) === reportDepartment
                                )
                            ) {
                                markerCount += 1;
                            }
                        }
                        const markerIndex = markerCount + 1;
                        applyCardTexts(cardEl, markerIndex);
                        return;
                    }

                    if (modeType === 'target') {
                        targetIndex += 1;
                        applyCardTexts(cardEl, targetIndex);
                        return;
                    }

                    applyCardTexts(cardEl, nextReportIndex(reportDepartment));
                });
            };

            const buildMonthInputs = (cardEl, uid, presetMonths = null) => {
                const monthGrid = cardEl.querySelector('[data-role="month-grid"]');
                if (!monthGrid) {
                    return;
                }

                monthGrid.innerHTML = '';
                monthKeys.forEach((monthKey, i) => {
                    const monthIdx = i + 1;
                    const monthConfig = resolveMonthConfig(monthIdx);
                    const monthIsOpen = isMonthOpen(monthIdx);
                    const presetMonth = presetMonths && typeof presetMonths === 'object'
                        ? (presetMonths[String(monthIdx)] || presetMonths[monthIdx] || null)
                        : null;
                    const monthOpenDateText = monthConfig && monthConfig.open_at
                        ? (formatDateDmy(monthConfig.open_at) || String(monthConfig.open_at))
                        : getText('kpiMonthCycleOpenPending');

                    const box = document.createElement('div');
                    box.className = 'kpi-month-item';

                    const label = document.createElement('label');
                    label.className = 'kpi-month-label';
                    label.setAttribute('data-role', 'month-label');
                    label.dataset.monthIndex = String(monthIdx);
                    label.setAttribute('for', `kpi-${uid}-month-${monthIdx}-score`);
                    label.textContent = getText(monthKey);

                    const scoreField = document.createElement('div');
                    scoreField.className = 'kpi-month-field';
                    const scoreFieldLabel = document.createElement('label');
                    scoreFieldLabel.className = 'kpi-month-field-label';
                    scoreFieldLabel.setAttribute('data-role', 'month-score-label');
                    scoreFieldLabel.setAttribute('for', `kpi-${uid}-month-${monthIdx}-score`);
                    scoreFieldLabel.textContent = getText('kpiMonthScoreLabel');
                    const scoreInput = document.createElement('input');
                    scoreInput.className = 'kpi-input kpi-score-input';
                    scoreInput.type = 'number';
                    scoreInput.step = 'any';
                    scoreInput.id = `kpi-${uid}-month-${monthIdx}-score`;
                    scoreInput.name = `kpi_cards[${uid}][months][${monthIdx}][score]`;
                    scoreInput.setAttribute('data-role', 'month-input');
                    const scoreInputRow = document.createElement('div');
                    scoreInputRow.className = 'kpi-score-input-row';
                    const scoreUnit = document.createElement('span');
                    scoreUnit.className = 'kpi-score-unit';
                    scoreUnit.setAttribute('data-role', 'month-unit-text');
                    scoreInputRow.appendChild(scoreInput);
                    scoreInputRow.appendChild(scoreUnit);
                    scoreField.appendChild(scoreFieldLabel);
                    scoreField.appendChild(scoreInputRow);

                    const evidenceField = document.createElement('div');
                    evidenceField.className = 'kpi-month-field';
                    evidenceField.setAttribute('data-role', 'month-evidence-field');
                    const evidenceLabel = document.createElement('label');
                    evidenceLabel.className = 'kpi-month-field-label';
                    evidenceLabel.setAttribute('data-role', 'month-evidence-label');
                    evidenceLabel.setAttribute('for', `kpi-${uid}-month-${monthIdx}-evidence`);
                    evidenceLabel.textContent = getText('kpiMonthEvidenceLabel');
                    const evidenceInput = document.createElement('input');
                    evidenceInput.className = 'kpi-file-input';
                    evidenceInput.type = 'file';
                    evidenceInput.id = `kpi-${uid}-month-${monthIdx}-evidence`;
                    evidenceInput.name = `kpi_cards[${uid}][months][${monthIdx}][evidence_files][]`;
                    evidenceInput.multiple = true;
                    evidenceInput.accept = '.doc,.docx,.pdf,.png,.jpg,.jpeg,.heic,.heif,.webp,.gif,.bmp,.xls,.xlsx';
                    evidenceInput.setAttribute('data-role', 'month-evidence-input');
                    const evidenceSelected = document.createElement('p');
                    evidenceSelected.className = 'kpi-file-selected';
                    evidenceSelected.setAttribute('data-role', 'month-evidence-selected');
                    evidenceSelected.textContent = getText('kpiMonthEvidenceNotSelected');
                    const evidenceFiles = document.createElement('ul');
                    evidenceFiles.className = 'kpi-file-list';
                    evidenceFiles.setAttribute('data-role', 'month-evidence-files');
                    evidenceField.appendChild(evidenceLabel);
                    evidenceField.appendChild(evidenceInput);
                    evidenceField.appendChild(evidenceSelected);
                    evidenceField.appendChild(evidenceFiles);
                    evidenceField.classList.add('kpi-month-evidence-row');

                    const actionPlanField = document.createElement('div');
                    actionPlanField.className = 'kpi-month-field kpi-month-action-plan-row';
                    actionPlanField.setAttribute('data-role', 'month-action-plan-field');
                    actionPlanField.hidden = true;
                    const actionPlanLabel = document.createElement('label');
                    actionPlanLabel.className = 'kpi-month-field-label';
                    actionPlanLabel.setAttribute('data-role', 'month-action-plan-label');
                    actionPlanLabel.setAttribute('for', `kpi-${uid}-month-${monthIdx}-action-plan`);
                    actionPlanLabel.textContent = getText('kpiMonthActionPlanLabel');
                    const actionPlanInput = document.createElement('input');
                    actionPlanInput.className = 'kpi-file-input';
                    actionPlanInput.type = 'file';
                    actionPlanInput.id = `kpi-${uid}-month-${monthIdx}-action-plan`;
                    actionPlanInput.name = `kpi_cards[${uid}][months][${monthIdx}][action_plan_files][]`;
                    actionPlanInput.multiple = true;
                    actionPlanInput.accept = '.doc,.docx,.pdf,.png,.jpg,.jpeg,.heic,.heif,.webp,.gif,.bmp,.xls,.xlsx';
                    actionPlanInput.setAttribute('data-role', 'month-action-plan-input');
                    const actionPlanSelected = document.createElement('p');
                    actionPlanSelected.className = 'kpi-file-selected';
                    actionPlanSelected.setAttribute('data-role', 'month-action-plan-selected');
                    actionPlanSelected.textContent = getText('kpiMonthActionPlanNotSelected');
                    const actionPlanFiles = document.createElement('ul');
                    actionPlanFiles.className = 'kpi-file-list';
                    actionPlanFiles.setAttribute('data-role', 'month-action-plan-files');
                    actionPlanField.appendChild(actionPlanLabel);
                    actionPlanField.appendChild(actionPlanInput);
                    actionPlanField.appendChild(actionPlanSelected);
                    actionPlanField.appendChild(actionPlanFiles);

                    const reviewToggle = document.createElement('button');
                    reviewToggle.type = 'button';
                    reviewToggle.className = 'kpi-month-review-toggle';
                    reviewToggle.setAttribute('data-role', 'month-review-toggle');
                    reviewToggle.hidden = true;
                    reviewToggle.textContent = '';

                    const reviewNote = document.createElement('p');
                    reviewNote.className = 'kpi-month-review-note';
                    reviewNote.setAttribute('data-role', 'month-review-note');
                    reviewNote.hidden = true;

                    const saveBtn = document.createElement('button');
                    saveBtn.type = 'button';
                    saveBtn.className = 'kpi-month-save-btn';
                    saveBtn.setAttribute('data-role', 'month-save-btn');
                    saveBtn.textContent = getText('kpiMonthSaveBtn');

                    const monthHead = document.createElement('div');
                    monthHead.className = 'kpi-month-head';
                    monthHead.appendChild(label);
                    monthHead.appendChild(saveBtn);

                    const status = document.createElement('p');
                    status.className = 'kpi-month-status';
                    status.setAttribute('data-role', 'month-status');
                    status.textContent = getText('kpiMonthNotSaved');

                    const criteriaStatus = document.createElement('p');
                    criteriaStatus.className = 'kpi-month-criteria';
                    criteriaStatus.setAttribute('data-role', 'month-criteria-status');
                    criteriaStatus.textContent = '-';

                    const criteriaLabel = document.createElement('span');
                    criteriaLabel.className = 'kpi-month-meta-label';
                    criteriaLabel.setAttribute('data-role', 'month-criteria-label');
                    criteriaLabel.textContent = getText('kpiLabelCriteria');

                    const criteriaGroup = document.createElement('div');
                    criteriaGroup.className = 'kpi-month-meta-group';
                    criteriaGroup.appendChild(criteriaLabel);
                    criteriaGroup.appendChild(criteriaStatus);

                    const statusLabel = document.createElement('span');
                    statusLabel.className = 'kpi-month-meta-label';
                    statusLabel.setAttribute('data-role', 'month-status-label');
                    statusLabel.textContent = getText('kpiMonthStatusLabel');

                    const statusGroup = document.createElement('div');
                    statusGroup.className = 'kpi-month-meta-group';
                    statusGroup.appendChild(statusLabel);
                    statusGroup.appendChild(status);

                    const cycleLabel = document.createElement('span');
                    cycleLabel.className = 'kpi-month-meta-label';
                    cycleLabel.setAttribute('data-role', 'month-cycle-label');
                    cycleLabel.textContent = getText('kpiMonthCycleOpenLabel');

                    const cycleDate = document.createElement('p');
                    cycleDate.className = 'kpi-month-cycle-date';
                    cycleDate.setAttribute('data-role', 'month-cycle-date');
                    cycleDate.dataset.autoLabel = '0';
                    cycleDate.textContent = monthOpenDateText;

                    const cycleGroup = document.createElement('div');
                    cycleGroup.className = 'kpi-month-meta-group';
                    cycleGroup.appendChild(cycleLabel);
                    cycleGroup.appendChild(cycleDate);

                    const monthMeta = document.createElement('div');
                    monthMeta.className = 'kpi-month-meta';
                    monthMeta.appendChild(criteriaGroup);
                    monthMeta.appendChild(statusGroup);
                    monthMeta.appendChild(cycleGroup);

                    box.setAttribute('data-role', 'month-item');
                    box.dataset.monthNo = String(monthIdx);
                    box.dataset.scoreId = '';
                    box.dataset.saved = '0';
                    box.dataset.passed = '';
                    box.dataset.fileCount = '0';
                    box.dataset.actionPlanCount = '0';
                    box.dataset.locked = monthIsOpen ? '0' : '1';
                    box.dataset.reviewStatus = 'pending';
                    box.dataset.reviewRejectDetail = '';
                    box.dataset.reviewedAt = '';
                    box.appendChild(monthHead);
                    box.appendChild(monthMeta);
                    box.appendChild(scoreField);
                    box.appendChild(evidenceField);
                    box.appendChild(actionPlanField);
                    box.appendChild(reviewToggle);
                    box.appendChild(reviewNote);
                    monthGrid.appendChild(box);

                    reviewToggle.addEventListener('click', () => {
                        const reviewStatus = normalizeMonthReviewStatus(box.dataset.reviewStatus);
                        const rejectDetail = String(box.dataset.reviewRejectDetail || '').trim();
                        if (reviewStatus !== 'rejected' || rejectDetail === '') {
                            return;
                        }
                        openRejectDetailModal(rejectDetail);
                    });

                    if (presetMonth && typeof presetMonth === 'object') {
                        const presetScoreId = Number(presetMonth.score_id || 0);
                        if (Number.isFinite(presetScoreId) && presetScoreId > 0) {
                            box.dataset.scoreId = String(presetScoreId);
                        }
                        const scoreValue = parseNumeric(presetMonth.score);
                        if (!Number.isNaN(scoreValue)) {
                            scoreInput.value = String(scoreValue);
                            box.dataset.saved = '1';
                            box.dataset.passed = presetMonth.is_pass ? '1' : '0';
                        }
                        box.dataset.reviewStatus = normalizeMonthReviewStatus(presetMonth.review_status);
                        box.dataset.reviewRejectDetail = String(presetMonth.review_reject_detail || '').trim();
                        box.dataset.reviewedAt = String(presetMonth.reviewed_at || '').trim();

                        const persistedFiles = Array.isArray(presetMonth.evidence_files)
                            ? presetMonth.evidence_files
                            : [];
                        box._persistedFiles = persistedFiles;

                        const evidenceCount = Number(presetMonth.evidence_count || 0);
                        if (Number.isFinite(evidenceCount) && evidenceCount > 0) {
                            box.dataset.fileCount = String(evidenceCount);
                        } else if (persistedFiles.length > 0) {
                            box.dataset.fileCount = String(persistedFiles.length);
                        }

                        const persistedActionPlanFiles = Array.isArray(presetMonth.action_plan_files)
                            ? presetMonth.action_plan_files
                            : [];
                        box._persistedActionPlanFiles = persistedActionPlanFiles;

                        const actionPlanCount = Number(presetMonth.action_plan_count || 0);
                        if (Number.isFinite(actionPlanCount) && actionPlanCount > 0) {
                            box.dataset.actionPlanCount = String(actionPlanCount);
                        } else if (persistedActionPlanFiles.length > 0) {
                            box.dataset.actionPlanCount = String(persistedActionPlanFiles.length);
                        }
                    }

                    const presetActionPlanCount = Number(box.dataset.actionPlanCount || 0);
                    const hasPresetActionPlan = Number.isFinite(presetActionPlanCount) && presetActionPlanCount > 0;
                    const monthMode = (
                        box.dataset.saved === '1' &&
                        (box.dataset.passed === '1' || hasPresetActionPlan)
                    )
                        ? 'edit'
                        : 'save';
                    setMonthActionMode(box, monthMode);

                    if (!monthIsOpen) {
                        box.classList.add('is-locked');
                        scoreInput.disabled = true;
                        evidenceInput.disabled = true;
                        actionPlanInput.disabled = true;
                        saveBtn.disabled = true;
                    }
                    renderMonthStatus(box);
                    renderEvidenceSelection(box);
                    renderActionPlanSelection(box);
                    updateActionPlanVisibility(cardEl, box);

                    scoreInput.addEventListener('input', () => {
                        markMonthDraft(box);
                        setCardDraft(cardEl);
                        renderCardSummary(cardEl);
                        updateActionPlanVisibility(cardEl, box);
                    });

                    evidenceInput.addEventListener('change', () => {
                        markMonthDraft(box);
                        setCardDraft(cardEl);
                        renderEvidenceSelection(box, evidenceInput.files ? evidenceInput.files.length : 0);
                        renderCardSummary(cardEl);
                    });

                    actionPlanInput.addEventListener('change', () => {
                        markMonthDraft(box);
                        setCardDraft(cardEl);
                        renderActionPlanSelection(box, actionPlanInput.files ? actionPlanInput.files.length : 0);
                        renderCardSummary(cardEl);
                    });

                    saveBtn.addEventListener('click', () => {
                        if (box.dataset.locked === '1') {
                            showAlert('kpiAlertMonthLocked');
                            return;
                        }
                        if (isMonthApprovedAndSaved(box)) {
                            const approvedLockedMessage = resolveActiveLang() === 'th'
                                ? 'เดือนนี้อนุมัติแล้ว ไม่สามารถแก้ไขได้'
                                : 'This month is already approved and cannot be edited.';
                            showRawAlert(approvedLockedMessage, 'info');
                            return;
                        }

                        if (!cycleContext || !cycleContext.has_active_cycle || !cycleContext.cycle_id) {
                            showAlert('kpiAlertNeedActiveCycle');
                            return;
                        }

                        const itemId = Number(cardEl.dataset.itemId || 0);
                        if (itemId < 1) {
                            showAlert('kpiAlertNeedStepTwo');
                            return;
                        }

                        if (saveMonthUrl === '#' || !csrfToken) {
                            showAlert('kpiAlertMonthSaveFailed');
                            return;
                        }

                        if (getMonthActionMode(box) === 'edit') {
                            setMonthActionMode(box, 'save');
                            return;
                        }

                        showConfirm('confirmSaveMonthData', () => {
                            const scoreValue = parseNumeric(scoreInput.value);

                            if (String(scoreInput.value).trim() === '' || Number.isNaN(scoreValue)) {
                                showAlert('kpiAlertNeedMonthScore');
                                return;
                            }

                            const shouldRequireEvidence = !canSkipEvidenceUpload;

                            const selectedEvidenceCount = evidenceInput.files ? evidenceInput.files.length : 0;
                            const persistedEvidenceCount = Array.isArray(box._persistedFiles)
                                ? box._persistedFiles.length
                                : Number(box.dataset.fileCount || 0);
                            if (
                                shouldRequireEvidence &&
                                selectedEvidenceCount < 1 &&
                                (!Number.isFinite(persistedEvidenceCount) || persistedEvidenceCount < 1)
                            ) {
                                showAlert('kpiAlertNeedMonthEvidence');
                                return;
                            }

                            const payload = new FormData();
                            payload.append('_token', csrfToken);
                            payload.append('item_id', String(itemId));
                            payload.append('month_no', String(monthIdx));
                            payload.append('mode_type', resolveAllowedCardModeType(cardEl.dataset.modeType || 'report'));
                            payload.append('score', String(scoreValue));
                            [...(evidenceInput.files || [])].forEach((file) => {
                                payload.append('evidence_files[]', file);
                            });
                            [...(actionPlanInput.files || [])].forEach((file) => {
                                payload.append('action_plan_files[]', file);
                            });

                            saveBtn.disabled = true;
                            saveBtn.textContent = getText('kpiSaving');

                            (async () => {
                                try {
                                    const data = await postForm(saveMonthUrl, payload, 'kpiAlertMonthSaveFailed');
                                    const pass = data.score && typeof data.score.is_pass !== 'undefined'
                                        ? Boolean(data.score.is_pass)
                                        : false;
                                    if (data.score && Number(data.score.item_id || 0) > 0) {
                                        const scoreItemId = Number(data.score.item_id);
                                        cardEl.dataset.itemId = String(scoreItemId);
                                        setStoredCardModeType(scoreItemId, 'report');
                                    }
                                    if (data.score && Number(data.score.id || 0) > 0) {
                                        box.dataset.scoreId = String(Number(data.score.id));
                                    }
                                    const evidenceCount = data.score && typeof data.score.evidence_count !== 'undefined'
                                        ? Number(data.score.evidence_count || 0)
                                        : (evidenceInput.files ? evidenceInput.files.length : Number(box.dataset.fileCount || 0));
                                    const persistedFiles = data.score && Array.isArray(data.score.evidence_files)
                                        ? data.score.evidence_files
                                        : [];
                                    const actionPlanCount = data.score && typeof data.score.action_plan_count !== 'undefined'
                                        ? Number(data.score.action_plan_count || 0)
                                        : (actionPlanInput.files ? actionPlanInput.files.length : Number(box.dataset.actionPlanCount || 0));
                                    const persistedActionPlanFiles = data.score && Array.isArray(data.score.action_plan_files)
                                        ? data.score.action_plan_files
                                        : [];

                                    box.dataset.saved = '1';
                                    box.dataset.passed = pass ? '1' : '0';
                                    box.dataset.fileCount = String(evidenceCount);
                                    box.dataset.actionPlanCount = String(actionPlanCount);
                                    box.dataset.reviewStatus = normalizeMonthReviewStatus(data.score ? data.score.review_status : '');
                                    box.dataset.reviewRejectDetail = String(data.score ? (data.score.review_reject_detail || '') : '').trim();
                                    box.dataset.reviewedAt = String(data.score ? (data.score.reviewed_at || '') : '').trim();
                                    setCardModeType(cardEl, 'report', {
                                        persist: true,
                                        applyFilter: true,
                                    });
                                    ensureCardOrder();
                                    box._persistedFiles = persistedFiles;
                                    box._persistedActionPlanFiles = persistedActionPlanFiles;
                                    evidenceInput.value = '';
                                    actionPlanInput.value = '';
                                    renderMonthStatus(box);
                                    renderEvidenceSelection(box, evidenceCount);
                                    renderActionPlanSelection(box, actionPlanCount);
                                    updateActionPlanVisibility(cardEl, box);
                                    const persistedActionPlanCount = Array.isArray(box._persistedActionPlanFiles)
                                        ? box._persistedActionPlanFiles.length
                                        : Number(box.dataset.actionPlanCount || 0);
                                    const hasActionPlanAttached = Number.isFinite(persistedActionPlanCount)
                                        && persistedActionPlanCount > 0;
                                    const requiresActionPlan = box.dataset.actionPlanRequired === '1';
                                    const actionModeAfterSave = (
                                        pass ||
                                        (requiresActionPlan && hasActionPlanAttached)
                                    )
                                        ? 'edit'
                                        : 'save';
                                    setMonthActionMode(box, actionModeAfterSave);
                                    setCardDraft(cardEl);
                                    renderCardSummary(cardEl);
                                    const monthSavedShortMessage = resolveActiveLang() === 'th'
                                        ? 'บันทึกคะแนนเรียบร้อย'
                                        : 'Month score saved.';
                                    if (requiresActionPlan && !hasActionPlanAttached) {
                                        const failNeedActionPlanMessage = resolveActiveLang() === 'th'
                                            ? 'ไม่ผ่านเกณฑ์กรุณาแนบ Action Plan'
                                            : 'Not pass criteria. Please attach Action Plan.';
                                        showRawAlert(failNeedActionPlanMessage, 'info');
                                    } else {
                                        showRawAlert(monthSavedShortMessage, 'success');
                                    }
                                } catch (err) {
                                    showRawAlert(err && err.message ? err.message : getText('kpiAlertMonthSaveFailed'), 'error');
                                } finally {
                                    saveBtn.disabled = box.dataset.locked === '1';
                                    setMonthActionMode(box, getMonthActionMode(box));
                                }
                            })();
                        });
                    });
                });
            };

            const createCard = (initialData = null) => {
                cardSeq += 1;
                const uid = cardSeq;

                const cardEl = template.content.firstElementChild.cloneNode(true);
                cardEl.dataset.uid = String(uid);
                const preset = initialData && typeof initialData === 'object' ? initialData : null;
                const presetMonths = preset && typeof preset.months === 'object' ? preset.months : null;
                const presetTargetDepartments = preset && Array.isArray(preset.target_departments)
                    ? preset.target_departments
                    : null;
                const hasPresetSavedMonth = !!(
                    presetMonths &&
                    typeof presetMonths === 'object' &&
                    Object.values(presetMonths).some((monthValue) => {
                        if (!monthValue || typeof monthValue !== 'object') {
                            return false;
                        }
                        const scoreValue = parseNumeric(monthValue.score);
                        return Number.isFinite(scoreValue);
                    })
                );
                const presetItemId = preset && Number(preset.item_id || 0) > 0
                    ? Number(preset.item_id)
                    : 0;
                const storedCardModeType = presetItemId > 0
                    ? getStoredCardModeType(presetItemId)
                    : '';
                if (presetItemId > 0) {
                    cardEl.dataset.itemId = String(presetItemId);
                }
                const presetModeType = preset && typeof preset.mode_type === 'string'
                    ? resolveAllowedCardModeType(preset.mode_type)
                    : '';
                let initialCardModeType = hasPresetSavedMonth
                    ? 'report'
                    : (canUseTargetMode ? currentKpiMode : 'report');
                if (storedCardModeType === 'report' || storedCardModeType === 'target') {
                    initialCardModeType = storedCardModeType;
                } else if (presetModeType === 'report' || presetModeType === 'target') {
                    initialCardModeType = presetModeType;
                }
                cardEl.dataset.modeType = resolveAllowedCardModeType(initialCardModeType);

                const targetDepartmentsWrap = cardEl.querySelector('[data-role="target-departments-wrap"]');
                const cardModeWrap = cardEl.querySelector('[data-role="card-mode-wrap"]');
                const cardModeReportBtn = cardEl.querySelector('[data-role="card-mode-report"]');
                const cardModeTargetBtn = cardEl.querySelector('[data-role="card-mode-target"]');
                const targetDepartmentsSelect = cardEl.querySelector('[data-role="target-departments"]');
                const targetDepartmentsSummary = cardEl.querySelector('[data-role="target-departments-summary"]');
                const targetDepartmentsOptions = cardEl.querySelector('[data-role="target-departments-options"]');
                const okrHierarchySelect = cardEl.querySelector('[data-role="okr-hierarchy"]');
                const okrLevelThreeSelect = cardEl.querySelector('[data-role="okr-level-three"]');
                const objectiveInput = cardEl.querySelector('[data-role="objective"]');
                const detailInput = cardEl.querySelector('[data-role="detail"]');
                const targetInput = cardEl.querySelector('[data-role="target"]');
                const unitSelect = cardEl.querySelector('[data-role="unit"]');
                const customUnitInput = cardEl.querySelector('[data-role="unit-other"]');
                const criteriaSelect = cardEl.querySelector('[data-role="criteria"]');
                const hasCriteriaInputs = [...cardEl.querySelectorAll('[data-role="has-criteria"]')];
                const targetSection = cardEl.querySelector('[data-role="target-section"]');
                const scoreSection = cardEl.querySelector('[data-role="score-section"]');
                const resultInput = cardEl.querySelector('[data-role="result"]');
                const removeBtn = cardEl.querySelector('[data-role="remove-item"]');
                const editBtn = cardEl.querySelector('[data-role="edit-card"]');
                const openTargetBtn = cardEl.querySelector('[data-role="open-target"]');
                const openScoreBtn = cardEl.querySelector('[data-role="open-score"]');
                const confirmResultBtn = cardEl.querySelector('[data-role="confirm-result"]');
                const hideBtn = cardEl.querySelector('[data-role="hide-card"]');

                if (targetDepartmentsSelect) {
                    targetDepartmentsSelect.name = `kpi_cards[${uid}][target_departments][]`;
                }
                if (okrHierarchySelect) {
                    okrHierarchySelect.name = `kpi_cards[${uid}][okr_hierarchy]`;
                }
                if (okrLevelThreeSelect) {
                    okrLevelThreeSelect.name = `kpi_cards[${uid}][parent_target_kpi_id]`;
                }
                if (objectiveInput) {
                    objectiveInput.name = `kpi_cards[${uid}][objective]`;
                }
                if (detailInput) {
                    detailInput.name = `kpi_cards[${uid}][detail]`;
                }
                if (targetInput) {
                    targetInput.name = `kpi_cards[${uid}][target]`;
                }
                if (unitSelect) {
                    unitSelect.name = `kpi_cards[${uid}][kpi_unit_id]`;
                    renderUnitOptions(unitSelect);
                }
                if (customUnitInput) {
                    customUnitInput.name = `kpi_cards[${uid}][custom_unit]`;
                    customUnitInput.placeholder = getOtherUnitPlaceholderText();
                }
                if (criteriaSelect) {
                    criteriaSelect.name = `kpi_cards[${uid}][criteria_operator]`;
                    renderCriteriaOptions(criteriaSelect);
                }
                hasCriteriaInputs.forEach((inputEl) => {
                    inputEl.name = `kpi_cards[${uid}][has_criteria]`;
                });
                const activeReportDepartment = normalizeDepartmentCode(currentReportDepartment || '');
                const activeTargetDepartment = normalizeDepartmentCode(currentTargetDepartment || '');
                const cardModeTypeForDepartment = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                const preferredTargetDepartments = Array.isArray(presetTargetDepartments)
                    ? presetTargetDepartments
                    : (
                        cardModeTypeForDepartment === 'report' && activeReportDepartment !== ''
                            ? [activeReportDepartment]
                            : (
                                cardModeTypeForDepartment === 'target' && activeTargetDepartment !== ''
                                    ? [activeTargetDepartment]
                                    : null
                            )
                    );
                renderCardTargetDepartmentSelection(cardEl, preferredTargetDepartments);
                const presetOkrSelection = preset &&
                    Number(preset.okr_objective_id || 0) > 0 &&
                    Number(preset.okr_key_result_id || 0) > 0
                    ? {
                        objectiveId: Number(preset.okr_objective_id),
                        keyResultId: Number(preset.okr_key_result_id),
                    }
                    : null;
                if (presetOkrSelection) {
                    cardEl.dataset.okrObjectiveId = String(presetOkrSelection.objectiveId);
                    cardEl.dataset.okrKeyResultId = String(presetOkrSelection.keyResultId);
                }
                const presetParentTargetId = preset && Number(preset.parent_target_kpi_id || 0) > 0
                    ? Number(preset.parent_target_kpi_id)
                    : 0;
                if (presetParentTargetId > 0) {
                    cardEl.dataset.parentTargetKpiId = String(presetParentTargetId);
                }
                renderCardOkrHierarchySelection(cardEl, presetOkrSelection);
                renderCardReportLevelThreeSelection(cardEl, presetParentTargetId);

                if (objectiveInput && preset && typeof preset.objective === 'string') {
                    objectiveInput.value = preset.objective;
                }
                if (detailInput && preset && typeof preset.detail === 'string') {
                    detailInput.value = preset.detail;
                }
                if (targetInput && preset && preset.target !== null && typeof preset.target !== 'undefined') {
                    targetInput.value = String(preset.target);
                }
                if (unitSelect && preset && Number(preset.kpi_unit_id || 0) > 0) {
                    unitSelect.value = String(Number(preset.kpi_unit_id));
                }
                if (customUnitInput && preset && typeof preset.custom_unit === 'string') {
                    customUnitInput.value = preset.custom_unit;
                }
                if (criteriaSelect && preset && typeof preset.criteria_operator === 'string' && preset.criteria_operator.trim() !== '') {
                    criteriaSelect.value = preset.criteria_operator;
                }
                const presetHasCriteria = (
                    preset &&
                    typeof preset.has_criteria !== 'undefined' &&
                    preset.has_criteria !== null
                )
                    ? toBool(preset.has_criteria)
                    : (
                        preset &&
                        typeof preset.criteria_operator === 'string' &&
                        preset.criteria_operator.trim() !== '' &&
                        preset.target !== null &&
                        Number(preset.kpi_unit_id || 0) > 0
                            ? true
                            : null
                    );
                if (presetHasCriteria !== null) {
                    const selectedHasCriteriaInput = cardEl.querySelector(
                        `[data-role="has-criteria"][value="${presetHasCriteria ? '1' : '0'}"]`
                    );
                    if (selectedHasCriteriaInput) {
                        selectedHasCriteriaInput.checked = true;
                    }
                }

                if (resultInput) {
                    const presetResultConfirmed = !!(preset && toBool(preset.result_confirmed));
                    const presetResult = preset ? parseNumeric(preset.result) : Number.NaN;
                    if (Number.isFinite(presetResult)) {
                        resultInput.dataset.confirmed = '1';
                        resultInput.dataset.resultValue = String(presetResult);
                        resultInput.textContent = buildStoredResultText(presetResult);
                    } else if (presetResultConfirmed) {
                        resultInput.dataset.confirmed = '1';
                        delete resultInput.dataset.resultValue;
                        resultInput.textContent = resolveCardPendingResultText(cardEl);
                    } else {
                        resultInput.textContent = resolveCardPendingResultText(cardEl);
                    }
                }

                buildMonthInputs(cardEl, uid, presetMonths);
                renderCustomUnitInput(cardEl);
                renderMonthUnitLabels(cardEl);

                const hasStepOneSaved = presetItemId > 0;
                const hasStepTwoSaved = !!(preset && (
                    (
                        resolveAllowedCardModeType(cardEl.dataset.modeType || '') === 'target' &&
                        presetHasCriteria === false
                    ) ||
                    (
                        presetHasCriteria === true &&
                        typeof preset.criteria_operator === 'string' &&
                        preset.criteria_operator.trim() !== '' &&
                        preset.target !== null &&
                        typeof preset.target !== 'undefined' &&
                        String(preset.target).trim() !== '' &&
                        Number(preset.kpi_unit_id || 0) > 0
                    )
                ));

                if (targetSection) {
                    targetSection.style.display = hasStepOneSaved ? 'block' : 'none';
                }
                if (openTargetBtn) {
                    openTargetBtn.style.display = 'inline-block';
                    openTargetBtn.disabled = false;
                    setStepActionMode(openTargetBtn, hasStepOneSaved ? 'edit' : 'save');
                }
                if (scoreSection) {
                    scoreSection.style.display = hasStepTwoSaved ? 'block' : 'none';
                }
                if (openScoreBtn) {
                    openScoreBtn.style.display = 'inline-block';
                    openScoreBtn.disabled = false;
                    setStepActionMode(openScoreBtn, hasStepTwoSaved ? 'edit' : 'save');
                }
                updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                if (hideBtn) {
                    hideBtn.style.display = hasStepTwoSaved ? 'inline-block' : 'none';
                }

                setCardModeType(cardEl, cardEl.dataset.modeType || 'report', {
                    persist: false,
                    applyFilter: false,
                });

                hasCriteriaInputs.forEach((inputEl) => {
                    inputEl.addEventListener('change', () => {
                        syncCardCriteriaChoice(cardEl);
                    });
                });

                renderCardSummary(cardEl);
                setCompactSummary(cardEl, null);
                const isResultConfirmed = !!(resultInput && resultInput.dataset.confirmed === '1');
                const collapsedByPreference = !isResultConfirmed && isCardHiddenByPreference(cardEl);
                setCardCollapsed(cardEl, isResultConfirmed || collapsedByPreference || hasStepOneSaved);

                if (targetDepartmentsSummary && targetDepartmentsOptions && targetDepartmentsSelect) {
                    targetDepartmentsSummary.addEventListener('click', (event) => {
                        event.preventDefault();
                        if (targetDepartmentsWrap && targetDepartmentsWrap.hidden) {
                            return;
                        }
                        const isClosed = targetDepartmentsOptions.hidden;
                        setCardTargetDepartmentsPanelOpen(cardEl, isClosed);
                    });

                    targetDepartmentsOptions.addEventListener('click', (event) => {
                        const targetBtn = event.target instanceof HTMLElement
                            ? event.target.closest('[data-role="target-department-option"]')
                            : null;
                        if (!targetBtn) {
                            return;
                        }

                        const code = normalizeDepartmentCode(targetBtn.dataset.value || '');
                        if (code === '') {
                            return;
                        }

                        const matchedOption = [...targetDepartmentsSelect.options]
                            .find((optionEl) => normalizeDepartmentCode(optionEl.value) === code);
                        if (!matchedOption) {
                            return;
                        }

                        [...targetDepartmentsSelect.options].forEach((optionEl) => {
                            optionEl.selected = false;
                        });
                        matchedOption.selected = true;

                        renderCardTargetDepartmentSelection(cardEl, [code]);
                        renderCardReportLevelThreeSelection(cardEl);
                        setStepActionMode(openTargetBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                        renderCardSummary(cardEl);
                        setCompactSummary(cardEl, null);
                        const cardModeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                        if (cardModeType === 'report') {
                            currentReportDepartmentFilterAll = false;
                            currentReportDepartment = code;
                            saveCurrentReportDepartment();
                        } else if (cardModeType === 'target') {
                            currentTargetDepartmentFilterAll = false;
                            currentTargetDepartment = code;
                            saveCurrentTargetDepartment();
                        }
                        renderKpiModeTabs();
                        renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                        applyKpiModeFilterToCards();
                        ensureCardOrder();
                    });
                }

                if (cardModeWrap && cardModeReportBtn && cardModeTargetBtn) {
                    const onSelectCardMode = (nextModeType) => {
                        if (!canSwitchCardModeType(cardEl)) {
                            syncCardModePicker(cardEl);
                            return;
                        }

                        const normalizedNextMode = resolveAllowedCardModeType(nextModeType);
                        setCardModeType(cardEl, normalizedNextMode, {
                            applyFilter: false,
                        });
                        ensureCardOrder();

                        if (normalizedNextMode === 'report') {
                            const cardDepartment = resolveCardReportDepartment(cardEl);
                            if (cardDepartment !== '') {
                                currentReportDepartmentFilterAll = false;
                                currentReportDepartment = cardDepartment;
                                saveCurrentReportDepartment();
                            }
                        } else if (normalizedNextMode === 'target') {
                            const cardDepartment = resolveCardTargetDepartment(cardEl);
                            if (cardDepartment !== '') {
                                currentTargetDepartmentFilterAll = false;
                                currentTargetDepartment = cardDepartment;
                                saveCurrentTargetDepartment();
                            }
                        }

                        if (canUseCardModeSelection && currentKpiMode !== normalizedNextMode) {
                            currentKpiMode = normalizedNextMode;
                            saveCurrentKpiMode();
                            renderKpiModeTabs();
                            renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                        } else if (normalizedNextMode === 'report' || normalizedNextMode === 'target') {
                            renderKpiModeTabs();
                            renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                        }

                        applyKpiModeFilterToCards();
                        keepKpiViewportAtTabs('smooth');
                        renderCardSummary(cardEl);
                    };

                    cardModeReportBtn.addEventListener('click', () => {
                        onSelectCardMode('report');
                    });

                    cardModeTargetBtn.addEventListener('click', () => {
                        onSelectCardMode('target');
                    });
                }

                const okrHierarchyDeptFilterSelect = cardEl.querySelector('[data-role="okr-hierarchy-dept-filter"]');
                if (okrHierarchyDeptFilterSelect) {
                    okrHierarchyDeptFilterSelect.addEventListener('change', () => {
                        okrHierarchyDeptFilterSelect.classList.toggle('has-filter', okrHierarchyDeptFilterSelect.value !== '');
                        renderCardOkrHierarchySelection(cardEl);
                    });
                }

                const updateHierarchyPreview = () => {
                    const previewBox = cardEl.querySelector('[data-role="hierarchy-preview"]');
                    const selectWrap = cardEl.querySelector('[data-role="hierarchy-select-wrap"]');
                    const changeBtn = cardEl.querySelector('[data-role="hierarchy-change-btn"]');
                    if (!previewBox) return;
                    const sel = getCardOkrHierarchySelection(cardEl);
                    const summary = resolveOkrHierarchySummaryBySelection(sel);
                    if (!summary) {
                        previewBox.hidden = true;
                        if (selectWrap) selectWrap.hidden = false;
                        if (changeBtn) changeBtn.hidden = true;
                        return;
                    }
                    const l1El = previewBox.querySelector('[data-role="hierarchy-preview-l1"]');
                    const l2El = previewBox.querySelector('[data-role="hierarchy-preview-l2"]');
                    if (l1El) l1El.textContent = summary.objectiveLabel || '';
                    if (l2El) l2El.textContent = '-  ' + (summary.keyResultLabel || '');
                    previewBox.hidden = false;
                    if (selectWrap) selectWrap.hidden = true;
                    if (changeBtn) changeBtn.hidden = false;
                };

                const updateL3Preview = () => {
                    const previewBox = cardEl.querySelector('[data-role="l3-preview"]');
                    const selectWrap = cardEl.querySelector('[data-role="l3-select-wrap"]');
                    const changeBtn = cardEl.querySelector('[data-role="l3-change-btn"]');
                    if (!previewBox) return;
                    const l3Sel = getCardReportLevelThreeSelection(cardEl);
                    if (!l3Sel) {
                        previewBox.hidden = true;
                        if (selectWrap) selectWrap.hidden = false;
                        if (changeBtn) changeBtn.hidden = true;
                        return;
                    }
                    const sel = { objectiveId: l3Sel.objectiveId, keyResultId: l3Sel.keyResultId };
                    const summary = resolveOkrHierarchySummaryBySelection(sel);
                    const l1El = previewBox.querySelector('[data-role="l3-preview-l1"]');
                    const l2El = previewBox.querySelector('[data-role="l3-preview-l2"]');
                    const l3El = previewBox.querySelector('[data-role="l3-preview-l3"]');
                    if (l1El) l1El.textContent = summary ? (summary.objectiveLabel || '') : '';
                    if (l2El) l2El.textContent = summary ? ('-  ' + (summary.keyResultLabel || '')) : '';
                    if (l3El) l3El.textContent = '-  ' + (l3Sel.levelThreeLabel || '');
                    previewBox.hidden = false;
                    if (selectWrap) selectWrap.hidden = true;
                    if (changeBtn) changeBtn.hidden = false;
                };

                const hierarchyChangeBtn = cardEl.querySelector('[data-role="hierarchy-change-btn"]');
                if (hierarchyChangeBtn) {
                    hierarchyChangeBtn.addEventListener('click', () => {
                        if (okrHierarchySelect) {
                            okrHierarchySelect.value = '';
                            okrHierarchySelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                }

                const l3ChangeBtn = cardEl.querySelector('[data-role="l3-change-btn"]');
                if (l3ChangeBtn) {
                    l3ChangeBtn.addEventListener('click', () => {
                        setL3DropdownValue(cardEl, '');
                        delete cardEl.dataset.parentTargetKpiId;
                        if (okrLevelThreeSelect) {
                            okrLevelThreeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                }

                updateHierarchyPreview();
                updateL3Preview();

                if (okrHierarchySelect) {
                    okrHierarchySelect.addEventListener('change', () => {
                        const okrSelection = getCardOkrHierarchySelection(cardEl);
                        if (okrSelection) {
                            cardEl.dataset.okrObjectiveId = String(okrSelection.objectiveId);
                            cardEl.dataset.okrKeyResultId = String(okrSelection.keyResultId);
                            currentTargetHierarchyKey = buildOkrHierarchyFilterKey(okrSelection.objectiveId, okrSelection.keyResultId);
                            saveCurrentTargetHierarchyKey();
                        }
                        updateHierarchyPreview();
                        setStepActionMode(openTargetBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                        renderKpiModeTabs();
                        applyKpiModeFilterToCards();
                        renderCardSummary(cardEl);
                        setCompactSummary(cardEl, null);
                    });
                }

                if (okrLevelThreeSelect) {
                    okrLevelThreeSelect.addEventListener('change', () => {
                        const levelThreeSelection = getCardReportLevelThreeSelection(cardEl);
                        if (levelThreeSelection) {
                            cardEl.dataset.parentTargetKpiId = String(levelThreeSelection.parentTargetKpiId);
                            cardEl.dataset.okrObjectiveId = String(levelThreeSelection.objectiveId);
                            cardEl.dataset.okrKeyResultId = String(levelThreeSelection.keyResultId);
                            currentReportHierarchyKey = buildOkrLevelThreeFilterKey(levelThreeSelection.parentTargetKpiId);
                            saveCurrentReportHierarchyKey();
                            const targetDepartment = normalizeDepartmentCodeList(levelThreeSelection.targetDepartments)[0] || '';
                            if (targetDepartment !== '') {
                                renderCardTargetDepartmentSelection(cardEl, [targetDepartment]);
                                currentReportDepartmentFilterAll = false;
                                currentReportDepartment = targetDepartment;
                                saveCurrentReportDepartment();
                            }
                        } else {
                            delete cardEl.dataset.parentTargetKpiId;
                        }
                        setStepActionMode(openTargetBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                        renderKpiModeTabs();
                        renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                        applyKpiModeFilterToCards();
                        ensureCardOrder();
                        updateL3Preview();
                        renderCardSummary(cardEl);
                        setCompactSummary(cardEl, null);
                    });
                }

                if (objectiveInput) {
                    objectiveInput.addEventListener('input', () => {
                        setStepActionMode(openTargetBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                    });
                }

                if (detailInput) {
                    detailInput.addEventListener('input', () => {
                        setStepActionMode(openTargetBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                    });
                }

                if (targetInput) {
                    targetInput.addEventListener('input', () => {
                        setStepActionMode(openScoreBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                        markAllMonthsDraft(cardEl);
                    });
                }

                if (criteriaSelect) {
                    criteriaSelect.addEventListener('change', () => {
                        setStepActionMode(openScoreBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                        markAllMonthsDraft(cardEl);
                    });
                }

                if (unitSelect) {
                    unitSelect.addEventListener('change', () => {
                        const selected = String(unitSelect.value || '').trim();
                        if (selected !== CUSTOM_UNIT_VALUE && selected !== '') {
                            unitSelect.dataset.lastStandardUnit = selected;
                        }
                        setStepActionMode(openScoreBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                        renderCustomUnitInput(cardEl);
                        markAllMonthsDraft(cardEl);
                        renderMonthUnitLabels(cardEl);
                    });
                }

                if (customUnitInput) {
                    customUnitInput.addEventListener('input', () => {
                        setStepActionMode(openScoreBtn, 'save');
                        updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                        markAllMonthsDraft(cardEl);
                        renderMonthUnitLabels(cardEl);
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', () => {
                        showConfirm('confirmRemoveKpiItem', () => {
                            const removeCardFromUi = () => {
                                clearStoredCardModeType(getCardItemId(cardEl));
                                setCardHiddenPreference(cardEl, false);
                                if (cardEl._restoreMarker) {
                                    cardEl._restoreMarker.remove();
                                    delete cardEl._restoreMarker;
                                }
                                cleanupCardEvidencePreviews(cardEl);
                                cardEl.remove();
                                updateEditorZoneState();
                                const totalCardsCount = listEl.querySelectorAll('.kpi-card').length
                                    + editorListEl.querySelectorAll('.kpi-card').length;
                                if (totalCardsCount === 0) {
                                    const freshCard = createCard();
                                    editorListEl.appendChild(freshCard);
                                    pinCardToEditorZone(freshCard);
                                }
                                ensureCardOrder();
                                applyKpiModeFilterToCards();
                                showAlert('kpiAlertCardDeleted', 'success');
                            };

                            const itemId = Number(cardEl.dataset.itemId || 0);
                            if (itemId < 1) {
                                removeCardFromUi();
                                return;
                            }

                            if (deleteItemUrl === '#' || !csrfToken) {
                                showAlert('kpiAlertDeleteFailed');
                                return;
                            }

                            const payload = new FormData();
                            payload.append('_token', csrfToken);
                            payload.append('item_id', String(itemId));

                            const originalLabel = removeBtn.textContent;
                            removeBtn.disabled = true;
                            removeBtn.textContent = getText('kpiSaving');

                            (async () => {
                                try {
                                    await postForm(deleteItemUrl, payload, 'kpiAlertDeleteFailed');
                                    removeCardFromUi();
                                } catch (err) {
                                    showRawAlert(err && err.message ? err.message : getText('kpiAlertDeleteFailed'), 'error');
                                } finally {
                                    removeBtn.disabled = false;
                                    removeBtn.textContent = originalLabel;
                                }
                            })();
                        });
                    });
                }

                if (openTargetBtn) {
                    openTargetBtn.addEventListener('click', () => {
                        if (getStepActionMode(openTargetBtn) === 'edit') {
                            setStepActionMode(openTargetBtn, 'save');
                            updateStepSavedHint(cardEl, 'open-target-saved-hint', false);
                            return;
                        }

                        showConfirm('confirmOpenTargetSection', () => {
                            if (!objectiveInput || !detailInput || !objectiveInput.value.trim() || !detailInput.value.trim()) {
                                showAlert('kpiAlertNeedObjectiveDetail');
                                return;
                            }

                            if (!cycleContext || !cycleContext.has_active_cycle || !cycleContext.cycle_id) {
                                showAlert('kpiAlertNeedActiveCycle');
                                return;
                            }

                            const payload = new FormData();
                            const submitModeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                            payload.append('_token', csrfToken);
                            payload.append('cycle_id', String(cycleContext.cycle_id));
                            payload.append('objective', String(objectiveInput.value || '').trim());
                            payload.append('detail', String(detailInput.value || '').trim());
                            payload.append('mode_type', submitModeType);
                            if (canSelectTargetDepartments) {
                                const selectedTargetDepartments = getCardSelectedTargetDepartments(cardEl);
                                const submitDepartmentCode = selectedTargetDepartments[0]
                                    || defaultTargetDepartments[0]
                                    || '';
                                if (submitDepartmentCode !== '') {
                                    payload.append('target_departments[]', submitDepartmentCode);
                                }
                            }
                            if (submitModeType === 'target' && hasOkrHierarchyOptions()) {
                                const okrSelection = getCardOkrHierarchySelection(cardEl);
                                if (!okrSelection) {
                                    showAlert('kpiAlertNeedOkrHierarchy');
                                    return;
                                }
                                cardEl.dataset.okrObjectiveId = String(okrSelection.objectiveId);
                                cardEl.dataset.okrKeyResultId = String(okrSelection.keyResultId);
                                payload.append('okr_objective_id', String(okrSelection.objectiveId));
                                payload.append('okr_key_result_id', String(okrSelection.keyResultId));
                            } else if (submitModeType === 'report' && flattenOkrLevelThreeEntries().length > 0) {
                                const levelThreeSelection = getCardReportLevelThreeSelection(cardEl);
                                if (!levelThreeSelection) {
                                    showAlert('kpiAlertNeedOkrLevelThree');
                                    return;
                                }
                                cardEl.dataset.parentTargetKpiId = String(levelThreeSelection.parentTargetKpiId);
                                cardEl.dataset.okrObjectiveId = String(levelThreeSelection.objectiveId);
                                cardEl.dataset.okrKeyResultId = String(levelThreeSelection.keyResultId);
                                payload.append('parent_target_kpi_id', String(levelThreeSelection.parentTargetKpiId));
                                payload.append('okr_objective_id', String(levelThreeSelection.objectiveId));
                                payload.append('okr_key_result_id', String(levelThreeSelection.keyResultId));
                            }
                            if (cardEl.dataset.itemId) {
                                payload.append('item_id', String(cardEl.dataset.itemId));
                            }

                            const originalMode = getStepActionMode(openTargetBtn);
                            openTargetBtn.disabled = true;
                            openTargetBtn.textContent = getText('kpiSaving');

                            (async () => {
                                try {
                                    const data = await postForm(saveStepOneUrl, payload, 'kpiAlertStepOneSaveFailed');
                                    if (data && data.item && data.item.id) {
                                        cardEl.dataset.itemId = String(data.item.id);
                                        setStoredCardModeType(data.item.id, resolveAllowedCardModeType(cardEl.dataset.modeType || 'report'));
                                    }
                                    if (data && data.item && Array.isArray(data.item.target_departments)) {
                                        renderCardTargetDepartmentSelection(cardEl, data.item.target_departments);
                                        const savedDepartment = normalizeDepartmentCode(data.item.target_departments[0] || '');
                                        const cardModeType = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                                        if (
                                            savedDepartment !== '' &&
                                            cardModeType === 'report'
                                        ) {
                                            currentReportDepartmentFilterAll = false;
                                            currentReportDepartment = savedDepartment;
                                            saveCurrentReportDepartment();
                                        } else if (savedDepartment !== '' && cardModeType === 'target') {
                                            currentTargetDepartmentFilterAll = false;
                                            currentTargetDepartment = savedDepartment;
                                            saveCurrentTargetDepartment();
                                        }
                                    }
                                    if (
                                        data &&
                                        data.item &&
                                        Number(data.item.okr_objective_id || 0) > 0 &&
                                        Number(data.item.okr_key_result_id || 0) > 0
                                    ) {
                                        cardEl.dataset.okrObjectiveId = String(Number(data.item.okr_objective_id));
                                        cardEl.dataset.okrKeyResultId = String(Number(data.item.okr_key_result_id));
                                        renderCardOkrHierarchySelection(cardEl, {
                                            objectiveId: Number(data.item.okr_objective_id),
                                            keyResultId: Number(data.item.okr_key_result_id),
                                        });
                                        const savedHierarchyKey = buildOkrHierarchyFilterKey(
                                            Number(data.item.okr_objective_id),
                                            Number(data.item.okr_key_result_id)
                                        );
                                        if (resolveAllowedCardModeType(cardEl.dataset.modeType || 'report') === 'target') {
                                            currentTargetHierarchyKey = savedHierarchyKey;
                                            saveCurrentTargetHierarchyKey();
                                        } else {
                                            const savedLevelThreeKey = Number(data.item.parent_target_kpi_id || 0) > 0
                                                ? buildOkrLevelThreeFilterKey(Number(data.item.parent_target_kpi_id))
                                                : '';
                                            if (savedLevelThreeKey !== '') {
                                                currentReportHierarchyKey = savedLevelThreeKey;
                                                saveCurrentReportHierarchyKey();
                                            }
                                        }
                                    }
                                    if (data && data.item && Number(data.item.parent_target_kpi_id || 0) > 0) {
                                        cardEl.dataset.parentTargetKpiId = String(Number(data.item.parent_target_kpi_id));
                                        renderCardReportLevelThreeSelection(cardEl, Number(data.item.parent_target_kpi_id));
                                    } else if (resolveAllowedCardModeType(cardEl.dataset.modeType || 'report') === 'report') {
                                        renderCardReportLevelThreeSelection(cardEl);
                                    }
                                    upsertOkrLevelThreeEntryFromTargetCard(cardEl);

                                    if (targetSection) {
                                        targetSection.style.display = 'block';
                                    }
                                    renderKpiModeTabs();
                                    renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                                    applyKpiModeFilterToCards();
                                    ensureCardOrder();
                                    openTargetBtn.disabled = false;
                                    setStepActionMode(openTargetBtn, 'edit');
                                    updateStepSavedHint(cardEl, 'open-target-saved-hint', true);
                                    renderCardSummary(cardEl);
                                    setCompactSummary(cardEl, null);
                                } catch (err) {
                                    showRawAlert(err && err.message ? err.message : getText('kpiAlertStepOneSaveFailed'), 'error');
                                    openTargetBtn.disabled = false;
                                    setStepActionMode(openTargetBtn, originalMode);
                                }
                            })();
                        });
                    });
                }

                if (openScoreBtn) {
                    openScoreBtn.addEventListener('click', () => {
                        if (getStepActionMode(openScoreBtn) === 'edit') {
                            setStepActionMode(openScoreBtn, 'save');
                            updateStepSavedHint(cardEl, 'open-score-saved-hint', false);
                            return;
                        }

                        showConfirm('confirmOpenScoreSection', () => {
                            const itemId = Number(cardEl.dataset.itemId || 0);
                            if (itemId < 1) {
                                showAlert('kpiAlertNeedStepOne');
                                return;
                            }

                            const hasCriteriaChoice = resolveCardHasCriteriaChoice(cardEl);
                            if (hasCriteriaChoice === null) {
                                showAlert('kpiAlertNeedCriteriaChoice');
                                return;
                            }

                            const selectedUnitRaw = unitSelect ? String(unitSelect.value || '').trim() : '';
                            const customUnitText = selectedUnitRaw === CUSTOM_UNIT_VALUE ? getCardCustomUnitText(cardEl) : '';
                            const submitUnitId = resolveCardSubmitUnitId(cardEl);
                            if (
                                hasCriteriaChoice &&
                                (!targetInput || !unitSelect || !criteriaSelect || !targetInput.value.trim() || !submitUnitId || !criteriaSelect.value.trim())
                            ) {
                                showAlert('kpiAlertNeedTargetCriteriaAndUnit');
                                return;
                            }
                            if (hasCriteriaChoice && selectedUnitRaw === CUSTOM_UNIT_VALUE && customUnitText === '') {
                                showRawAlert(
                                    resolveActiveLang() === 'th'
                                        ? '\u0e01\u0e23\u0e38\u0e13\u0e32\u0e23\u0e30\u0e1a\u0e38\u0e2b\u0e19\u0e48\u0e27\u0e22\u0e2d\u0e37\u0e48\u0e19\u0e46'
                                        : 'Please specify other unit.',
                                    'error'
                                );
                                return;
                            }

                            if (hasCriteriaChoice && Number.isNaN(parseNumeric(targetInput.value))) {
                                showAlert('kpiAlertNeedNumericTarget');
                                return;
                            }

                            const payload = new FormData();
                            payload.append('_token', csrfToken);
                            payload.append('item_id', String(itemId));
                            payload.append('has_criteria', hasCriteriaChoice ? '1' : '0');
                            if (hasCriteriaChoice) {
                                payload.append('target', String(targetInput.value || '').trim());
                                payload.append('kpi_unit_id', submitUnitId);
                                payload.append('criteria_operator', String(criteriaSelect.value || '').trim());
                            }
                            if (hasCriteriaChoice && customUnitText !== '') {
                                payload.append('custom_unit', customUnitText);
                            }

                            const originalMode = getStepActionMode(openScoreBtn);
                            openScoreBtn.disabled = true;
                            openScoreBtn.textContent = getText('kpiSaving');

                            (async () => {
                                try {
                                    await postForm(saveStepTwoUrl, payload, 'kpiAlertStepTwoSaveFailed');

                                    if (scoreSection) {
                                        scoreSection.style.display = 'block';
                                    }
                                    if (hideBtn) {
                                        hideBtn.style.display = 'inline-block';
                                    }
                                    openScoreBtn.disabled = false;
                                    setStepActionMode(openScoreBtn, 'edit');
                                    updateStepSavedHint(cardEl, 'open-score-saved-hint', true);
                                } catch (err) {
                                    showRawAlert(err && err.message ? err.message : getText('kpiAlertStepTwoSaveFailed'), 'error');
                                    openScoreBtn.disabled = false;
                                    setStepActionMode(openScoreBtn, originalMode);
                                }
                            })();
                        });
                    });
                }

                if (confirmResultBtn) {
                    confirmResultBtn.addEventListener('click', () => {
                        const missingActionPlan = findFirstMissingActionPlanMonth(cardEl);
                        if (missingActionPlan && Number(missingActionPlan.monthNo) > 0) {
                            showAlert('kpiAlertNeedActionPlan', 'error');
                            jumpToMonthForActionPlan(cardEl, missingActionPlan.monthNo);
                            return;
                        }

                        showConfirm('confirmKpiResult', () => {
                            const itemId = Number(cardEl.dataset.itemId || 0);
                            if (itemId < 1) {
                                showAlert('kpiAlertNeedStepTwo');
                                return;
                            }

                            if (confirmResultUrl === '#' || !csrfToken) {
                                showAlert('kpiAlertResultSaveFailed');
                                return;
                            }

                            const payload = new FormData();
                            payload.append('_token', csrfToken);
                            payload.append('item_id', String(itemId));
                            payload.append('mode_type', resolveAllowedCardModeType(cardEl.dataset.modeType || 'report'));
                            if (canSelectTargetDepartments) {
                                const selectedTargetDepartments = getCardSelectedTargetDepartments(cardEl);
                                const submitDepartmentCode = selectedTargetDepartments[0]
                                    || defaultTargetDepartments[0]
                                    || '';
                                if (submitDepartmentCode !== '') {
                                    payload.append('target_departments[]', submitDepartmentCode);
                                }
                            }

                            const originalLabel = confirmResultBtn.textContent;
                            confirmResultBtn.disabled = true;
                            confirmResultBtn.textContent = getText('kpiSaving');

                            (async () => {
                                try {
                                    const data = await postForm(confirmResultUrl, payload, 'kpiAlertResultSaveFailed');
                                    const excludedFromCalculation = !!(
                                        data &&
                                        data.result &&
                                        (
                                            data.result.excluded_from_calculation === true ||
                                            data.result.excluded_from_calculation === 1 ||
                                            data.result.excluded_from_calculation === '1'
                                        )
                                    );
                                    const resultValue = (
                                        !excludedFromCalculation &&
                                        data.result &&
                                        data.result.value !== null &&
                                        typeof data.result.value !== 'undefined'
                                    )
                                        ? Number(data.result.value)
                                        : Number.NaN;
                                    if (data.result && Number(data.result.item_id || 0) > 0) {
                                        const resultItemId = Number(data.result.item_id);
                                        cardEl.dataset.itemId = String(resultItemId);
                                        setStoredCardModeType(resultItemId, resolveAllowedCardModeType(cardEl.dataset.modeType || 'report'));
                                    }
                                    if (excludedFromCalculation) {
                                        setCardHiddenPreference(cardEl, true);
                                    } else {
                                        setCardHiddenPreference(cardEl, false);
                                    }
                                    if (resultInput) {
                                        resultInput.dataset.confirmed = '1';
                                        if (Number.isFinite(resultValue)) {
                                            resultInput.dataset.resultValue = String(resultValue);
                                            resultInput.textContent = buildStoredResultText(resultValue);
                                        } else {
                                            delete resultInput.dataset.resultValue;
                                            resultInput.textContent = resolveCardPendingResultText(cardEl);
                                        }
                                    }
                                    syncCardModePicker(cardEl);

                                    kpiAverageByDepartmentState = (
                                        data && data.result && data.result.kpi_avg_by_department
                                            ? data.result.kpi_avg_by_department
                                            : {}
                                    );
                                    const confirmedCardMode = resolveAllowedCardModeType(cardEl.dataset.modeType || 'report');
                                    if (canUseTargetMode && currentKpiMode !== confirmedCardMode) {
                                        currentKpiMode = confirmedCardMode;
                                        saveCurrentKpiMode();
                                    }
                                    renderKpiModeTabs();
                                    renderKpiAvgByDepartment(kpiAverageByDepartmentState);

                                    restoreCardFromEditorZone(cardEl);
                                    setCardCollapsed(cardEl, true);
                                    renderCardSummary(cardEl);
                                    setCardCollapsed(cardEl, true);
                                    ensureCardOrder();
                                    applyKpiModeFilterToCards();
                                    keepKpiViewportAtTabs('smooth');
                                    showAlert('kpiAlertResultConfirmed', 'success');
                                } catch (err) {
                                    const missingMonthNo = Number(
                                        err && err.payload && err.payload.missing_action_plan_month_no
                                            ? err.payload.missing_action_plan_month_no
                                            : 0
                                    );
                                    if (Number.isFinite(missingMonthNo) && missingMonthNo > 0) {
                                        showAlert('kpiAlertNeedActionPlan', 'error');
                                        jumpToMonthForActionPlan(cardEl, missingMonthNo);
                                        return;
                                    }
                                    showRawAlert(err && err.message ? err.message : getText('kpiAlertResultSaveFailed'), 'error');
                                } finally {
                                    confirmResultBtn.disabled = false;
                                    confirmResultBtn.textContent = originalLabel;
                                }
                            })();
                        });
                    });
                }

                if (hideBtn) {
                    hideBtn.addEventListener('click', () => {
                        showConfirm('confirmHideKpiItem', () => {
                            restoreCardFromEditorZone(cardEl);
                            setCardCollapsed(cardEl, true);
                            setCardHiddenPreference(cardEl, true);
                            ensureCardOrder();
                            applyKpiModeFilterToCards();
                            showAlert('kpiAlertCardHidden', 'success');
                        });
                    });
                }

                if (editBtn) {
                    editBtn.addEventListener('click', () => {
                        showConfirm('confirmEditKpiItem', () => {
                            if (!pinCardToEditorZone(cardEl)) {
                                return;
                            }
                            setCardCollapsed(cardEl, false);
                            setCardHiddenPreference(cardEl, false);
                            if (hideBtn) {
                                hideBtn.style.display = 'inline-block';
                            }
                            ensureCardOrder();
                            applyKpiModeFilterToCards();
                            showAlert('kpiAlertCardEditMode', 'success');
                        });
                    });
                }

                cardEl.querySelectorAll('input, textarea, select').forEach((field) => {
                    field.addEventListener('input', () => {
                        setCardDraft(cardEl);
                        renderCardSummary(cardEl);
                        setCompactSummary(cardEl, null);
                    });
                    field.addEventListener('change', () => {
                        setCardDraft(cardEl);
                        renderCardSummary(cardEl);
                        setCompactSummary(cardEl, null);
                    });
                });

                return cardEl;
            };

            if (canNoticeModal) {
                renderNoticeModalContent();
                if (noticeFabEl) {
                    const noticeFabLabel = getText('kpiNoticeHint');
                    noticeFabEl.setAttribute('aria-label', noticeFabLabel);
                    noticeFabEl.setAttribute('title', noticeFabLabel);
                }

                restoreNoticeFabPosition();

                if (shouldOpenNoticeFromQuery) {
                    clearOpenNoticeQueryFlag();
                }

                if (noticeFabEl) {
                    let fabDragState = null;
                    const releaseFabDrag = (event) => {
                        if (!fabDragState || event.pointerId !== fabDragState.pointerId) {
                            return;
                        }
                        if (noticeFabEl.hasPointerCapture && noticeFabEl.hasPointerCapture(event.pointerId)) {
                            noticeFabEl.releasePointerCapture(event.pointerId);
                        }
                        noticeFabEl.classList.remove('is-dragging');
                        if (fabDragState.moved) {
                            noticeFabIgnoreClick = true;
                            setNoticeFabPosition(fabDragState.left, fabDragState.top, true);
                        }
                        fabDragState = null;
                    };

                    noticeFabEl.addEventListener('pointerdown', (event) => {
                        if (event.pointerType === 'mouse' && event.button !== 0) {
                            return;
                        }
                        const rect = noticeFabEl.getBoundingClientRect();
                        fabDragState = {
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            startLeft: rect.left,
                            startTop: rect.top,
                            left: rect.left,
                            top: rect.top,
                            moved: false,
                        };
                        if (noticeFabEl.setPointerCapture) {
                            noticeFabEl.setPointerCapture(event.pointerId);
                        }
                        noticeFabEl.classList.add('is-dragging');
                        event.preventDefault();
                    });

                    noticeFabEl.addEventListener('pointermove', (event) => {
                        if (!fabDragState || event.pointerId !== fabDragState.pointerId) {
                            return;
                        }
                        const deltaX = event.clientX - fabDragState.startX;
                        const deltaY = event.clientY - fabDragState.startY;
                        if (!fabDragState.moved && Math.abs(deltaX) + Math.abs(deltaY) < 6) {
                            return;
                        }
                        fabDragState.moved = true;
                        const nextLeft = fabDragState.startLeft + deltaX;
                        const nextTop = fabDragState.startTop + deltaY;
                        const clamped = clampNoticeFabPosition(nextLeft, nextTop);
                        fabDragState.left = clamped.left;
                        fabDragState.top = clamped.top;
                        setNoticeFabPosition(clamped.left, clamped.top, false);
                        event.preventDefault();
                    });

                    noticeFabEl.addEventListener('pointerup', releaseFabDrag);
                    noticeFabEl.addEventListener('pointercancel', releaseFabDrag);
                    noticeFabEl.addEventListener('click', (event) => {
                        if (noticeFabIgnoreClick) {
                            noticeFabIgnoreClick = false;
                            event.preventDefault();
                            event.stopPropagation();
                            return;
                        }
                        event.preventDefault();
                        event.stopPropagation();
                        openNoticeModal();
                    });
                }

                if (noticePanelEl && noticeHeadEl) {
                    let panelDragState = null;
                    const releasePanelDrag = (event) => {
                        if (!panelDragState || event.pointerId !== panelDragState.pointerId) {
                            return;
                        }
                        if (noticeHeadEl.hasPointerCapture && noticeHeadEl.hasPointerCapture(event.pointerId)) {
                            noticeHeadEl.releasePointerCapture(event.pointerId);
                        }
                        panelDragState = null;
                        saveNoticePanelFloatingState();
                    };
                    noticeHeadEl.addEventListener('pointerdown', (event) => {
                        const dragTarget = event.target instanceof HTMLElement ? event.target : null;
                        if (dragTarget && dragTarget.closest('.kpi-notice-close')) {
                            return;
                        }
                        if (event.pointerType === 'mouse' && event.button !== 0) {
                            return;
                        }
                        const panelRect = noticePanelEl.getBoundingClientRect();
                        noticeModalFloatingState = {
                            left: panelRect.left,
                            top: panelRect.top,
                            width: panelRect.width,
                            height: panelRect.height,
                        };
                        applyNoticePanelFloatingState();
                        panelDragState = {
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            startLeft: noticeModalFloatingState.left,
                            startTop: noticeModalFloatingState.top,
                        };
                        if (noticeHeadEl.setPointerCapture) {
                            noticeHeadEl.setPointerCapture(event.pointerId);
                        }
                        event.preventDefault();
                    });
                    noticeHeadEl.addEventListener('pointermove', (event) => {
                        if (!panelDragState || event.pointerId !== panelDragState.pointerId || !noticeModalFloatingState) {
                            return;
                        }
                        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
                        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                        const deltaX = event.clientX - panelDragState.startX;
                        const deltaY = event.clientY - panelDragState.startY;
                        const width = Math.min(
                            Math.max(340, Number(noticeModalFloatingState.width) || 340),
                            Math.max(340, viewportWidth - 16)
                        );
                        const height = Math.min(
                            Math.max(320, Number(noticeModalFloatingState.height) || 320),
                            Math.max(320, viewportHeight - 16)
                        );
                        const maxLeft = Math.max(8, viewportWidth - width - 8);
                        const maxTop = Math.max(8, viewportHeight - height - 8);
                        noticeModalFloatingState.left = Math.min(maxLeft, Math.max(8, panelDragState.startLeft + deltaX));
                        noticeModalFloatingState.top = Math.min(maxTop, Math.max(8, panelDragState.startTop + deltaY));
                        applyNoticePanelFloatingState();
                        event.preventDefault();
                    });
                    noticeHeadEl.addEventListener('pointerup', releasePanelDrag);
                    noticeHeadEl.addEventListener('pointercancel', releasePanelDrag);
                }

                if (noticeCloseBtn) {
                    noticeCloseBtn.addEventListener('click', () => {
                        closeNoticeModal();
                    });
                }

                if (noticeModalEl) {
                    noticeModalEl.addEventListener('click', (event) => {
                        if (event.target === noticeModalEl) {
                            closeNoticeModal();
                        }
                    });
                }

                document.addEventListener('click', () => {
                    document.querySelectorAll('[data-role="okr-level-three-options"]').forEach((el) => {
                        el.hidden = true;
                    });
                    document.querySelectorAll('[data-role="okr-level-three-dropdown"]').forEach((el) => {
                        el.classList.remove('open');
                    });
                });

                window.addEventListener('resize', () => {
                    if (noticeFabEl && noticeFabEl.style.left && noticeFabEl.style.top) {
                        const currentLeft = Number.parseFloat(noticeFabEl.style.left);
                        const currentTop = Number.parseFloat(noticeFabEl.style.top);
                        if (Number.isFinite(currentLeft) && Number.isFinite(currentTop)) {
                            setNoticeFabPosition(currentLeft, currentTop, true);
                        }
                    }
                    if (noticeModalEl && noticeModalEl.classList.contains('show')) {
                        saveNoticePanelFloatingState();
                        applyNoticePanelFloatingState();
                    }
                });

                if (noticeLevel3CardEl) {
                    noticeLevel3CardEl.addEventListener('click', () => {
                        if (noticeLevel3SectionEl && noticeLevel3SectionEl.hidden) {
                            return;
                        }
                        openNoticeLevel3Modal();
                        openNoticeLevelThreeInNewTab();
                    });
                }

                if (noticeLevel3ModalCloseEl) {
                    noticeLevel3ModalCloseEl.addEventListener('click', () => {
                        closeNoticeLevel3Modal();
                    });
                }

                if (noticeLevel3ModalEl) {
                    noticeLevel3ModalEl.addEventListener('click', (event) => {
                        if (event.target === noticeLevel3ModalEl) {
                            closeNoticeLevel3Modal();
                        }
                    });
                }

                if (noticeFileModalCloseEl) {
                    noticeFileModalCloseEl.addEventListener('click', () => {
                        closeNoticeFileModal();
                    });
                }

                if (noticeFileModalEl) {
                    noticeFileModalEl.addEventListener('click', (event) => {
                        if (event.target === noticeFileModalEl) {
                            closeNoticeFileModal();
                        }
                    });
                }

                if (noticeAnnouncementModalCloseEl) {
                    noticeAnnouncementModalCloseEl.addEventListener('click', () => {
                        closeNoticeAnnouncementModal();
                    });
                }

                if (noticeAnnouncementModalEl) {
                    noticeAnnouncementModalEl.addEventListener('click', (event) => {
                        if (event.target === noticeAnnouncementModalEl) {
                            closeNoticeAnnouncementModal();
                        }
                    });
                }

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Escape') {
                        return;
                    }
                    if (rejectDetailModalEl && rejectDetailModalEl.classList.contains('show')) {
                        closeRejectDetailModal();
                        return;
                    }
                    if (noticeFileModalEl && noticeFileModalEl.classList.contains('show')) {
                        closeNoticeFileModal();
                        return;
                    }
                    if (noticeLevel3ModalEl && noticeLevel3ModalEl.classList.contains('show')) {
                        closeNoticeLevel3Modal();
                        return;
                    }
                    if (noticeAnnouncementModalEl && noticeAnnouncementModalEl.classList.contains('show')) {
                        closeNoticeAnnouncementModal();
                        return;
                    }
                    if (noticeModalEl && noticeModalEl.classList.contains('show')) {
                        closeNoticeModal();
                    }
                });
            }

            if (canRejectDetailModal) {
                if (rejectDetailModalCloseEl) {
                    rejectDetailModalCloseEl.addEventListener('click', () => {
                        closeRejectDetailModal();
                    });
                }

                if (rejectDetailModalEl) {
                    rejectDetailModalEl.addEventListener('click', (event) => {
                        if (event.target === rejectDetailModalEl) {
                            closeRejectDetailModal();
                        }
                    });
                }

                if (!canNoticeModal) {
                    document.addEventListener('keydown', (event) => {
                        if (event.key !== 'Escape') {
                            return;
                        }
                        if (rejectDetailModalEl && rejectDetailModalEl.classList.contains('show')) {
                            closeRejectDetailModal();
                        }
                    });
                }
            }

            if (shouldHideKpiCreateUi) {
                addBtn.hidden = true;
            }

            const buildNewCardPreset = () => {
                const modeType = currentKpiMode === 'target' ? 'target' : 'report';
                const departmentCode = modeType === 'target'
                    ? normalizeDepartmentCode(currentTargetDepartment || '')
                    : normalizeDepartmentCode(currentReportDepartment || '');
                if (departmentCode === '') {
                    return null;
                }

                return {
                    mode_type: modeType,
                    target_departments: [departmentCode],
                };
            };

            if (!shouldHideKpiCreateUi) {
                addBtn.addEventListener('click', () => {
                    showConfirm('confirmAddKpiItem', () => {
                        const existingEditorCard = editorListEl.querySelector('.kpi-card');
                        if (existingEditorCard) {
                            pinCardToEditorZone(existingEditorCard);
                            focusAddedCard(existingEditorCard);
                            return;
                        }
                        const cardEl = createCard(buildNewCardPreset());
                        editorListEl.appendChild(cardEl);
                        pinCardToEditorZone(cardEl);
                        ensureCardOrder();
                        applyKpiModeFilterToCards();
                        focusAddedCard(cardEl);
                    });
                });
            }

            if (Array.isArray(savedCards) && savedCards.length > 0) {
                savedCards.forEach((savedCard) => {
                    const cardEl = createCard(savedCard);
                    listEl.appendChild(cardEl);
                });
            }
            ensureCardOrder();
            applyKpiModeFilterToCards();
            updateEditorZoneState();
            if (notificationFocusState.active) {
                setTimeout(() => {
                    focusKpiMonthFromNotification();
                }, 120);
            }

            const refreshAllTexts = () => {
                ensureCardOrder();
                if (!shouldHideKpiCreateUi) {
                    addBtn.textContent = getText('kpiAddCard');
                }
                updateEditorZoneState();
                renderKpiModeTabs();
                renderKpiAvgByDepartment(kpiAverageByDepartmentState);
                applyKpiModeFilterToCards();
                if (noticeFabEl) {
                    const noticeFabLabel = getText('kpiNoticeHint');
                    noticeFabEl.setAttribute('aria-label', noticeFabLabel);
                    noticeFabEl.setAttribute('title', noticeFabLabel);
                }
                renderNoticeModalContent();
            };

            const langThBtn = document.getElementById('lang-th');
            const langEnBtn = document.getElementById('lang-en');
            if (langThBtn) {
                langThBtn.addEventListener('click', () => setTimeout(refreshAllTexts, 0));
            }
            if (langEnBtn) {
                langEnBtn.addEventListener('click', () => setTimeout(refreshAllTexts, 0));
            }

            document.addEventListener('app:lang-changed', () => {
                refreshAllTexts();
            });
        })();
    </script>
@endsection
