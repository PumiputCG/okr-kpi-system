(() => {
    const initialLang = @json($lang) === 'th' ? 'th' : 'en';
    const saveUrl = @json($userAnnouncementSaveUrl);
    const updateUrl = @json($userAnnouncementUpdateUrl);
    const deleteUrl = @json($userAnnouncementDeleteUrl);
    const initialAnnouncements = @json($userVisibleAnnouncements);
    const defaultAdminId = Number(@json($userPostPrimaryAdminId)) || 0;
    const viewerUserId = Number(@json($homeViewerUserId)) || 0;
    const postContexts = @json($userPostContexts);

    const addBtn = document.getElementById('user-post-add-card');
    const metaEl = document.getElementById('user-post-meta');
    const listEl = document.getElementById('user-post-list');
    const targetsEl = document.getElementById('user-post-targets');
    const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenEl ? String(csrfTokenEl.getAttribute('content') || '') : '';

    if (!addBtn || !metaEl || !listEl || !targetsEl) {
        return;
    }

    const textPack = {
        en: {
            title: 'Announcements',
            desc: 'Posts from your level, upper levels, and same level appear here.',
            postsTitle: 'Posts',
            postsAddTopicBtn: '+ Add Topic',
            postsHint: 'Positions that can see your posts',
            postTargetLabel: 'Positions that can see this post',
            postTargetEmpty: 'No lower positions found.',
            postsTitleLabel: 'Title',
            postsSubjectLabel: 'Subject',
            postsDetailLabel: 'Detail',
            postsDetailSectionLabel: 'Detail',
            postsFilesLabel: 'Files',
            postsFilesHint: 'Allowed: png, jpg, xlsx, word, pdf. Multiple files, large size.',
            postsReplaceFilesHint: 'Upload files to replace current attachments. Leave empty to keep old files.',
            postsCreateSaveBtn: 'Save',
            postsUpdateSaveBtn: 'Save',
            postsCancelEditBtn: 'Cancel',
            postsEditBtn: 'Edit',
            postsRemoveDraftBtn: 'Remove Card',
            postsListEmpty: 'No posts yet.',
            postsLevelEmpty: 'No posts in this level.',
            postsDeleteBtn: 'Delete',
            postsUntitled: 'Untitled',
            postsNoDetail: '-',
            postsNoFiles: 'No attached files.',
            postsSaving: 'Saving...',
            postsDeleting: 'Deleting...',
            postsPostOk: 'Post saved successfully.',
            postsUpdateOk: 'Post updated successfully.',
            postsPostFail: 'Unable to save post.',
            postsUpdateFail: 'Unable to update post.',
            postsDeleteOk: 'Post deleted successfully.',
            postsDeleteFail: 'Unable to delete post.',
            postsPreviewClose: 'Close',
            postsNeedPayload: 'Please enter title/detail or attach at least one file.',
            postsNeedUpdateRoute: 'Update route is not available yet.',
            postsRouteMissing: 'Post route is not available yet.',
            postsNoContext: 'No hierarchy context found for your position.',
            levelLabel: 'Level {level}',
        },
        th: {
            title: '\u0e1b\u0e23\u0e30\u0e01\u0e32\u0e28',
            desc: '\u0e04\u0e38\u0e13\u0e08\u0e30\u0e40\u0e2b\u0e47\u0e19\u0e42\u0e1e\u0e2a\u0e15\u0e4c\u0e08\u0e32\u0e01\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e15\u0e31\u0e27\u0e40\u0e2d\u0e07 \u0e25\u0e33\u0e14\u0e31\u0e1a\u0e40\u0e14\u0e35\u0e22\u0e27\u0e01\u0e31\u0e19 \u0e41\u0e25\u0e30\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e17\u0e35\u0e48\u0e2a\u0e39\u0e07\u0e01\u0e27\u0e48\u0e32',
            postsTitle: '\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsAddTopicBtn: '+ \u0e40\u0e1e\u0e34\u0e48\u0e21\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d',
            postsHint: '\u0e15\u0e33\u0e41\u0e2b\u0e19\u0e48\u0e07\u0e17\u0e35\u0e48\u0e2a\u0e32\u0e21\u0e32\u0e23\u0e16\u0e40\u0e2b\u0e47\u0e19\u0e42\u0e1e\u0e2a',
            postTargetLabel: '\u0e15\u0e33\u0e41\u0e2b\u0e19\u0e48\u0e07\u0e17\u0e35\u0e48\u0e2a\u0e32\u0e21\u0e32\u0e23\u0e16\u0e40\u0e2b\u0e47\u0e19\u0e42\u0e1e\u0e2a',
            postTargetEmpty: '\u0e44\u0e21\u0e48\u0e1e\u0e1a\u0e15\u0e33\u0e41\u0e2b\u0e19\u0e48\u0e07\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e16\u0e31\u0e14\u0e25\u0e07\u0e44\u0e1b',
            postsTitleLabel: '\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d',
            postsSubjectLabel: '\u0e2b\u0e31\u0e27\u0e40\u0e23\u0e37\u0e48\u0e2d\u0e07',
            postsDetailLabel: '\u0e23\u0e32\u0e22\u0e25\u0e30\u0e40\u0e2d\u0e35\u0e22\u0e14',
            postsDetailSectionLabel: '\u0e23\u0e32\u0e22\u0e25\u0e30\u0e40\u0e2d\u0e35\u0e22\u0e14',
            postsFilesLabel: '\u0e44\u0e1f\u0e25\u0e4c\u0e41\u0e19\u0e1a',
            postsFilesHint: '\u0e23\u0e2d\u0e07\u0e23\u0e31\u0e1a: png, jpg, xlsx, word, pdf \u0e41\u0e19\u0e1a\u0e44\u0e14\u0e49\u0e2b\u0e25\u0e32\u0e22\u0e44\u0e1f\u0e25\u0e4c \u0e41\u0e25\u0e30\u0e02\u0e19\u0e32\u0e14\u0e44\u0e14\u0e49\u0e04\u0e48\u0e2d\u0e19\u0e02\u0e49\u0e32\u0e07\u0e21\u0e32\u0e01',
            postsReplaceFilesHint: '\u0e2b\u0e32\u0e01\u0e41\u0e19\u0e1a\u0e44\u0e1f\u0e25\u0e4c\u0e43\u0e2b\u0e21\u0e48 \u0e08\u0e30\u0e41\u0e17\u0e19\u0e44\u0e1f\u0e25\u0e4c\u0e40\u0e14\u0e34\u0e21\u0e17\u0e31\u0e49\u0e07\u0e2b\u0e21\u0e14',
            postsCreateSaveBtn: '\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01',
            postsUpdateSaveBtn: '\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01',
            postsCancelEditBtn: '\u0e22\u0e01\u0e40\u0e25\u0e34\u0e01',
            postsEditBtn: '\u0e41\u0e01\u0e49\u0e44\u0e02',
            postsRemoveDraftBtn: '\u0e25\u0e1a\u0e01\u0e32\u0e23\u0e4c\u0e14',
            postsListEmpty: '\u0e22\u0e31\u0e07\u0e44\u0e21\u0e48\u0e21\u0e35\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsLevelEmpty: '\u0e22\u0e31\u0e07\u0e44\u0e21\u0e48\u0e21\u0e35\u0e42\u0e1e\u0e2a\u0e15\u0e4c\u0e43\u0e19\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e19\u0e35\u0e49',
            postsDeleteBtn: '\u0e25\u0e1a',
            postsUntitled: '\u0e44\u0e21\u0e48\u0e21\u0e35\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d',
            postsNoDetail: '-',
            postsNoFiles: '\u0e44\u0e21\u0e48\u0e21\u0e35\u0e44\u0e1f\u0e25\u0e4c\u0e41\u0e19\u0e1a',
            postsSaving: '\u0e01\u0e33\u0e25\u0e31\u0e07\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01...',
            postsDeleting: '\u0e01\u0e33\u0e25\u0e31\u0e07\u0e25\u0e1a...',
            postsPostOk: '\u0e42\u0e1e\u0e2a\u0e15\u0e4c\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08',
            postsUpdateOk: '\u0e2d\u0e31\u0e1b\u0e40\u0e14\u0e15\u0e42\u0e1e\u0e2a\u0e15\u0e4c\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08',
            postsPostFail: '\u0e44\u0e21\u0e48\u0e2a\u0e32\u0e21\u0e32\u0e23\u0e16\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsUpdateFail: '\u0e44\u0e21\u0e48\u0e2a\u0e32\u0e21\u0e32\u0e23\u0e16\u0e41\u0e01\u0e49\u0e44\u0e02\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsDeleteOk: '\u0e25\u0e1a\u0e42\u0e1e\u0e2a\u0e15\u0e4c\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08',
            postsDeleteFail: '\u0e44\u0e21\u0e48\u0e2a\u0e32\u0e21\u0e32\u0e23\u0e16\u0e25\u0e1a\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsPreviewClose: '\u0e1b\u0e34\u0e14',
            postsNeedPayload: '\u0e01\u0e23\u0e38\u0e13\u0e32\u0e01\u0e23\u0e2d\u0e01\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d/\u0e23\u0e32\u0e22\u0e25\u0e30\u0e40\u0e2d\u0e35\u0e22\u0e14 \u0e2b\u0e23\u0e37\u0e2d\u0e41\u0e19\u0e1a\u0e44\u0e1f\u0e25\u0e4c\u0e2d\u0e22\u0e48\u0e32\u0e07\u0e19\u0e49\u0e2d\u0e22 1 \u0e44\u0e1f\u0e25\u0e4c',
            postsNeedUpdateRoute: '\u0e22\u0e31\u0e07\u0e44\u0e21\u0e48\u0e21\u0e35 route \u0e2a\u0e33\u0e2b\u0e23\u0e31\u0e1a\u0e41\u0e01\u0e49\u0e44\u0e02\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsRouteMissing: '\u0e22\u0e31\u0e07\u0e44\u0e21\u0e48\u0e21\u0e35 route \u0e2a\u0e33\u0e2b\u0e23\u0e31\u0e1a\u0e42\u0e1e\u0e2a\u0e15\u0e4c',
            postsNoContext: '\u0e44\u0e21\u0e48\u0e1e\u0e1a\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e15\u0e33\u0e41\u0e2b\u0e19\u0e48\u0e07\u0e02\u0e2d\u0e07\u0e04\u0e38\u0e13\u0e43\u0e19\u0e1c\u0e31\u0e07\u0e17\u0e35\u0e48\u0e41\u0e2d\u0e14\u0e21\u0e34\u0e19\u0e15\u0e31\u0e49\u0e07\u0e04\u0e48\u0e32',
            levelLabel: '\u0e25\u0e33\u0e14\u0e31\u0e1a\u0e17\u0e35\u0e48 {level}',
        },
    };

    const state = {
        lang: initialLang,
        drafts: [],
        announcements: (Array.isArray(initialAnnouncements) ? initialAnnouncements : []).map((item) => normalizeAnnouncement(item)),
        editing: {},
        postingKey: '',
        deletingId: 0,
        draftNo: 1,
    };

    function t(key, params = {}) {
        const lang = state.lang === 'th' ? 'th' : 'en';
        const pack = textPack[lang] || textPack.en;
        let value = pack[key] || textPack.en[key] || key;
        Object.keys(params).forEach((paramKey) => {
            value = value.replace(`{${paramKey}}`, String(params[paramKey]));
        });
        return value;
    }

    function normalizeAnnouncement(item) {
        const files = Array.isArray(item && item.files) ? item.files : [];
        const postedByUserId = Number(item && item.posted_by_user_id) || 0;
        let canManage = false;
        if (item && typeof item.can_manage !== 'undefined' && item.can_manage !== null) {
            if (typeof item.can_manage === 'boolean') {
                canManage = item.can_manage;
            } else if (typeof item.can_manage === 'number') {
                canManage = item.can_manage === 1;
            } else if (typeof item.can_manage === 'string') {
                const normalizedCanManage = item.can_manage.trim().toLowerCase();
                canManage = normalizedCanManage === '1' || normalizedCanManage === 'true' || normalizedCanManage === 'yes';
            }
        }
        if (!canManage && viewerUserId > 0 && postedByUserId > 0 && postedByUserId === viewerUserId) {
            canManage = true;
        }
        return {
            id: Number(item && item.id) || 0,
            posted_by_user_id: postedByUserId,
            can_manage: canManage,
            level_no: Number(item && item.level_no) || 1,
            title: String((item && item.title) || ''),
            detail: String((item && item.detail) || ''),
            posted_at: String((item && item.posted_at) || ''),
            author_name: String((item && item.author_name) || ''),
            author_profile: String((item && item.author_profile) || ''),
            author_role: String((item && item.author_role) || '').trim().toLowerCase(),
            files: files.map((file) => ({
                id: Number(file && file.id) || 0,
                name: String((file && file.name) || ''),
                size_text: String((file && file.size_text) || '-'),
                url: String((file && file.url) || ''),
            })),
        };
    }

    function normalizePosition(value) {
        return String(value || '').trim().replace(/\s+/g, ' ').toUpperCase();
    }

    function setMeta(message, mode = '') {
        metaEl.className = 'post-meta';
        if (mode === 'ok') {
            metaEl.classList.add('ok');
        } else if (mode === 'fail') {
            metaEl.classList.add('fail');
        }
        metaEl.textContent = message;
    }

    function applyStaticText() {
        document.querySelectorAll('[data-home-user-i18n]').forEach((el) => {
            const key = el.getAttribute('data-home-user-i18n');
            if (key) {
                el.textContent = t(key);
            }
        });
    }

    function resolveAdminId() {
        if (defaultAdminId > 0) {
            return defaultAdminId;
        }
        if (!Array.isArray(postContexts) || postContexts.length === 0) {
            return 0;
        }
        return Number(postContexts[0] && postContexts[0].admin_user_id) || 0;
    }

    function resolvePostLevelTone(rawLevel) {
        const level = Math.max(1, Number(rawLevel) || 1);
        if (level === 1) {
            return 1;
        }
        if (level === 2) {
            return 2;
        }
        return 3;
    }

    function resolveAuthorDisplay(item) {
        const rawRole = String((item && item.author_role) || '').trim().toLowerCase();
        if (rawRole === 'admin') {
            return { summary: 'Admin', expanded: 'Admin' };
        }

        const rawName = String((item && item.author_name) || '').trim();
        const rawProfile = String((item && item.author_profile) || '').trim();
        const parts = rawProfile.split(',').map((part) => String(part || '').trim()).filter((part) => part !== '');

        let fullName = rawName;
        let employeeCode = '';
        let department = '';
        let position = '';

        if (parts.length >= 1 && fullName === '') {
            fullName = parts[0];
        }
        if (parts.length >= 2) {
            employeeCode = parts[1];
        }
        if (parts.length >= 3) {
            department = parts[2];
        }
        if (parts.length >= 4) {
            position = parts[3];
        }

        if (position === '' && parts.length >= 1) {
            position = parts[parts.length - 1];
        }
        if (department === '' && parts.length >= 2) {
            department = parts[parts.length - 2];
        }

        const safe = (value) => {
            const text = String(value || '').trim();
            return text !== '' && text !== '-' ? text : '-';
        };

        const normalizedPosition = safe(position).toUpperCase();
        const normalizedDepartment = safe(department);
        const summary = `${normalizedPosition} ,${normalizedDepartment}`;
        const expanded = `${normalizedPosition} ,${normalizedDepartment} (${safe(employeeCode)} ${safe(fullName)})`;

        return { summary, expanded };
    }

    function formatPostedDate(rawValue) {
        const text = String(rawValue || '').trim();
        if (text === '') {
            return '-';
        }

        const date = new Date(text);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        const parts = new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            timeZone: 'Asia/Bangkok',
        }).formatToParts(date);
        const day = parts.find((part) => part.type === 'day')?.value || '';
        const month = parts.find((part) => part.type === 'month')?.value || '';
        const yearText = parts.find((part) => part.type === 'year')?.value || '';
        const year = Number(yearText);
        if (!day || !month || !Number.isFinite(year)) {
            return '-';
        }

        return `${day}.${month}.${year + 543}`;
    }

    function renderTargets() {
        targetsEl.innerHTML = '';
        const used = new Set();
        const values = [];
        if (Array.isArray(postContexts)) {
            postContexts.forEach((context) => {
                const list = Array.isArray(context && context.visible_positions) ? context.visible_positions : [];
                list.forEach((position) => {
                    const normalized = normalizePosition(position);
                    if (!normalized || used.has(normalized)) {
                        return;
                    }
                    used.add(normalized);
                    values.push(normalized);
                });
            });
        }

        values.sort((a, b) => a.localeCompare(b));
        if (values.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'post-target-empty';
            empty.textContent = t('postTargetEmpty');
            targetsEl.appendChild(empty);
            return;
        }

        values.forEach((value) => {
            const chip = document.createElement('span');
            chip.className = 'post-target-chip';
            chip.textContent = value;
            targetsEl.appendChild(chip);
        });
    }

    function appendFiles(container, files) {
        if (!Array.isArray(files) || files.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'post-item-empty';
            empty.textContent = t('postsNoFiles');
            container.appendChild(empty);
            return;
        }

        const ul = document.createElement('ul');
        ul.className = 'post-item-files';
        files.forEach((file) => {
            const li = document.createElement('li');
            if (file.url) {
                const a = document.createElement('a');
                a.href = file.url;
                a.textContent = `${file.name || 'file'} (${file.size_text || '-'})`;
                li.appendChild(a);
            } else {
                li.textContent = `${file.name || 'file'} (${file.size_text || '-'})`;
            }
            ul.appendChild(li);
        });
        container.appendChild(ul);
    }

    function createDraft() {
        const next = state.draftNo;
        state.draftNo += 1;
        return {
            id: `draft-${next}`,
            title: '',
            detail: '',
        };
    }

    function createField(labelText, inputEl) {
        const field = document.createElement('label');
        field.className = 'post-field';
        const label = document.createElement('span');
        label.className = 'post-label';
        label.textContent = labelText;
        field.appendChild(label);
        field.appendChild(inputEl);
        return field;
    }

    const EDIT_CARD_MIN_WIDTH = 320;
    const EDIT_CARD_BASE_WIDTH = 380;
    const EDIT_CARD_MAX_WIDTH = 860;

    const clampNumber = (value, min, max) => Math.min(Math.max(value, min), max);

    function resolveLongestLineLength(value) {
        return String(value || '')
            .split(/\r?\n/)
            .reduce((maxLen, line) => Math.max(maxLen, String(line).length), 0);
    }

    function resolveLongestWordLength(value) {
        return String(value || '')
            .split(/\s+/)
            .reduce((maxLen, word) => Math.max(maxLen, String(word).length), 0);
    }

    function computeEditCardWidth(titleValue, detailValue) {
        const title = String(titleValue || '');
        const detail = String(detailValue || '');
        const longestLine = Math.max(resolveLongestLineLength(title), resolveLongestLineLength(detail));
        const longestWord = Math.max(resolveLongestWordLength(title), resolveLongestWordLength(detail));
        const contentLength = title.length + detail.length;

        const widthByLine = EDIT_CARD_BASE_WIDTH + Math.round(Math.min(420, longestLine * 4.6));
        const widthByWord = EDIT_CARD_BASE_WIDTH + Math.round(Math.min(320, longestWord * 6.2));
        const widthByTotal = EDIT_CARD_BASE_WIDTH + Math.round(Math.min(180, Math.max(0, contentLength - 120) * 0.35));
        const resolvedWidth = Math.max(widthByLine, widthByWord, widthByTotal);
        return clampNumber(resolvedWidth, EDIT_CARD_MIN_WIDTH, EDIT_CARD_MAX_WIDTH);
    }

    function fitEditTextareaHeight(textareaEl) {
        if (!(textareaEl instanceof HTMLTextAreaElement)) {
            return;
        }

        textareaEl.style.height = 'auto';
        const nextHeight = clampNumber(textareaEl.scrollHeight + 2, 110, 520);
        textareaEl.style.height = `${nextHeight}px`;
    }

    function bindEditorCardSizing(cardEl, titleInputEl, detailInputEl) {
        if (!(cardEl instanceof HTMLElement)) {
            return;
        }

        const refreshLayout = () => {
            const width = computeEditCardWidth(titleInputEl?.value, detailInputEl?.value);
            cardEl.style.setProperty('--post-edit-card-width', `${width}px`);
            fitEditTextareaHeight(detailInputEl);
            const trackEl = cardEl.closest('.post-level-track');
            if (trackEl instanceof HTMLElement) {
                const isOverflowing = trackEl.scrollWidth > trackEl.clientWidth + 2;
                trackEl.classList.toggle('is-overflowing', isOverflowing);
            }
        };

        refreshLayout();

        if (titleInputEl instanceof HTMLInputElement) {
            titleInputEl.addEventListener('input', refreshLayout);
        }
        if (detailInputEl instanceof HTMLTextAreaElement) {
            detailInputEl.addEventListener('input', refreshLayout);
        }
    }

    function createEditorCard({ mode, id, data, files = [], busy = false }) {
        const card = document.createElement('article');
        card.className = 'post-item post-item-editing';
        if (mode === 'draft') {
            card.dataset.draftId = id;
        } else {
            card.dataset.postId = String(id);
        }

        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.className = 'post-input';
        titleInput.maxLength = 60000;
        titleInput.value = String(data.title || '');
        if (mode === 'draft') {
            titleInput.dataset.draftField = 'title';
            titleInput.dataset.draftId = id;
        } else {
            titleInput.dataset.editField = 'title';
            titleInput.dataset.postId = String(id);
        }
        card.appendChild(createField(t('postsTitleLabel'), titleInput));

        const detailInput = document.createElement('textarea');
        detailInput.className = 'post-textarea';
        detailInput.maxLength = 60000;
        detailInput.value = String(data.detail || '');
        if (mode === 'draft') {
            detailInput.dataset.draftField = 'detail';
            detailInput.dataset.draftId = id;
        } else {
            detailInput.dataset.editField = 'detail';
            detailInput.dataset.postId = String(id);
        }
        card.appendChild(createField(t('postsDetailLabel'), detailInput));
        bindEditorCardSizing(card, titleInput, detailInput);

        const filesInput = document.createElement('input');
        filesInput.type = 'file';
        filesInput.className = 'post-input';
        filesInput.multiple = true;
        filesInput.accept = '.png,.jpg,.jpeg,.xlsx,.xls,.doc,.docx,.pdf';
        if (mode === 'draft') {
            filesInput.dataset.draftFiles = id;
        } else {
            filesInput.dataset.editFiles = String(id);
        }
        const filesField = createField(t('postsFilesLabel'), filesInput);
        const filesHint = document.createElement('p');
        filesHint.className = 'post-hint';
        filesHint.textContent = t('postsFilesHint');
        filesField.appendChild(filesHint);
        if (mode === 'edit') {
            const replaceHint = document.createElement('p');
            replaceHint.className = 'post-file-note';
            replaceHint.textContent = t('postsReplaceFilesHint');
            filesField.appendChild(replaceHint);
        }
        card.appendChild(filesField);

        if (mode === 'edit') {
            appendFiles(card, files);
        }

        const actions = document.createElement('div');
        actions.className = 'post-item-actions';
        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'post-btn post-btn-primary';
        saveBtn.textContent = mode === 'draft' ? t('postsCreateSaveBtn') : t('postsUpdateSaveBtn');
        saveBtn.disabled = busy;
        if (mode === 'draft') {
            saveBtn.dataset.saveDraftId = id;
        } else {
            saveBtn.dataset.savePostId = String(id);
        }
        actions.appendChild(saveBtn);

        if (mode === 'draft') {
            const hasContent = String(data.title || '').trim() !== '' || String(data.detail || '').trim() !== '';
            if (hasContent) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'post-btn';
                removeBtn.textContent = t('postsRemoveDraftBtn');
                removeBtn.disabled = busy;
                removeBtn.dataset.removeDraftId = id;
                actions.appendChild(removeBtn);
            }
        } else {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'post-btn';
            cancelBtn.textContent = t('postsCancelEditBtn');
            cancelBtn.disabled = busy;
            cancelBtn.dataset.cancelPostId = String(id);
            actions.appendChild(cancelBtn);
        }

        card.appendChild(actions);
        return card;
    }

    let previewBackdropEl = null;
    let previewTitleEl = null;
    let previewAuthorEl = null;
    let previewSubjectEl = null;
    let previewDetailEl = null;
    let previewFilesEl = null;
    let previewPostedAtEl = null;
    let previewCloseBtn = null;

    function ensurePreviewModal() {
        if (previewBackdropEl) {
            return;
        }

        previewBackdropEl = document.createElement('div');
        previewBackdropEl.className = 'post-preview-backdrop';

        const modal = document.createElement('article');
        modal.className = 'post-preview-modal';
        previewBackdropEl.appendChild(modal);

        const head = document.createElement('div');
        head.className = 'post-preview-head';
        modal.appendChild(head);

        const headLeft = document.createElement('div');
        headLeft.className = 'post-preview-head-left';
        head.appendChild(headLeft);

        previewTitleEl = document.createElement('h3');
        previewTitleEl.className = 'post-preview-title';
        headLeft.appendChild(previewTitleEl);

        previewAuthorEl = document.createElement('p');
        previewAuthorEl.className = 'post-item-author';
        headLeft.appendChild(previewAuthorEl);

        const headRight = document.createElement('div');
        headRight.className = 'post-preview-head-right';
        head.appendChild(headRight);

        previewPostedAtEl = document.createElement('p');
        previewPostedAtEl.className = 'post-preview-date';
        headRight.appendChild(previewPostedAtEl);

        previewCloseBtn = document.createElement('button');
        previewCloseBtn.type = 'button';
        previewCloseBtn.className = 'post-preview-close';
        previewCloseBtn.addEventListener('click', () => closePreviewModal());
        headRight.appendChild(previewCloseBtn);

        const subjectLabel = document.createElement('p');
        subjectLabel.className = 'post-item-field-label';
        subjectLabel.dataset.previewLabel = 'subject';
        modal.appendChild(subjectLabel);

        previewSubjectEl = document.createElement('p');
        previewSubjectEl.className = 'post-item-title';
        modal.appendChild(previewSubjectEl);

        const detailLabel = document.createElement('p');
        detailLabel.className = 'post-item-field-label';
        detailLabel.dataset.previewLabel = 'detail';
        modal.appendChild(detailLabel);

        previewDetailEl = document.createElement('p');
        previewDetailEl.className = 'post-preview-detail';
        modal.appendChild(previewDetailEl);

        previewFilesEl = document.createElement('div');
        modal.appendChild(previewFilesEl);

        previewBackdropEl.addEventListener('click', (event) => {
            if (event.target === previewBackdropEl) {
                closePreviewModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && previewBackdropEl && previewBackdropEl.classList.contains('is-open')) {
                closePreviewModal();
            }
        });

        document.body.appendChild(previewBackdropEl);
    }

    function closePreviewModal() {
        if (!previewBackdropEl) {
            return;
        }
        previewBackdropEl.classList.remove('is-open');
    }

    function openPreviewModal(item) {
        if (!item) {
            return;
        }
        ensurePreviewModal();
        if (!previewBackdropEl || !previewTitleEl || !previewCloseBtn || !previewAuthorEl || !previewSubjectEl || !previewDetailEl || !previewFilesEl || !previewPostedAtEl) {
            return;
        }

        previewTitleEl.textContent = t('postsTitle');
        previewCloseBtn.textContent = t('postsPreviewClose');
        const subjectLabelEl = previewBackdropEl.querySelector('[data-preview-label="subject"]');
        const detailLabelEl = previewBackdropEl.querySelector('[data-preview-label="detail"]');
        if (subjectLabelEl) {
            subjectLabelEl.textContent = t('postsSubjectLabel');
        }
        if (detailLabelEl) {
            detailLabelEl.textContent = t('postsDetailSectionLabel');
        }

        const authorDisplay = resolveAuthorDisplay(item);
        previewAuthorEl.textContent = authorDisplay.expanded;
        previewPostedAtEl.textContent = formatPostedDate(item.posted_at);
        previewSubjectEl.textContent = item.title !== '' ? item.title : t('postsUntitled');
        previewDetailEl.textContent = item.detail !== '' ? item.detail : t('postsNoDetail');

        previewFilesEl.innerHTML = '';
        const filesWrap = document.createElement('div');
        appendFiles(filesWrap, item.files);
        previewFilesEl.appendChild(filesWrap);

        previewBackdropEl.classList.add('is-open');
    }

    function renderList() {
        listEl.innerHTML = '';
        let hasCard = false;

        const resolveUserContext = () => {
            if (!Array.isArray(postContexts) || postContexts.length === 0) {
                return null;
            }

            if (defaultAdminId > 0) {
                const matched = postContexts.find((context) => Number(context && context.admin_user_id) === defaultAdminId);
                if (matched) {
                    return matched;
                }
            }

            return postContexts[0] || null;
        };

        const userContext = resolveUserContext();
        const draftLevelNo = Math.max(1, Number(userContext && userContext.level_no) || 1);
        const contextLevelsCount = Math.max(draftLevelNo, Number(userContext && userContext.levels_count) || draftLevelNo);
        const levelOrder = [];
        for (let level = 1; level <= contextLevelsCount; level += 1) {
            levelOrder.push(level);
        }
        state.announcements.forEach((item) => {
            const level = Math.max(1, Number(item && item.level_no) || 1);
            if (!levelOrder.includes(level)) {
                levelOrder.push(level);
            }
        });
        levelOrder.sort((a, b) => a - b);

        const levelTrackMap = new Map();
        const levelCardCounts = new Map();
        levelOrder.forEach((level) => {
            const group = document.createElement('section');
            group.className = 'post-level-group';
            group.dataset.level = String(level);

            const title = document.createElement('div');
            title.className = 'post-level-title';
            title.textContent = t('levelLabel', { level });
            group.appendChild(title);

            const track = document.createElement('div');
            track.className = 'post-level-track';
            group.appendChild(track);

            listEl.appendChild(group);
            levelTrackMap.set(level, track);
            levelCardCounts.set(level, 0);
        });

        const appendCardToLevel = (rawLevel, card) => {
            const level = Math.max(1, Number(rawLevel) || 1);
            const track = levelTrackMap.get(level) || levelTrackMap.get(levelOrder[0]);
            if (!track) {
                listEl.appendChild(card);
                return;
            }
            track.appendChild(card);
            levelCardCounts.set(level, (levelCardCounts.get(level) || 0) + 1);
        };

        state.drafts.forEach((draft) => {
            hasCard = true;
            const busy = state.postingKey === `draft:${draft.id}`;
            appendCardToLevel(draftLevelNo, createEditorCard({
                mode: 'draft',
                id: draft.id,
                data: draft,
                busy,
            }));
        });

        const announcements = state.announcements.slice().sort((a, b) => {
            if (a.posted_at && b.posted_at && a.posted_at !== b.posted_at) {
                return a.posted_at < b.posted_at ? 1 : -1;
            }
            return (Number(b.id) || 0) - (Number(a.id) || 0);
        });

        announcements.forEach((announcement) => {
            const postId = Number(announcement.id) || 0;
            const canManage = announcement.can_manage === true;
            const editState = state.editing[String(postId)] || null;
            if (canManage && editState) {
                hasCard = true;
                const busy = state.postingKey === `post:${postId}`;
                appendCardToLevel(announcement.level_no, createEditorCard({
                    mode: 'edit',
                    id: postId,
                    data: editState,
                    files: announcement.files,
                    busy,
                }));
                return;
            }

            hasCard = true;
            const deleting = state.deletingId === postId;

            const card = document.createElement('article');
            card.className = 'post-item post-item-summary';
            card.dataset.postId = String(postId);
            card.dataset.previewPostId = String(postId);
            card.dataset.levelTone = String(resolvePostLevelTone(announcement.level_no));

            const head = document.createElement('div');
            head.className = 'post-item-head';

            if (canManage) {
                const actions = document.createElement('div');
                actions.className = 'post-item-actions';
                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'post-btn';
                editBtn.dataset.editPostId = String(postId);
                editBtn.textContent = t('postsEditBtn');
                editBtn.disabled = deleting;
                actions.appendChild(editBtn);

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'post-btn post-btn-danger';
                deleteBtn.dataset.deletePostId = String(postId);
                deleteBtn.textContent = t('postsDeleteBtn');
                deleteBtn.disabled = deleting;
                actions.appendChild(deleteBtn);
                head.appendChild(actions);
            }

            card.appendChild(head);

            const authorDisplay = resolveAuthorDisplay(announcement);
            const author = document.createElement('p');
            author.className = 'post-item-author';
            author.textContent = authorDisplay.summary;
            card.appendChild(author);

            const subjectLabelEl = document.createElement('p');
            subjectLabelEl.className = 'post-item-field-label';
            subjectLabelEl.textContent = t('postsSubjectLabel');
            card.appendChild(subjectLabelEl);

            const title = document.createElement('h4');
            title.className = 'post-item-title';
            title.textContent = announcement.title !== '' ? announcement.title : t('postsUntitled');
            card.appendChild(title);

            const detailLabelEl = document.createElement('p');
            detailLabelEl.className = 'post-item-field-label';
            detailLabelEl.textContent = t('postsDetailSectionLabel');
            card.appendChild(detailLabelEl);

            const detail = document.createElement('p');
            detail.className = 'post-item-detail';
            detail.textContent = announcement.detail !== '' ? announcement.detail : t('postsNoDetail');
            card.appendChild(detail);
            appendFiles(card, announcement.files);
            appendCardToLevel(announcement.level_no, card);
        });

        levelTrackMap.forEach((track, level) => {
            if ((levelCardCounts.get(level) || 0) > 0) {
                return;
            }
            const empty = document.createElement('p');
            empty.className = 'post-level-empty';
            empty.textContent = t('postsLevelEmpty');
            track.appendChild(empty);
        });

        requestAnimationFrame(() => {
            levelTrackMap.forEach((track) => {
                const isOverflowing = track.scrollWidth > track.clientWidth + 2;
                track.classList.toggle('is-overflowing', isOverflowing);
            });
        });

        if (!hasCard && levelTrackMap.size === 0) {
            const empty = document.createElement('p');
            empty.className = 'post-item-empty';
            empty.textContent = t('postsListEmpty');
            listEl.appendChild(empty);
        }
    }

    function renderAll() {
        applyStaticText();
        renderTargets();
        renderList();
    }

    function updateDraft(draftId, field, value) {
        const index = state.drafts.findIndex((item) => item.id === String(draftId));
        if (index < 0) {
            return;
        }
        if (field === 'title' || field === 'detail') {
            state.drafts[index][field] = String(value || '');
        }
    }

    function updateEditing(postId, field, value) {
        const key = String(postId);
        if (!state.editing[key]) {
            return;
        }
        if (field === 'title' || field === 'detail') {
            state.editing[key][field] = String(value || '');
        }
    }

    addBtn.addEventListener('click', () => {
        state.drafts.unshift(createDraft());
        renderList();
    });

    listEl.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }
        if (target.dataset.draftField && target.dataset.draftId) {
            updateDraft(target.dataset.draftId, target.dataset.draftField, target.value);
            return;
        }
        if (target.dataset.editField && target.dataset.postId) {
            updateEditing(target.dataset.postId, target.dataset.editField, target.value);
        }
    });

    listEl.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }
        if (target.dataset.draftField && target.dataset.draftId) {
            updateDraft(target.dataset.draftId, target.dataset.draftField, target.value);
            return;
        }
        if (target.dataset.editField && target.dataset.postId) {
            updateEditing(target.dataset.postId, target.dataset.editField, target.value);
        }
    });

    listEl.addEventListener('click', async (event) => {
        const clicked = event.target instanceof Element ? event.target : null;
        if (!clicked) {
            return;
        }

        const summaryCard = clicked.closest('.post-item-summary[data-preview-post-id]');
        const clickedAction = clicked.closest('button, a, input, textarea, select');
        if (summaryCard && !clickedAction) {
            const previewPostId = Number(summaryCard.getAttribute('data-preview-post-id') || 0);
            const previewItem = state.announcements.find((item) => Number(item.id) === previewPostId);
            if (previewItem) {
                openPreviewModal(previewItem);
                return;
            }
        }

        const removeDraftBtn = clicked.closest('[data-remove-draft-id]');
        if (removeDraftBtn) {
            const draftId = String(removeDraftBtn.getAttribute('data-remove-draft-id') || '');
            if (state.postingKey === `draft:${draftId}`) {
                return;
            }
            state.drafts = state.drafts.filter((item) => item.id !== draftId);
            renderList();
            return;
        }

        const editBtn = clicked.closest('[data-edit-post-id]');
        if (editBtn) {
            const postId = Number(editBtn.getAttribute('data-edit-post-id') || 0);
            const current = state.announcements.find((item) => Number(item.id) === postId);
            if (postId < 1 || !current || current.can_manage !== true || state.deletingId === postId) {
                return;
            }
            state.editing[String(postId)] = {
                title: String(current.title || ''),
                detail: String(current.detail || ''),
            };
            renderList();
            return;
        }

        const cancelBtn = clicked.closest('[data-cancel-post-id]');
        if (cancelBtn) {
            const postId = Number(cancelBtn.getAttribute('data-cancel-post-id') || 0);
            if (postId < 1 || state.postingKey === `post:${postId}`) {
                return;
            }
            delete state.editing[String(postId)];
            renderList();
            return;
        }

        const saveDraftBtn = clicked.closest('[data-save-draft-id]');
        if (saveDraftBtn) {
            const draftId = String(saveDraftBtn.getAttribute('data-save-draft-id') || '');
            const key = `draft:${draftId}`;
            if (draftId === '' || state.postingKey === key) {
                return;
            }
            if (!saveUrl) {
                setMeta(t('postsRouteMissing'), 'fail');
                return;
            }
            const adminId = resolveAdminId();
            if (adminId < 1) {
                setMeta(t('postsNoContext'), 'fail');
                return;
            }
            const draft = state.drafts.find((item) => item.id === draftId);
            if (!draft) {
                return;
            }
            const card = listEl.querySelector(`[data-draft-id="${draftId}"]`);
            const fileInput = card ? card.querySelector(`[data-draft-files="${draftId}"]`) : null;
            const files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
            const title = String(draft.title || '').trim();
            const detail = String(draft.detail || '');
            if (title === '' && detail.trim() === '' && files.length === 0) {
                setMeta(t('postsNeedPayload'), 'fail');
                return;
            }

            const formData = new FormData();
            formData.append('admin_user_id', String(adminId));
            formData.append('title', title);
            formData.append('detail', detail);
            formData.append('lang', state.lang);
            files.forEach((file) => formData.append('files[]', file));

            state.postingKey = key;
            renderList();
            setMeta(t('postsSaving'));

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result || result.ok !== true) {
                    setMeta(result && result.message ? String(result.message) : t('postsPostFail'), 'fail');
                    return;
                }
                if (result.announcement) {
                    state.announcements.push(normalizeAnnouncement(result.announcement));
                }
                state.drafts = state.drafts.filter((item) => item.id !== draftId);
                setMeta(result.message ? String(result.message) : t('postsPostOk'), 'ok');
            } catch (error) {
                setMeta(t('postsPostFail'), 'fail');
            } finally {
                state.postingKey = '';
                renderList();
            }
            return;
        }

        const savePostBtn = clicked.closest('[data-save-post-id]');
        if (savePostBtn) {
            const postId = Number(savePostBtn.getAttribute('data-save-post-id') || 0);
            const key = `post:${postId}`;
            if (postId < 1 || state.postingKey === key) {
                return;
            }
            if (!updateUrl) {
                setMeta(t('postsNeedUpdateRoute'), 'fail');
                return;
            }
            const edit = state.editing[String(postId)];
            const current = state.announcements.find((item) => Number(item.id) === postId);
            if (!edit || !current || current.can_manage !== true) {
                return;
            }

            const card = listEl.querySelector(`[data-post-id="${postId}"]`);
            const fileInput = card ? card.querySelector(`[data-edit-files="${postId}"]`) : null;
            const files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
            const title = String(edit.title || '').trim();
            const detail = String(edit.detail || '');
            const currentFileCount = Array.isArray(current.files) ? current.files.length : 0;
            const resultingFileCount = files.length > 0 ? files.length : currentFileCount;
            if (title === '' && detail.trim() === '' && resultingFileCount === 0) {
                setMeta(t('postsNeedPayload'), 'fail');
                return;
            }

            const formData = new FormData();
            formData.append('announcement_id', String(postId));
            formData.append('title', title);
            formData.append('detail', detail);
            formData.append('lang', state.lang);
            files.forEach((file) => formData.append('files[]', file));

            state.postingKey = key;
            renderList();
            setMeta(t('postsSaving'));

            try {
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result || result.ok !== true) {
                    setMeta(result && result.message ? String(result.message) : t('postsUpdateFail'), 'fail');
                    return;
                }
                if (result.announcement) {
                    const updated = normalizeAnnouncement(result.announcement);
                    state.announcements = state.announcements.map((item) => (
                        Number(item.id) === postId ? updated : item
                    ));
                }
                delete state.editing[String(postId)];
                setMeta(result.message ? String(result.message) : t('postsUpdateOk'), 'ok');
            } catch (error) {
                setMeta(t('postsUpdateFail'), 'fail');
            } finally {
                state.postingKey = '';
                renderList();
            }
            return;
        }

        const deleteBtn = clicked.closest('[data-delete-post-id]');
        if (!deleteBtn) {
            return;
        }
        if (!deleteUrl) {
            setMeta(t('postsRouteMissing'), 'fail');
            return;
        }

        const postId = Number(deleteBtn.getAttribute('data-delete-post-id') || 0);
        const current = state.announcements.find((item) => Number(item.id) === postId);
        if (postId < 1 || !current || current.can_manage !== true || state.postingKey !== '') {
            return;
        }

        state.deletingId = postId;
        renderList();
        setMeta(t('postsDeleting'));

        try {
            const response = await fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    announcement_id: postId,
                    lang: state.lang,
                }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result || result.ok !== true) {
                setMeta(result && result.message ? String(result.message) : t('postsDeleteFail'), 'fail');
                return;
            }
            state.announcements = state.announcements.filter((item) => Number(item.id) !== postId);
            delete state.editing[String(postId)];
            setMeta(result.message ? String(result.message) : t('postsDeleteOk'), 'ok');
        } catch (error) {
            setMeta(t('postsDeleteFail'), 'fail');
        } finally {
            state.deletingId = 0;
            renderList();
        }
    });

    document.addEventListener('app:lang-changed', (event) => {
        const changed = event && event.detail && event.detail.lang === 'th' ? 'th' : 'en';
        state.lang = changed;
        renderAll();
    });

    renderAll();
})();
