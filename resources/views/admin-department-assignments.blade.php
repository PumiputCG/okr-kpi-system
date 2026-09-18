@extends('layout')

@section('title', 'Assign Employees')
@section('page_heading_key', 'employeeAssignPageTitle')
@section('page_heading', 'กำหนดพนักงาน')

@section('content')
    @php
        $lang = ($lang ?? request('lang', 'en')) === 'th' ? 'th' : 'en';
        $rows = is_array($rows ?? null) ? $rows : [];
        $staffOptions = is_array($staffOptions ?? null) ? $staffOptions : [];
        $t = $lang === 'th'
            ? [
                'headline' => 'กำหนดพนักงานตามแผนก',
                'subtitle' => 'เลือกผู้รับผิดชอบฝั่งเป้าหมายและผู้ตรวจรายงานของแต่ละแผนก',
                'target' => 'เป้าหมาย',
                'reviewer' => 'ตรวจรายงาน',
                'notSet' => '-',
                'search' => 'ค้นหา รหัสพนักงาน หรือ ชื่อ-สกุล',
                'noResult' => 'ไม่พบรายชื่อที่ค้นหา',
                'pick' => 'เลือก',
                'change' => 'เปลี่ยน',
                'remove' => 'ลบ',
                'add' => 'เพิ่ม',
                'save' => 'บันทึก',
                'members' => 'สมาชิกแผนก',
                'employeeCode' => 'รหัสพนักงาน',
                'name' => 'ชื่อ-นามสกุล',
                'position' => 'ตำแหน่ง',
                'empty' => 'ยังไม่พบข้อมูลแผนก HR จากรายชื่อพนักงานที่ import',
            ]
            : [
                'headline' => 'Assign Employees By Department',
                'subtitle' => 'Select target owners and report reviewers for each department.',
                'target' => 'Target',
                'reviewer' => 'Report Reviewer',
                'notSet' => 'Not assigned',
                'search' => 'Search employee code or full name',
                'noResult' => 'No matched employee found',
                'pick' => 'Pick',
                'change' => 'Change',
                'remove' => 'Remove',
                'add' => 'Add',
                'save' => 'Save',
                'members' => 'Department Members',
                'employeeCode' => 'Employee Code',
                'name' => 'Full Name',
                'position' => 'Position',
                'empty' => 'No HR department data found from imported employees.',
            ];
    @endphp

    <style>
        /* ── Shell ─────────────────────────────────────────────────────── */
        .assign-shell {
            display: grid;
            gap: 18px;
            max-width: 960px;
        }

        /* ── Header ────────────────────────────────────────────────────── */
        .assign-head {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .assign-head h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }

        .assign-head p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        /* ── Alerts ─────────────────────────────────────────────────────── */
        .assign-status {
            border-left: 4px solid #16a34a;
            background: #f0fdf4;
            color: #166534;
            padding: 10px 14px;
            font-size: 13px;
        }

        .assign-errors {
            border-left: 4px solid #dc2626;
            background: #fff1f2;
            color: #9f1239;
            padding: 10px 14px;
            display: grid;
            gap: 4px;
            font-size: 13px;
        }

        .assign-empty {
            border: 1px dashed #d1d5db;
            background: #fafafa;
            color: #6b7280;
            padding: 24px;
            text-align: center;
            font-size: 13px;
        }

        /* ── Card list — single column ──────────────────────────────────── */
        .assign-list {
            display: grid;
            gap: 8px;
        }

        /* ── Card ───────────────────────────────────────────────────────── */
        .assign-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }

        .assign-card[open] {
            border-color: #fbcfe8;
            box-shadow: 0 2px 10px rgba(190,24,93,.08);
        }

        /* ── Summary row ─────────────────────────────────────────────────── */
        .assign-summary {
            list-style: none;
            cursor: pointer;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            user-select: none;
        }

        .assign-summary::-webkit-details-marker { display: none; }

        .assign-chevron {
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            color: #9ca3af;
            transition: transform .2s;
        }

        .assign-card[open] .assign-chevron {
            transform: rotate(90deg);
            color: #be185d;
        }

        .assign-dept-badge {
            flex: 0 0 auto;
            background: #fce7f3;
            color: #9d174d;
            font-size: 13px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            min-width: 60px;
            text-align: center;
            letter-spacing: .3px;
        }

        .assign-summary-meta {
            flex: 1 1 0;
            display: flex;
            flex-wrap: wrap;
            gap: 6px 20px;
            min-width: 0;
        }

        .assign-inline {
            display: inline-flex;
            gap: 4px;
            align-items: baseline;
            font-size: 12px;
            color: #6b7280;
            min-width: 0;
            flex-wrap: wrap;
        }

        .assign-inline strong {
            color: #be185d;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .assign-inline span {
            color: #374151;
            word-break: break-word;
        }

        /* ── Body ───────────────────────────────────────────────────────── */
        .assign-body {
            border-top: 1px solid #f3f4f6;
            padding: 16px;
            display: grid;
            gap: 16px;
            background: #fafafa;
        }

        /* ── Form: two fields + button ───────────────────────────────────── */
        .assign-form {
            display: grid;
            gap: 12px;
        }

        .assign-form-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .assign-multi-field {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 4px;
            padding: 10px 12px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .assign-multi-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .assign-multi-head span {
            font-size: 12px;
            color: #374151;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .assign-add-btn {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            font-size: 16px;
            line-height: 1;
            font-weight: 700;
            width: 24px;
            height: 24px;
            padding: 0;
            cursor: pointer;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .assign-add-btn:hover {
            background: #be185d;
            border-color: #be185d;
            color: #fff;
        }

        .assign-entry-btn {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            font-size: 11px;
            padding: 3px 8px;
            cursor: pointer;
            border-radius: 3px;
        }

        .assign-entry-btn:hover {
            background: #f3f4f6;
        }

        .assign-submit-btn {
            border: 1px solid #be185d;
            background: #be185d;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 24px;
            cursor: pointer;
            border-radius: 4px;
            justify-self: start;
            transition: background .12s;
        }

        .assign-submit-btn:hover {
            background: #9d174d;
            border-color: #9d174d;
        }

        /* ── Entry items ─────────────────────────────────────────────────── */
        .assign-entry-list {
            display: grid;
            gap: 6px;
        }

        .assign-entry {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 3px;
            padding: 7px 8px;
            display: grid;
            gap: 6px;
        }

        .assign-entry-view {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .assign-entry-view.is-hidden,
        .assign-entry-edit.is-hidden {
            display: none !important;
        }

        .assign-entry-label {
            font-size: 12px;
            color: #111827;
            font-weight: 600;
            word-break: break-word;
            flex: 1 1 0;
            min-width: 0;
        }

        .assign-entry-actions {
            display: inline-flex;
            gap: 4px;
            flex: 0 0 auto;
        }

        .assign-entry-edit {
            display: grid;
            gap: 5px;
        }

        /* ── Search + Select ─────────────────────────────────────────────── */
        .assign-search {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 6px 10px;
            font-size: 12px;
            width: 100%;
            box-sizing: border-box;
            border-radius: 3px;
        }

        .assign-employee-select {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 4px 6px;
            font-size: 12px;
            width: 100%;
            box-sizing: border-box;
            border-radius: 3px;
            min-height: 130px;
        }

        .assign-employee-select,
        .assign-employee-select option {
            font-family: Consolas, 'Courier New', monospace;
            white-space: pre;
        }

        /* ── Members section ─────────────────────────────────────────────── */
        .assign-members-title {
            margin: 0;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .assign-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .assign-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .assign-table th,
        .assign-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 7px 10px;
            text-align: left;
            font-size: 12px;
            color: #111827;
            vertical-align: top;
            word-break: break-word;
        }

        .assign-table th {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .assign-table tr:last-child td {
            border-bottom: none;
        }

        /* ── Responsive ──────────────────────────────────────────────────── */
        @media (max-width: 640px) {
            .assign-form-fields {
                grid-template-columns: 1fr;
            }

            .assign-summary {
                flex-wrap: wrap;
            }
        }
    </style>

    <section class="assign-shell">
        <div class="assign-head">
            <h2>{{ $t['headline'] }}</h2>
            <p>{{ $t['subtitle'] }}</p>
        </div>

        @if (session('status'))
            <div class="assign-status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="assign-errors">
                @foreach ($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        @if (count($rows) < 1)
            <div class="assign-empty">{{ $t['empty'] }}</div>
        @else
            <div class="assign-list">
                @foreach ($rows as $row)
                    @php
                        $deptCode = (string) ($row['dept_abbr_hr'] ?? '');
                        $members = is_array($row['members'] ?? null) ? $row['members'] : [];
                        $targetUserIds = array_values(array_filter(array_map(
                            'intval',
                            is_array($row['target_user_ids'] ?? null)
                                ? $row['target_user_ids']
                                : [$row['target_user_id'] ?? 0]
                        )));
                        $reviewerUserIds = array_values(array_filter(array_map(
                            'intval',
                            is_array($row['reviewer_user_ids'] ?? null)
                                ? $row['reviewer_user_ids']
                                : [$row['reviewer_user_id'] ?? 0]
                        )));
                        $targetNames = array_values(array_filter(array_map('trim', is_array($row['target_names'] ?? null)
                            ? $row['target_names']
                            : [trim((string) ($row['target_name'] ?? ''))]), fn ($name) => $name !== '' && $name !== '-'));
                        $reviewerNames = array_values(array_filter(array_map('trim', is_array($row['reviewer_names'] ?? null)
                            ? $row['reviewer_names']
                            : [trim((string) ($row['reviewer_name'] ?? ''))]), fn ($name) => $name !== '' && $name !== '-'));
                        $targetSummary = $targetNames !== [] ? implode(', ', $targetNames) : $t['notSet'];
                        $reviewerSummary = $reviewerNames !== [] ? implode(', ', $reviewerNames) : $t['notSet'];
                    @endphp
                    <details class="assign-card">
                        <summary class="assign-summary">
                            <svg class="assign-chevron" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="7 5 13 10 7 15"/>
                            </svg>
                            <span class="assign-dept-badge">{{ $deptCode }}</span>
                            <span class="assign-summary-meta">
                                <span class="assign-inline">
                                    <strong>{{ $t['target'] }}:</strong>
                                    <span>{{ $targetSummary }}</span>
                                </span>
                                <span class="assign-inline">
                                    <strong>{{ $t['reviewer'] }}:</strong>
                                    <span>{{ $reviewerSummary }}</span>
                                </span>
                            </span>
                        </summary>
                        <div class="assign-body">
                            <form class="assign-form" method="POST" action="{{ route('admin.employee.assignments.save', ['lang' => $lang]) }}">
                                @csrf
                                <input type="hidden" name="dept_abbr_hr" value="{{ $deptCode }}">

                                <div class="assign-form-fields">
                                    <div
                                        class="assign-multi-field"
                                        data-input-name="target_user_ids[]"
                                        data-selected-ids='@json($targetUserIds)'
                                        data-placeholder="{{ $t['notSet'] }}"
                                        data-search-placeholder="{{ $t['search'] }}"
                                        data-empty-text="{{ $t['noResult'] }}"
                                        data-pick-label="{{ $t['pick'] }}"
                                        data-change-label="{{ $t['change'] }}"
                                        data-remove-label="{{ $t['remove'] }}"
                                    >
                                        <div class="assign-multi-head">
                                            <span>{{ $t['target'] }}</span>
                                            <button type="button" class="assign-add-btn" data-add-entry title="{{ $t['add'] }}">+</button>
                                        </div>
                                        <div class="assign-entry-list"></div>
                                    </div>

                                    <div
                                        class="assign-multi-field"
                                        data-input-name="reviewer_user_ids[]"
                                        data-selected-ids='@json($reviewerUserIds)'
                                        data-placeholder="{{ $t['notSet'] }}"
                                        data-search-placeholder="{{ $t['search'] }}"
                                        data-empty-text="{{ $t['noResult'] }}"
                                        data-pick-label="{{ $t['pick'] }}"
                                        data-change-label="{{ $t['change'] }}"
                                        data-remove-label="{{ $t['remove'] }}"
                                    >
                                        <div class="assign-multi-head">
                                            <span>{{ $t['reviewer'] }}</span>
                                            <button type="button" class="assign-add-btn" data-add-entry title="{{ $t['add'] }}">+</button>
                                        </div>
                                        <div class="assign-entry-list"></div>
                                    </div>
                                </div>

                                <button type="submit" class="assign-submit-btn">{{ $t['save'] }}</button>
                            </form>

                            <p class="assign-members-title">{{ $t['members'] }} ({{ count($members) }})</p>
                            <div class="assign-table-wrap">
                                <table class="assign-table">
                                    <thead>
                                        <tr>
                                            <th>{{ $t['employeeCode'] }}</th>
                                            <th>{{ $t['name'] }}</th>
                                            <th>{{ $t['position'] }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($members as $member)
                                            <tr>
                                                <td>{{ $member['employee_code'] ?? '-' }}</td>
                                                <td>{{ $member['name'] ?? '-' }}</td>
                                                <td>{{ $member['position'] ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" style="color:#9ca3af;text-align:center;">-</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (() => {
            const normalize = (value) => String(value || '').toLowerCase().trim().replace(/\s+/g, ' ');
            const serverLang = @json($lang);
            const staffOptions = @json($staffOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            const buildOptionTemplate = () => {
                const templateSelect = document.createElement('select');
                for (const item of staffOptions) {
                    const optionId = Number(item && item.id ? item.id : 0);
                    if (optionId < 1) {
                        continue;
                    }
                    const optionEl = document.createElement('option');
                    optionEl.value = String(optionId);
                    optionEl.textContent = String(item.label || '-');
                    optionEl.dataset.displayName = String(item.display_name || item.label || '-');
                    optionEl.dataset.searchKey = normalize(item.search_key || item.label || '');
                    templateSelect.appendChild(optionEl);
                }
                return templateSelect;
            };
            const optionTemplate = buildOptionTemplate();

            const filterSelectOptions = (selectEl, searchEl, emptyText) => {
                const query = normalize(searchEl.value);
                let visibleCount = 0;
                for (const optionEl of selectEl.options) {
                    const optionSearchKey = normalize(optionEl.dataset.searchKey || optionEl.textContent || '');
                    const matches = query === '' || optionSearchKey.includes(query);
                    optionEl.hidden = !matches;
                    optionEl.disabled = !matches;
                    if (matches) {
                        visibleCount++;
                    }
                }

                let emptyOpt = selectEl.querySelector('option[data-empty="1"]');
                if (visibleCount < 1) {
                    if (!emptyOpt) {
                        emptyOpt = document.createElement('option');
                        emptyOpt.dataset.empty = '1';
                        emptyOpt.value = '__empty__';
                        selectEl.appendChild(emptyOpt);
                    }
                    emptyOpt.textContent = emptyText;
                    emptyOpt.hidden = false;
                    emptyOpt.disabled = true;
                    emptyOpt.selected = true;
                } else if (emptyOpt) {
                    emptyOpt.remove();
                }
            };

            const createEntry = (fieldEl, userId = 0) => {
                const inputName = String(fieldEl.dataset.inputName || '');
                const placeholderText = String(fieldEl.dataset.placeholder || '-');
                const searchPlaceholder = String(fieldEl.dataset.searchPlaceholder || 'Search');
                const emptyText = String(fieldEl.dataset.emptyText || '-');
                const pickLabel = String(fieldEl.dataset.pickLabel || 'Pick');
                const changeLabel = String(fieldEl.dataset.changeLabel || 'Change');
                const removeLabel = String(fieldEl.dataset.removeLabel || 'Remove');

                const entryEl = document.createElement('div');
                entryEl.className = 'assign-entry';

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = inputName;
                hiddenInput.value = '';
                hiddenInput.disabled = true;

                const viewEl = document.createElement('div');
                viewEl.className = 'assign-entry-view is-hidden';

                const labelEl = document.createElement('span');
                labelEl.className = 'assign-entry-label';
                labelEl.textContent = placeholderText;

                const actionsEl = document.createElement('span');
                actionsEl.className = 'assign-entry-actions';

                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'assign-entry-btn';
                changeBtn.textContent = changeLabel;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'assign-entry-btn';
                removeBtn.textContent = removeLabel;

                actionsEl.appendChild(changeBtn);
                actionsEl.appendChild(removeBtn);
                viewEl.appendChild(labelEl);
                viewEl.appendChild(actionsEl);

                const editEl = document.createElement('div');
                editEl.className = 'assign-entry-edit';

                const searchEl = document.createElement('input');
                searchEl.type = 'text';
                searchEl.className = 'assign-search';
                searchEl.placeholder = searchPlaceholder;

                const selectEl = optionTemplate.cloneNode(true);
                selectEl.className = 'assign-employee-select';
                selectEl.size = 6;

                const pickBtn = document.createElement('button');
                pickBtn.type = 'button';
                pickBtn.className = 'assign-entry-btn';
                pickBtn.textContent = pickLabel;

                editEl.appendChild(searchEl);
                editEl.appendChild(selectEl);
                editEl.appendChild(pickBtn);

                entryEl.appendChild(hiddenInput);
                entryEl.appendChild(viewEl);
                entryEl.appendChild(editEl);

                const showEdit = () => {
                    viewEl.classList.add('is-hidden');
                    editEl.classList.remove('is-hidden');
                    searchEl.focus();
                    filterSelectOptions(selectEl, searchEl, emptyText);
                };

                const showView = (displayName) => {
                    labelEl.textContent = displayName || placeholderText;
                    editEl.classList.add('is-hidden');
                    viewEl.classList.remove('is-hidden');
                };

                const isDuplicateInField = (selectedId) => {
                    const currentValue = String(selectedId || '');
                    if (currentValue === '') {
                        return false;
                    }
                    const siblings = fieldEl.querySelectorAll('.assign-entry input[type="hidden"]');
                    for (const siblingInput of siblings) {
                        if (siblingInput === hiddenInput || siblingInput.disabled) {
                            continue;
                        }
                        if (String(siblingInput.value || '') === currentValue) {
                            return true;
                        }
                    }
                    return false;
                };

                pickBtn.addEventListener('click', () => {
                    const selectedOption = selectEl.options[selectEl.selectedIndex] || null;
                    if (!selectedOption || selectedOption.value === '__empty__') {
                        return;
                    }

                    const selectedId = Number(selectedOption.value || 0);
                    if (selectedId < 1) {
                        return;
                    }

                    if (isDuplicateInField(selectedId)) {
                        return;
                    }

                    const selectedDisplayName = String(selectedOption.dataset.displayName || selectedOption.textContent || '-').trim() || '-';
                    hiddenInput.value = String(selectedId);
                    hiddenInput.disabled = false;
                    showView(selectedDisplayName);
                });

                changeBtn.addEventListener('click', () => {
                    const currentValue = String(hiddenInput.value || '');
                    if (currentValue !== '') {
                        selectEl.value = currentValue;
                    }
                    showEdit();
                });

                removeBtn.addEventListener('click', () => {
                    entryEl.remove();
                });

                searchEl.addEventListener('input', () => {
                    filterSelectOptions(selectEl, searchEl, emptyText);
                });

                selectEl.addEventListener('dblclick', () => {
                    pickBtn.click();
                });

                if (Number(userId) > 0) {
                    const currentId = String(Number(userId));
                    selectEl.value = currentId;
                    const selectedOption = selectEl.querySelector(`option[value="${currentId}"]`);
                    if (selectedOption) {
                        hiddenInput.value = currentId;
                        hiddenInput.disabled = false;
                        showView(String(selectedOption.dataset.displayName || selectedOption.textContent || '-'));
                    } else {
                        showEdit();
                    }
                } else {
                    showEdit();
                }

                return entryEl;
            };

            const hydrateField = (fieldEl) => {
                if (!fieldEl || fieldEl.dataset.hydrated === '1') {
                    return;
                }

                const entryListEl = fieldEl.querySelector('.assign-entry-list');
                if (!entryListEl) {
                    return;
                }

                let selectedIds = [];
                try {
                    const parsed = JSON.parse(fieldEl.dataset.selectedIds || '[]');
                    if (Array.isArray(parsed)) {
                        selectedIds = parsed.map((value) => Number(value || 0)).filter((id) => id > 0);
                    }
                } catch (error) {
                    selectedIds = [];
                }

                if (selectedIds.length < 1) {
                    entryListEl.appendChild(createEntry(fieldEl, 0));
                } else {
                    for (const userId of selectedIds) {
                        entryListEl.appendChild(createEntry(fieldEl, userId));
                    }
                }

                const addBtn = fieldEl.querySelector('[data-add-entry]');
                if (addBtn) {
                    addBtn.addEventListener('click', () => {
                        entryListEl.appendChild(createEntry(fieldEl, 0));
                    });
                }

                fieldEl.dataset.hydrated = '1';
            };

            const hydrateDetails = (detailsEl) => {
                if (!detailsEl || !detailsEl.open) {
                    return;
                }

                for (const fieldEl of detailsEl.querySelectorAll('.assign-multi-field')) {
                    hydrateField(fieldEl);
                }
            };

            for (const detailsEl of document.querySelectorAll('details.assign-card')) {
                detailsEl.addEventListener('toggle', () => {
                    hydrateDetails(detailsEl);
                });
                hydrateDetails(detailsEl);
            }

            document.addEventListener('app:lang-changed', (event) => {
                const nextLang = event && event.detail && event.detail.lang === 'th' ? 'th' : 'en';
                const htmlLangRaw = (document.documentElement.getAttribute('lang') || serverLang || 'en').toLowerCase();
                const htmlLang = htmlLangRaw === 'th' ? 'th' : 'en';
                if (nextLang === htmlLang) {
                    return;
                }

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('lang', nextLang);
                window.location.assign(nextUrl.toString());
            });
        })();
    </script>
@endsection
