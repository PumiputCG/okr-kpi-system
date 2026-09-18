@extends('layout')

@section('title', 'Add Employee List')
@section('page_heading_key', 'menuAdminImportEmployees')
@section('page_heading', 'Add Employee List')
@section('content')
    <style>
        .import-shell {
            background: #ffffff;
            padding: 22px;
            min-height: 420px;
            box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);
        }

        .import-head {
            margin-bottom: 16px;
        }

        .import-head h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111111;
        }

        .import-head p {
            margin: 8px 0 0;
            font-size: 13px;
            color: #4b5563;
        }

        .import-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 16px;
            max-width: 680px;
        }

        .import-field {
            display: grid;
            gap: 8px;
        }

        .import-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .import-input {
            width: 100%;
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 13px;
            color: #111111;
            outline: none;
        }

        .import-input:focus {
            border-color: #e83e8c;
        }

        .import-note {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }

        .import-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .import-btn {
            border: 1px solid #e83e8c;
            color: #ffffff;
            background: #e83e8c;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .import-btn:hover {
            background: #d2307c;
            border-color: #d2307c;
        }

        .import-btn-secondary {
            border-color: #111111;
            background: #111111;
        }

        .import-btn-secondary:hover {
            border-color: #000000;
            background: #000000;
        }
    </style>

    <section class="import-shell">
        <header class="import-head">
            <h2 data-i18n-local="importTitle">Add Employee List</h2>
            <p data-i18n-local="importSubtitle">Upload an Excel file for employee list import.</p>
        </header>

        <form id="employee-import-form" class="import-card" method="POST"
            action="{{ route('admin.employees.import.store', ['lang' => $lang ?? request('lang', 'en')]) }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="lang" value="{{ $lang ?? (request('lang', 'en') === 'th' ? 'th' : 'en') }}"
                data-lang-sync="1">

            <div class="import-field">
                <label for="employee_excel" class="import-label" data-i18n-local="importFileLabel">Excel File</label>
                <input id="employee_excel" name="employee_excel" class="import-input" type="file" accept=".xlsx,.xls,.csv"
                    required>
                <p class="import-note" data-i18n-local="importHint">Supported formats: .xlsx, .xls, .csv</p>
            </div>

            <div class="import-actions">
                <button type="submit" class="import-btn" name="import_mode" value="append"
                    data-i18n-local="importBtnAdd">Add</button>
                <button type="submit" class="import-btn import-btn-secondary" name="import_mode" value="replace"
                    data-i18n-local="importBtnReplace">Replace All</button>
            </div>
        </form>
    </section>

    <script>
        (function() {
            const translations = {
                en: {
                    importTitle: 'Add Employee List',
                    importSubtitle: 'Upload an Excel file for employee list import.',
                    importFileLabel: 'Excel File',
                    importHint: 'Supported formats: .xlsx, .xls, .csv',
                    importBtnAdd: 'Add',
                    importBtnReplace: 'Replace All'
                },
                th: {
                    importTitle: '\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E1E\u0E19\u0E31\u0E01\u0E07\u0E32\u0E19',
                    importSubtitle: '\u0E19\u0E33\u0E40\u0E02\u0E49\u0E32\u0E44\u0E1F\u0E25\u0E4C Excel \u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E1E\u0E19\u0E31\u0E01\u0E07\u0E32\u0E19',
                    importFileLabel: '\u0E44\u0E1F\u0E25\u0E4C Excel',
                    importHint: '\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A\u0E44\u0E1F\u0E25\u0E4C .xlsx, .xls \u0E41\u0E25\u0E30 .csv',
                    importBtnAdd: '\u0E40\u0E1E\u0E34\u0E48\u0E21',
                    importBtnReplace: '\u0E41\u0E01\u0E49\u0E44\u0E02\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14'
                }
            };

            const getCurrentLang = () => {
                if (window.AppModal && typeof window.AppModal.getLang === 'function') {
                    return window.AppModal.getLang() === 'th' ? 'th' : 'en';
                }

                const fromUrl = new URLSearchParams(window.location.search).get('lang');
                return fromUrl === 'th' ? 'th' : 'en';
            };

            const applyTranslations = (lang) => {
                const activeLang = lang === 'th' ? 'th' : 'en';
                document.querySelectorAll('[data-i18n-local]').forEach((el) => {
                    const key = el.getAttribute('data-i18n-local');
                    el.textContent = translations[activeLang][key] || translations.en[key] || key;
                });
            };

            document.addEventListener('app:lang-changed', (event) => {
                const lang = event && event.detail && event.detail.lang ? event.detail.lang : getCurrentLang();
                applyTranslations(lang);
            });

            applyTranslations(getCurrentLang());
        })();
    </script>
@endsection
