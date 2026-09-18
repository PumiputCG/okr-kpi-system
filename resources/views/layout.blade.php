<!DOCTYPE html>
<html lang="{{ request('lang', 'en') === 'th' ? 'th' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OKR KPI System')</title>
    <style>
        :root {
            --pink: #e83e8c;
            --white: #ffffff;
            --black: #111111;
            --muted: #6b7280;
            --soft: #fff7fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', 'Noto Sans Thai', Arial, sans-serif;
            color: var(--black);
            background: var(--white);
        }

        .app-grid {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
            overflow: visible;
        }

        .side-rail {
            background: var(--white);
            color: var(--black);
            padding: 22px 18px;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 24px rgba(0, 0, 0, 0.06);
            z-index: 3;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .side-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            min-height: 46px;
        }

        .side-brand-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .side-brand-text {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .menu-block {
            margin-top: 22px;
        }

        .menu-title {
            margin: 0 0 6px;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .nav-stack {
            display: grid;
            gap: 9px;
            margin-top: 16px;
        }

        .nav-btn {
            display: block;
            width: 100%;
            text-decoration: none;
            color: var(--black);
            background: transparent;
            font-size: 18px;
            font-weight: 400;
            padding: 12px 12px;
            border-left: 4px solid transparent;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .nav-btn:hover {
            background: var(--pink);
            color: var(--white);
            border-left-color: var(--pink);
        }

        .nav-btn.is-active {
            background: #fff1f8;
            color: var(--black);
            border-left-color: var(--pink);
        }

        .logout-wrap {
            margin-top: auto;
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.78), #ffffff 28%);
            padding-top: 12px;
        }

        .logout-btn {
            width: 100%;
            border: 0;
            background: var(--pink);
            color: var(--white);
            font-size: 15px;
            font-weight: 400;
            padding: 11px 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .logout-btn:hover {
            background: var(--black);
        }

        .main-shell {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            overflow: visible;
            background: linear-gradient(180deg, #ffffff 0%, #fff9fc 100%);
        }

        .top-strip {
            min-height: 78px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 26px;
            position: sticky;
            top: 0;
            z-index: 5;
            box-shadow: 0 3px 10px rgba(17, 17, 17, 0.05);
        }

        .page-head {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .page-head-title {
            font-size: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--black);
            white-space: nowrap;
        }

        .page-head-title[data-i18n="menuKpiInput"],
        .page-head-title[data-i18n="menuKpiDept"],
        .page-head-title[data-i18n="menuOkrAllDept"] {
            text-transform: none;
        }

        .lang-switch {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .top-strip-tools {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .notify-wrap {
            position: relative;
        }

        .notify-btn {
            border: none;
            background: transparent;
            color: #e83e8c;
            min-width: auto;
            height: auto;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
        }

        .notify-btn:hover {
            background: transparent;
        }

        .notify-icon {
            width: 28px;
            height: 28px;
            display: block;
        }

        .notify-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 9px;
            background: #dc2626;
            color: #ffffff;
            font-size: 10px;
            line-height: 18px;
            text-align: center;
            font-weight: 700;
            display: none;
        }

        .notify-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: min(92vw, 380px);
            max-height: 420px;
            overflow: auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.16);
            z-index: 50;
            display: none;
        }

        .notify-panel.show {
            display: block;
        }

        .notify-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 2;
        }

        .notify-head-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .notify-mark-read {
            border: 0;
            background: transparent;
            color: #2563eb;
            font-size: 12px;
            cursor: pointer;
            padding: 0;
        }

        .notify-tabs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .notify-tab {
            border: 0;
            background: transparent;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            cursor: pointer;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .notify-tab.is-active {
            background: #ffffff;
            color: #111827;
            box-shadow: inset 0 -2px 0 #111827;
        }

        .notify-tab-label {
            line-height: 1;
        }

        .notify-tab-count {
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 8px;
            background: #dc2626;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
            display: none;
        }

        .notify-list {
            display: grid;
        }

        .notify-item {
            border: 0;
            border-bottom: 1px solid #f3f4f6;
            background: #ffffff;
            text-align: left;
            padding: 10px 12px;
            cursor: pointer;
            width: 100%;
        }

        .notify-item:hover {
            background: #f9fafb;
        }

        .notify-item.unread {
            background: #fef2f2;
        }

        .notify-item-title {
            margin: 0 0 3px;
            font-size: 12px;
            color: #111827;
            font-weight: 700;
        }

        .notify-item-message {
            margin: 0;
            font-size: 12px;
            color: #374151;
            line-height: 1.45;
            display: grid;
            gap: 2px;
        }

        .notify-item-line {
            margin: 0;
        }

        .notify-item-note {
            margin: 0;
            color: #111827;
            font-weight: 700;
        }

        .notify-item-label {
            font-weight: 700;
            color: #111827;
        }

        .notify-item-value.is-approved {
            color: #15803d;
            font-weight: 700;
        }

        .notify-item-value.is-rejected {
            color: #b91c1c;
            font-weight: 700;
        }

        .notify-item-time {
            margin: 6px 0 0;
            font-size: 11px;
            color: #9ca3af;
        }

        .notify-empty {
            padding: 14px 12px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }

        .employee-brief {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
            min-width: 0;
        }

        .employee-brief-row {
            margin: 0;
            display: flex;
            align-items: baseline;
            gap: 6px;
            font-size: 11px;
            line-height: 1.3;
            color: #6b7280;
            white-space: nowrap;
            max-width: min(46vw, 420px);
        }

        .employee-brief-label {
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 0.01em;
        }

        .employee-brief-value {
            color: #374151;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lang-btn {
            border: 0;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 8px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .lang-btn.active {
            color: var(--pink);
        }

        .work-area {
            flex: 1;
            position: relative;
            overflow: visible;
            background:
                linear-gradient(90deg, rgba(232, 62, 140, 0.07) 0, rgba(232, 62, 140, 0.07) 24px, transparent 24px),
                linear-gradient(180deg, #ffffff 0%, #fff9fc 100%);
        }


        .page-wrap {
            position: relative;
            z-index: 1;
            padding: 30px 34px;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1000;
        }

        .modal-backdrop.show {
            display: flex;
        }

        .modal-panel {
            width: min(96vw, 460px);
            background: var(--white);
            padding: 18px;
        }

        .modal-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .modal-text {
            margin: 12px 0 0;
            color: #374151;
            font-size: 14px;
            line-height: 1.45;
        }

        .alert-row {
            margin-top: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-icon {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            border-radius: 999px;
            background: #f3f4f6;
            color: #111827;
            flex: 0 0 22px;
        }

        .alert-icon.success {
            background: #dcfce7;
            color: #15803d;
        }

        .modal-actions {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-ghost {
            border: 0;
            background: #f3f4f6;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            cursor: pointer;
        }

        .btn-primary {
            border: 0;
            background: var(--pink);
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #c72f75;
        }

        .global-visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
            white-space: nowrap;
        }

        .global-notice-fab {
            position: fixed;
            right: 18px;
            bottom: 20px;
            width: 68px;
            height: 68px;
            padding: 0;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(145deg, #f472b6 0%, #db2777 56%, #be185d 100%);
            color: #f8fafc;
            box-shadow: 0 18px 30px rgba(157, 23, 77, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            z-index: 2147482950;
            touch-action: none;
            user-select: none;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
        }

        .global-notice-fab-icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .global-notice-fab svg {
            width: 44px;
            height: 44px;
            display: block;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            filter: drop-shadow(0 2px 4px rgba(131, 24, 67, 0.3));
        }

        .global-notice-fab-text,
        .global-notice-fab-dot {
            display: none;
        }

        .global-notice-fab:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 24px 38px rgba(157, 23, 77, 0.44);
            background: linear-gradient(145deg, #fb72bd 0%, #e11d7b 56%, #be185d 100%);
        }

        .global-notice-fab:focus-visible {
            outline: 3px solid #f9a8d4;
            outline-offset: 2px;
        }

        .global-notice-fab.is-dragging {
            cursor: grabbing;
            transform: none;
            transition: none;
        }

        .global-kpi-notice-modal {
            position: fixed;
            inset: 0;
            z-index: 2147483400;
            background: transparent;
            display: none;
            pointer-events: none;
        }

        .global-kpi-notice-modal.show {
            display: block;
        }

        .global-kpi-notice-panel {
            position: absolute;
            top: 18px;
            right: 12px;
            width: min(1440px, calc(100vw - 24px));
            min-width: min(480px, calc(100vw - 18px));
            max-height: calc(100vh - 36px);
            min-height: 260px;
            background: #ffffff;
            border: 1px solid #d8e0ed;
            border-radius: 14px;
            box-shadow: 0 22px 46px rgba(15, 23, 42, 0.24);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            pointer-events: auto;
            transition: box-shadow 0.1s ease;
            will-change: top, left, width, height;
        }

        .global-kpi-notice-panel.is-expanded {
            top: 0;
            right: 0;
            left: 0;
            width: auto;
            max-height: 100vh;
            min-height: 100vh;
            border-radius: 0;
        }

        .global-kpi-notice-panel.is-moving {
            box-shadow: 0 26px 50px rgba(15, 23, 42, 0.34);
        }

        .global-kpi-notice-panel.is-resizing {
            box-shadow: 0 28px 56px rgba(15, 23, 42, 0.36);
        }

        .global-kpi-notice-head {
            display: grid;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #f8fafc 0%, #eff6ff 100%);
            cursor: move;
            user-select: none;
            flex-shrink: 0;
        }

        .global-kpi-notice-title {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0.01em;
            text-align: left;
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .global-kpi-notice-head-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%;
        }

        .global-kpi-notice-action,
        .global-kpi-notice-close {
            width: 28px;
            height: 28px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #ffffff;
            color: #334155;
            font-size: 14px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
            transition: background 0.16s ease, color 0.16s ease, border-color 0.16s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            user-select: none;
        }

        .global-kpi-notice-action:hover,
        .global-kpi-notice-close:hover {
            background: #e2e8f0;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .global-kpi-notice-action.is-active {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        .global-kpi-notice-sortbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-start;
            min-height: 28px;
            flex: 1 1 620px;
        }

        .global-kpi-notice-sort-label,
        .global-kpi-notice-level-label {
            color: #334155;
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
        }

        .global-kpi-notice-sort-label {
            color: #0f172a;
        }

        .global-kpi-notice-reset-filter {
            width: auto;
            min-width: 58px;
            padding: 0 9px;
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
        }

        .global-kpi-notice-filter-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .global-kpi-notice-ceo-filter {
            width: auto;
            min-width: 64px;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
        }

        .global-kpi-notice-panel.is-ceo-view .global-kpi-notice-secondary-filter {
            display: none;
        }

        .global-kpi-notice-action[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .global-kpi-notice-dept-filter {
            width: auto;
            min-width: 80px;
            max-width: 140px;
            padding: 0 6px;
            font-size: 12px;
            font-weight: 400;
            cursor: pointer;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #374151;
        }
        .global-kpi-notice-dept-filter.is-active {
            background: #eef2ff;
            border-color: #818cf8;
            color: #4338ca;
        }

        #global-kpi-notice-pan {
            color: #111827;
            padding: 0;
        }

        #global-kpi-notice-pan:hover,
        #global-kpi-notice-pan.is-active {
            color: #111827;
        }

        #global-kpi-notice-pan .global-kpi-notice-pan-icon {
            width: 18px;
            height: 18px;
            display: block;
            fill: none;
            stroke: #111827;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .global-kpi-notice-zoom-value {
            min-width: 46px;
            height: 28px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .global-kpi-notice-body {
            flex: 1 1 auto;
            overflow: auto;
            padding: 0;
            background: #f8fafc;
            cursor: auto;
            display: flex;
            flex-direction: column;
        }

        .global-kpi-notice-content {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            gap: 0;
            transform-origin: top left;
            width: 100%;
        }

        .global-kpi-notice-body.is-pan-enabled {
            cursor: grab;
            touch-action: none;
        }

        .global-kpi-notice-body.is-pan-enabled.is-panning {
            cursor: grabbing;
        }

        .global-kpi-notice-resize-handle {
            position: absolute;
            z-index: 3;
            pointer-events: auto;
            user-select: none;
            touch-action: none;
        }

        .global-kpi-notice-resize-handle[data-resize="n"] {
            top: 0; left: 16px; right: 16px; height: 8px; cursor: n-resize;
        }
        .global-kpi-notice-resize-handle[data-resize="s"] {
            bottom: 0; left: 16px; right: 16px; height: 8px; cursor: s-resize;
        }
        .global-kpi-notice-resize-handle[data-resize="w"] {
            top: 16px; bottom: 16px; left: 0; width: 8px; cursor: w-resize;
        }
        .global-kpi-notice-resize-handle[data-resize="e"] {
            top: 16px; bottom: 16px; right: 0; width: 8px; cursor: e-resize;
        }
        .global-kpi-notice-resize-handle[data-resize="nw"] {
            top: 0; left: 0; width: 20px; height: 20px; cursor: nw-resize; z-index: 4;
        }
        .global-kpi-notice-resize-handle[data-resize="ne"] {
            top: 0; right: 0; width: 20px; height: 20px; cursor: ne-resize; z-index: 4;
        }
        .global-kpi-notice-resize-handle[data-resize="sw"] {
            bottom: 0; left: 0; width: 20px; height: 20px; cursor: sw-resize; z-index: 4;
        }
        .global-kpi-notice-resize-handle[data-resize="se"] {
            bottom: 0; right: 0; width: 20px; height: 20px; cursor: se-resize; z-index: 4;
        }

        .global-kpi-notice-section-title {
            display: none;
        }

        .global-kpi-notice-section {
            border: none;
            background: #ffffff;
            padding: 0;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            border-radius: 12px;
        }

        .global-kpi-notice-section-title {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
        }

        .global-kpi-notice-list {
            display: grid;
            gap: 10px;
        }

        #global-kpi-notice-level2-list {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        #global-kpi-notice-level2-list .global-kpi-notice-item {
            flex: 0 0 min(460px, 88vw);
            min-width: min(460px, 88vw);
        }

        .global-kpi-notice-item {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 12px;
            display: grid;
            gap: 8px;
            border-radius: 10px;
        }

        .global-kpi-notice-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            align-items: start;
        }

        .global-kpi-notice-label {
            font-weight: 400;
            color: #111827;
            font-size: 13px;
        }

        .global-kpi-notice-value {
            color: #1f2937;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .global-kpi-notice-files {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 4px;
        }

        .global-kpi-notice-files a {
            color: #1d4ed8;
            text-decoration: none;
        }

        .global-kpi-notice-files a:hover {
            text-decoration: underline;
        }

        .global-kpi-notice-empty {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .global-kpi-notice-table-wrap {
            border: 1px solid #f3f4f6;
            overflow: auto;
            background: #ffffff;
        }

        .global-kpi-notice-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .global-kpi-notice-table th,
        .global-kpi-notice-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }

        .global-kpi-notice-table th {
            background: #eef2ff;
            color: #1e293b;
            font-weight: 700;
            white-space: nowrap;
        }

        .okr-notice-tree {
            display: grid;
            gap: 12px;
        }

        .okr-notice-obj {
            border: 2px solid #fce7f3;
            background: #fff7fb;
            border-radius: 10px;
            padding: 12px 14px;
            display: grid;
            gap: 8px;
        }

        .okr-notice-obj-head {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .okr-notice-badge-l1 {
            background: #e83e8c;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 5px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .okr-notice-badge-l2 {
            background: #7c3aed;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 5px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .okr-notice-obj-title {
            font-size: 14px;
            font-weight: 700;
            color: #9d174d;
        }

        .okr-notice-kr-title {
            font-size: 13px;
            font-weight: 700;
            color: #5b21b6;
        }

        .okr-notice-obj-detail,
        .okr-notice-kr-detail {
            font-size: 13px;
            color: #374151;
            white-space: pre-wrap;
            word-break: break-word;
            margin-left: 2px;
        }

        .okr-notice-file-link {
            font-size: 12px;
            color: #6d28d9;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .okr-notice-file-link:hover {
            text-decoration: underline;
        }

        .okr-notice-kr-list {
            display: grid;
            gap: 8px;
            margin-left: 8px;
            padding-left: 12px;
            border-left: 3px solid #e9d5ff;
        }

        .okr-notice-kr {
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 8px;
            padding: 10px 12px;
            display: grid;
            gap: 6px;
        }

        .okr-notice-kr-head {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .okr-notice-kr-dept {
            font-size: 11px;
            background: #ddd6fe;
            color: #5b21b6;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 700;
        }

        .okr-notice-kpi-wrap {
            margin-top: 6px;
            overflow-x: auto;
            border-radius: 6px;
        }

        .okr-notice-kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: #0e7490;
            margin-bottom: 4px;
        }

        .okr-notice-kpi-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 580px;
            font-size: 12px;
        }

        .okr-notice-kpi-table th,
        .okr-notice-kpi-table td {
            border: 1px solid #a5f3fc;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }

        .okr-notice-kpi-table th {
            background: #0891b2;
            color: #fff;
            font-weight: 700;
            white-space: nowrap;
        }

        .okr-notice-kpi-table tr:nth-child(even) td {
            background: #f0fdff;
        }

        .okr-notice-empty-tree {
            color: #9ca3af;
            font-size: 13px;
            font-style: italic;
            margin: 0;
        }

        .okr-notice-excel-wrap {
            border: 0;
            background: #ffffff;
            overflow: auto;
            flex: 1 1 auto;
            min-height: 0;
        }

        .okr-notice-excel-table {
            width: 100%;
            min-width: 1560px;
            table-layout: fixed;
            border-collapse: collapse;
            color: #111827;
        }

        .okr-notice-excel-table th,
        .okr-notice-excel-table td {
            border: 1px solid #d1d5db;
            padding: 10px 9px;
            font-size: 13px;
            line-height: 1.55;
            text-align: center;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
        }

        .okr-notice-excel-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .okr-notice-excel-table thead th {
            font-weight: 800;
            vertical-align: middle;
        }

        .okr-notice-excel-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .okr-notice-excel-table tbody tr:hover td {
            filter: brightness(0.985);
        }

        .okr-notice-excel-table tbody td {
            text-align: left;
            font-weight: 400;
            white-space: pre-line;
        }

        .okr-notice-excel-table tbody td:first-child {
            text-align: center;
            font-weight: 400;
        }

        .okr-notice-excel-table tbody .okr-notice-col-l1 {
            text-align: left;
            vertical-align: top;
        }

        .okr-notice-excel-table .okr-notice-col-l2,
        .okr-notice-excel-table .okr-notice-col-l3 {
            text-align: center;
            vertical-align: middle;
        }

        .okr-notice-excel-table .okr-notice-align-center {
            text-align: center;
            vertical-align: middle;
        }

        .okr-notice-excel-table tbody .okr-notice-col-l1.okr-notice-align-center {
            text-align: center;
            vertical-align: middle;
        }

        .okr-notice-excel-table .okr-notice-group-l1 {
            background: #fdf2f8;
            color: #9d174d;
        }

        .okr-notice-excel-table .okr-notice-group-l2 {
            background: #f5f3ff;
            color: #5b21b6;
        }

        .okr-notice-excel-table .okr-notice-group-l3 {
            background: #ecfeff;
            color: #0e7490;
        }

        .okr-notice-excel-table .okr-notice-col-l1 {
            background: #fff7fb;
        }

        .okr-notice-excel-table .okr-notice-col-l2 {
            background: #faf5ff;
        }

        .okr-notice-excel-table .okr-notice-col-l3 {
            background: #f0fdff;
        }

        .okr-notice-excel-table.is-ceo-only {
            min-width: 0;
        }

        .okr-notice-excel-table.is-ceo-only th,
        .okr-notice-excel-table.is-ceo-only td {
            padding: 16px 18px;
            font-size: 14px;
            line-height: 1.65;
        }

        .okr-notice-excel-table.is-ceo-only thead th {
            font-size: 15px;
        }

        .okr-notice-excel-table .okr-notice-row-empty td {
            color: #9ca3af;
            font-style: italic;
        }

        .okr-notice-file-link {
            color: #1d4ed8;
            font-weight: 400;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .okr-notice-source {
            display: grid;
            gap: 2px;
            justify-items: center;
        }

        .okr-notice-source-main {
            font-weight: 400;
        }

        .okr-notice-source-meta {
            color: #475569;
            font-size: 11px;
        }

        @media (max-width: 980px) {
            .app-grid {
                grid-template-columns: 1fr;
            }

            .side-rail {
                box-shadow: none;
                padding: 16px;
                height: auto;
                position: relative;
            }

            .nav-stack {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .top-strip {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 8px 12px;
            }

            .page-head {
                align-items: center;
            }

            .top-strip-tools {
                margin-left: auto;
            }

            .employee-brief-row {
                max-width: 62vw;
            }


            .page-wrap {
                padding: 20px 16px 28px;
            }

            .global-notice-fab {
                width: 58px;
                height: 58px;
                right: 12px;
                bottom: 12px;
            }

            .global-notice-fab-icon-wrap {
                width: auto;
                height: auto;
                flex-basis: auto;
            }

            .global-notice-fab svg {
                width: 38px;
                height: 38px;
            }


            .global-kpi-notice-row {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .global-kpi-notice-panel {
                top: 10px;
                right: 10px;
                left: 10px;
                width: auto;
                min-width: 0;
                max-height: calc(100vh - 20px);
                min-height: 0;
            }

            .global-kpi-notice-panel.is-expanded {
                top: 0;
                right: 0;
                left: 0;
                min-height: 100vh;
                max-height: 100vh;
                border-radius: 0;
            }

            .global-kpi-notice-head {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .global-kpi-notice-title {
                flex-basis: 100%;
            }

            .global-kpi-notice-head-actions {
                gap: 4px;
                justify-content: flex-start;
                width: 100%;
            }

            .global-kpi-notice-sortbar {
                justify-content: flex-start;
            }

            .global-kpi-notice-resize-handle {
                display: none;
            }
        }

    </style>
</head>
<body>
    @php
        $currentLang = request('lang', 'en');
        /** @var \App\Models\AppUser|null $authUser */
        $authUser = auth()->user();
        $authRules = \App\Http\Controllers\AppUserAuthController::class;
        $isAdminMenu = $authRules::isAdminRole($authUser?->role);
        $detectedLevels = $authRules::resolveLevelsByPosition($authUser?->position);
        $employeeCode = trim((string) ($authUser?->employee_code ?? ''));
        $employeeNameThRaw = trim((string) ($authUser?->full_name_th ?? ''));
        $employeeNameEnRaw = trim((string) ($authUser?->full_name_en ?? ''));
        $employeeNameTh = $employeeNameThRaw !== '' ? $employeeNameThRaw : ($employeeNameEnRaw !== '' ? $employeeNameEnRaw : '-');
        $employeeNameEn = $employeeNameEnRaw !== '' ? $employeeNameEnRaw : ($employeeNameThRaw !== '' ? $employeeNameThRaw : '-');
        $employeeCodeDisplay = $employeeCode !== '' ? $employeeCode : '-';
        $employeeNameDisplay = $currentLang === 'th' ? $employeeNameTh : $employeeNameEn;
        $targetDepartments = $authUser
            ? \App\Support\DepartmentAssignmentResolver::resolveTargetDepartmentsByUserId((int) $authUser->id)
            : [];
        $hasTargetDepartmentAssignments = $targetDepartments !== [];
        $reviewerDepartments = $authUser
            ? \App\Support\DepartmentAssignmentResolver::resolveReviewerDepartmentsByUserId((int) $authUser->id)
            : [];
        $canReviewReports = Route::has('kpi.review.index') && $reviewerDepartments !== [];
        $authUserId = (int) ($authUser?->id ?? 0);
        $globalNoticeViewerDepartment = strtoupper(trim((string) ($authUser?->dept_abbr_hr ?? '')));
        $globalNoticeDataUrl = Route::has('kpi.input.notice.payload')
            ? route('kpi.input.notice.payload', ['lang' => $currentLang])
            : '';
        $showGlobalNoticeFab = $authUserId > 0 && $globalNoticeDataUrl !== '';

        $menuItems = [];
        if ($isAdminMenu) {
            if (Route::has('admin.goal.targets')) {
                $menuItems[] = ['key' => 'menuGoalTargets', 'label' => $currentLang === 'th' ? 'กำหนดเป้าหมายตัวชี้วัด' : 'Target Indicator Setup', 'href' => route('admin.goal.targets', ['lang' => $currentLang]), 'disabled' => false];
            } else {
                $menuItems[] = ['key' => 'menuHome', 'label' => $currentLang === 'th' ? 'กำหนดเป้าหมายตัวชี้วัด' : 'Target Indicator Setup', 'href' => route('home', ['lang' => $currentLang]), 'disabled' => false];
            }
            if (Route::has('admin.employee.assignments.index')) {
                $menuItems[] = ['key' => 'menuAdminAssignEmployees', 'label' => $currentLang === 'th' ? 'กำหนดพนักงาน' : 'Assign Employees', 'href' => route('admin.employee.assignments.index', ['lang' => $currentLang]), 'disabled' => false];
            }
        }
        $menuItems[] = ['key' => 'menuProfile', 'label' => $currentLang === 'th' ? 'โปรไฟล์' : 'Profile', 'href' => route('profile.edit', ['lang' => $currentLang]), 'disabled' => false];

        $menuVisibilityRules = config('menu_visibility.buttons', []);
        $normalizeMenuPosition = static function (?string $value): string {
            $text = trim((string) ($value ?? ''));
            if ($text === '') {
                return '';
            }

            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

            return strtolower($text);
        };
        $canSeeConfiguredMenu = static function (array $rule) use ($isAdminMenu, $detectedLevels, $authUser, $normalizeMenuPosition): bool {
            $visibleTo = is_array($rule['visible_to'] ?? null) ? $rule['visible_to'] : [];
            $allowAdmin = (bool) ($visibleTo['admin'] ?? false);
            if ($isAdminMenu) {
                return $allowAdmin;
            }

            $allowedPositions = array_values(array_unique(array_filter(array_map(
                static fn ($position): string => $normalizeMenuPosition((string) $position),
                (array) ($visibleTo['positions'] ?? [])
            ))));
            if ($allowedPositions !== []) {
                $userPosition = $normalizeMenuPosition($authUser?->position);
                if ($userPosition === '' || ! in_array($userPosition, $allowedPositions, true)) {
                    return false;
                }
            }

            $allowedLevels = array_map('intval', (array) ($visibleTo['levels'] ?? []));
            if ($allowedLevels === []) {
                return $allowedPositions !== [];
            }

            foreach ($allowedLevels as $allowedLevel) {
                if (in_array($allowedLevel, $detectedLevels, true)) {
                    return true;
                }
            }

            return false;
        };
        $appendConfiguredMenu = function (string $menuKey) use (&$menuItems, $menuVisibilityRules, $canSeeConfiguredMenu, $currentLang, $isAdminMenu, $hasTargetDepartmentAssignments): void {
            $rule = $menuVisibilityRules[$menuKey] ?? null;
            $canSeeByTargetAssignment = ! $isAdminMenu
                && $hasTargetDepartmentAssignments
                && in_array($menuKey, ['menuOkrDept', 'menuKpiDept'], true);
            if (! is_array($rule) || (! $canSeeConfiguredMenu($rule) && ! $canSeeByTargetAssignment)) {
                return;
            }

            $routeName = (string) ($rule['route'] ?? '');
            if ($routeName === '') {
                return;
            }

            $labels = is_array($rule['label'] ?? null) ? $rule['label'] : [];
            $menuItems[] = [
                'key' => $menuKey,
                'label' => (string) ($labels[$currentLang] ?? ($labels['en'] ?? $menuKey)),
                'href' => route($routeName, ['lang' => $currentLang]),
                'disabled' => false,
            ];
        };

        if ($isAdminMenu) {
            $menuItems[] = ['key' => 'menuAdminSetCycle', 'label' => 'Set Cycle', 'href' => route('admin.cycle', ['lang' => $currentLang]), 'disabled' => false];
            $appendConfiguredMenu('menuOkrAllDept');
            $appendConfiguredMenu('menuOkrDept');
            $appendConfiguredMenu('menuKpiDept');
            $menuItems[] = ['key' => 'menuAdminDownloadDoc', 'label' => 'Download Documents', 'href' => route('admin.documents', ['lang' => $currentLang]), 'disabled' => false];
            $menuItems[] = ['key' => 'menuAdminImportEmployees', 'label' => 'เพิ่มรายชื่อพนักงาน', 'href' => route('admin.employees.import', ['lang' => $currentLang]), 'disabled' => false];
        } else {
            $appendConfiguredMenu('menuOkrAllDept');
            $appendConfiguredMenu('menuOkrDept');
            $appendConfiguredMenu('menuKpiDept');
            $appendConfiguredMenu('menuKpiInput');
        }
        if ($canReviewReports) {
            $menuItems[] = [
                'key' => 'menuKpiReview',
                'label' => $currentLang === 'th' ? 'ตรวจรายงาน' : 'Review Reports',
                'href' => route('kpi.review.index', ['lang' => $currentLang]),
                'disabled' => false,
            ];
        }

        $notificationsEnabled = (bool) ($authUser && Route::has('notifications.index') && Route::has('notifications.read') && Route::has('notifications.read_all'));
        $showNotificationTabs = (bool) $canReviewReports;
        $notificationsIndexUrl = $notificationsEnabled ? route('notifications.index', ['lang' => $currentLang]) : '';
        $notificationsReadUrlTemplate = $notificationsEnabled
            ? route('notifications.read', ['notification' => '__ID__', 'lang' => $currentLang])
            : '';
        $notificationsReadAllUrl = $notificationsEnabled ? route('notifications.read_all', ['lang' => $currentLang]) : '';
    @endphp
    <div class="app-grid">
        <aside class="side-rail">
            <div class="side-brand">
                <img class="side-brand-logo" src="{{ asset('images/company-logo.png') }}" alt="Company Logo">
                <span class="side-brand-text">OKR - KPI System</span>
            </div>
            <div class="menu-block">
                <p class="menu-title" data-i18n="menuTitle">Menu:</p>
                <nav class="nav-stack">
                    @foreach ($menuItems as $item)
                        @php
                            $itemPath = trim((string) parse_url($item['href'], PHP_URL_PATH), '/');
                            $currentPath = trim(request()->path(), '/');
                            $isActiveMenu = $itemPath !== '' && (
                                $currentPath === $itemPath
                                || str_starts_with($currentPath, $itemPath.'/')
                            );
                        @endphp
                        <a class="nav-btn{{ $isActiveMenu ? ' is-active' : '' }}" href="{{ $item['href'] }}" data-i18n="{{ $item['key'] }}" @if ($item['disabled']) onclick="return false;" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <form class="logout-wrap js-confirm-action" action="{{ route('logout', ['lang' => $currentLang]) }}" method="POST" data-confirm-key="confirmLogout">
                @csrf
                <input type="hidden" name="lang" value="{{ $currentLang }}" data-lang-sync="1">
                <button class="logout-btn" type="submit" data-i18n="logout">Logout</button>
            </form>
        </aside>

        <main class="main-shell">
            @php
                $pageHeadingKey = trim($__env->yieldContent('page_heading_key')) ?: 'homeTitle';
            @endphp
            <header class="top-strip">
                <div class="page-head">
                    <span class="page-head-title" data-i18n="{{ $pageHeadingKey }}">
                        @yield('page_heading', 'Home')
                    </span>
                </div>

                <div class="top-strip-tools">
                    <div id="employee-brief" class="employee-brief" data-name-th="{{ $employeeNameTh }}" data-name-en="{{ $employeeNameEn }}">
                        <p class="employee-brief-row">
                            <span class="employee-brief-label" data-i18n="topEmployeeCode">Employee No.</span>
                            <span class="employee-brief-value">{{ $employeeCodeDisplay }}</span>
                        </p>
                        <p class="employee-brief-row">
                            <span class="employee-brief-label" data-i18n="topEmployeeName">Employee Name</span>
                            <span id="employee-name-display" class="employee-brief-value">{{ $employeeNameDisplay }}</span>
                        </p>
                    </div>
                    @if ($notificationsEnabled)
                        <div id="top-notify-wrap" class="notify-wrap">
                            <button id="top-notify-btn" class="notify-btn" type="button" aria-label="Notifications" title="Notifications">
                                <svg class="notify-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span id="top-notify-badge" class="notify-badge">0</span>
                            </button>
                            <div id="top-notify-panel" class="notify-panel" aria-hidden="true">
                                <div class="notify-head">
                                    <p class="notify-head-title" data-i18n="notifyTitle">Notifications</p>
                                    <button id="top-notify-mark-all" type="button" class="notify-mark-read" data-i18n="notifyMarkAll">Mark all read</button>
                                </div>
                                @if ($showNotificationTabs)
                                    <div class="notify-tabs">
                                        <button id="top-notify-tab-user" type="button" class="notify-tab is-active" data-tab="user">
                                            <span class="notify-tab-label" data-i18n="notifyTabUser">Users</span>
                                            <span id="top-notify-tab-user-count" class="notify-tab-count">0</span>
                                        </button>
                                        <button id="top-notify-tab-reviewer" type="button" class="notify-tab" data-tab="reviewer">
                                            <span class="notify-tab-label" data-i18n="notifyTabReviewer">Reviewers</span>
                                            <span id="top-notify-tab-reviewer-count" class="notify-tab-count">0</span>
                                        </button>
                                    </div>
                                @endif
                                <div id="top-notify-list" class="notify-list">
                                    <p class="notify-empty" data-i18n="notifyEmpty">No notifications</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="lang-switch">
                        <button id="lang-th" class="lang-btn" type="button" onclick="switchLang('th')">TH</button>
                        <button id="lang-en" class="lang-btn" type="button" onclick="switchLang('en')">EN</button>
                    </div>
                </div>
            </header>

            <section class="work-area">
                <div class="page-wrap">
                    @yield('content')
                </div>
            </section>
        </main>
    </div>

    @if ($showGlobalNoticeFab)
        <button
            id="global-notice-fab"
            type="button"
            class="global-notice-fab"
            aria-label="{{ $currentLang === 'th' ? 'ประกาศ' : 'Announcement' }}"
        >
            <span class="global-notice-fab-icon-wrap" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M13 15V7m0 8 5.504 3.145A1 1 0 0 0 20 17.277V4.723a1 1 0 0 0-1.496-.868L13 7m0 8h-3m3-8H7a4 4 0 0 0-4 4v0a4 4 0 0 0 4 4v0m0 0v4.5A1.5 1.5 0 0 0 8.5 21v0a1.5 1.5 0 0 0 1.5-1.5V15m-3 0h3"
                    />
                </svg>
            </span>
        </button>
    @endif

    <div id="global-kpi-notice-modal" class="global-kpi-notice-modal" aria-hidden="true">
        <div id="global-kpi-notice-panel" class="global-kpi-notice-panel" role="dialog" aria-modal="true" aria-labelledby="global-kpi-notice-title">
            <div class="global-kpi-notice-head">
                <h3 id="global-kpi-notice-title" class="global-kpi-notice-title" data-i18n="kpiNoticeModalTitle">Review Organization Targets</h3>
                <div class="global-kpi-notice-head-actions">
                    <div class="global-kpi-notice-sortbar" aria-label="{{ $currentLang === 'th' ? 'แสดงข้อมูลตาม' : 'Display by' }}">
                        <span class="global-kpi-notice-sort-label" data-i18n="kpiNoticeSortLabel">{{ $currentLang === 'th' ? 'แสดงข้อมูลตาม :' : 'Display by:' }}</span>
                        <button
                            id="global-kpi-notice-filter-reset"
                            type="button"
                            class="global-kpi-notice-action global-kpi-notice-reset-filter is-active"
                            data-i18n="kpiNoticeFilterAll"
                            aria-label="{{ $currentLang === 'th' ? 'ทั้งหมด' : 'All' }}"
                            title="{{ $currentLang === 'th' ? 'แสดงเป้าหมายองค์กรทั้งหมด' : 'Show all organization targets' }}"
                        >{{ $currentLang === 'th' ? 'ทั้งหมด' : 'All' }}</button>
                        <span class="global-kpi-notice-filter-group">
                            <span class="global-kpi-notice-level-label" data-i18n="kpiNoticeLevel1FilterLabel">{{ $currentLang === 'th' ? 'ระดับองค์กร' : 'Corporate Level' }}</span>
                            <button
                                id="global-kpi-notice-filter-ceo"
                                type="button"
                                class="global-kpi-notice-action global-kpi-notice-ceo-filter"
                                aria-label="{{ $currentLang === 'th' ? 'แสดงเฉพาะเป้าหมายระดับ CEO' : 'Show CEO objectives only' }}"
                                title="{{ $currentLang === 'th' ? 'แสดงเฉพาะเป้าหมายระดับ CEO' : 'Show CEO objectives only' }}"
                            >CEO</button>
                        </span>
                        <span class="global-kpi-notice-filter-group global-kpi-notice-secondary-filter">
                            <label class="global-kpi-notice-level-label" for="global-kpi-notice-l2-filter" data-i18n="kpiNoticeLevel2FilterLabel">{{ $currentLang === 'th' ? 'ระดับสายงาน' : 'Functional Level' }}</label>
                            <select
                                id="global-kpi-notice-l2-filter"
                                class="global-kpi-notice-action global-kpi-notice-dept-filter"
                                aria-label="{{ $currentLang === 'th' ? 'กรองเป้าหมายระดับกลยุทธ์สายงาน' : 'Filter functional-level strategic objectives' }}"
                                title="{{ $currentLang === 'th' ? 'กรองเป้าหมายตามหน่วยงานระดับกลยุทธ์สายงาน' : 'Filter objectives by responsible function' }}"
                            >
                                <option value="">{{ $currentLang === 'th' ? 'ทุกแผนก' : 'All depts' }}</option>
                            </select>
                        </span>
                        <span class="global-kpi-notice-filter-group global-kpi-notice-secondary-filter">
                            <label class="global-kpi-notice-level-label" for="global-kpi-notice-dept-filter" data-i18n="kpiNoticeLevel3FilterLabel">{{ $currentLang === 'th' ? 'ระดับแผนก' : 'Department Level' }}</label>
                            <select
                                id="global-kpi-notice-dept-filter"
                                class="global-kpi-notice-action global-kpi-notice-dept-filter"
                                aria-label="{{ $currentLang === 'th' ? 'กรองเป้าหมายการดำเนินงานระดับแผนก' : 'Filter department-level operational objectives' }}"
                                title="{{ $currentLang === 'th' ? 'กรองเป้าหมายตามหน่วยงานเป้าหมาย' : 'Filter objectives by target department' }}"
                            >
                                <option value="">{{ $currentLang === 'th' ? 'ทุกแผนก' : 'All depts' }}</option>
                            </select>
                        </span>
                    </div>
                    <button id="global-kpi-notice-zoom-in" type="button" class="global-kpi-notice-action" aria-label="Zoom in" title="Zoom in">&#43;</button>
                    <button id="global-kpi-notice-zoom-out" type="button" class="global-kpi-notice-action" aria-label="Zoom out" title="Zoom out">&#8722;</button>
                    <button id="global-kpi-notice-pan" type="button" class="global-kpi-notice-action" aria-label="Hand tool" title="Hand tool">
                        <svg class="global-kpi-notice-pan-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.4 18.7V10a1.1 1.1 0 1 1 2.2 0v3.9V8.5a1.1 1.1 0 1 1 2.2 0v5.4V7.6a1.1 1.1 0 1 1 2.2 0v6.3V8.9a1.1 1.1 0 1 1 2.2 0v7.4a4.9 4.9 0 0 1-4.9 4.9h-.8a5 5 0 0 1-5-5v-1.1a1.5 1.5 0 0 0-1.1-1.4l-.5-.1" />
                        </svg>
                    </button>
                    <button id="global-kpi-notice-expand" type="button" class="global-kpi-notice-action" aria-label="Expand" title="Expand">&#x26F6;</button>
                    <button id="global-kpi-notice-close" type="button" class="global-kpi-notice-close" aria-label="Close" title="Close">&#x2715;</button>
                </div>
            </div>
            <div id="global-kpi-notice-body" class="global-kpi-notice-body">
                <div id="global-kpi-notice-content" class="global-kpi-notice-content">
                    <section id="global-kpi-notice-okr-section" class="global-kpi-notice-section" hidden>
                        <h4 class="global-kpi-notice-section-title" data-i18n="kpiNoticeOkrSectionTitle">Organization Goal Hierarchy</h4>
                        <div id="global-kpi-notice-okr-tree" class="okr-notice-excel-wrap"></div>
                    </section>
                </div>
            </div>
            <div class="global-kpi-notice-resize-handle" data-resize="n"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="s"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="w"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="e"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="nw"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="ne"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="sw"></div>
            <div class="global-kpi-notice-resize-handle" data-resize="se"></div>
        </div>
    </div>

    <div id="confirm-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <h3 id="confirm-title" class="modal-title" data-i18n="modalConfirmTitle">Confirm Action</h3>
            <p id="confirm-message" class="modal-text"></p>
            <div class="modal-actions">
                <button id="confirm-ok" type="button" class="btn-primary" data-i18n="modalConfirm">Confirm</button>
                <button id="confirm-cancel" type="button" class="btn-ghost" data-i18n="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <div id="alert-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="alert-title">
            <h3 id="alert-title" class="modal-title" data-i18n="modalAlertTitle">Notice</h3>
            <div class="alert-row">
                <span id="alert-icon" class="alert-icon">!</span>
                <p id="alert-message" class="modal-text" style="margin: 0;"></p>
            </div>
            <div class="modal-actions">
                <button id="alert-close" type="button" class="btn-primary" data-i18n="modalClose">Close</button>
            </div>
        </div>
    </div>

    <script>
        const translations = {
            en: {
                menuTitle: 'Menu:',
                topEmployeeCode: 'Employee No.',
                topEmployeeName: 'Employee Name',
                menuHome: 'Target Indicator Setup',
                menuProfile: 'Profile',
                menuKpiInput: 'Add KPIs',
                menuKpiReview: 'Review Reports',
                menuKpiDept: 'Report Summary',
                menuOkrDept: 'Overall Score Summary',
                menuOkrAllDept: 'OKRs Score Summary',
                menuAdminSetCycle: 'Set Cycle',
                menuAdminAssignEmployees: 'Assign Employees',
                menuAdminDownloadDoc: 'Download Documents',
                menuAdminImportEmployees: 'Add Employee List',
                docsTitle: 'Download Documents',
                docsSubtitle: 'Choose a cycle and download its Excel file.',
                docsNo: 'No.',
                docsCycleName: 'Cycle Name',
                docsPeriod: 'Period',
                docsManage: 'Manage',
                docsDownloadExcel: 'Download Excel',
                docsRangeTitle: 'Download Excel by Month Range',
                docsRangeHelp: 'Select cycle and months for Excel export.',
                docsRangeCycle: 'Cycle',
                docsRangeMonths: 'Months',
                docsRangeFrom: 'From Month',
                docsRangeTo: 'To Month',
                docsRangeDownload: 'Download Excel',
                docsRangeNoCycle: 'No cycle available.',
                docsRangeRoutePending: 'Waiting for Controller/Route integration for month-range download.',
                docsRangeInvalid: 'Please select at least one month.',
                docsEmpty: 'No cycle data yet.',
                logout: 'Logout',
                homeTitle: 'Home',
                homeTargetIndicatorTitle: 'Indicator Target Setup',
                homeAssignEmployeesBtn: 'Assign Employees',
                employeeAssignPageTitle: 'Assign Employees',
                homeStatus: 'Signed in with AppUser successfully.',
                labelEmployeeCode: 'Username',
                labelName: 'Name',
                labelRole: 'Role',
                profileTitle: 'Profile',
                profileSubtitle: 'Review your account details and update your account safely.',
                profileSectionUser: 'User Information',
                profileSectionSecurity: 'Security',
                profileSectionPhoto: 'Profile Photo',
                labelFullNameTh: 'Full Name (TH)',
                labelFullNameEn: 'Full Name (EN)',
                labelUsernameProfile: 'Username',
                labelPositionProfile: 'Position',
                labelEmployeeType: 'Employee Type',
                labelDepartment: 'Department',
                labelRoleProfile: 'Role',
                labelIdentityCode: 'National ID / Social Security No.',
                labelNewPassword: 'New Password',
                labelNewPasswordConfirm: 'Confirm New Password',
                hintPasswordMin8: 'At least 8 characters',
                hintIdentityCode: 'Enter and verify National ID / Social Security No. first.',
                btnVerifyIdentity: 'Verify',
                btnSave: 'Save',
                btnSavePassword: 'Save Password',
                btnSaveProfilePhoto: 'Save Photo',
                profilePhotoHint: 'Supported: .jpg .jpeg .png .webp .heic .heif, up to 50MB',
                profilePhotoAutoHint: 'Choose a photo and it will update immediately.',
                confirmLogout: 'Do you want to log out now?',
                confirmSavePassword: 'Do you want to change the password now?',
                confirmSavePhoto: 'Do you want to update the profile photo now?',
                confirmVerifyIdentity: 'Do you want to verify identity now?',
                confirmSubmit: 'Do you want to continue?',
                modalConfirmTitle: 'Confirm Action',
                modalAlertTitle: 'Notice',
                modalCancel: 'Cancel',
                modalConfirm: 'Confirm',
                modalClose: 'Close',
                notifyTitle: 'Notifications',
                notifyMarkAll: 'Mark all read',
                notifyEmpty: 'No notifications',
                notifyEmptyUser: 'No user notifications',
                notifyEmptyReviewer: 'No reviewer notifications',
                notifyTabUser: 'Users',
                notifyTabReviewer: 'Reviewers',
                notifyKpiReportTitle: 'KPI report',
                notifyKpiReviewedDescription: 'KPI report has been reviewed.',
                notifyKpiReviewRequestDescription: 'You received a KPI report. Please review it.',
                notifyKpiTopicLabel: 'Topic',
                notifyKpiMonthLabel: 'Month',
                notifyKpiStatusLabel: 'Status',
                notifyKpiFromLabel: 'From',
                notifyKpiDepartmentLabel: 'Department',
                identityVerified: 'Identity verification successful.',
                identityCodeValid: 'National ID / Social Security No. is valid.',
                identityNotVerified: 'Please verify identity first.',
                verifyFailed: 'Unable to verify identity.',
                networkVerifyError: 'Network error while verifying identity.',
                photoPlaceholder: 'No Photo',
                placeholderPageTitle: 'Page in progress',
                placeholderPageDescription: 'This page is prepared as a white placeholder and will be implemented next.',
                kpiIntroTitle: 'Add KPIs',
                kpiIntroDescription: 'Please review the objective and target details before defining targets and recording monthly KPI scores.',
                kpiAvgLabel: 'Average Individual KPIs Result',
                kpiNoticeTitle: 'Review Organization Targets',
                kpiNoticeHint: 'Review Organization Targets',
                globalNoticeFabLabel: 'Announcement',
                kpiNoticeModalTitle: 'Review Organization Targets',
                kpiNoticeModalClose: 'Close',
                kpiNoticeModalExpand: 'Expand',
                kpiNoticeModalRestore: 'Restore',
                kpiNoticeModalCollapse: 'Collapse',
                kpiNoticeModalExpandBody: 'Expand content',
                kpiNoticeModalZoomIn: 'Zoom in',
                kpiNoticeModalZoomOut: 'Zoom out',
                kpiNoticeModalPanEnable: 'Enable hand tool',
                kpiNoticeModalPanDisable: 'Disable hand tool',
                kpiNoticeSectionLevel1: 'Corporate Level · CEO',
                kpiNoticeSectionLevel2: 'Functional Level · GM / DM / AM / Manager',
                kpiNoticeSectionLevel3: 'Department Level · AM / Manager',
                gtLevel1Header: 'Level 1 · CEO',
                gtLevel2Header: 'Level 2 · GM / DM / AM / Mgr.',
                gtLevel3Header: 'Level 3 · AM / Mgr.',
                kpiNoticeSortLabel: 'Display by:',
                kpiNoticeLevel1FilterLabel: 'Corporate Level',
                kpiNoticeLevel2FilterLabel: 'Functional Level',
                kpiNoticeLevel3FilterLabel: 'Department Level',
                kpiNoticeFilterAll: 'All',
                kpiNoticeFilterCeo: 'CEO',
                kpiNoticeCeoFilterShow: 'Show CEO objectives only',
                kpiNoticeDeptLabel: 'Function',
                kpiNoticeTitleLabel: 'Objective',
                kpiNoticeDetailLabel: 'Objective Description',
                kpiNoticeFilesLabel: 'Reference Documents',
                kpiNoticeLevel1ObjectiveLabel: 'Corporate Objective',
                kpiNoticeLevel1DetailLabel: 'Details',
                kpiNoticeLevel1FileLabel: 'Documents',
                kpiNoticeLevel2ObjectiveLabel: 'Functional Objective',
                kpiNoticeLevel2DetailLabel: 'Details',
                kpiNoticeLevel2FileLabel: 'Documents',
                kpiNoticeLevel3OwnerLabel: 'Owner',
                kpiNoticeLevel3TargetDeptLabel: 'Target Department',
                kpiNoticeLevel3ObjectiveLabel: 'Department Objective',
                kpiNoticeLevel3DetailLabel: 'Details',
                kpiNoticeLevel3CriteriaLabel: 'Criteria',
                kpiNoticeLevel3TargetLabel: 'Target',
                kpiNoticeLevel3UnitLabel: 'Unit',
                kpiHasCriteriaQuestion: 'Do you want to define criteria?',
                kpiHasCriteriaYes: 'Yes',
                kpiHasCriteriaNo: 'No',
                kpiAlertNeedCriteriaChoice: 'Please choose whether you want to define criteria.',
                kpiNoticePostedLabel: 'Posted at',
                kpiNoticeNoData: 'No announcement data found.',
                kpiNoticeNoFile: '-',
                kpiNoticeLevel3DeptLabel: 'Department',
                kpiNoticeLevel3Hint: 'Click to review information',
                kpiNoticeLevel3ModalTitleTpl: 'Department {dept} KPI Table',
                kpiNoticeTableNo: 'Sequence',
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
                kpiNoticeOkrSectionTitle: 'Organization Goal Hierarchy',
                kpiNoticeOkrNoData: 'No organizational goal data available.',
                kpiNoticeOkrKpiEntriesLabel: 'KPI Targets (L3)',
                kpiNoticeOkrNoKpiEntries: 'No KPI targets entered yet.',
                kpiNoticeOkrEmpCode: 'Code',
                kpiNoticeOkrEmpName: 'Name',
                kpiNoticeOkrEmpPosition: 'Position',
                kpiNoticeOkrKpiObjective: 'Objective',
                kpiNoticeOkrKpiDetail: 'Detail',
                kpiNoticeOkrKpiGoal: 'Criteria',
                kpiNoticeOkrKpiTarget: 'Target',
                kpiNoticeOkrKpiUnit: 'Unit',
                kpiNoticeOkrFrom: 'From',
                kpiNoticeOkrTargetDept: 'To (Dept)',
                kpiNoticeOkrFile: 'File',
                kpiNoticeLevel2FilterShowDept: 'Show functional-level strategic objectives for {dept}',
                kpiNoticeLevel2FilterShowAll: 'Show strategic objectives for all responsible functions',
                kpiNoticeDeptFilterShowDept: 'Show operational objectives for target department {dept}',
                kpiNoticeDeptFilterShowAll: 'Show the complete organization objective hierarchy',
                kpiCycleActiveLabel: 'Active Cycle',
                kpiCyclePeriodLabel: 'Period',
                kpiCycleHelp: 'You can save month score only for months that admin opened.',
                kpiCycleNoActive: 'No active cycle. Please contact admin to activate cycle first.',
                kpiAddCard: 'Add KPI Report',
                kpiEditorZoneTitle: 'Add/Edit KPI Report',
                kpiCardTitle: 'KPI Item',
                kpiBtnRemoveCard: 'Remove',
                kpiBtnEditCard: 'Edit',
                kpiBtnHideCard: 'Hide ↑',
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
                kpiLabelCriteria: 'Criteria',
                kpiCriteriaGreater: '>         (Greater than)',
                kpiCriteriaGreaterEqual: '≥         (Greater than or equal)',
                kpiCriteriaLessEqual: '≤         (Less than or equal)',
                kpiCriteriaLess: '<         (Less than)',
                kpiCriteriaEqual: '=         (Equal)',
                kpiCriteriaNotEqual: '≠         (Not equal)',
                kpiScoreTitle: 'KPI Score (12 Months)',
                kpiResultLabel: 'Average KPI Result',
                kpiResultPending: 'Waiting for approval',
                kpiResultSummary: '{percent}% ({point}/{max})',
                kpiResultCompactSummary: 'OKR Summary: {percent}% ({point}/{max})',
                kpiResultApprovedAverageLabel: 'Approved KPI Item Average',
                kpiResultPendingAverageLabel: 'Pending KPI Items',
                kpiResultOverallAverageLabel: 'All Saved KPI Item Average',
                kpiBtnConfirmResult: 'Confirm Result',
                kpiStatusDraft: 'Draft',
                kpiStatusConfirmed: '✓ Confirmed',
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
                kpiMonthStatusLabel: 'Status',
                kpiMonthCycleOpenLabel: 'Cycle Open Date',
                kpiMonthCycleOpenPending: '-',
                kpiMonthLocked: 'Locked (month not opened)',
                kpiMonthNotSaved: 'Not saved',
                kpiMonthSavedPass: 'Pass criteria',
                kpiMonthSavedFail: 'Not pass criteria',
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
                kpiSummaryTitle: 'KPI Monthly Summary',
                kpiSummaryColMonth: 'Month',
                kpiSummaryColScore: 'Score',
                kpiSummaryColCriteria: 'Criteria',
                kpiSummaryColResult: 'Result',
                kpiSummaryNetAverage: 'Net Average Score',
                kpiSummaryTotalPending: 'No month saved yet.',
                kpiSummaryTotalLine: 'Saved {saved}/{total} months | Pass {pass} | Score {point}/{max} ({percent}%)',
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
                confirmHideKpiItem: 'Do you want to hide this KPI card?',
                confirmEditKpiItem: 'Do you want to edit this KPI card?',
                kpiAlertNeedObjectiveDetail: 'Please complete Objective and Detail first.',
                kpiAlertNeedTarget: 'Please complete Target first.',
                kpiAlertNeedTargetAndCriteria: 'Please complete Target and Criteria first.',
                kpiAlertNeedTargetCriteriaAndUnit: 'Please complete Target, Unit, and Criteria first.',
                kpiAlertNeedNumericTarget: 'Target must be a valid number.',
                kpiAlertNeedMonthScore: 'Please enter month score.',
                kpiAlertNeedMonthEvidence: 'Please attach at least one evidence file.',
                kpiAlertNeedActionPlan: 'Please attach Action Plan.',
                kpiAlertNeedAllMonths: 'Please save scores for all 12 months first.',
                kpiAlertNeedSavedMonth: 'Please save at least 1 month first.',
                kpiAlertNeedAllMonthEvidence: 'Please attach evidence files for all 12 months.',
                kpiAlertMonthSaved: 'Month score saved.',
                kpiAlertMinimumOneCard: 'At least one KPI card is required.',
                kpiAlertResultConfirmed: 'KPI report submitted for review successfully.',
                kpiAlertNeedConfirmResult: 'Please confirm result first.',
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
                kpiSaving: 'Saving...'
            },
            th: {
                menuTitle: 'Menu:',
                topEmployeeCode: 'รหัสพนักงาน',
                topEmployeeName: 'ชื่อ-นามสกุล',
                menuHome: 'กำหนดเป้าหมายตัวชี้วัด',
                menuProfile: 'โปรไฟล์',
                menuKpiInput: 'เพิ่มข้อมูล KPIs',
                menuKpiReview: 'ตรวจรายงาน',
                menuKpiDept: 'สรุปรายงาน',
                menuOkrDept: 'สรุปภาพรวมคะแนน',
                menuOkrAllDept: 'สรุปคะแนน OKRs',
                menuAdminSetCycle: 'กำหนดรอบ',
                menuAdminAssignEmployees: 'กำหนดพนักงาน',
                menuAdminDownloadDoc: 'ดาวน์โหลดเอกสาร',
                menuAdminImportEmployees: 'เพิ่มรายชื่อพนักงาน',
                docsTitle: 'ดาวน์โหลดเอกสาร',
                docsSubtitle: 'เลือกแต่ละรอบเพื่อดาวน์โหลดไฟล์ Excel สำหรับใช้งานต่อ',
                docsNo: 'หมายเลข',
                docsCycleName: 'ชื่อรอบ',
                docsPeriod: 'ช่วงเวลา',
                docsManage: 'จัดการ',
                docsDownloadExcel: 'ดาวน์โหลด Excel',
                docsRangeTitle: 'ดาวน์โหลด Excel ตามช่วงเดือน',
                docsRangeHelp: 'เลือกปีรอบ และเลือกเดือนที่ต้องการดาวน์โหลด',
                docsRangeCycle: 'ปีรอบ',
                docsRangeMonths: 'เดือน',
                docsRangeFrom: 'จากเดือน',
                docsRangeTo: 'ถึงเดือน',
                docsRangeDownload: 'ดาวน์โหลด Excel',
                docsRangeNoCycle: 'ยังไม่มีปีรอบให้เลือก',
                docsRangeRoutePending: 'กำลังรอเชื่อมต่อ Controller/Route สำหรับดาวน์โหลดช่วงเดือน',
                docsRangeInvalid: 'กรุณาเลือกอย่างน้อย 1 เดือน',
                docsEmpty: 'ยังไม่มีข้อมูลรอบ',
                logout: 'ออกจากระบบ',
                homeTitle: 'หน้าหลัก',
                homeTargetIndicatorTitle: 'กำหนดเป้าหมายตัวชี้วัด',
                homeAssignEmployeesBtn: 'กำหนดพนักงาน',
                employeeAssignPageTitle: 'กำหนดพนักงาน',
                homeStatus: 'เข้าสู่ระบบด้วย AppUser สำเร็จแล้ว',
                labelEmployeeCode: 'ชื่อผู้ใช้',
                labelName: 'ชื่อ-นามสกุล',
                labelRole: 'สิทธิ์',
                profileTitle: 'โปรไฟล์',
                profileSubtitle: 'ตรวจสอบข้อมูลบัญชีและแก้ไขข้อมูลของคุณอย่างปลอดภัย',
                profileSectionUser: 'ข้อมูลผู้ใช้',
                profileSectionSecurity: 'ความปลอดภัย',
                profileSectionPhoto: 'รูปโปรไฟล์',
                labelFullNameTh: 'ชื่อ-นามสกุล (TH)',
                labelFullNameEn: 'ชื่อ-นามสกุล (EN)',
                labelUsernameProfile: 'ชื่อผู้ใช้',
                labelPositionProfile: 'ตำแหน่ง',
                labelEmployeeType: 'ประเภทพนักงาน',
                labelDepartment: 'แผนก',
                labelRoleProfile: 'บทบาท',
                labelIdentityCode: 'รหัสบัตรประชาชน / บัตรประกันสังคม',
                labelNewPassword: 'รหัสผ่านใหม่',
                labelNewPasswordConfirm: 'ยืนยันรหัสผ่านใหม่',
                hintPasswordMin8: 'รหัสผ่าน 8 ตัวขึ้นไป',
                hintIdentityCode: 'กรอกรหัสบัตรประชาชน / บัตรประกันสังคม แล้วกดตรวจสอบก่อน',
                btnVerifyIdentity: 'ตรวจสอบ',
                btnSave: 'บันทึก',
                btnSavePassword: 'บันทึกรหัสผ่าน',
                btnSaveProfilePhoto: 'บันทึกรูป',
                profilePhotoHint: 'รองรับ: .jpg .jpeg .png .webp .heic .heif ขนาดไม่เกิน 50MB',
                profilePhotoAutoHint: 'เลือกไฟล์รูปแล้วระบบจะอัปเดตทันที',
                confirmLogout: 'คุณต้องการออกจากระบบตอนนี้ใช่หรือไม่?',
                confirmSavePassword: 'คุณต้องการเปลี่ยนรหัสผ่านตอนนี้ใช่หรือไม่?',
                confirmSavePhoto: 'คุณต้องการอัปเดตรูปโปรไฟล์ตอนนี้ใช่หรือไม่?',
                confirmVerifyIdentity: 'คุณต้องการตรวจสอบข้อมูลตอนนี้ใช่หรือไม่?',
                confirmSubmit: 'คุณต้องการดำเนินการต่อใช่หรือไม่?',
                modalConfirmTitle: 'ยืนยันการทำรายการ',
                modalAlertTitle: 'แจ้งเตือน',
                modalCancel: 'ยกเลิก',
                modalConfirm: 'ยืนยัน',
                modalClose: 'ปิด',
                notifyTitle: 'การแจ้งเตือน',
                notifyMarkAll: 'อ่านทั้งหมดแล้ว',
                notifyEmpty: 'ไม่มีการแจ้งเตือน',
                notifyEmptyUser: 'ไม่มีแจ้งเตือนผู้ใช้งาน',
                notifyEmptyReviewer: 'ไม่มีแจ้งเตือนผู้ตรวจรายงาน',
                notifyTabUser: 'ผู้ใช้งาน',
                notifyTabReviewer: 'ผู้ตรวจรายงาน',
                notifyKpiReportTitle: 'รายงาน KPI',
                notifyKpiReviewedDescription: 'รายงาน KPI ได้รับการตรวจสอบแล้ว',
                notifyKpiReviewRequestDescription: 'คุณได้รับรายงาน KPI กรุณาตรวจสอบรายงาน',
                notifyKpiTopicLabel: 'หัวข้อ',
                notifyKpiMonthLabel: 'เดือน',
                notifyKpiStatusLabel: 'สถานะ',
                notifyKpiFromLabel: 'จาก',
                notifyKpiDepartmentLabel: 'แผนก',
                identityVerified: 'ตรวจสอบข้อมูลสำเร็จ',
                identityCodeValid: 'รหัสบัตรประชาชน / บัตรประกันสังคม ถูกต้อง',
                identityNotVerified: 'กรุณาตรวจสอบข้อมูลก่อน',
                verifyFailed: 'ไม่สามารถตรวจสอบข้อมูลได้',
                networkVerifyError: 'เครือข่ายมีปัญหาระหว่างตรวจสอบข้อมูล',
                photoPlaceholder: 'ไม่มีรูป',
                placeholderPageTitle: 'หน้ากำลังพัฒนา',
                placeholderPageDescription: 'หน้านี้เตรียมไว้เป็นหน้าขาวสำหรับใช้งานชั่วคราว และจะพัฒนารายละเอียดต่อในลำดับถัดไป',
                kpiIntroTitle: 'เพิ่มข้อมูล KPIs',
                kpiIntroDescription: 'กรุณาตรวจสอบวัตถุประสงค์และรายละเอียดของเป้าหมาย ก่อนดำเนินการกำหนด Target และบันทึกคะแนน KPI รายเดือน',
                kpiAvgLabel: 'ผลลัพธ์ KPIs เฉลี่ยทั้งหมดรายบุคคล',
                kpiNoticeTitle: 'ตรวจสอบเป้าหมายองค์กร',
                kpiNoticeHint: 'ตรวจสอบเป้าหมายองค์กร',
                globalNoticeFabLabel: 'ประกาศ',
                kpiNoticeModalTitle: 'ตรวจสอบเป้าหมายองค์กร',
                kpiNoticeModalClose: 'ปิด',
                kpiNoticeModalExpand: 'ขยาย',
                kpiNoticeModalRestore: 'คืนขนาด',
                kpiNoticeModalCollapse: 'ย่อ',
                kpiNoticeModalExpandBody: 'ขยายเนื้อหา',
                kpiNoticeModalZoomIn: 'ซูมเข้า',
                kpiNoticeModalZoomOut: 'ซูมออก',
                kpiNoticeModalPanEnable: 'เปิดเครื่องมือมือเลื่อน',
                kpiNoticeModalPanDisable: 'ปิดเครื่องมือมือเลื่อน',
                kpiNoticeSectionLevel1: 'ระดับองค์กร · CEO',
                kpiNoticeSectionLevel2: 'ระดับสายงาน · GM / DM / AM / Manager',
                kpiNoticeSectionLevel3: 'ระดับแผนก · AM / Manager',
                gtLevel1Header: 'ลำดับชั้น 1 · CEO',
                gtLevel2Header: 'ลำดับชั้น 2 · GM / DM / AM / Mgr.',
                gtLevel3Header: 'ลำดับชั้น 3 · AM / Mgr.',
                kpiNoticeSortLabel: 'แสดงข้อมูลตาม :',
                kpiNoticeLevel1FilterLabel: 'ระดับองค์กร',
                kpiNoticeLevel2FilterLabel: 'ระดับสายงาน',
                kpiNoticeLevel3FilterLabel: 'ระดับแผนก',
                kpiNoticeFilterAll: 'ทั้งหมด',
                kpiNoticeFilterCeo: 'CEO',
                kpiNoticeCeoFilterShow: 'แสดงเฉพาะเป้าหมายระดับ CEO',
                kpiNoticeDeptLabel: 'หน่วยงาน',
                kpiNoticeTitleLabel: 'เป้าหมาย',
                kpiNoticeDetailLabel: 'รายละเอียดเป้าหมาย',
                kpiNoticeFilesLabel: 'เอกสารอ้างอิง',
                kpiNoticeLevel1ObjectiveLabel: 'เป้าหมายองค์กร',
                kpiNoticeLevel1DetailLabel: 'รายละเอียด',
                kpiNoticeLevel1FileLabel: 'เอกสาร',
                kpiNoticeLevel2ObjectiveLabel: 'เป้าหมายสายงาน',
                kpiNoticeLevel2DetailLabel: 'รายละเอียด',
                kpiNoticeLevel2FileLabel: 'เอกสาร',
                kpiNoticeLevel3OwnerLabel: 'ผู้รับผิดชอบ',
                kpiNoticeLevel3TargetDeptLabel: 'หน่วยงานเป้าหมาย',
                kpiNoticeLevel3ObjectiveLabel: 'เป้าหมายแผนก',
                kpiNoticeLevel3DetailLabel: 'รายละเอียด',
                kpiNoticeLevel3CriteriaLabel: 'เกณฑ์',
                kpiNoticeLevel3TargetLabel: 'เป้าหมาย',
                kpiNoticeLevel3UnitLabel: 'หน่วย',
                kpiHasCriteriaQuestion: 'คุณต้องการกำหนดเกณฑ์หรือไม่',
                kpiHasCriteriaYes: 'ใช่',
                kpiHasCriteriaNo: 'ไม่',
                kpiAlertNeedCriteriaChoice: 'กรุณาเลือกว่าต้องการกำหนดเกณฑ์หรือไม่',
                kpiNoticePostedLabel: 'โพสต์เมื่อ',
                kpiNoticeNoData: 'ไม่พบข้อมูลประกาศ',
                kpiNoticeNoFile: '-',
                kpiNoticeLevel3DeptLabel: 'แผนก',
                kpiNoticeLevel3Hint: 'กดเพื่อตรวจสอบข้อมูล',
                kpiNoticeLevel3ModalTitleTpl: 'ตาราง KPI แผนก {dept}',
                kpiNoticeTableNo: 'ลำดับ',
                kpiNoticeTableEmployeeCode: 'รหัสพนักงาน',
                kpiNoticeTableFullName: 'ชื่อ-สกุล',
                kpiNoticeTablePosition: 'ตำแหน่ง',
                kpiNoticeTableObjective: 'Objective',
                kpiNoticeTableDetail: 'Detail',
                kpiNoticeTableTargetValue: 'เป้าหมาย',
                kpiNoticeTableGoal: 'เกณฑ์',
                kpiNoticeTableUnit: 'หน่วย',
                kpiNoticeTableEmpty: 'ยังไม่พบข้อมูล KPI ของแผนกนี้',
                kpiNoticeFilePreviewFail: 'ไม่สามารถแสดงตัวอย่างไฟล์นี้ได้',
                kpiNoticeFilePreviewUnsupported: 'ไฟล์ประเภทนี้ยังไม่รองรับการแสดงผลในหน้า',
                kpiNoticeOkrSectionTitle: 'โครงสร้างเป้าหมายองค์กร',
                kpiNoticeOkrNoData: 'ยังไม่มีข้อมูลเป้าหมายองค์กร',
                kpiNoticeOkrKpiEntriesLabel: 'เป้าหมาย KPI (ลำดับชั้นที่ 3)',
                kpiNoticeOkrNoKpiEntries: 'ยังไม่มีรายการ KPI ที่บันทึกไว้',
                kpiNoticeOkrEmpCode: 'รหัส',
                kpiNoticeOkrEmpName: 'ชื่อ-สกุล',
                kpiNoticeOkrEmpPosition: 'ตำแหน่ง',
                kpiNoticeOkrKpiObjective: 'วัตถุประสงค์',
                kpiNoticeOkrKpiDetail: 'รายละเอียด',
                kpiNoticeOkrKpiGoal: 'เกณฑ์',
                kpiNoticeOkrKpiTarget: 'เป้าหมาย',
                kpiNoticeOkrKpiUnit: 'หน่วย',
                kpiNoticeOkrFrom: 'จาก',
                kpiNoticeOkrTargetDept: 'ถึง (แผนก)',
                kpiNoticeOkrFile: 'ไฟล์',
                kpiNoticeLevel2FilterShowDept: 'แสดงเป้าหมายระดับกลยุทธ์สายงานของ {dept}',
                kpiNoticeLevel2FilterShowAll: 'แสดงเป้าหมายระดับกลยุทธ์ของทุกหน่วยงาน',
                kpiNoticeDeptFilterShowDept: 'แสดงเป้าหมายการดำเนินงานของหน่วยงานเป้าหมาย {dept}',
                kpiNoticeDeptFilterShowAll: 'แสดงโครงสร้างเป้าหมายองค์กรทั้งหมด',
                kpiCycleActiveLabel: 'รอบที่เปิดใช้งาน',
                kpiCyclePeriodLabel: 'ช่วงเวลา',
                kpiCycleHelp: 'สามารถบันทึกคะแนนได้เฉพาะเดือนที่ผู้ดูแลระบบเปิดรอบแล้ว',
                kpiCycleNoActive: 'ยังไม่มีรอบที่เปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                kpiAddCard: 'เพิ่มรายงาน KPI',
                kpiEditorZoneTitle: 'เพิ่ม/แก้ไขรายการ KPI',
                kpiCardTitle: 'รายการ KPI',
                kpiBtnRemoveCard: 'ลบ',
                kpiBtnEditCard: 'แก้ไข',
                kpiBtnHideCard: 'ซ่อน ↑',
                kpiLabelTargetDepartments: 'เลือกแผนก',
                kpiLabelCardMode: 'ประเภท KPI',
                kpiCardModeReport: 'รายงาน',
                kpiCardModeTarget: 'เป้าหมาย',
                kpiSelectPlaceholder: 'เลือก',
                kpiLabelOkrHierarchy: 'เลือกหัวข้อลำดับ 1 / หัวข้อลำดับ 2',
                kpiLabelOkrLevelOne: 'ลำดับชั้นที่ 1',
                kpiLabelOkrLevelTwo: 'ลำดับชั้นที่ 2',
                kpiLabelOkrLevelThree: 'ลำดับชั้นที่ 3',
                kpiLabelReportLevelThree: 'เลือกหัวข้อลำดับ 3',
                kpiLabelTargetLevelOne: 'หัวข้อลำดับที่ 1',
                kpiLabelTargetLevelTwo: 'หัวข้อลำดับที่ 2',
                kpiCompactSectionTarget: 'หัวข้อลำดับที่ 3',
                kpiCompactSectionReport: 'หัวข้อลำดับที่ 4',
                kpiOkrHierarchyPlaceholder: 'เลือก',
                kpiOkrHierarchyNoData: 'ยังไม่มีหัวข้อ 1 และ 2 ให้เลือก',
                kpiOkrLevelThreePlaceholder: 'เลือก',
                kpiOkrLevelThreeNoData: 'ยังไม่มีเป้าหมายลำดับชั้นที่ 3 ให้เลือก',
                kpiFilterAll: 'ทั้งหมด',
                kpiLabelObjective: 'วัตถุประสงค์',
                kpiLabelDetail: 'รายละเอียด',
                kpiDocumentLabel: 'เอกสาร',
                kpiBtnNext: 'บันทึก',
                kpiLabelTarget: 'เป้าหมาย',
                kpiTargetPlaceholder: 'ตัวอย่าง: 2.30',
                kpiLabelUnit: 'หน่วย',
                kpiUnitPlaceholder: 'หน่วย',
                kpiCriteriaSectionTitle: 'กำหนดเกณฑ์',
                kpiLabelCriteria: 'เกณฑ์',
                kpiCriteriaGreater: '>         (มากกว่า)',
                kpiCriteriaGreaterEqual: '≥         (มากกว่าหรือเท่ากับ)',
                kpiCriteriaLessEqual: '≤         (น้อยกว่าหรือเท่ากับ)',
                kpiCriteriaLess: '<         (น้อยกว่า)',
                kpiCriteriaEqual: '=         (เท่ากับ)',
                kpiCriteriaNotEqual: '≠         (ไม่เท่ากับ)',
                kpiScoreTitle: 'คะแนน KPI (12 เดือน)',
                kpiResultLabel: 'ค่าเฉลี่ยผลลัพธ์ KPI',
                kpiResultPending: 'รอการอนุมัติผล',
                kpiResultSummary: '{percent}% ({point}/{max})',
                kpiResultCompactSummary: 'สรุป OKR: {percent}% ({point}/{max})',
                kpiResultApprovedAverageLabel: 'ค่าเฉลี่ยรายการ KPI ที่อนุมัติแล้ว',
                kpiResultPendingAverageLabel: 'รายการ KPI ที่รอการดำเนินการ',
                kpiResultOverallAverageLabel: 'ค่าเฉลี่ยรายการ KPI ที่บันทึกทั้งหมด',
                kpiBtnConfirmResult: 'ยืนยันผล',
                kpiStatusDraft: 'แบบร่าง',
                kpiStatusConfirmed: '✓ ยืนยันแล้ว',
                kpiMonthScoreLabel: 'คะแนน',
                kpiMonthEvidenceLabel: 'แนบหลักฐาน',
                kpiMonthEvidenceHint: 'รองรับ: Word, PDF, PNG, JPG, Excel (สูงสุด 1GB - โครงร่าง)',
                kpiMonthEvidenceNotSelected: 'ยังไม่ได้เลือกไฟล์',
                kpiMonthEvidenceSelected: 'เลือกแล้ว {count} ไฟล์',
                kpiMonthActionPlanLabel: 'แนบไฟล์ Action Plan',
                kpiMonthActionPlanNotSelected: 'ยังไม่ได้เลือกไฟล์',
                kpiMonthActionPlanSelected: 'เลือกแล้ว {count} ไฟล์',
                kpiMonthSaveBtn: 'บันทึก',
                kpiMonthEditBtn: 'แก้ไข',
                kpiMonthStatusLabel: 'สถานะ',
                kpiMonthCycleOpenLabel: 'วันเปิดรอบ',
                kpiMonthCycleOpenPending: '-',
                kpiMonthLocked: 'ปิดอยู่',
                kpiMonthNotSaved: 'ยังไม่บันทึก',
                kpiMonthSavedPass: 'ผ่านเกณฑ์',
                kpiMonthSavedFail: 'ไม่ผ่านเกณฑ์',
                kpiMonthReviewPending: 'รอการดำเนินการ',
                kpiMonthReviewApproved: 'อนุมัติแล้ว',
                kpiMonthReviewRejected: 'ปฏิเสธรายงาน',
                kpiMonthReviewDetailLabel: 'รายละเอียดการปฏิเสธ',
                kpiCardReviewStatusLabel: 'สถานะรายงาน',
                kpiCardReportStatusTitle: 'สถานะ',
                kpiCardReportStatusMonthsTitle: 'สถานะทั้ง 12 เดือน',
                kpiCardReportStatusPending: 'รอการดำเนินการ',
                kpiCardReportStatusApproved: 'อนุมัติ',
                kpiCardReportStatusRejected: 'ปฏิเสธ',
                kpiCardReportStatusWaitingScore: 'รอคะแนนรายงาน',
                kpiSummaryTitle: 'สรุปผล KPI รายเดือน',
                kpiSummaryColMonth: 'เดือน',
                kpiSummaryColScore: 'คะแนน',
                kpiSummaryColCriteria: 'เกณฑ์',
                kpiSummaryColResult: 'ผลลัพธ์',
                kpiSummaryNetAverage: 'คะแนนเฉลี่ยสุทธิ',
                kpiSummaryTotalPending: 'ยังไม่มีเดือนที่บันทึก',
                kpiSummaryTotalLine: 'บันทึกแล้ว {saved}/{total} เดือน | ผ่าน {pass} เดือน | คะแนน {point}/{max} ({percent}%)',
                kpiMonth01: 'มกราคม',
                kpiMonth02: 'กุมภาพันธ์',
                kpiMonth03: 'มีนาคม',
                kpiMonth04: 'เมษายน',
                kpiMonth05: 'พฤษภาคม',
                kpiMonth06: 'มิถุนายน',
                kpiMonth07: 'กรกฎาคม',
                kpiMonth08: 'สิงหาคม',
                kpiMonth09: 'กันยายน',
                kpiMonth10: 'ตุลาคม',
                kpiMonth11: 'พฤศจิกายน',
                kpiMonth12: 'ธันวาคม',
                confirmAddKpiItem: 'คุณต้องการเพิ่มรายงาน KPI ใหม่ใช่หรือไม่?',
                confirmRemoveKpiItem: 'คุณต้องการลบการ์ด KPI นี้ใช่หรือไม่?',
                confirmOpenTargetSection: 'คุณต้องการไปขั้นตอนกำหนด Target ใช่หรือไม่?',
                confirmOpenScoreSection: 'คุณต้องการไปขั้นตอนกรอกคะแนน KPI ใช่หรือไม่?',
                confirmKpiResult: 'คุณต้องการยืนยันผล KPI นี้ใช่หรือไม่?',
                confirmSaveMonthData: 'คุณต้องการบันทึกคะแนนของเดือนนี้ใช่หรือไม่?',
                confirmHideKpiItem: 'คุณต้องการซ่อนการ์ด KPI นี้ใช่หรือไม่?',
                confirmEditKpiItem: 'คุณต้องการแก้ไขการ์ด KPI นี้ใช่หรือไม่?',
                kpiAlertNeedObjectiveDetail: 'กรุณากรอก วัตถุประสงค์ และ รายละเอียด ให้ครบก่อน',
                kpiAlertNeedTarget: 'กรุณากรอก Target ก่อน',
                kpiAlertNeedTargetAndCriteria: 'กรุณากรอก Target และเลือกเกณฑ์ก่อน',
                kpiAlertNeedTargetCriteriaAndUnit: 'กรุณากรอก Target หน่วย และเลือกเกณฑ์ก่อน',
                kpiAlertNeedNumericTarget: 'Target ต้องเป็นตัวเลขที่ถูกต้อง',
                kpiAlertNeedMonthScore: 'กรุณากรอกคะแนนของเดือน',
                kpiAlertNeedMonthEvidence: 'กรุณาแนบไฟล์หลักฐานอย่างน้อย 1 ไฟล์',
                kpiAlertNeedActionPlan: 'กรุณาแนบ Action Plan',
                kpiAlertNeedAllMonths: 'กรุณาบันทึกคะแนนให้ครบทั้ง 12 เดือนก่อน',
                kpiAlertNeedSavedMonth: 'กรุณาบันทึกคะแนนอย่างน้อย 1 เดือนก่อน',
                kpiAlertNeedAllMonthEvidence: 'กรุณาแนบหลักฐานให้ครบทั้ง 12 เดือน',
                kpiAlertMonthSaved: 'บันทึกคะแนนของเดือนเรียบร้อยแล้ว',
                kpiAlertMinimumOneCard: 'ต้องมีอย่างน้อย 1 การ์ด KPI',
                kpiAlertResultConfirmed: 'ส่งรายงาน KPI เพื่อตรวจสอบเรียบร้อยแล้ว',
                kpiAlertNeedConfirmResult: 'กรุณายืนยันผลก่อน',
                kpiAlertCardHidden: 'ซ่อนการ์ด KPI แล้ว',
                kpiAlertCardEditMode: 'เปิดโหมดแก้ไขแล้ว',
                kpiAlertNeedActiveCycle: 'ยังไม่มีรอบที่เปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                kpiAlertNeedStepOne: 'กรุณาบันทึก วัตถุประสงค์ และ รายละเอียด ก่อน',
                kpiAlertNeedStepTwo: 'กรุณาบันทึก เป้าหมาย และ เกณฑ์ ก่อน',
                kpiAlertNeedOkrHierarchy: 'กรุณาเลือกลำดับชั้น 1 และ 2 ก่อน',
                kpiAlertNeedOkrLevelThree: 'กรุณาเลือกลำดับชั้นที่ 3 ก่อน',
                kpiAlertStepOneSaveFailed: 'ไม่สามารถบันทึก วัตถุประสงค์ และ รายละเอียด ได้',
                kpiAlertStepTwoSaveFailed: 'ไม่สามารถบันทึก เป้าหมาย และ เกณฑ์ ได้',
                kpiAlertDeleteFailed: 'ไม่สามารถลบการ์ด KPI นี้ได้',
                kpiAlertMonthLocked: 'เดือนนี้ยังไม่เปิดรอบจากผู้ดูแลระบบ',
                kpiAlertMonthSaveFailed: 'ไม่สามารถบันทึกคะแนนของเดือนนี้ได้',
                kpiAlertResultSaveFailed: 'ไม่สามารถยืนยันผล KPI ได้',
                kpiAlertCardDeleted: 'ลบการ์ด KPI แล้ว',
                kpiSaving: 'กำลังบันทึก...'
            }
        };
        let currentLang = 'en';
        let pendingConfirmCallback = null;

        const confirmModal = document.getElementById('confirm-modal');
        const confirmMessage = document.getElementById('confirm-message');
        const confirmCancel = document.getElementById('confirm-cancel');
        const confirmOk = document.getElementById('confirm-ok');

        const alertModal = document.getElementById('alert-modal');
        const alertIcon = document.getElementById('alert-icon');
        const alertMessage = document.getElementById('alert-message');
        const alertClose = document.getElementById('alert-close');
        const notifyEnabled = @json($notificationsEnabled);
        const notifyIndexUrl = @json($notificationsIndexUrl);
        const notifyReadUrlTemplate = @json($notificationsReadUrlTemplate);
        const notifyReadAllUrl = @json($notificationsReadAllUrl);
        const notifyWrap = document.getElementById('top-notify-wrap');
        const notifyBtn = document.getElementById('top-notify-btn');
        const notifyBadge = document.getElementById('top-notify-badge');
        const notifyPanel = document.getElementById('top-notify-panel');
        const notifyList = document.getElementById('top-notify-list');
        const notifyMarkAllBtn = document.getElementById('top-notify-mark-all');
        const notifyTabUserBtn = document.getElementById('top-notify-tab-user');
        const notifyTabReviewerBtn = document.getElementById('top-notify-tab-reviewer');
        const notifyTabUserCountEl = document.getElementById('top-notify-tab-user-count');
        const notifyTabReviewerCountEl = document.getElementById('top-notify-tab-reviewer-count');
        const globalNoticeFab = document.getElementById('global-notice-fab');
        const globalNoticeDataUrl = @json($globalNoticeDataUrl);
        const globalNoticeFabStorageKey = `global-notice-fab-pos:${@json($authUserId)}`;
        const globalKpiNoticeModal = document.getElementById('global-kpi-notice-modal');
        const globalKpiNoticePanel = document.getElementById('global-kpi-notice-panel');
        const globalKpiNoticeHead = globalKpiNoticePanel
            ? globalKpiNoticePanel.querySelector('.global-kpi-notice-head')
            : null;
        const globalKpiNoticeBody = document.getElementById('global-kpi-notice-body');
        const globalKpiNoticeContent = document.getElementById('global-kpi-notice-content');
        const globalKpiNoticeCloseBtn = document.getElementById('global-kpi-notice-close');
        const globalKpiNoticeExpandBtn = document.getElementById('global-kpi-notice-expand');
        const globalKpiNoticeResetFilterBtn = document.getElementById('global-kpi-notice-filter-reset');
        const globalKpiNoticeCeoFilterBtn = document.getElementById('global-kpi-notice-filter-ceo');
        const globalKpiNoticeLevelTwoFilterSelect = document.getElementById('global-kpi-notice-l2-filter');
        const globalKpiNoticeDeptFilterSelect = document.getElementById('global-kpi-notice-dept-filter');
        const globalKpiNoticeZoomOutBtn = document.getElementById('global-kpi-notice-zoom-out');
        const globalKpiNoticeZoomInBtn = document.getElementById('global-kpi-notice-zoom-in');
        const globalKpiNoticeZoomValue = document.getElementById('global-kpi-notice-zoom-value');
        const globalKpiNoticePanBtn = document.getElementById('global-kpi-notice-pan');
        const globalKpiNoticeResizeHandles = globalKpiNoticePanel
            ? Array.from(globalKpiNoticePanel.querySelectorAll('.global-kpi-notice-resize-handle'))
            : [];
        const globalKpiNoticeLevel1Section = document.getElementById('global-kpi-notice-level1-section');
        const globalKpiNoticeLevel2Section = document.getElementById('global-kpi-notice-level2-section');
        const globalKpiNoticeLevel3Section = document.getElementById('global-kpi-notice-level3-section');
        const globalKpiNoticeLevel1List = document.getElementById('global-kpi-notice-level1-list');
        const globalKpiNoticeLevel2List = document.getElementById('global-kpi-notice-level2-list');
        const globalKpiNoticeLevel3Table = document.getElementById('global-kpi-notice-level3-table');
        const globalKpiNoticeOkrSection = document.getElementById('global-kpi-notice-okr-section');
        const globalKpiNoticeOkrTree = document.getElementById('global-kpi-notice-okr-tree');
        let globalNoticeFabIgnoreClick = false;
        let globalNoticeDataCache = null;
        let globalNoticeLoadingPromise = null;
        let globalNoticePanelExpanded = false;
        let globalNoticePanelZoom = 1;
        let globalNoticePanelPanEnabled = false;
        let globalNoticeHierarchyViewMode = 'all';
        let globalNoticeLevelTwoFilterValue = '';
        let globalNoticeDeptFilterValue = '';
        let globalNoticeRenderedPayload = null;
        const globalNoticeViewerDepartment = normalizeGlobalNoticeDepartmentCode(@json($globalNoticeViewerDepartment));
        const globalNoticeViewerUserId = @json($authUserId);
        let notificationsState = [];
        let notificationsUnreadCount = 0;
        let notifyActiveTab = 'user';

        function getText(key) {
            return (translations[currentLang] && translations[currentLang][key]) || (translations.en[key] || key);
        }

        function formatGlobalNoticeText(template, replacements = {}) {
            let result = String(template || '');
            Object.keys(replacements || {}).forEach((key) => {
                result = result.replace(new RegExp(`\\{${key}\\}`, 'g'), String(replacements[key] || ''));
            });
            return result;
        }

        function withLangParam(rawUrl) {
            if (! rawUrl) return '';
            let url;
            try {
                url = new URL(rawUrl, window.location.origin);
            } catch (error) {
                return rawUrl;
            }
            if (url.origin !== window.location.origin) {
                return rawUrl;
            }
            url.searchParams.set('lang', currentLang);
            return `${url.pathname}${url.search}${url.hash}`;
        }

        function normalizeGlobalNoticeDepartmentCode(value) {
            const rawText = String(value || '').trim().toUpperCase();
            if (rawText === '') {
                return '';
            }
            return rawText.replace(/\s+/g, '');
        }

        function clampGlobalNoticeFabPosition(left, top) {
            if (!globalNoticeFab) {
                return { left: 0, top: 0 };
            }
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const fabWidth = globalNoticeFab.offsetWidth || 62;
            const fabHeight = globalNoticeFab.offsetHeight || 62;
            const minGap = 8;
            const minLeft = minGap;
            const minTop = minGap;
            const maxLeft = Math.max(minLeft, viewportWidth - fabWidth - minGap);
            const maxTop = Math.max(minTop, viewportHeight - fabHeight - minGap);
            const nextLeft = Math.min(maxLeft, Math.max(minLeft, Number(left) || 0));
            const nextTop = Math.min(maxTop, Math.max(minTop, Number(top) || 0));
            return { left: nextLeft, top: nextTop };
        }

        function setGlobalNoticeFabPosition(left, top, persist = false) {
            if (!globalNoticeFab) {
                return;
            }
            const clamped = clampGlobalNoticeFabPosition(left, top);
            globalNoticeFab.style.left = `${clamped.left}px`;
            globalNoticeFab.style.top = `${clamped.top}px`;
            globalNoticeFab.style.right = 'auto';
            globalNoticeFab.style.bottom = 'auto';
            if (!persist) {
                return;
            }
            try {
                if (window.localStorage) {
                    window.localStorage.setItem(globalNoticeFabStorageKey, JSON.stringify(clamped));
                }
            } catch (error) {
                // Ignore storage errors
            }
        }

        function restoreGlobalNoticeFabPosition() {
            if (!globalNoticeFab) {
                return;
            }
            try {
                if (!window.localStorage) {
                    return;
                }
                const raw = window.localStorage.getItem(globalNoticeFabStorageKey);
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
                setGlobalNoticeFabPosition(left, top, false);
            } catch (error) {
                // Ignore storage errors
            }
        }

        function createEmptyGlobalNoticePayload() {
            return {
                visibilityLevel: 0,
                announcementsByLevel: {
                    1: [],
                    2: [],
                },
                levelThreeCard: null,
                levelThreeRows: [],
                levelThreeCycleLabel: '',
                okrHierarchy: [],
            };
        }

        function formatGlobalNoticePostedAt(value) {
            const raw = String(value || '').trim();
            if (raw === '') {
                return '-';
            }
            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) {
                return '-';
            }
            return date.toLocaleString(currentLang === 'th' ? 'th-TH' : 'en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function normalizeGlobalNoticeAnnouncement(item) {
            const source = item && typeof item === 'object' ? item : {};
            return {
                dept_abbr_hr: String(source.dept_abbr_hr || '').trim().toUpperCase(),
                title: String(source.title || '').trim(),
                detail: String(source.detail || '').trim(),
                posted_at: String(source.posted_at || '').trim(),
                files: Array.isArray(source.files)
                    ? source.files.map((file) => ({
                        name: String(file && file.name ? file.name : '').trim(),
                        size_text: String(file && file.size_text ? file.size_text : '').trim(),
                        url: String(file && file.url ? file.url : '').trim(),
                        preview_url: String(file && file.preview_url ? file.preview_url : '').trim(),
                    }))
                    : [],
            };
        }

        function normalizeGlobalNoticeLevelThreeRow(item) {
            const source = item && typeof item === 'object' ? item : {};
            const rawTargetDepts = Array.isArray(source.target_departments) ? source.target_departments : [];
            return {
                app_user_id: Number(source.app_user_id) || 0,
                employee_code: String(source.employee_code || '').trim(),
                full_name_th: String(source.full_name_th || '').trim(),
                full_name_en: String(source.full_name_en || '').trim(),
                position: String(source.position || '').trim(),
                objective: String(source.objective || '').trim(),
                detail: String(source.detail || '').trim(),
                target_goal: String(source.target_goal || '').trim(),
                target_value: String(source.target_value || '').trim(),
                unit: String(source.unit || '').trim(),
                dept_abbr_hr: String(source.dept_abbr_hr || '').trim().toUpperCase(),
                target_departments: rawTargetDepts.map((d) => String(d || '').trim().toUpperCase()),
            };
        }

        function normalizeGlobalNoticePayload(payload) {
            const safePayload = payload && typeof payload === 'object' ? payload : {};
            const visibilityRaw = Number(safePayload.visibility_level);
            const visibilityLevel = Number.isFinite(visibilityRaw)
                ? Math.min(3, Math.max(0, Math.floor(visibilityRaw)))
                : 0;

            const announcementsByLevelRaw = (
                safePayload.announcements_by_level && typeof safePayload.announcements_by_level === 'object'
            ) ? safePayload.announcements_by_level : {};

            const announcementsByLevel = {
                1: [],
                2: [],
            };

            [1, 2].forEach((levelNo) => {
                const rawList = announcementsByLevelRaw[String(levelNo)] || announcementsByLevelRaw[levelNo] || [];
                announcementsByLevel[levelNo] = Array.isArray(rawList)
                    ? rawList.map((item) => normalizeGlobalNoticeAnnouncement(item))
                    : [];
            });

            const levelThreeCardRaw = safePayload.level_three_card && typeof safePayload.level_three_card === 'object'
                ? safePayload.level_three_card
                : null;
            const levelThreeCard = levelThreeCardRaw
                ? {
                    dept_abbr_hr: String(levelThreeCardRaw.dept_abbr_hr || '').trim().toUpperCase(),
                    rows_count: Number(levelThreeCardRaw.rows_count) || 0,
                }
                : null;

            const levelThreeRows = Array.isArray(safePayload.level_three_rows)
                ? safePayload.level_three_rows.map((item) => normalizeGlobalNoticeLevelThreeRow(item))
                : [];

            const levelThreeCycleLabel = String(safePayload.level_three_cycle_label || '').trim();

            const rawOkrHierarchy = Array.isArray(safePayload.okr_hierarchy) ? safePayload.okr_hierarchy : [];
            const okrHierarchy = rawOkrHierarchy.map((obj) => {
                const safeObj = obj && typeof obj === 'object' ? obj : {};
                const krs = Array.isArray(safeObj.key_results) ? safeObj.key_results : [];
                return {
                    id: Number(safeObj.id) || 0,
                    sort_no: Number(safeObj.sort_no) || 0,
                    title: String(safeObj.title || '').trim(),
                    detail: String(safeObj.detail || '').trim(),
                    file_original_name: String(safeObj.file_original_name || '').trim(),
                    file_url: String(safeObj.file_url || '').trim(),
                    key_results: krs.map((kr) => {
                        const safeKr = kr && typeof kr === 'object' ? kr : {};
                        const entries = Array.isArray(safeKr.kpi_entries) ? safeKr.kpi_entries : [];
                        return {
                            id: Number(safeKr.id) || 0,
                            sort_no: Number(safeKr.sort_no) || 0,
                            title: String(safeKr.title || '').trim(),
                            detail: String(safeKr.detail || '').trim(),
                            dept_abbr_hr: String(safeKr.dept_abbr_hr || '').trim().toUpperCase(),
                            file_original_name: String(safeKr.file_original_name || '').trim(),
                            file_url: String(safeKr.file_url || '').trim(),
                            kpi_entries: entries.map((e) => normalizeGlobalNoticeLevelThreeRow(e)),
                        };
                    }),
                };
            });

            return {
                visibilityLevel,
                announcementsByLevel,
                levelThreeCard,
                levelThreeRows,
                levelThreeCycleLabel,
                okrHierarchy,
            };
        }

        function createGlobalNoticeRow(labelText, valueNodeOrText) {
            const rowEl = document.createElement('div');
            rowEl.className = 'global-kpi-notice-row';

            const labelEl = document.createElement('span');
            labelEl.className = 'global-kpi-notice-label';
            labelEl.textContent = labelText;
            rowEl.appendChild(labelEl);

            if (valueNodeOrText instanceof HTMLElement) {
                valueNodeOrText.classList.add('global-kpi-notice-value');
                rowEl.appendChild(valueNodeOrText);
            } else {
                const valueEl = document.createElement('span');
                valueEl.className = 'global-kpi-notice-value';
                const text = String(valueNodeOrText || '').trim();
                valueEl.textContent = text !== '' ? text : '-';
                rowEl.appendChild(valueEl);
            }

            return rowEl;
        }

        function createGlobalNoticeFilesNode(files) {
            if (!Array.isArray(files) || files.length < 1) {
                return getText('kpiNoticeNoFile');
            }

            const listEl = document.createElement('ul');
            listEl.className = 'global-kpi-notice-files';

            files.forEach((file) => {
                const fileName = String(file && file.name ? file.name : '').trim();
                if (fileName === '') {
                    return;
                }
                const fileSize = String(file && file.size_text ? file.size_text : '').trim();
                const openUrl = String(
                    file && (file.preview_url || file.url)
                        ? (file.preview_url || file.url)
                        : ''
                ).trim();

                const liEl = document.createElement('li');
                if (openUrl !== '') {
                    const linkEl = document.createElement('a');
                    linkEl.href = openUrl;
                    linkEl.target = '_blank';
                    linkEl.rel = 'noopener noreferrer';
                    linkEl.textContent = fileSize !== '' ? `${fileName} (${fileSize})` : fileName;
                    liEl.appendChild(linkEl);
                } else {
                    liEl.textContent = fileSize !== '' ? `${fileName} (${fileSize})` : fileName;
                }
                listEl.appendChild(liEl);
            });

            if (listEl.children.length < 1) {
                return getText('kpiNoticeNoFile');
            }

            return listEl;
        }

        function createGlobalNoticeCard(announcement) {
            const cardEl = document.createElement('article');
            cardEl.className = 'global-kpi-notice-item';
            cardEl.appendChild(createGlobalNoticeRow(getText('kpiNoticeDeptLabel'), announcement.dept_abbr_hr || '-'));
            cardEl.appendChild(createGlobalNoticeRow(getText('kpiNoticeTitleLabel'), announcement.title || '-'));
            cardEl.appendChild(createGlobalNoticeRow(getText('kpiNoticeDetailLabel'), announcement.detail || '-'));
            cardEl.appendChild(createGlobalNoticeRow(getText('kpiNoticePostedLabel'), formatGlobalNoticePostedAt(announcement.posted_at)));
            cardEl.appendChild(createGlobalNoticeRow(getText('kpiNoticeFilesLabel'), createGlobalNoticeFilesNode(announcement.files)));
            return cardEl;
        }

        function renderGlobalNoticeLevel(levelNo, containerEl, payload) {
            if (!containerEl) {
                return;
            }

            const source = payload && payload.announcementsByLevel && payload.announcementsByLevel[levelNo]
                ? payload.announcementsByLevel[levelNo]
                : [];
            const items = Array.isArray(source) ? source : [];

            containerEl.innerHTML = '';
            if (items.length < 1) {
                const emptyEl = document.createElement('p');
                emptyEl.className = 'global-kpi-notice-empty';
                emptyEl.textContent = getText('kpiNoticeNoData');
                containerEl.appendChild(emptyEl);
                return;
            }

            items.forEach((announcement) => {
                containerEl.appendChild(createGlobalNoticeCard(announcement));
            });
        }

        function renderGlobalNoticeLevelThree(payload) {
            if (!globalKpiNoticeLevel3Section || !globalKpiNoticeLevel3Table) {
                return;
            }

            const levelThreeCard = payload && payload.levelThreeCard ? payload.levelThreeCard : null;
            const canShowLevelThree = (
                payload &&
                payload.visibilityLevel >= 3 &&
                levelThreeCard &&
                String(levelThreeCard.dept_abbr_hr || '').trim() !== ''
            );

            globalKpiNoticeLevel3Section.hidden = !canShowLevelThree;
            if (!canShowLevelThree) {
                return;
            }

            const rows = Array.isArray(payload && payload.levelThreeRows ? payload.levelThreeRows : [])
                ? payload.levelThreeRows
                : [];

            globalKpiNoticeLevel3Table.innerHTML = '';

            if (rows.length < 1) {
                const trEl = document.createElement('tr');
                const tdEl = document.createElement('td');
                tdEl.colSpan = 9;
                tdEl.textContent = getText('kpiNoticeTableEmpty');
                trEl.appendChild(tdEl);
                globalKpiNoticeLevel3Table.appendChild(trEl);
                return;
            }

            rows.forEach((row, index) => {
                const trEl = document.createElement('tr');
                const fullName = currentLang === 'th'
                    ? (row.full_name_th || row.full_name_en || '-')
                    : (row.full_name_en || row.full_name_th || '-');
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
                globalKpiNoticeLevel3Table.appendChild(trEl);
            });
        }

        function populateNoticeHierarchyFilterOptions(rawHierarchy) {
            const levelTwoDepts = new Set();
            const levelThreeDepts = new Set();
            if (globalNoticeViewerDepartment !== '') {
                levelThreeDepts.add(globalNoticeViewerDepartment);
            }
            rawHierarchy.forEach((obj) => {
                const krs = Array.isArray(obj && obj.key_results ? obj.key_results : []) ? obj.key_results : [];
                krs.forEach((kr) => {
                    const levelTwoDept = normalizeGlobalNoticeDepartmentCode(kr && kr.dept_abbr_hr ? kr.dept_abbr_hr : '');
                    if (levelTwoDept !== '') {
                        levelTwoDepts.add(levelTwoDept);
                    }

                    const entries = kr && Array.isArray(kr.kpi_entries) ? kr.kpi_entries : [];
                    entries.forEach((entry) => {
                        // กรองเฉพาะ entry ที่ viewer มีสิทธิ์เห็น
                        if (globalNoticeViewerDepartment !== '') {
                            const entryUserId = Number(entry && entry.app_user_id ? entry.app_user_id : 0);
                            const isOwn = globalNoticeViewerUserId > 0 && entryUserId > 0 && entryUserId === globalNoticeViewerUserId;
                            if (!isOwn) {
                                const tDepts = Array.isArray(entry && entry.target_departments ? entry.target_departments : []) ? entry.target_departments : [];
                                const visible = tDepts.length > 0
                                    ? tDepts.some((d) => normalizeGlobalNoticeDepartmentCode(d) === globalNoticeViewerDepartment)
                                    : normalizeGlobalNoticeDepartmentCode(entry && entry.dept_abbr_hr ? entry.dept_abbr_hr : '') === globalNoticeViewerDepartment;
                                if (!visible) return;
                            }
                        }
                        // เก็บค่าจากคอลัมน์ "ถึง (แผนก)" = target_departments
                        const targetDepts = Array.isArray(entry && entry.target_departments ? entry.target_departments : []) ? entry.target_departments : [];
                        if (targetDepts.length > 0) {
                            targetDepts.forEach((d) => {
                                const norm = normalizeGlobalNoticeDepartmentCode(d);
                                if (norm !== '') levelThreeDepts.add(norm);
                            });
                        } else {
                            const norm = normalizeGlobalNoticeDepartmentCode(entry && entry.dept_abbr_hr ? entry.dept_abbr_hr : '');
                            if (norm !== '') levelThreeDepts.add(norm);
                        }
                    });
                });
            });

            const populateSelect = (selectEl, depts, currentValue) => {
                if (!selectEl) {
                    return '';
                }

                const normalizedCurrent = normalizeGlobalNoticeDepartmentCode(currentValue);
                selectEl.innerHTML = '';

                const allOpt = document.createElement('option');
                allOpt.value = '';
                allOpt.textContent = currentLang === 'th' ? 'ทุกแผนก' : 'All depts';
                selectEl.appendChild(allOpt);

                Array.from(depts).sort().forEach((dept) => {
                    const opt = document.createElement('option');
                    opt.value = dept;
                    opt.textContent = dept;
                    selectEl.appendChild(opt);
                });

                selectEl.value = depts.has(normalizedCurrent) ? normalizedCurrent : '';
                return selectEl.value;
            };

            globalNoticeLevelTwoFilterValue = populateSelect(
                globalKpiNoticeLevelTwoFilterSelect,
                levelTwoDepts,
                globalNoticeLevelTwoFilterValue || (globalKpiNoticeLevelTwoFilterSelect ? globalKpiNoticeLevelTwoFilterSelect.value : '')
            );
            globalNoticeDeptFilterValue = populateSelect(
                globalKpiNoticeDeptFilterSelect,
                levelThreeDepts,
                globalNoticeDeptFilterValue || (globalKpiNoticeDeptFilterSelect ? globalKpiNoticeDeptFilterSelect.value : '')
            );

            if (globalNoticeLevelTwoFilterValue !== '' && globalNoticeDeptFilterValue !== '') {
                globalNoticeDeptFilterValue = '';
                if (globalKpiNoticeDeptFilterSelect) {
                    globalKpiNoticeDeptFilterSelect.value = '';
                }
            }
        }

        function populateNoticeDeptFilterOptions(rawHierarchy) {
            populateNoticeHierarchyFilterOptions(rawHierarchy);
        }

        function renderOkrHierarchySection(payload) {
            if (!globalKpiNoticeOkrSection || !globalKpiNoticeOkrTree) {
                return;
            }

            const rawHierarchy = Array.isArray(payload && payload.okrHierarchy ? payload.okrHierarchy : [])
                ? payload.okrHierarchy
                : [];

            populateNoticeDeptFilterOptions(rawHierarchy);

            globalKpiNoticeOkrSection.hidden = false;
            globalKpiNoticeOkrTree.innerHTML = '';

            const normalizeCellText = (value) => {
                const text = String(value || '').trim();
                return text !== '' ? text : '-';
            };

            const appendCell = (rowEl, value, options = {}) => {
                const cellEl = document.createElement(options.header ? 'th' : 'td');
                const className = String(options.className || '').trim();
                if (className !== '') {
                    cellEl.className = className;
                }
                if (Number(options.colSpan || 0) > 1) {
                    cellEl.colSpan = Number(options.colSpan);
                }
                if (Number(options.rowSpan || 0) > 1) {
                    cellEl.rowSpan = Number(options.rowSpan);
                }
                cellEl.textContent = normalizeCellText(value);
                rowEl.appendChild(cellEl);
                return cellEl;
            };

            const appendFileCell = (rowEl, item, options = {}) => {
                const cellEl = appendCell(rowEl, '', options);
                cellEl.textContent = '';
                const fileName = normalizeCellText(item && item.file_original_name ? item.file_original_name : '');
                const fileUrl = String(item && item.file_url ? item.file_url : '').trim();
                if (fileName === '-' || fileUrl === '') {
                    cellEl.textContent = '-';
                    return cellEl;
                }

                const linkEl = document.createElement('a');
                linkEl.className = 'okr-notice-file-link';
                linkEl.href = fileUrl;
                linkEl.target = '_blank';
                linkEl.rel = 'noopener noreferrer';
                linkEl.textContent = fileName;
                cellEl.appendChild(linkEl);
                return cellEl;
            };

            const appendSourceCell = (rowEl, entry, options = {}) => {
                const cellEl = appendCell(rowEl, '', options);
                cellEl.textContent = '';
                const safeEntry = entry && typeof entry === 'object' ? entry : {};
                const fullName = currentLang === 'th'
                    ? (safeEntry.full_name_th || safeEntry.full_name_en || '')
                    : (safeEntry.full_name_en || safeEntry.full_name_th || '');
                const mainParts = [
                    safeEntry.employee_code || '',
                    fullName || '',
                ].map((part) => String(part || '').trim()).filter((part) => part !== '');
                const metaParts = [
                    normalizeGlobalNoticeDepartmentCode(safeEntry.dept_abbr_hr || ''),
                    safeEntry.position || '',
                ].map((part) => String(part || '').trim()).filter((part) => part !== '');

                const wrapEl = document.createElement('div');
                wrapEl.className = 'okr-notice-source';

                const mainEl = document.createElement('div');
                mainEl.className = 'okr-notice-source-main';
                mainEl.textContent = mainParts.length > 0 ? mainParts.join(' ') : '-';
                wrapEl.appendChild(mainEl);

                if (metaParts.length > 0) {
                    const metaEl = document.createElement('div');
                    metaEl.className = 'okr-notice-source-meta';
                    metaEl.textContent = `[ ${metaParts.join(' ')} ]`;
                    wrapEl.appendChild(metaEl);
                }

                cellEl.appendChild(wrapEl);
                return cellEl;
            };

            const entryMatchesViewerDepartment = (entry) => {
                if (globalNoticeViewerDepartment === '') {
                    return true;
                }

                // เจ้าของ entry เห็น entry ของตนเองเสมอ
                const entryUserId = Number(entry && entry.app_user_id ? entry.app_user_id : 0);
                if (globalNoticeViewerUserId > 0 && entryUserId > 0 && entryUserId === globalNoticeViewerUserId) {
                    return true;
                }

                // กรอง L3 โดยเช็คคอลัมน์ "ถึง (แผนก)" = target_departments
                const targetDepts = Array.isArray(entry && entry.target_departments ? entry.target_departments : [])
                    ? entry.target_departments
                    : [];

                if (targetDepts.length > 0) {
                    return targetDepts.some(
                        (dept) => normalizeGlobalNoticeDepartmentCode(dept) === globalNoticeViewerDepartment
                    );
                }

                // fallback: ถ้าไม่มี target_departments ให้เช็ค dept_abbr_hr ของผู้ส่ง
                const entryDepartment = normalizeGlobalNoticeDepartmentCode(entry && entry.dept_abbr_hr ? entry.dept_abbr_hr : '');
                return entryDepartment === globalNoticeViewerDepartment;
            };

            const entryTargetsDepartment = (entry, departmentCode) => {
                const targetDepartment = normalizeGlobalNoticeDepartmentCode(departmentCode);
                if (targetDepartment === '') {
                    return true;
                }

                const targetDepts = Array.isArray(entry && entry.target_departments ? entry.target_departments : [])
                    ? entry.target_departments
                    : [];
                if (targetDepts.length > 0) {
                    return targetDepts.some((dept) => normalizeGlobalNoticeDepartmentCode(dept) === targetDepartment);
                }

                return normalizeGlobalNoticeDepartmentCode(entry && entry.dept_abbr_hr ? entry.dept_abbr_hr : '') === targetDepartment;
            };

            const resolveVisibleHierarchy = (items) => {
                const hasLevelTwoFilter = globalNoticeLevelTwoFilterValue !== '';
                const hasLevelThreeFilter = globalNoticeDeptFilterValue !== '';

                return items.map((obj) => {
                    let keyResults = Array.isArray(obj && obj.key_results ? obj.key_results : [])
                        ? obj.key_results
                        : [];

                    if (hasLevelTwoFilter) {
                        keyResults = keyResults.filter((kr) => (
                            normalizeGlobalNoticeDepartmentCode(kr && kr.dept_abbr_hr ? kr.dept_abbr_hr : '') === globalNoticeLevelTwoFilterValue
                        ));
                    }

                    const filteredKeyResults = keyResults.map((kr) => {
                        const entries = kr && Array.isArray(kr.kpi_entries) ? kr.kpi_entries : [];
                        const visibleEntries = entries.filter((entry) => entryMatchesViewerDepartment(entry));
                        return {
                            ...kr,
                            kpi_entries: visibleEntries,
                        };
                    });

                    const visibleKeyResults = hasLevelThreeFilter
                        ? filteredKeyResults.map((kr) => {
                            const filteredByDept = (kr.kpi_entries || []).filter((entry) => {
                                return entryTargetsDepartment(entry, globalNoticeDeptFilterValue);
                            });
                            return { ...kr, kpi_entries: filteredByDept };
                        }).filter((kr) => (kr.kpi_entries || []).length > 0)
                        : filteredKeyResults;

                    return {
                        ...obj,
                        key_results: visibleKeyResults,
                    };
                }).filter((obj) => (
                    (!hasLevelTwoFilter && !hasLevelThreeFilter) ||
                    (Array.isArray(obj.key_results) && obj.key_results.length > 0)
                ));
            };

            const hierarchy = resolveVisibleHierarchy(rawHierarchy);
            const isCeoOnlyView = globalNoticeHierarchyViewMode === 'ceo';

            if (hierarchy.length < 1) {
                const emptyEl = document.createElement('p');
                emptyEl.className = 'okr-notice-empty-tree';
                emptyEl.textContent = getText('kpiNoticeOkrNoData');
                globalKpiNoticeOkrTree.appendChild(emptyEl);
                return;
            }

            const tableEl = document.createElement('table');
            tableEl.className = 'okr-notice-excel-table';
            tableEl.classList.toggle('is-ceo-only', isCeoOnlyView);

            const colgroupEl = document.createElement('colgroup');
            const colWidths = isCeoOnlyView
                ? ['80px', '30%', '50%', '20%']
                : [
                    '54px', '210px', '250px', '120px',
                    '100px', '220px', '250px', '120px',
                    '320px', '120px', '210px', '260px', '90px', '110px', '100px',
                ];
            colWidths.forEach((width) => {
                const colEl = document.createElement('col');
                colEl.style.width = width;
                colgroupEl.appendChild(colEl);
            });
            tableEl.appendChild(colgroupEl);

            const theadEl = document.createElement('thead');
            const groupRowEl = document.createElement('tr');
            appendCell(groupRowEl, getText('kpiNoticeSectionLevel1'), {
                header: true,
                colSpan: 4,
                className: 'okr-notice-group-l1',
            });
            if (!isCeoOnlyView) {
                appendCell(groupRowEl, getText('kpiNoticeSectionLevel2'), {
                    header: true,
                    colSpan: 4,
                    className: 'okr-notice-group-l2',
                });
                appendCell(groupRowEl, getText('kpiNoticeSectionLevel3'), {
                    header: true,
                    colSpan: 7,
                    className: 'okr-notice-group-l3',
                });
            }
            theadEl.appendChild(groupRowEl);

            const headerRowEl = document.createElement('tr');
            const headerCols = [
                [getText('kpiNoticeTableNo'), 'okr-notice-group-l1'],
                [getText('kpiNoticeLevel1ObjectiveLabel'), 'okr-notice-group-l1'],
                [getText('kpiNoticeLevel1DetailLabel'), 'okr-notice-group-l1'],
                [getText('kpiNoticeLevel1FileLabel'), 'okr-notice-group-l1'],
            ];
            if (!isCeoOnlyView) {
                headerCols.push(
                    [getText('kpiNoticeDeptLabel'), 'okr-notice-group-l2'],
                    [getText('kpiNoticeLevel2ObjectiveLabel'), 'okr-notice-group-l2'],
                    [getText('kpiNoticeLevel2DetailLabel'), 'okr-notice-group-l2'],
                    [getText('kpiNoticeLevel2FileLabel'), 'okr-notice-group-l2'],
                    [getText('kpiNoticeLevel3OwnerLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3TargetDeptLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3ObjectiveLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3DetailLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3CriteriaLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3TargetLabel'), 'okr-notice-group-l3'],
                    [getText('kpiNoticeLevel3UnitLabel'), 'okr-notice-group-l3'],
                );
            }
            headerCols.forEach(([label, className]) => {
                appendCell(headerRowEl, label, {
                    header: true,
                    className,
                });
            });
            theadEl.appendChild(headerRowEl);
            tableEl.appendChild(theadEl);

            if (isCeoOnlyView) {
                const ceoBodyEl = document.createElement('tbody');
                hierarchy.forEach((obj, objIndex) => {
                    const rowEl = document.createElement('tr');
                    appendCell(rowEl, obj.sort_no || objIndex + 1, { className: 'okr-notice-col-l1' });
                    appendCell(rowEl, obj.title, { className: 'okr-notice-col-l1' });
                    appendCell(rowEl, obj.detail, { className: 'okr-notice-col-l1' });
                    appendFileCell(rowEl, obj, { className: 'okr-notice-col-l1 okr-notice-align-center' });
                    ceoBodyEl.appendChild(rowEl);
                });
                tableEl.appendChild(ceoBodyEl);
                globalKpiNoticeOkrTree.appendChild(tableEl);
                tableEl.style.zoom = String(globalNoticePanelZoom);
                return;
            }

            const getL3OwnerSortKey = (entry) => {
                if (!entry) return '';
                const dept = normalizeGlobalNoticeDepartmentCode(entry.dept_abbr_hr || '');
                const name = currentLang === 'th'
                    ? (entry.full_name_th || entry.full_name_en || '')
                    : (entry.full_name_en || entry.full_name_th || '');
                return `${dept}|${name}`.toLowerCase();
            };

            const getKrL3SortKey = (kr) => {
                const entries = kr && Array.isArray(kr.kpi_entries) ? kr.kpi_entries : [];
                return entries.length > 0 ? getL3OwnerSortKey(entries[0]) : '￿';
            };

            const tbodyEl = document.createElement('tbody');
            hierarchy.forEach((obj, objIndex) => {
                const rawKeyResults = Array.isArray(obj.key_results) ? obj.key_results : [];
                // sort key results by L3 entry owner (dept + name) instead of L2 dept
                const keyResults = rawKeyResults.slice().sort((a, b) => {
                    const ka = getKrL3SortKey(a);
                    const kb = getKrL3SortKey(b);
                    return ka < kb ? -1 : ka > kb ? 1 : 0;
                });
                const objRowspan = Math.max(1, keyResults.reduce((total, kr) => {
                    const entries = kr && Array.isArray(kr.kpi_entries) ? kr.kpi_entries : [];
                    return total + Math.max(1, entries.length);
                }, 0));
                let wroteObjectiveCells = false;

                if (keyResults.length < 1) {
                    const rowEl = document.createElement('tr');
                    appendCell(rowEl, obj.sort_no || objIndex + 1, { className: 'okr-notice-col-l1' });
                    appendCell(rowEl, obj.title, { className: 'okr-notice-col-l1' });
                    appendCell(rowEl, obj.detail, { className: 'okr-notice-col-l1' });
                    appendFileCell(rowEl, obj, { className: 'okr-notice-col-l1 okr-notice-align-center' });
                    for (let i = 0; i < 4 + 7; i += 1) {
                        const centeredIndexes = [0, 3, 5, 8, 9, 10];
                        const baseClass = i < 4 ? 'okr-notice-col-l2' : 'okr-notice-col-l3';
                        const centerClass = centeredIndexes.includes(i) ? ' okr-notice-align-center' : '';
                        appendCell(rowEl, '-', { className: `${baseClass}${centerClass}` });
                    }
                    tbodyEl.appendChild(rowEl);
                    return;
                }

                keyResults.forEach((kr) => {
                    const rawEntries = kr && Array.isArray(kr.kpi_entries) ? kr.kpi_entries : [];
                    // sort entries within each KR by L3 owner (dept + name)
                    const entries = rawEntries.slice().sort((a, b) => {
                        const ka = getL3OwnerSortKey(a);
                        const kb = getL3OwnerSortKey(b);
                        return ka < kb ? -1 : ka > kb ? 1 : 0;
                    });
                    const rowsForKr = entries.length > 0 ? entries : [null];
                    if (rowsForKr.length < 1) {
                        return;
                    }
                    const krRowspan = Math.max(1, rowsForKr.length);

                    rowsForKr.forEach((entry, entryIndex) => {
                        const rowEl = document.createElement('tr');

                        if (!wroteObjectiveCells) {
                            appendCell(rowEl, obj.sort_no || objIndex + 1, {
                                rowSpan: objRowspan,
                                className: 'okr-notice-col-l1',
                            });
                            appendCell(rowEl, obj.title, {
                                rowSpan: objRowspan,
                                className: 'okr-notice-col-l1',
                            });
                            appendCell(rowEl, obj.detail, {
                                rowSpan: objRowspan,
                                className: 'okr-notice-col-l1',
                            });
                            appendFileCell(rowEl, obj, {
                                rowSpan: objRowspan,
                                className: 'okr-notice-col-l1 okr-notice-align-center',
                            });
                            wroteObjectiveCells = true;
                        }

                        if (entryIndex === 0) {
                            appendCell(rowEl, kr.dept_abbr_hr, {
                                rowSpan: krRowspan,
                                className: 'okr-notice-col-l2 okr-notice-align-center',
                            });
                            appendCell(rowEl, kr.title, {
                                rowSpan: krRowspan,
                                className: 'okr-notice-col-l2',
                            });
                            appendCell(rowEl, kr.detail, {
                                rowSpan: krRowspan,
                                className: 'okr-notice-col-l2',
                            });
                            appendFileCell(rowEl, kr, {
                                rowSpan: krRowspan,
                                className: 'okr-notice-col-l2 okr-notice-align-center',
                            });
                        }

                        if (entry) {
                            appendSourceCell(rowEl, entry, { className: 'okr-notice-col-l3' });
                            const targetDepts = Array.isArray(entry.target_departments) ? entry.target_departments : [];
                            const targetDeptText = targetDepts.length > 0
                                ? normalizeGlobalNoticeDepartmentCode(targetDepts[0])
                                : normalizeGlobalNoticeDepartmentCode(entry.dept_abbr_hr || '');
                            appendCell(rowEl, targetDeptText || '-', { className: 'okr-notice-col-l3 okr-notice-align-center' });
                            appendCell(rowEl, entry.objective, { className: 'okr-notice-col-l3' });
                            appendCell(rowEl, entry.detail, { className: 'okr-notice-col-l3' });
                            appendCell(rowEl, entry.target_goal, { className: 'okr-notice-col-l3 okr-notice-align-center' });
                            appendCell(rowEl, entry.target_value, { className: 'okr-notice-col-l3 okr-notice-align-center' });
                            appendCell(rowEl, entry.unit, { className: 'okr-notice-col-l3 okr-notice-align-center' });
                        } else {
                            for (let i = 0; i < 7; i += 1) {
                                const centerClass = [1, 4, 5, 6].includes(i) ? ' okr-notice-align-center' : '';
                                appendCell(rowEl, '-', { className: `okr-notice-col-l3${centerClass}` });
                            }
                        }

                        tbodyEl.appendChild(rowEl);
                    });
                });
            });

            tableEl.appendChild(tbodyEl);
            globalKpiNoticeOkrTree.appendChild(tableEl);
            tableEl.style.zoom = String(globalNoticePanelZoom);
        }

        function renderGlobalNoticeModalContent(payload) {
            const safePayload = payload && typeof payload === 'object'
                ? payload
                : createEmptyGlobalNoticePayload();
            globalNoticeRenderedPayload = safePayload;

            if (globalKpiNoticeLevel1Section) {
                globalKpiNoticeLevel1Section.hidden = true;
            }
            if (globalKpiNoticeLevel2Section) {
                globalKpiNoticeLevel2Section.hidden = true;
            }
            if (globalKpiNoticeLevel3Section) {
                globalKpiNoticeLevel3Section.hidden = true;
            }

            renderOkrHierarchySection(safePayload);
            applyGlobalNoticePanelState();
        }

        function applyGlobalNoticePanelState() {
            if (!globalKpiNoticePanel) {
                return;
            }
            globalKpiNoticePanel.classList.toggle('is-expanded', globalNoticePanelExpanded);
            globalKpiNoticePanel.classList.toggle('is-ceo-view', globalNoticeHierarchyViewMode === 'ceo');
            if (globalKpiNoticeBody) {
                globalKpiNoticeBody.setAttribute('aria-hidden', 'false');
                globalKpiNoticeBody.classList.toggle('is-pan-enabled', globalNoticePanelPanEnabled);
                if (!globalNoticePanelPanEnabled) {
                    globalKpiNoticeBody.classList.remove('is-panning');
                }
            }
        }

        function clampGlobalNoticePanelZoom(value) {
            const next = Number(value || 1);
            if (!Number.isFinite(next)) {
                return 1;
            }
            return Math.min(1.8, Math.max(0.7, Math.round(next * 100) / 100));
        }

        function applyGlobalNoticePanelZoom() {
            if (globalKpiNoticeOkrTree) {
                const tableEl = globalKpiNoticeOkrTree.querySelector('table');
                if (tableEl) {
                    tableEl.style.zoom = String(globalNoticePanelZoom);
                }
            }
            if (globalKpiNoticeContent) {
                globalKpiNoticeContent.style.transform = '';
                globalKpiNoticeContent.style.width = '';
            }
            if (globalKpiNoticeZoomValue) {
                globalKpiNoticeZoomValue.textContent = `${Math.round(globalNoticePanelZoom * 100)}%`;
            }
        }

        function setGlobalNoticePanelZoom(nextZoom) {
            globalNoticePanelZoom = clampGlobalNoticePanelZoom(nextZoom);
            applyGlobalNoticePanelZoom();
            syncGlobalNoticePanelActions();
        }

        function stepGlobalNoticePanelZoom(delta) {
            setGlobalNoticePanelZoom(globalNoticePanelZoom + delta);
        }

        function setGlobalNoticePanelPanEnabled(enabled) {
            globalNoticePanelPanEnabled = enabled === true;
            applyGlobalNoticePanelState();
            syncGlobalNoticePanelActions();
        }

        function normalizeGlobalNoticePanelRect(rect) {
            if (!rect || typeof rect !== 'object') {
                return null;
            }

            const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const margin = 8;
            const maxWidth = Math.max(220, viewportWidth - (margin * 2));
            const maxHeight = Math.max(180, viewportHeight - (margin * 2));
            const minWidth = Math.min(420, maxWidth);
            const minHeight = Math.min(220, maxHeight);

            let width = Number(rect.width) || minWidth;
            let height = Number(rect.height) || minHeight;
            let left = Number(rect.left) || margin;
            let top = Number(rect.top) || margin;

            width = Math.min(maxWidth, Math.max(minWidth, width));
            height = Math.min(maxHeight, Math.max(minHeight, height));
            left = Math.min(viewportWidth - margin - width, Math.max(margin, left));
            top = Math.min(viewportHeight - margin - height, Math.max(margin, top));

            return { left, top, width, height };
        }

        function setGlobalNoticePanelRect(rect) {
            if (!globalKpiNoticePanel) {
                return;
            }
            const normalized = normalizeGlobalNoticePanelRect(rect);
            if (!normalized) {
                return;
            }
            globalKpiNoticePanel.style.left = `${normalized.left}px`;
            globalKpiNoticePanel.style.top = `${normalized.top}px`;
            globalKpiNoticePanel.style.right = 'auto';
            globalKpiNoticePanel.style.bottom = 'auto';
            globalKpiNoticePanel.style.width = `${normalized.width}px`;
            globalKpiNoticePanel.style.height = `${normalized.height}px`;
            globalKpiNoticePanel.style.maxHeight = 'none';
        }

        function clearGlobalNoticePanelRect() {
            if (!globalKpiNoticePanel) {
                return;
            }
            globalKpiNoticePanel.style.left = '';
            globalKpiNoticePanel.style.top = '';
            globalKpiNoticePanel.style.right = '';
            globalKpiNoticePanel.style.bottom = '';
            globalKpiNoticePanel.style.width = '';
            globalKpiNoticePanel.style.height = '';
            globalKpiNoticePanel.style.maxHeight = '';
        }

        function ensureGlobalNoticePanelManualMode() {
            if (!globalKpiNoticePanel) {
                return;
            }
            const rect = globalKpiNoticePanel.getBoundingClientRect();
            globalNoticePanelExpanded = false;
            applyGlobalNoticePanelState();
            setGlobalNoticePanelRect(rect);
        }

        function syncGlobalNoticePanelActions() {
            if (globalKpiNoticeExpandBtn) {
                const key = globalNoticePanelExpanded ? 'kpiNoticeModalRestore' : 'kpiNoticeModalExpand';
                const label = getText(key);
                globalKpiNoticeExpandBtn.setAttribute('aria-label', label);
                globalKpiNoticeExpandBtn.setAttribute('title', label);
                globalKpiNoticeExpandBtn.innerHTML = globalNoticePanelExpanded ? '&#x2750;' : '&#x26F6;';
                globalKpiNoticeExpandBtn.classList.toggle('is-active', globalNoticePanelExpanded);
            }

            if (globalKpiNoticeResetFilterBtn) {
                const label = getText('kpiNoticeDeptFilterShowAll');
                globalKpiNoticeResetFilterBtn.setAttribute('aria-label', getText('kpiNoticeFilterAll'));
                globalKpiNoticeResetFilterBtn.setAttribute('title', label);
                globalKpiNoticeResetFilterBtn.classList.toggle(
                    'is-active',
                    globalNoticeHierarchyViewMode === 'all' &&
                    globalNoticeLevelTwoFilterValue === '' &&
                    globalNoticeDeptFilterValue === ''
                );
            }

            if (globalKpiNoticeCeoFilterBtn) {
                const label = getText('kpiNoticeCeoFilterShow');
                globalKpiNoticeCeoFilterBtn.textContent = getText('kpiNoticeFilterCeo');
                globalKpiNoticeCeoFilterBtn.setAttribute('aria-label', label);
                globalKpiNoticeCeoFilterBtn.setAttribute('title', label);
                globalKpiNoticeCeoFilterBtn.classList.toggle('is-active', globalNoticeHierarchyViewMode === 'ceo');
            }

            if (globalKpiNoticeLevelTwoFilterSelect) {
                const label = globalNoticeLevelTwoFilterValue !== ''
                    ? formatGlobalNoticeText(getText('kpiNoticeLevel2FilterShowDept'), { dept: globalNoticeLevelTwoFilterValue })
                    : getText('kpiNoticeLevel2FilterShowAll');
                globalKpiNoticeLevelTwoFilterSelect.setAttribute('aria-label', getText('kpiNoticeLevel2FilterLabel'));
                globalKpiNoticeLevelTwoFilterSelect.setAttribute('title', label);
                globalKpiNoticeLevelTwoFilterSelect.classList.toggle('is-active', globalNoticeLevelTwoFilterValue !== '');
            }

            if (globalKpiNoticeDeptFilterSelect) {
                globalKpiNoticeDeptFilterSelect.classList.toggle('is-active', globalNoticeDeptFilterValue !== '');
                const label = globalNoticeDeptFilterValue !== ''
                    ? formatGlobalNoticeText(getText('kpiNoticeDeptFilterShowDept'), { dept: globalNoticeDeptFilterValue })
                    : getText('kpiNoticeDeptFilterShowAll');
                globalKpiNoticeDeptFilterSelect.setAttribute('aria-label', getText('kpiNoticeLevel3FilterLabel'));
                globalKpiNoticeDeptFilterSelect.setAttribute('title', label);
            }

            if (globalKpiNoticeZoomOutBtn) {
                const label = getText('kpiNoticeModalZoomOut');
                globalKpiNoticeZoomOutBtn.setAttribute('aria-label', label);
                globalKpiNoticeZoomOutBtn.setAttribute('title', label);
                globalKpiNoticeZoomOutBtn.disabled = globalNoticePanelZoom <= 0.7001;
            }

            if (globalKpiNoticeZoomInBtn) {
                const label = getText('kpiNoticeModalZoomIn');
                globalKpiNoticeZoomInBtn.setAttribute('aria-label', label);
                globalKpiNoticeZoomInBtn.setAttribute('title', label);
                globalKpiNoticeZoomInBtn.disabled = globalNoticePanelZoom >= 1.7999;
            }

            if (globalKpiNoticePanBtn) {
                const key = globalNoticePanelPanEnabled ? 'kpiNoticeModalPanDisable' : 'kpiNoticeModalPanEnable';
                const label = getText(key);
                globalKpiNoticePanBtn.setAttribute('aria-label', label);
                globalKpiNoticePanBtn.setAttribute('title', label);
                globalKpiNoticePanBtn.classList.toggle('is-active', globalNoticePanelPanEnabled);
            }

            if (globalKpiNoticeCloseBtn) {
                const closeLabel = getText('kpiNoticeModalClose');
                globalKpiNoticeCloseBtn.setAttribute('aria-label', closeLabel);
                globalKpiNoticeCloseBtn.setAttribute('title', closeLabel);
            }

            if (globalKpiNoticeZoomValue) {
                globalKpiNoticeZoomValue.textContent = `${Math.round(globalNoticePanelZoom * 100)}%`;
            }
        }

        function setGlobalNoticePanelExpanded(expanded) {
            globalNoticePanelExpanded = expanded === true;
            if (globalNoticePanelExpanded) {
                clearGlobalNoticePanelRect();
            }
            applyGlobalNoticePanelState();
            syncGlobalNoticePanelActions();
        }

        function resetGlobalNoticePanelState() {
            globalNoticePanelExpanded = false;
            globalNoticePanelPanEnabled = false;
            globalNoticePanelZoom = 1;
            globalNoticeHierarchyViewMode = 'all';
            globalNoticeLevelTwoFilterValue = '';
            globalNoticeDeptFilterValue = globalNoticeViewerDepartment;
            if (globalKpiNoticeLevelTwoFilterSelect) {
                globalKpiNoticeLevelTwoFilterSelect.value = '';
            }
            if (globalKpiNoticeDeptFilterSelect) {
                globalKpiNoticeDeptFilterSelect.value = globalNoticeViewerDepartment;
            }
            clearGlobalNoticePanelRect();
            applyGlobalNoticePanelZoom();
            applyGlobalNoticePanelState();
            syncGlobalNoticePanelActions();
        }

        async function loadGlobalNoticePayload(forceRefresh = false, requestedDepartment = '') {
            const normalizedDepartment = normalizeGlobalNoticeDepartmentCode(requestedDepartment);
            const cacheKey = `${currentLang}:${normalizedDepartment}`;
            if (forceRefresh) {
                globalNoticeDataCache = null;
            }
            if (!forceRefresh && globalNoticeDataCache && globalNoticeDataCache.cacheKey === cacheKey) {
                return globalNoticeDataCache.payload;
            }
            if (globalNoticeLoadingPromise) {
                return globalNoticeLoadingPromise;
            }
            if (!globalNoticeDataUrl) {
                const emptyPayload = createEmptyGlobalNoticePayload();
                globalNoticeDataCache = emptyPayload;
                return emptyPayload;
            }

            const endpoint = withLangParam(globalNoticeDataUrl);
            if (!endpoint) {
                const emptyPayload = createEmptyGlobalNoticePayload();
                globalNoticeDataCache = {
                    cacheKey,
                    payload: emptyPayload,
                };
                return emptyPayload;
            }

            const endpointUrl = new URL(endpoint, window.location.origin);
            if (normalizedDepartment !== '') {
                endpointUrl.searchParams.set('dept', normalizedDepartment);
            } else {
                endpointUrl.searchParams.delete('dept');
            }

            globalNoticeLoadingPromise = fetch(`${endpointUrl.pathname}${endpointUrl.search}${endpointUrl.hash}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(async (response) => {
                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        throw new Error('failed_to_load_notice_payload');
                    }

                    const normalizedPayload = normalizeGlobalNoticePayload(payload);
                    globalNoticeDataCache = {
                        cacheKey,
                        payload: normalizedPayload,
                    };
                    return normalizedPayload;
                })
                .finally(() => {
                    globalNoticeLoadingPromise = null;
                });

            return globalNoticeLoadingPromise;
        }

        async function openGlobalNoticeModal(forceRefresh = false, requestedDepartment = '') {
            if (!globalKpiNoticeModal) {
                return;
            }

            const wasOpen = globalKpiNoticeModal.classList.contains('show');
            if (!wasOpen) {
                resetGlobalNoticePanelState();
            }

            try {
                const payload = await loadGlobalNoticePayload(forceRefresh, requestedDepartment);
                renderGlobalNoticeModalContent(payload);
            } catch (error) {
                renderGlobalNoticeModalContent(createEmptyGlobalNoticePayload());
                openAlert(getText('kpiNoticeNoData'));
            }

            globalKpiNoticeModal.classList.add('show');
            globalKpiNoticeModal.setAttribute('aria-hidden', 'false');
            syncGlobalNoticePanelActions();
        }

        function closeGlobalNoticeModal() {
            if (!globalKpiNoticeModal) {
                return;
            }
            globalKpiNoticeModal.classList.remove('show');
            globalKpiNoticeModal.setAttribute('aria-hidden', 'true');
        }

        window.openKpiOrganizationNoticeModal = (options = {}) => {
            const settings = options && typeof options === 'object' ? options : {};
            const department = normalizeGlobalNoticeDepartmentCode(settings.department || '');
            const forceRefresh = settings.forceRefresh === true;
            openGlobalNoticeModal(forceRefresh, department);
        };

        document.addEventListener('app:notice-department-changed', () => {
            if (!globalKpiNoticeModal || !globalKpiNoticeModal.classList.contains('show')) {
                return;
            }
            const activeDepartment = (
                typeof window.resolveGlobalNoticeDepartment === 'function'
                    ? normalizeGlobalNoticeDepartmentCode(window.resolveGlobalNoticeDepartment())
                    : ''
            );
            openGlobalNoticeModal(true, activeDepartment);
        });

        function openGlobalNoticeTarget() {
            if (globalKpiNoticeModal && globalKpiNoticeModal.classList.contains('show')) {
                closeGlobalNoticeModal();
                return;
            }
            const activeDepartment = (
                typeof window.resolveGlobalNoticeDepartment === 'function'
                    ? normalizeGlobalNoticeDepartmentCode(window.resolveGlobalNoticeDepartment())
                    : ''
            );
            const department = globalNoticeViewerDepartment || activeDepartment;
            openGlobalNoticeModal(false, department);
        }

        function setupGlobalNoticeFab() {
            if (!globalNoticeFab) {
                return;
            }

            restoreGlobalNoticeFabPosition();
            let dragState = null;
            let panelMoveState = null;
            let panelResizeState = null;
            let panelPanState = null;

            const releaseDrag = (event) => {
                if (!dragState || event.pointerId !== dragState.pointerId) {
                    return;
                }
                if (globalNoticeFab.hasPointerCapture && globalNoticeFab.hasPointerCapture(event.pointerId)) {
                    globalNoticeFab.releasePointerCapture(event.pointerId);
                }
                globalNoticeFab.classList.remove('is-dragging');
                if (dragState.moved) {
                    globalNoticeFabIgnoreClick = true;
                    setGlobalNoticeFabPosition(dragState.left, dragState.top, true);
                }
                dragState = null;
            };

            const releasePanelMove = () => {
                panelMoveState = null;
                if (globalKpiNoticePanel) {
                    globalKpiNoticePanel.classList.remove('is-moving');
                }
            };

            const releasePanelResize = () => {
                panelResizeState = null;
                if (globalKpiNoticePanel) {
                    globalKpiNoticePanel.classList.remove('is-resizing');
                }
            };

            const releasePanelPan = () => {
                panelPanState = null;
                if (globalKpiNoticeBody) {
                    globalKpiNoticeBody.classList.remove('is-panning');
                }
            };

            const resolveGlobalNoticePanTargets = () => {
                const el = globalKpiNoticeOkrTree || globalKpiNoticeBody;
                return el ? [{ element: el }] : [];
            };

            globalNoticeFab.addEventListener('pointerdown', (event) => {
                if (event.pointerType === 'mouse' && event.button !== 0) {
                    return;
                }
                const rect = globalNoticeFab.getBoundingClientRect();
                dragState = {
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    startLeft: rect.left,
                    startTop: rect.top,
                    left: rect.left,
                    top: rect.top,
                    moved: false,
                };
                if (globalNoticeFab.setPointerCapture) {
                    globalNoticeFab.setPointerCapture(event.pointerId);
                }
                globalNoticeFab.classList.add('is-dragging');
                event.preventDefault();
            });

            globalNoticeFab.addEventListener('pointermove', (event) => {
                if (!dragState || event.pointerId !== dragState.pointerId) {
                    return;
                }
                const deltaX = event.clientX - dragState.startX;
                const deltaY = event.clientY - dragState.startY;
                if (!dragState.moved && Math.abs(deltaX) + Math.abs(deltaY) < 6) {
                    return;
                }
                dragState.moved = true;
                const nextLeft = dragState.startLeft + deltaX;
                const nextTop = dragState.startTop + deltaY;
                const clamped = clampGlobalNoticeFabPosition(nextLeft, nextTop);
                dragState.left = clamped.left;
                dragState.top = clamped.top;
                setGlobalNoticeFabPosition(clamped.left, clamped.top, false);
                event.preventDefault();
            });

            globalNoticeFab.addEventListener('pointerup', releaseDrag);
            globalNoticeFab.addEventListener('pointercancel', releaseDrag);
            globalNoticeFab.addEventListener('click', (event) => {
                if (globalNoticeFabIgnoreClick) {
                    globalNoticeFabIgnoreClick = false;
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                openGlobalNoticeTarget();
            });

            if (globalKpiNoticeHead && globalKpiNoticePanel) {
                globalKpiNoticeHead.addEventListener('pointerdown', (event) => {
                    if (event.pointerType === 'mouse' && event.button !== 0) {
                        return;
                    }
                    if (event.target && event.target.closest('.global-kpi-notice-head-actions')) {
                        return;
                    }

                    if (globalNoticePanelExpanded) {
                        ensureGlobalNoticePanelManualMode();
                    }

                    const rect = globalKpiNoticePanel.getBoundingClientRect();
                    panelMoveState = {
                        pointerId: event.pointerId,
                        startX: event.clientX,
                        startY: event.clientY,
                        startLeft: rect.left,
                        startTop: rect.top,
                        width: rect.width,
                        height: rect.height,
                    };
                    globalKpiNoticePanel.classList.add('is-moving');
                    if (globalKpiNoticeHead.setPointerCapture) {
                        globalKpiNoticeHead.setPointerCapture(event.pointerId);
                    }
                    event.preventDefault();
                });
            }

            if (globalKpiNoticePanel && globalKpiNoticeResizeHandles.length > 0) {
                globalKpiNoticeResizeHandles.forEach((handleEl) => {
                    handleEl.addEventListener('pointerdown', (event) => {
                        if (event.pointerType === 'mouse' && event.button !== 0) {
                            return;
                        }
                        const direction = String(handleEl.dataset.resize || '').trim().toLowerCase();
                        if (direction === '') {
                            return;
                        }

                        if (globalNoticePanelExpanded) {
                            ensureGlobalNoticePanelManualMode();
                        }

                        const rect = globalKpiNoticePanel.getBoundingClientRect();
                        panelResizeState = {
                            pointerId: event.pointerId,
                            direction,
                            startX: event.clientX,
                            startY: event.clientY,
                            startLeft: rect.left,
                            startTop: rect.top,
                            startWidth: rect.width,
                            startHeight: rect.height,
                        };
                        globalKpiNoticePanel.classList.add('is-resizing');
                        if (handleEl.setPointerCapture) {
                            handleEl.setPointerCapture(event.pointerId);
                        }
                        event.preventDefault();
                        event.stopPropagation();
                    });
                });
            }

            if (globalKpiNoticeBody) {
                globalKpiNoticeBody.addEventListener('pointerdown', (event) => {
                    if (!globalNoticePanelPanEnabled) {
                        return;
                    }
                    if (event.pointerType === 'mouse' && event.button !== 0) {
                        return;
                    }
                    if (event.target && event.target.closest('a,button,input,select,textarea,label')) {
                        return;
                    }
                    const scrollTargets = resolveGlobalNoticePanTargets();
                    if (scrollTargets.length < 1) {
                        return;
                    }
                    panelPanState = {
                        pointerId: event.pointerId,
                        lastX: event.clientX,
                        lastY: event.clientY,
                        scrollTargets,
                    };
                    globalKpiNoticeBody.classList.add('is-panning');
                    if (globalKpiNoticeBody.setPointerCapture) {
                        globalKpiNoticeBody.setPointerCapture(event.pointerId);
                    }
                    event.preventDefault();
                });

                globalKpiNoticeBody.addEventListener('pointermove', (event) => {
                    if (!panelPanState || event.pointerId !== panelPanState.pointerId) {
                        return;
                    }
                    const dx = event.clientX - panelPanState.lastX;
                    const dy = event.clientY - panelPanState.lastY;
                    panelPanState.lastX = event.clientX;
                    panelPanState.lastY = event.clientY;
                    panelPanState.scrollTargets.forEach((target) => {
                        target.element.scrollLeft -= dx;
                        target.element.scrollTop -= dy;
                    });
                    event.preventDefault();
                });

                globalKpiNoticeBody.addEventListener('pointerup', (event) => {
                    if (!panelPanState || event.pointerId !== panelPanState.pointerId) {
                        return;
                    }
                    releasePanelPan();
                });

                globalKpiNoticeBody.addEventListener('pointercancel', (event) => {
                    if (!panelPanState || event.pointerId !== panelPanState.pointerId) {
                        return;
                    }
                    releasePanelPan();
                });
            }

            window.addEventListener('pointermove', (event) => {
                if (panelMoveState && event.pointerId === panelMoveState.pointerId) {
                    const deltaX = event.clientX - panelMoveState.startX;
                    const deltaY = event.clientY - panelMoveState.startY;
                    setGlobalNoticePanelRect({
                        left: panelMoveState.startLeft + deltaX,
                        top: panelMoveState.startTop + deltaY,
                        width: panelMoveState.width,
                        height: panelMoveState.height,
                    });
                    event.preventDefault();
                    return;
                }

                if (panelResizeState && event.pointerId === panelResizeState.pointerId) {
                    const deltaX = event.clientX - panelResizeState.startX;
                    const deltaY = event.clientY - panelResizeState.startY;
                    let left = panelResizeState.startLeft;
                    let top = panelResizeState.startTop;
                    let width = panelResizeState.startWidth;
                    let height = panelResizeState.startHeight;
                    const direction = panelResizeState.direction;

                    if (direction.includes('e')) {
                        width = panelResizeState.startWidth + deltaX;
                    }
                    if (direction.includes('s')) {
                        height = panelResizeState.startHeight + deltaY;
                    }
                    if (direction.includes('w')) {
                        width = panelResizeState.startWidth - deltaX;
                        left = panelResizeState.startLeft + deltaX;
                    }
                    if (direction.includes('n')) {
                        height = panelResizeState.startHeight - deltaY;
                        top = panelResizeState.startTop + deltaY;
                    }

                    setGlobalNoticePanelRect({ left, top, width, height });
                    event.preventDefault();
                }
            });

            window.addEventListener('pointerup', (event) => {
                if (panelMoveState && event.pointerId === panelMoveState.pointerId) {
                    releasePanelMove();
                }
                if (panelResizeState && event.pointerId === panelResizeState.pointerId) {
                    releasePanelResize();
                }
            });

            window.addEventListener('pointercancel', (event) => {
                if (panelMoveState && event.pointerId === panelMoveState.pointerId) {
                    releasePanelMove();
                }
                if (panelResizeState && event.pointerId === panelResizeState.pointerId) {
                    releasePanelResize();
                }
            });

            if (globalKpiNoticeCloseBtn) {
                globalKpiNoticeCloseBtn.addEventListener('click', () => {
                    closeGlobalNoticeModal();
                });
            }

            if (globalKpiNoticeExpandBtn) {
                globalKpiNoticeExpandBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setGlobalNoticePanelExpanded(!globalNoticePanelExpanded);
                });
            }

            if (globalKpiNoticeResetFilterBtn) {
                globalKpiNoticeResetFilterBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    globalNoticeHierarchyViewMode = 'all';
                    globalNoticeLevelTwoFilterValue = '';
                    globalNoticeDeptFilterValue = '';
                    if (globalKpiNoticeLevelTwoFilterSelect) {
                        globalKpiNoticeLevelTwoFilterSelect.value = '';
                    }
                    if (globalKpiNoticeDeptFilterSelect) {
                        globalKpiNoticeDeptFilterSelect.value = '';
                    }
                    renderGlobalNoticeModalContent(globalNoticeRenderedPayload || createEmptyGlobalNoticePayload());
                    syncGlobalNoticePanelActions();
                });
            }

            if (globalKpiNoticeCeoFilterBtn) {
                globalKpiNoticeCeoFilterBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    globalNoticeHierarchyViewMode = 'ceo';
                    globalNoticeLevelTwoFilterValue = '';
                    globalNoticeDeptFilterValue = '';
                    if (globalKpiNoticeLevelTwoFilterSelect) {
                        globalKpiNoticeLevelTwoFilterSelect.value = '';
                    }
                    if (globalKpiNoticeDeptFilterSelect) {
                        globalKpiNoticeDeptFilterSelect.value = '';
                    }
                    renderGlobalNoticeModalContent(globalNoticeRenderedPayload || createEmptyGlobalNoticePayload());
                    syncGlobalNoticePanelActions();
                });
            }

            if (globalKpiNoticeLevelTwoFilterSelect) {
                globalKpiNoticeLevelTwoFilterSelect.addEventListener('change', () => {
                    globalNoticeHierarchyViewMode = 'all';
                    globalNoticeLevelTwoFilterValue = normalizeGlobalNoticeDepartmentCode(globalKpiNoticeLevelTwoFilterSelect.value);
                    if (globalNoticeLevelTwoFilterValue !== '') {
                        globalNoticeDeptFilterValue = '';
                        if (globalKpiNoticeDeptFilterSelect) {
                            globalKpiNoticeDeptFilterSelect.value = '';
                        }
                    }
                    renderGlobalNoticeModalContent(globalNoticeRenderedPayload || createEmptyGlobalNoticePayload());
                    syncGlobalNoticePanelActions();
                });
            }

            if (globalKpiNoticeDeptFilterSelect) {
                globalKpiNoticeDeptFilterSelect.addEventListener('change', () => {
                    globalNoticeHierarchyViewMode = 'all';
                    globalNoticeDeptFilterValue = normalizeGlobalNoticeDepartmentCode(globalKpiNoticeDeptFilterSelect.value);
                    if (globalNoticeDeptFilterValue !== '') {
                        globalNoticeLevelTwoFilterValue = '';
                        if (globalKpiNoticeLevelTwoFilterSelect) {
                            globalKpiNoticeLevelTwoFilterSelect.value = '';
                        }
                    }
                    renderGlobalNoticeModalContent(globalNoticeRenderedPayload || createEmptyGlobalNoticePayload());
                    syncGlobalNoticePanelActions();
                });
            }

            if (globalKpiNoticeZoomOutBtn) {
                globalKpiNoticeZoomOutBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    stepGlobalNoticePanelZoom(-0.1);
                });
            }

            if (globalKpiNoticeZoomInBtn) {
                globalKpiNoticeZoomInBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    stepGlobalNoticePanelZoom(0.1);
                });
            }

            if (globalKpiNoticePanBtn) {
                globalKpiNoticePanBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setGlobalNoticePanelPanEnabled(!globalNoticePanelPanEnabled);
                });
            }

            if (globalKpiNoticeModal) {
                globalKpiNoticeModal.addEventListener('click', (event) => {
                    if (event.target === globalKpiNoticeModal) {
                        closeGlobalNoticeModal();
                    }
                });
            }

            window.addEventListener('resize', () => {
                if (!globalNoticeFab.style.left || !globalNoticeFab.style.top) {
                    // keep default anchor
                } else {
                    const left = Number.parseFloat(globalNoticeFab.style.left);
                    const top = Number.parseFloat(globalNoticeFab.style.top);
                    if (Number.isFinite(left) && Number.isFinite(top)) {
                        setGlobalNoticeFabPosition(left, top, true);
                    }
                }
                if (!globalKpiNoticePanel || globalNoticePanelExpanded) {
                    return;
                }
                const panelRect = globalKpiNoticePanel.getBoundingClientRect();
                setGlobalNoticePanelRect(panelRect);
            });

            applyGlobalNoticePanelZoom();
            syncGlobalNoticePanelActions();
            applyGlobalNoticePanelState();
        }

        function formatNotificationTime(value) {
            if (! value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleString();
        }

        function setNotificationBadge(count) {
            notificationsUnreadCount = Number.isFinite(Number(count)) ? Number(count) : 0;
            if (!notifyBadge) return;
            if (notificationsUnreadCount > 0) {
                notifyBadge.style.display = 'inline-block';
                notifyBadge.textContent = String(notificationsUnreadCount > 99 ? '99+' : notificationsUnreadCount);
            } else {
                notifyBadge.style.display = 'none';
                notifyBadge.textContent = '0';
            }
        }

        function normalizeNotificationDecision(value) {
            const status = String(value || '').trim().toLowerCase();
            if (status === 'approved') return 'approved';
            if (status === 'rejected') return 'rejected';
            return '';
        }

        function normalizeNotificationPassFlag(value) {
            if (value === true || value === 1 || value === '1') return '1';
            if (value === false || value === 0 || value === '0') return '0';
            return '';
        }

        function resolveNotificationAudienceTab(type) {
            return String(type || '').trim().toLowerCase() === 'kpi_review_requested'
                ? 'reviewer'
                : 'user';
        }

        function resolveNotificationUnreadCountsByTab(items) {
            const counts = {
                user: 0,
                reviewer: 0,
            };

            (Array.isArray(items) ? items : []).forEach((item) => {
                if (!item || item.is_read) {
                    return;
                }
                const tab = resolveNotificationAudienceTab(item.type || '');
                if (tab === 'reviewer') {
                    counts.reviewer += 1;
                } else {
                    counts.user += 1;
                }
            });

            return counts;
        }

        function setNotifyTabCount(el, count) {
            if (!el) {
                return;
            }
            const safeCount = Number.isFinite(Number(count)) ? Number(count) : 0;
            if (safeCount > 0) {
                el.style.display = 'inline-block';
                el.textContent = String(safeCount > 99 ? '99+' : safeCount);
            } else {
                el.style.display = 'none';
                el.textContent = '0';
            }
        }

        function syncNotifyTabUnreadCounts(items) {
            const counts = resolveNotificationUnreadCountsByTab(items);
            setNotifyTabCount(notifyTabUserCountEl, counts.user);
            setNotifyTabCount(notifyTabReviewerCountEl, counts.reviewer);
        }

        function getNotificationEmptyKeyByActiveTab() {
            return notifyActiveTab === 'reviewer' ? 'notifyEmptyReviewer' : 'notifyEmptyUser';
        }

        function setNotifyActiveTab(tab) {
            notifyActiveTab = tab === 'reviewer' ? 'reviewer' : 'user';
            if (notifyTabUserBtn) {
                notifyTabUserBtn.classList.toggle('is-active', notifyActiveTab === 'user');
            }
            if (notifyTabReviewerBtn) {
                notifyTabReviewerBtn.classList.toggle('is-active', notifyActiveTab === 'reviewer');
            }
            renderNotifications(notificationsState);
        }

        function normalizeNotificationTextValue(value) {
            const text = String(value || '').trim();
            return text !== '' ? text : '-';
        }

        function truncateNotificationTopic(value, maxChars = 60) {
            const normalized = normalizeNotificationTextValue(value);
            if (normalized === '-') {
                return normalized;
            }

            const safeMaxChars = Number.isFinite(Number(maxChars)) ? Number(maxChars) : 60;
            const chars = Array.from(normalized);
            if (chars.length <= safeMaxChars) {
                return normalized;
            }

            return `${chars.slice(0, safeMaxChars).join('').trimEnd()}...`;
        }

        function normalizeNotificationMonthValue(value) {
            const monthNo = Number(value || 0);
            if (!Number.isFinite(monthNo) || monthNo < 1) {
                return '-';
            }
            return String(monthNo);
        }

        function resolveNotificationDecisionText(value) {
            const decision = normalizeNotificationDecision(value);
            if (decision === 'approved') {
                return getText('kpiCardReportStatusApproved');
            }
            if (decision === 'rejected') {
                return getText('kpiCardReportStatusRejected');
            }
            return '-';
        }

        function appendNotificationLine(containerEl, label, value, extraValueClass = '') {
            const lineEl = document.createElement('p');
            lineEl.className = 'notify-item-line';

            const labelEl = document.createElement('span');
            labelEl.className = 'notify-item-label';
            labelEl.textContent = `${label}: `;

            const valueEl = document.createElement('span');
            valueEl.className = `notify-item-value${extraValueClass ? ` ${extraValueClass}` : ''}`;
            valueEl.textContent = normalizeNotificationTextValue(value);

            lineEl.appendChild(labelEl);
            lineEl.appendChild(valueEl);
            containerEl.appendChild(lineEl);
        }

        function appendNotificationNote(containerEl, noteText) {
            const noteEl = document.createElement('p');
            noteEl.className = 'notify-item-note';
            noteEl.textContent = normalizeNotificationTextValue(noteText);
            containerEl.appendChild(noteEl);
        }

        function buildNotificationTargetUrl(rawUrl, itemBtn) {
            const baseUrl = withLangParam(rawUrl);
            if (!baseUrl) {
                return '';
            }

            const notifyType = itemBtn ? String(itemBtn.dataset.notifyType || '').trim() : '';
            if (notifyType !== 'kpi_review_result' && notifyType !== 'kpi_review_requested') {
                return baseUrl;
            }

            try {
                const url = new URL(baseUrl, window.location.origin);
                const scoreId = Number(itemBtn.dataset.focusScoreId || 0);
                const monthNo = Number(itemBtn.dataset.focusMonthNo || 0);
                const decision = normalizeNotificationDecision(itemBtn.dataset.focusDecision || '');
                const passFlag = normalizeNotificationPassFlag(itemBtn.dataset.focusPass || '');
                const cycleId = Number(itemBtn.dataset.focusCycleId || 0);
                const departmentCode = String(itemBtn.dataset.focusDepartment || '').trim().toUpperCase();

                url.searchParams.set('focus_from', 'notification');
                if (scoreId > 0) {
                    url.searchParams.set('focus_score_id', String(scoreId));
                }
                if (monthNo >= 1 && monthNo <= 12) {
                    url.searchParams.set('focus_month_no', String(monthNo));
                }
                if (decision === 'approved' || decision === 'rejected') {
                    url.searchParams.set('focus_decision', decision);
                }
                if (passFlag === '1' || passFlag === '0') {
                    url.searchParams.set('focus_pass', passFlag);
                }
                if (notifyType === 'kpi_review_requested') {
                    url.searchParams.set('focus_highlight', 'pending');
                }
                if (cycleId > 0) {
                    url.searchParams.set('cycle_id', String(cycleId));
                }
                if (departmentCode !== '') {
                    url.searchParams.set('dept', departmentCode);
                }

                return `${url.pathname}${url.search}${url.hash}`;
            } catch (error) {
                return baseUrl;
            }
        }

        function renderNotifications(items) {
            if (!notifyList) return;
            notificationsState = Array.isArray(items) ? items : [];
            syncNotifyTabUnreadCounts(notificationsState);
            const filteredItems = notificationsState.filter((item) => (
                resolveNotificationAudienceTab(item && item.type ? item.type : '') === notifyActiveTab
            ));
            notifyList.innerHTML = '';
            if (filteredItems.length < 1) {
                const emptyEl = document.createElement('p');
                emptyEl.className = 'notify-empty';
                emptyEl.textContent = getText(getNotificationEmptyKeyByActiveTab());
                notifyList.appendChild(emptyEl);
                return;
            }

            filteredItems.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `notify-item${item && item.is_read ? '' : ' unread'}`;
                button.dataset.id = String(item && item.id ? item.id : 0);
                button.dataset.url = item && item.link_url ? String(item.link_url) : '';
                button.dataset.notifyType = '';
                button.dataset.focusScoreId = '';
                button.dataset.focusMonthNo = '';
                button.dataset.focusDecision = '';
                button.dataset.focusPass = '';
                button.dataset.focusDepartment = '';
                button.dataset.focusCycleId = '';
                const isKpiReviewResult = item && item.type === 'kpi_review_result';
                const isKpiReviewRequested = item && item.type === 'kpi_review_requested';
                const isCycleUpdate = item && item.type === 'cycle_update';
                const payload = item && item.payload && typeof item.payload === 'object'
                    ? item.payload
                    : {};
                const decision = isKpiReviewResult
                    ? normalizeNotificationDecision(payload.decision)
                    : '';
                if (isKpiReviewResult) {
                    const scoreId = Number(payload.score_id || 0);
                    const monthNo = Number(payload.month_no || 0);
                    button.dataset.notifyType = 'kpi_review_result';
                    if (scoreId > 0) {
                        button.dataset.focusScoreId = String(scoreId);
                    }
                    if (monthNo >= 1 && monthNo <= 12) {
                        button.dataset.focusMonthNo = String(monthNo);
                    }
                    if (decision === 'approved' || decision === 'rejected') {
                        button.dataset.focusDecision = decision;
                    }
                } else if (isKpiReviewRequested) {
                    const scoreId = Number(payload.score_id || 0);
                    const monthNo = Number(payload.month_no || 0);
                    const cycleId = Number(payload.cycle_id || 0);
                    const departmentCode = String(payload.department || '').trim().toUpperCase();
                    const passFlag = normalizeNotificationPassFlag(payload.is_pass);
                    button.dataset.notifyType = 'kpi_review_requested';
                    if (scoreId > 0) {
                        button.dataset.focusScoreId = String(scoreId);
                    }
                    if (monthNo >= 1 && monthNo <= 12) {
                        button.dataset.focusMonthNo = String(monthNo);
                    }
                    if (cycleId > 0) {
                        button.dataset.focusCycleId = String(cycleId);
                    }
                    if (departmentCode !== '') {
                        button.dataset.focusDepartment = departmentCode;
                    }
                    if (passFlag === '1' || passFlag === '0') {
                        button.dataset.focusPass = passFlag;
                    }
                }

                if (!isKpiReviewResult && !isKpiReviewRequested) {
                    const titleEl = document.createElement('p');
                    titleEl.className = 'notify-item-title';
                    titleEl.textContent = item && item.title ? String(item.title) : '-';
                    button.appendChild(titleEl);
                }

                button.dataset.noNavigate = isCycleUpdate ? '1' : '';

                const messageEl = document.createElement('div');
                messageEl.className = 'notify-item-message';
                if (isKpiReviewResult) {
                    appendNotificationNote(messageEl, getText('notifyKpiReviewedDescription'));
                    appendNotificationLine(messageEl, getText('notifyKpiTopicLabel'), truncateNotificationTopic(payload.objective));
                    appendNotificationLine(messageEl, getText('notifyKpiMonthLabel'), normalizeNotificationMonthValue(payload.month_no));
                    appendNotificationLine(
                        messageEl,
                        getText('notifyKpiStatusLabel'),
                        resolveNotificationDecisionText(payload.decision),
                        decision === 'approved'
                            ? 'is-approved'
                            : (decision === 'rejected' ? 'is-rejected' : '')
                    );
                    appendNotificationLine(messageEl, getText('notifyKpiFromLabel'), normalizeNotificationTextValue(payload.reviewer_name));
                } else if (isKpiReviewRequested) {
                    appendNotificationNote(messageEl, getText('notifyKpiReviewRequestDescription'));
                    appendNotificationLine(messageEl, getText('notifyKpiTopicLabel'), truncateNotificationTopic(payload.objective));
                    appendNotificationLine(messageEl, getText('notifyKpiMonthLabel'), normalizeNotificationMonthValue(payload.month_no));
                    appendNotificationLine(messageEl, getText('notifyKpiFromLabel'), normalizeNotificationTextValue(payload.owner_name));
                    appendNotificationLine(messageEl, getText('notifyKpiDepartmentLabel'), normalizeNotificationTextValue(payload.department));
                } else if (isCycleUpdate) {
                    const cAction = String(payload.action || '').trim();
                    const cName = String(payload.cycle_name || '').trim();
                    const cMonth = String(payload.month_name || '').trim();
                    const cIsOpen = cAction === 'cycle_opened' || cAction === 'month_opened';
                    appendNotificationNote(messageEl, 'แจ้งจากผู้ดูแลระบบ');
                    appendNotificationLine(messageEl, 'รอบ', cName || '-');
                    if (cMonth) {
                        appendNotificationLine(messageEl, 'เดือน', cMonth);
                    }
                    appendNotificationLine(
                        messageEl,
                        'สถานะ',
                        cIsOpen ? 'เปิดแล้ว' : 'ปิดแล้ว',
                        cIsOpen ? 'is-approved' : 'is-rejected'
                    );
                } else {
                    messageEl.textContent = item && item.message ? String(item.message) : '-';
                }
                button.appendChild(messageEl);

                const timeEl = document.createElement('p');
                timeEl.className = 'notify-item-time';
                timeEl.textContent = formatNotificationTime(item && item.created_at ? item.created_at : '');
                button.appendChild(timeEl);

                notifyList.appendChild(button);
            });
        }

        async function fetchNotifications() {
            if (!notifyEnabled || !notifyIndexUrl) return;
            try {
                const response = await fetch(withLangParam(notifyIndexUrl), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || !payload || payload.ok !== true) return;
                setNotificationBadge(payload.unread_count || 0);
                renderNotifications(payload.items || []);
            } catch (error) {
                // no-op
            }
        }

        async function markNotificationAsRead(id) {
            const notifyId = Number(id || 0);
            if (!notifyEnabled || notifyId < 1 || !notifyReadUrlTemplate) return;
            const endpoint = withLangParam(notifyReadUrlTemplate.replace('__ID__', String(notifyId)));
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || !payload || payload.ok !== true) return;
                setNotificationBadge(payload.unread_count || 0);
                notificationsState = notificationsState.map((item) => {
                    if (Number(item && item.id ? item.id : 0) !== notifyId) {
                        return item;
                    }
                    return {
                        ...item,
                        is_read: true,
                    };
                });
                renderNotifications(notificationsState);
            } catch (error) {
                // no-op
            }
        }

        async function markAllNotificationsAsRead() {
            if (!notifyEnabled || !notifyReadAllUrl) return;
            try {
                const response = await fetch(withLangParam(notifyReadAllUrl), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || !payload || payload.ok !== true) return;
                setNotificationBadge(0);
                notificationsState = notificationsState.map((item) => ({ ...item, is_read: true }));
                renderNotifications(notificationsState);
            } catch (error) {
                // no-op
            }
        }

        function openConfirm(message, onConfirm) {
            confirmMessage.textContent = message;
            pendingConfirmCallback = onConfirm;
            confirmModal.classList.add('show');
        }

        function closeConfirm() {
            confirmModal.classList.remove('show');
            pendingConfirmCallback = null;
        }

        function openAlert(message, type = 'info') {
            alertMessage.textContent = message;
            if (type === 'success') {
                alertIcon.textContent = '✓';
                alertIcon.classList.add('success');
            } else {
                alertIcon.textContent = '!';
                alertIcon.classList.remove('success');
            }
            alertModal.classList.add('show');
        }

        function closeAlert() {
            alertModal.classList.remove('show');
        }

        function syncLangToLinksAndForms(lang) {
            document.querySelectorAll('a[href]').forEach((link) => {
                const rawHref = link.getAttribute('href');
                if (! rawHref || rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:') || rawHref.startsWith('javascript:')) {
                    return;
                }

                let url;
                try {
                    url = new URL(rawHref, window.location.origin);
                } catch (error) {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                url.searchParams.set('lang', lang);
                link.setAttribute('href', `${url.pathname}${url.search}${url.hash}`);
            });

            document.querySelectorAll('form[action]').forEach((form) => {
                const rawAction = form.getAttribute('action');
                if (! rawAction) {
                    return;
                }

                let url;
                try {
                    url = new URL(rawAction, window.location.origin);
                } catch (error) {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                url.searchParams.set('lang', lang);
                form.setAttribute('action', `${url.pathname}${url.search}${url.hash}`);

                const hiddenLang = form.querySelector('input[name="lang"][data-lang-sync="1"]');
                if (hiddenLang) {
                    hiddenLang.value = lang;
                }
            });
        }

        confirmCancel.addEventListener('click', closeConfirm);
        confirmOk.addEventListener('click', () => {
            const callback = pendingConfirmCallback;
            closeConfirm();
            if (typeof callback === 'function') {
                callback();
            }
        });

        alertClose.addEventListener('click', closeAlert);

        function setNotifyPanelOpen(open) {
            if (!notifyPanel) return;
            notifyPanel.classList.toggle('show', !!open);
            notifyPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        if (notifyEnabled && notifyWrap && notifyBtn && notifyPanel && notifyList) {
            if (notifyTabUserBtn) {
                notifyTabUserBtn.addEventListener('click', () => {
                    if (notifyActiveTab !== 'user') {
                        setNotifyActiveTab('user');
                    }
                });
            }
            if (notifyTabReviewerBtn) {
                notifyTabReviewerBtn.addEventListener('click', () => {
                    if (notifyActiveTab !== 'reviewer') {
                        setNotifyActiveTab('reviewer');
                    }
                });
            }
            setNotifyActiveTab('user');

            fetchNotifications();
            setInterval(fetchNotifications, 60000);

            notifyBtn.addEventListener('click', () => {
                const isOpen = notifyPanel.classList.contains('show');
                setNotifyPanelOpen(!isOpen);
                if (!isOpen) {
                    fetchNotifications();
                }
            });

            if (notifyMarkAllBtn) {
                notifyMarkAllBtn.addEventListener('click', () => {
                    markAllNotificationsAsRead();
                });
            }

            notifyList.addEventListener('click', async (event) => {
                const itemBtn = event.target instanceof HTMLElement
                    ? event.target.closest('.notify-item')
                    : null;
                if (!itemBtn) return;

                const id = Number(itemBtn.dataset.id || 0);
                const noNavigate = itemBtn.dataset.noNavigate === '1';
                await markNotificationAsRead(id);
                if (!noNavigate) {
                    const targetUrl = buildNotificationTargetUrl(String(itemBtn.dataset.url || ''), itemBtn);
                    setNotifyPanelOpen(false);
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                }
            });

            document.addEventListener('click', (event) => {
                if (!notifyPanel.classList.contains('show')) return;
                if (!notifyWrap.contains(event.target)) {
                    setNotifyPanelOpen(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setNotifyPanelOpen(false);
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeGlobalNoticeModal();
            }
        });

        function syncEmployeeBrief(lang) {
            const brief = document.getElementById('employee-brief');
            if (! brief) {
                return;
            }

            const nameTh = (brief.dataset.nameTh || '').trim();
            const nameEn = (brief.dataset.nameEn || '').trim();
            const resolvedName = lang === 'th'
                ? (nameTh || nameEn || '-')
                : (nameEn || nameTh || '-');

            const nameDisplay = document.getElementById('employee-name-display');
            if (nameDisplay) {
                nameDisplay.textContent = resolvedName;
            }
        }

        function switchLang(lang) {
            currentLang = lang === 'th' ? 'th' : 'en';

            const thBtn = document.getElementById('lang-th');
            const enBtn = document.getElementById('lang-en');
            if (thBtn) thBtn.textContent = 'TH';
            if (enBtn) enBtn.textContent = 'EN';
            if (currentLang === 'th') {
                thBtn.classList.add('active');
                enBtn.classList.remove('active');
            } else {
                enBtn.classList.add('active');
                thBtn.classList.remove('active');
            }

            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.getAttribute('data-i18n');
                if (translations[currentLang] && translations[currentLang][key]) {
                    el.textContent = translations[currentLang][key];
                }
            });
            if (globalNoticeFab) {
                const noticeLabel = getText('globalNoticeFabLabel');
                globalNoticeFab.setAttribute('aria-label', noticeLabel);
            }
            syncGlobalNoticePanelActions();
            globalNoticeDataCache = null;
            if (globalKpiNoticeModal && globalKpiNoticeModal.classList.contains('show')) {
                const activeDepartment = (
                    typeof window.resolveGlobalNoticeDepartment === 'function'
                        ? normalizeGlobalNoticeDepartmentCode(window.resolveGlobalNoticeDepartment())
                        : ''
                );
                openGlobalNoticeModal(true, activeDepartment);
            }
            syncEmployeeBrief(currentLang);

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lang', currentLang);
            history.replaceState(null, '', currentUrl.toString());

            syncLangToLinksAndForms(currentLang);
            if (notifyEnabled) {
                fetchNotifications();
            }
            document.dispatchEvent(new CustomEvent('app:lang-changed', {
                detail: {
                    lang: currentLang,
                },
            }));
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form.js-confirm-action');
            if (! form || form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const key = form.dataset.confirmKey || 'confirmSubmit';
            openConfirm(getText(key), () => {
                form.dataset.confirmed = '1';
                form.submit();
            });
        });

        window.AppModal = {
            alert: openAlert,
            confirm: openConfirm,
            getText: getText,
            getLang: () => currentLang,
        };

        const serverStatus = @json(session('status'));
        const serverErrors = @json($errors->all());

        setupGlobalNoticeFab();

        const initialLang = new URLSearchParams(window.location.search).get('lang');
        switchLang(initialLang === 'th' ? 'th' : 'en');

        if (Array.isArray(serverErrors) && serverErrors.length > 0) {
            openAlert(serverErrors[0]);
        } else if (serverStatus) {
            openAlert(serverStatus, 'success');
        }
    </script>
</body>
</html>


