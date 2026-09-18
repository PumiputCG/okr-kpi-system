<script>
(() => {
    const currentLang = @json($lang);
    const saveUrl = @json($announcementSaveUrl);
    const updateUrl = @json($announcementUpdateUrl);
    const deleteUrl = @json($announcementDeleteUrl);
    const initialAnnouncements = @json($adminAnnouncements);
    const deptOptionsInput = @json($deptAbbrHrOptions);
    const levelThreeRowsByDeptInput = @json($levelThreeRowsByDept);

    const el = {
        addL1: document.getElementById('announcement-add-level1'),
        addL2: document.getElementById('announcement-add-level2'),
        search: document.getElementById('announcement-search'),
        filter: document.getElementById('announcement-filter-level1'),
        body: document.getElementById('announcement-table-body'),
        editor: document.getElementById('announcement-editor-host'),
        meta: document.getElementById('announcement-meta'),
        prev: document.getElementById('announcement-page-prev'),
        next: document.getElementById('announcement-page-next'),
        page: document.getElementById('announcement-page-meta'),
    };
    if (Object.values(el).some((node) => !node)) {
        return;
    }

    const csrfToken = String((document.querySelector('meta[name="csrf-token"]') || {}).content || '');
    const normText = (v) => String(v || '').trim();
    const normDept = (v) => normText(v).toUpperCase();
    const normLevel = (v) => (Number(v) === 2 ? 2 : 1);
    const normSearch = (v) => normText(v).toLowerCase();

    const deptOptions = Array.isArray(deptOptionsInput)
        ? [...new Set(deptOptionsInput.map((v) => normDept(v)).filter((v) => v !== ''))]
        : [];

    const rowsByDept = {};
    if (levelThreeRowsByDeptInput && typeof levelThreeRowsByDeptInput === 'object') {
        Object.keys(levelThreeRowsByDeptInput).forEach((rawKey) => {
            const key = normDept(rawKey);
            rowsByDept[key] = Array.isArray(levelThreeRowsByDeptInput[rawKey]) ? levelThreeRowsByDeptInput[rawKey] : [];
        });
    }

    const state = {
        announcements: [],
        busy: '',
        editor: null,
        search: '',
        filterL1: 0,
        page: 1,
        pageSize: 4,
    };

    const t = {
        allL1: 'ลำดับชั้นที่ 1 ทั้งหมด',
        noData: 'ไม่พบข้อมูล',
        noL3: 'ยังไม่มีข้อมูลลำดับชั้นที่ 3 ของแผนกนี้',
        noFiles: 'ไม่มีไฟล์',
        needL1First: 'กรุณาเพิ่มลำดับชั้นที่ 1 ก่อน',
        needParent: 'กรุณาเลือกลำดับชั้นที่ 1',
        needInput: 'กรุณากรอกหัวข้อ รายละเอียด หรือแนบไฟล์อย่างน้อย 1 ไฟล์',
        routeMissing: 'ไม่พบเส้นทางบันทึกข้อมูล',
        saveFail: 'ไม่สามารถบันทึกข้อมูลได้',
        deleteFail: 'ไม่สามารถลบข้อมูลได้',
        deleteConfirm: 'ต้องการลบข้อมูลนี้หรือไม่?',
    };

    const alertMsg = (msg, type = 'info') => {
        if (window.AppModal && typeof window.AppModal.alert === 'function') {
            window.AppModal.alert(msg, type);
            return;
        }
        window.alert(msg);
    };

    const confirmMsg = (msg, onConfirm) => {
        if (window.AppModal && typeof window.AppModal.confirm === 'function') {
            window.AppModal.confirm(msg, onConfirm);
            return;
        }
        if (window.confirm(msg)) {
            onConfirm();
        }
    };

    const setMeta = (msg, type = 'info') => {
        el.meta.className = type === 'success' ? 'save-meta success' : (type === 'fail' ? 'save-meta fail' : 'save-meta');
        el.meta.textContent = String(msg || '');
    };

    const parseAnnouncement = (raw) => {
        const src = raw && typeof raw === 'object' ? raw : {};
        return {
            id: Number(src.id) || 0,
            level_no: normLevel(src.level_no),
            parent_announcement_id: Number(src.parent_announcement_id) || 0,
            dept_abbr_hr: normDept(src.dept_abbr_hr),
            title: normText(src.title),
            detail: String(src.detail || ''),
            posted_at: src.posted_at || null,
            files: Array.isArray(src.files)
                ? src.files.map((f) => ({
                    id: Number(f && f.id) || 0,
                    name: normText(f && f.name),
                    size_text: normText(f && f.size_text),
                    url: normText(f && f.url),
                }))
                : [],
        };
    };

    const sortAnnouncements = (items) => items.slice().sort((a, b) => {
        if (a.level_no !== b.level_no) {
            return a.level_no - b.level_no;
        }
        if (a.level_no === 2 && a.parent_announcement_id !== b.parent_announcement_id) {
            return a.parent_announcement_id - b.parent_announcement_id;
        }
        return a.id - b.id;
    });

    const l1List = () => state.announcements.filter((a) => a.level_no === 1);
    const l2List = () => state.announcements.filter((a) => a.level_no === 2);

    const getL3Rows = (deptCode) => {
        const dept = normDept(deptCode);
        const rows = Array.isArray(rowsByDept[dept]) ? rowsByDept[dept] : [];
        return rows.map((row) => {
            const unit = normText(row.unit_th || row.unit || row.unit_en);
            const target = [normText(row.target_goal), normText(row.target_value), unit].filter((v) => v !== '').join(' ');
            const detail = [normText(row.detail), target !== '' ? `เป้าหมาย ${target}` : ''].filter((v) => v !== '').join('\n');
            return {
                dept_abbr_hr: dept,
                title: normText(row.objective) || '-',
                detail: detail || '-',
                file_text: '-',
            };
        });
    };

    const contains = (text, q) => normSearch(text).includes(q);
    const annMatch = (ann, q) => q === '' || contains(ann.title, q) || contains(ann.detail, q) || contains(ann.dept_abbr_hr, q);
    const rowMatch = (row, q) => q === '' || contains(row.dept_abbr_hr, q) || contains(row.title, q) || contains(row.detail, q);

    const buildGroups = () => {
        const q = normSearch(state.search);
        const l2ByParent = {};
        sortAnnouncements(l2List()).forEach((item) => {
            const key = Number(item.parent_announcement_id) || 0;
            if (!l2ByParent[key]) {
                l2ByParent[key] = [];
            }
            l2ByParent[key].push(item);
        });

        const groups = [];
        let indexNo = 0;

        sortAnnouncements(l1List()).forEach((l1) => {
            if (state.filterL1 > 0 && Number(l1.id) !== Number(state.filterL1)) {
                return;
            }
            const l1Matched = annMatch(l1, q) && q !== '';
            const children = [];
            const l2Items = l2ByParent[l1.id] || [];

            if (l2Items.length === 0) {
                if (q === '' || l1Matched) {
                    children.push({ l2: null, rows: [] });
                }
            } else {
                l2Items.forEach((l2) => {
                    const l2Matched = annMatch(l2, q) && q !== '';
                    const allRows = getL3Rows(l2.dept_abbr_hr);
                    const rows = (q !== '' && !l1Matched && !l2Matched) ? allRows.filter((r) => rowMatch(r, q)) : allRows;
                    if (q === '' || l1Matched || l2Matched || rows.length > 0) {
                        children.push({ l2, rows });
                    }
                });
            }

            if (children.length === 0) {
                return;
            }

            const totalRows = children.reduce((sum, child) => sum + Math.max(1, child.rows.length), 0);
            indexNo += 1;
            groups.push({ l1, indexNo, children, totalRows });
        });

        return groups;
    };

    const td = (text, cls = '') => {
        const node = document.createElement('td');
        if (cls) {
            node.className = cls;
        }
        node.textContent = text;
        return node;
    };

    const tdFiles = (files, cls = '') => {
        const node = document.createElement('td');
        if (cls) {
            node.className = cls;
        }
        const list = Array.isArray(files) ? files.filter((f) => normText(f.url) !== '') : [];
        if (list.length === 0) {
            const span = document.createElement('span');
            span.className = 'cell-empty';
            span.textContent = t.noFiles;
            node.appendChild(span);
            return node;
        }
        const ul = document.createElement('ul');
        ul.className = 'cell-files';
        list.forEach((f) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = f.url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = f.size_text ? `${f.name} (${f.size_text})` : (f.name || f.url);
            li.appendChild(a);
            ul.appendChild(li);
        });
        node.appendChild(ul);
        return node;
    };

    const actionBtn = (text, onClick, danger = false) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = danger ? 'action-btn action-btn-danger' : 'action-btn';
        btn.textContent = text;
        btn.disabled = state.busy !== '';
        btn.addEventListener('click', onClick);
        return btn;
    };

    const renderFilter = () => {
        const old = Number(state.filterL1) || 0;
        const list = sortAnnouncements(l1List());
        el.filter.innerHTML = '';

        const allOpt = document.createElement('option');
        allOpt.value = '0';
        allOpt.textContent = t.allL1;
        el.filter.appendChild(allOpt);

        list.forEach((item, idx) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = `${idx + 1}. ${item.title || '-'}`;
            el.filter.appendChild(opt);
        });

        state.filterL1 = list.some((item) => Number(item.id) === old) ? old : 0;
        el.filter.value = String(state.filterL1);
    };

    const openEditor = (payload) => {
        state.editor = payload;
        renderAll();
    };

    const closeEditor = () => {
        state.editor = null;
        renderAll();
    };

    const renderEditor = () => {
        el.editor.innerHTML = '';
        if (!state.editor) {
            return;
        }

        const editor = state.editor;
        const isEdit = editor.mode === 'edit' && Number(editor.id) > 0;
        const levelNo = normLevel(editor.level_no);
        const current = isEdit ? state.announcements.find((a) => Number(a.id) === Number(editor.id)) || null : null;

        const panel = document.createElement('section');
        panel.className = 'home-editor-panel';

        const title = document.createElement('h3');
        title.className = 'home-editor-title';
        title.textContent = levelNo === 1
            ? (isEdit ? 'แก้ไขลำดับชั้นที่ 1' : 'เพิ่มลำดับชั้นที่ 1')
            : (isEdit ? 'แก้ไขลำดับชั้นที่ 2' : 'เพิ่มลำดับชั้นที่ 2');
        panel.appendChild(title);

        const grid = document.createElement('div');
        grid.className = 'home-editor-grid';

        const field = (labelText, input, full = false) => {
            const label = document.createElement('label');
            label.className = full ? 'home-editor-field full' : 'home-editor-field';
            const span = document.createElement('span');
            span.className = 'home-editor-label';
            span.textContent = labelText;
            label.appendChild(span);
            label.appendChild(input);
            return label;
        };

        const parentSelect = document.createElement('select');
        parentSelect.className = 'home-select';
        const parentDefault = document.createElement('option');
        parentDefault.value = '0';
        parentDefault.textContent = '-';
        parentSelect.appendChild(parentDefault);
        sortAnnouncements(l1List()).forEach((item, idx) => {
            if (isEdit && Number(item.id) === Number(editor.id)) {
                return;
            }
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = `${idx + 1}. ${item.title || '-'}`;
            opt.selected = Number(item.id) === Number(editor.parent_announcement_id || 0);
            parentSelect.appendChild(opt);
        });
        parentSelect.addEventListener('change', (event) => {
            state.editor = { ...state.editor, parent_announcement_id: Number(event.target ? event.target.value : 0) || 0 };
        });

        const deptSelect = document.createElement('select');
        deptSelect.className = 'home-select';
        const deptDefault = document.createElement('option');
        deptDefault.value = '';
        deptDefault.textContent = '-';
        deptSelect.appendChild(deptDefault);
        deptOptions.forEach((dept) => {
            const opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            opt.selected = dept === normDept(editor.dept_abbr_hr);
            deptSelect.appendChild(opt);
        });
        deptSelect.addEventListener('change', (event) => {
            state.editor = { ...state.editor, dept_abbr_hr: normDept(event.target ? event.target.value : '') };
        });

        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.maxLength = 60000;
        titleInput.className = 'home-input';
        titleInput.value = String(editor.title || '');
        titleInput.addEventListener('input', (event) => {
            state.editor = { ...state.editor, title: String(event.target ? event.target.value : '') };
        });

        const detailInput = document.createElement('textarea');
        detailInput.maxLength = 60000;
        detailInput.className = 'home-textarea';
        detailInput.value = String(editor.detail || '');
        detailInput.addEventListener('input', (event) => {
            state.editor = { ...state.editor, detail: String(event.target ? event.target.value : '') };
        });

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = '.pdf,.png,.jpg,.jpeg,.doc,.docx,.ppt,.pptx,.xls,.xlsx';
        fileInput.className = 'home-file';

        if (levelNo === 2) {
            grid.appendChild(field('ลำดับชั้นที่ 1 ที่เชื่อมโยง', parentSelect));
        }
        grid.appendChild(field('แผนก (HR)', deptSelect));
        grid.appendChild(field('หัวข้อ', titleInput, true));
        grid.appendChild(field('รายละเอียด', detailInput, true));

        const filesField = field('แนบไฟล์', fileInput, true);
        const note = document.createElement('p');
        note.className = 'home-file-note';
        note.textContent = isEdit ? 'หากแนบไฟล์ใหม่ จะใช้แทนไฟล์เดิม' : 'รองรับไฟล์ pdf, รูปภาพ, Word, PowerPoint, Excel';
        filesField.appendChild(note);
        grid.appendChild(filesField);

        panel.appendChild(grid);

        if (isEdit && current && Array.isArray(current.files) && current.files.length > 0) {
            const fileList = document.createElement('ul');
            fileList.className = 'cell-files';
            current.files.forEach((f) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = f.url || '#';
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                a.textContent = f.size_text ? `${f.name} (${f.size_text})` : f.name;
                li.appendChild(a);
                fileList.appendChild(li);
            });
            panel.appendChild(fileList);
        }

        const actions = document.createElement('div');
        actions.className = 'home-editor-actions';
        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'btn-primary';
        saveBtn.textContent = isEdit ? 'อัปเดต' : 'บันทึก';
        saveBtn.disabled = state.busy !== '';
        saveBtn.addEventListener('click', () => saveEditor(fileInput));
        actions.appendChild(saveBtn);

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn-primary btn-level-white';
        cancelBtn.textContent = 'ยกเลิก';
        cancelBtn.disabled = state.busy !== '';
        cancelBtn.addEventListener('click', closeEditor);
        actions.appendChild(cancelBtn);

        panel.appendChild(actions);
        el.editor.appendChild(panel);
    };

    const renderTable = () => {
        const groups = buildGroups();
        const totalPages = Math.max(1, Math.ceil(groups.length / state.pageSize));
        state.page = Math.min(totalPages, Math.max(1, Number(state.page) || 1));
        const offset = (state.page - 1) * state.pageSize;
        const pageGroups = groups.slice(offset, offset + state.pageSize);

        el.body.innerHTML = '';

        if (pageGroups.length === 0) {
            const tr = document.createElement('tr');
            const tdNoData = document.createElement('td');
            tdNoData.colSpan = 14;
            tdNoData.className = 'home-empty';
            tdNoData.textContent = t.noData;
            tr.appendChild(tdNoData);
            el.body.appendChild(tr);
        } else {
            pageGroups.forEach((group) => {
                let firstGroupRow = true;
                group.children.forEach((child) => {
                    const rows = child.rows.length > 0
                        ? child.rows
                        : [{ dept_abbr_hr: normDept(child.l2 ? child.l2.dept_abbr_hr : ''), title: '-', detail: t.noL3, file_text: '-' }];

                    rows.forEach((row, rowIdx) => {
                        const tr = document.createElement('tr');
                        const firstChildRow = rowIdx === 0;

                        if (firstGroupRow) {
                            const l1Dept = td(group.l1.dept_abbr_hr || '-', 'cell-level1');
                            l1Dept.rowSpan = group.totalRows;
                            tr.appendChild(l1Dept);

                            const l1No = td(String(group.indexNo), 'cell-level1');
                            l1No.rowSpan = group.totalRows;
                            tr.appendChild(l1No);

                            const l1Title = td(group.l1.title || '-', 'cell-level1');
                            l1Title.rowSpan = group.totalRows;
                            tr.appendChild(l1Title);

                            const l1Detail = td(group.l1.detail || '-', 'cell-level1');
                            l1Detail.rowSpan = group.totalRows;
                            tr.appendChild(l1Detail);

                            const l1Files = tdFiles(group.l1.files, 'cell-level1');
                            l1Files.rowSpan = group.totalRows;
                            tr.appendChild(l1Files);
                        }

                        if (firstChildRow) {
                            const span = rows.length;
                            const l2 = child.l2;

                            const l2Dept = td(l2 ? (l2.dept_abbr_hr || '-') : '-', 'cell-level2');
                            l2Dept.rowSpan = span;
                            tr.appendChild(l2Dept);

                            const l2Title = td(l2 ? (l2.title || '-') : '-', 'cell-level2');
                            l2Title.rowSpan = span;
                            tr.appendChild(l2Title);

                            const l2Detail = td(l2 ? (l2.detail || '-') : '-', 'cell-level2');
                            l2Detail.rowSpan = span;
                            tr.appendChild(l2Detail);

                            const l2Files = tdFiles(l2 ? l2.files : [], 'cell-level2');
                            l2Files.rowSpan = span;
                            tr.appendChild(l2Files);
                        }

                        tr.appendChild(td(row.dept_abbr_hr || '-', 'cell-level3'));
                        tr.appendChild(td(row.title || '-', 'cell-level3'));
                        tr.appendChild(td(row.detail || '-', 'cell-level3'));
                        tr.appendChild(td(row.file_text || '-', 'cell-level3'));

                        const actionCell = document.createElement('td');
                        actionCell.className = 'cell-action';
                        if (firstGroupRow || firstChildRow) {
                            const wrap = document.createElement('div');
                            wrap.className = 'row-action-wrap';

                            if (firstGroupRow) {
                                const g1 = document.createElement('div');
                                g1.className = 'row-action-group';
                                const lb1 = document.createElement('span');
                                lb1.className = 'row-action-label';
                                lb1.textContent = 'L1';
                                g1.appendChild(lb1);
                                g1.appendChild(actionBtn('แก้ไข', () => openEditor({
                                    mode: 'edit',
                                    id: group.l1.id,
                                    level_no: 1,
                                    parent_announcement_id: 0,
                                    dept_abbr_hr: group.l1.dept_abbr_hr || '',
                                    title: group.l1.title || '',
                                    detail: group.l1.detail || '',
                                })));
                                g1.appendChild(actionBtn('ลบ', () => confirmMsg(t.deleteConfirm, () => deleteAnnouncement(group.l1.id)), true));
                                wrap.appendChild(g1);
                            }

                            if (firstChildRow && child.l2) {
                                const g2 = document.createElement('div');
                                g2.className = 'row-action-group';
                                const lb2 = document.createElement('span');
                                lb2.className = 'row-action-label';
                                lb2.textContent = 'L2';
                                g2.appendChild(lb2);
                                g2.appendChild(actionBtn('แก้ไข', () => openEditor({
                                    mode: 'edit',
                                    id: child.l2.id,
                                    level_no: 2,
                                    parent_announcement_id: Number(child.l2.parent_announcement_id) || 0,
                                    dept_abbr_hr: child.l2.dept_abbr_hr || '',
                                    title: child.l2.title || '',
                                    detail: child.l2.detail || '',
                                })));
                                g2.appendChild(actionBtn('ลบ', () => confirmMsg(t.deleteConfirm, () => deleteAnnouncement(child.l2.id)), true));
                                wrap.appendChild(g2);
                            }

                            actionCell.appendChild(wrap);
                        } else {
                            actionCell.textContent = '-';
                        }

                        tr.appendChild(actionCell);
                        el.body.appendChild(tr);
                        firstGroupRow = false;
                    });
                });
            });
        }

        el.page.textContent = `หน้า ${state.page}/${totalPages}`;
        el.prev.disabled = state.page <= 1;
        el.next.disabled = state.page >= totalPages;
    };

    const validateEditor = (fileInput) => {
        if (!state.editor) {
            return { ok: false, msg: t.saveFail };
        }
        const ed = state.editor;
        const isEdit = ed.mode === 'edit' && Number(ed.id) > 0;
        const levelNo = normLevel(ed.level_no);
        const parentId = Number(ed.parent_announcement_id) || 0;
        const title = normText(ed.title);
        const detail = normText(ed.detail);
        const files = Array.from((fileInput && fileInput.files) ? fileInput.files : []);

        const existing = isEdit ? state.announcements.find((a) => Number(a.id) === Number(ed.id)) || null : null;
        const existingFiles = existing && Array.isArray(existing.files) ? existing.files.length : 0;

        if (levelNo === 2 && parentId < 1) {
            return { ok: false, msg: t.needParent };
        }
        if (title === '' && detail === '' && files.length === 0 && (!isEdit || existingFiles === 0)) {
            return { ok: false, msg: t.needInput };
        }

        return {
            ok: true,
            isEdit,
            levelNo,
            parentId,
            dept: normDept(ed.dept_abbr_hr),
            title,
            detail,
            files,
            id: Number(ed.id) || 0,
        };
    };

    const saveEditor = async (fileInput) => {
        if (!state.editor || state.busy !== '') {
            return;
        }

        const v = validateEditor(fileInput);
        if (!v.ok) {
            alertMsg(v.msg || t.saveFail, 'error');
            setMeta(v.msg || t.saveFail, 'fail');
            return;
        }

        const url = v.isEdit ? updateUrl : saveUrl;
        if (!url) {
            alertMsg(t.routeMissing, 'error');
            setMeta(t.routeMissing, 'fail');
            return;
        }

        state.busy = v.isEdit ? `save:${v.id}` : 'save:create';
        renderAll();

        const form = new FormData();
        form.append('level_no', String(v.levelNo));
        form.append('parent_announcement_id', String(v.parentId));
        form.append('dept_abbr_hr', v.dept);
        form.append('title', v.title);
        form.append('detail', v.detail);
        form.append('lang', String(currentLang || 'en'));
        if (v.isEdit) {
            form.append('announcement_id', String(v.id));
        }
        v.files.forEach((file) => form.append('files[]', file));

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });
            const result = await res.json().catch(() => null);
            if (!res.ok || !result || result.ok !== true) {
                const msg = result && result.message ? String(result.message) : t.saveFail;
                alertMsg(msg, 'error');
                setMeta(msg, 'fail');
                return;
            }

            const saved = parseAnnouncement(result.announcement || {});
            if (saved.id > 0) {
                const idx = state.announcements.findIndex((a) => Number(a.id) === Number(saved.id));
                if (idx >= 0) {
                    state.announcements[idx] = saved;
                } else {
                    state.announcements.push(saved);
                }
                state.announcements = sortAnnouncements(state.announcements);
            }

            state.editor = null;
            setMeta(result.message ? String(result.message) : 'บันทึกสำเร็จ', 'success');
        } catch (error) {
            alertMsg(t.saveFail, 'error');
            setMeta(t.saveFail, 'fail');
        } finally {
            state.busy = '';
            renderAll();
        }
    };

    const deleteAnnouncement = async (announcementId) => {
        const id = Number(announcementId) || 0;
        if (id < 1 || state.busy !== '') {
            return;
        }
        if (!deleteUrl) {
            alertMsg(t.routeMissing, 'error');
            setMeta(t.routeMissing, 'fail');
            return;
        }

        state.busy = `delete:${id}`;
        renderAll();

        const form = new FormData();
        form.append('announcement_id', String(id));
        form.append('lang', String(currentLang || 'en'));

        try {
            const res = await fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });
            const result = await res.json().catch(() => null);
            if (!res.ok || !result || result.ok !== true) {
                const msg = result && result.message ? String(result.message) : t.deleteFail;
                alertMsg(msg, 'error');
                setMeta(msg, 'fail');
                return;
            }

            state.announcements = state.announcements.filter((a) => Number(a.id) !== id);
            if (state.editor && Number(state.editor.id) === id) {
                state.editor = null;
            }
            setMeta(result.message ? String(result.message) : 'ลบสำเร็จ', 'success');
        } catch (error) {
            alertMsg(t.deleteFail, 'error');
            setMeta(t.deleteFail, 'fail');
        } finally {
            state.busy = '';
            renderAll();
        }
    };

    const renderAll = () => {
        renderFilter();
        renderEditor();
        renderTable();
    };

    el.addL1.addEventListener('click', () => {
        if (state.busy !== '') {
            return;
        }
        openEditor({
            mode: 'create',
            id: 0,
            level_no: 1,
            parent_announcement_id: 0,
            dept_abbr_hr: '',
            title: '',
            detail: '',
        });
    });

    el.addL2.addEventListener('click', () => {
        if (state.busy !== '') {
            return;
        }
        const list = sortAnnouncements(l1List());
        if (list.length < 1) {
            alertMsg(t.needL1First, 'error');
            setMeta(t.needL1First, 'fail');
            return;
        }
        const parent = list.length > 0 ? Number(list[0].id) || 0 : 0;
        openEditor({
            mode: 'create',
            id: 0,
            level_no: 2,
            parent_announcement_id: parent,
            dept_abbr_hr: '',
            title: '',
            detail: '',
        });
    });

    el.search.addEventListener('input', (event) => {
        state.search = String(event.target ? event.target.value : '');
        state.page = 1;
        renderTable();
    });

    el.filter.addEventListener('change', (event) => {
        state.filterL1 = Number(event.target ? event.target.value : 0) || 0;
        state.page = 1;
        renderTable();
    });

    el.prev.addEventListener('click', () => {
        if (state.page <= 1) {
            return;
        }
        state.page -= 1;
        renderTable();
    });

    el.next.addEventListener('click', () => {
        state.page += 1;
        renderTable();
    });

    state.announcements = sortAnnouncements((Array.isArray(initialAnnouncements) ? initialAnnouncements : []).map(parseAnnouncement));
    renderAll();
})();
</script>

