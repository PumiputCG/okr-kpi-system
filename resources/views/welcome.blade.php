<!DOCTYPE html>
<html lang="{{ request('lang', 'en') === 'th' ? 'th' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OKR-KPI | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --brand-pink: #e83e8c;
            --brand-pink-hover: #d2307c;
            --brand-black: #111111;
        }

        body {
            font-family: 'Inter', 'Noto Sans Thai', sans-serif;
            background:
                radial-gradient(circle at 10% 20%, rgba(232, 62, 140, 0.03) 0%, transparent 42%),
                radial-gradient(circle at 90% 80%, rgba(232, 62, 140, 0.03) 0%, transparent 42%),
                #ffffff;
            min-height: 100vh;
        }

        .auth-shell {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            box-shadow: 0 18px 42px rgba(17, 17, 17, 0.08);
            border-radius: 18px;
            padding: 30px 28px;
        }

        .input-field {
            transition: all 0.25s ease;
            border: 1.5px solid #eeeeee;
        }

        .input-field:focus {
            border-color: var(--brand-pink);
            box-shadow: 0 0 0 4px rgba(232, 62, 140, 0.1);
            outline: none;
        }

        .btn-primary {
            border: 0;
            background: var(--brand-pink);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-primary:hover {
            background: var(--brand-pink-hover);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .input-field {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lang-btn {
            border: 0;
            background: transparent;
            color: #9ca3af;
            font-size: 14px;
            font-weight: 600;
            padding: 4px 8px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .lang-btn.active {
            color: var(--brand-pink);
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.48);
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
            width: min(96vw, 520px);
            background: #ffffff;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(17, 17, 17, 0.25);
        }

        .modal-title {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
        }

        .modal-text {
            margin: 10px 0 0;
            color: #4b5563;
            font-size: 14px;
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
            border-radius: 8px;
        }

        .btn-modal-primary {
            border: 0;
            background: var(--brand-pink);
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            cursor: pointer;
            border-radius: 8px;
        }

        .btn-modal-primary:hover {
            background: var(--brand-pink-hover);
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-6 relative overflow-x-hidden">
    <div class="absolute top-8 right-8 flex items-center gap-4">
        <a id="home-link" href="{{ url('/') }}?lang={{ request('lang', 'en') }}" class="p-2 text-gray-400 hover:text-pink-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        </a>
        <div class="inline-flex items-center gap-1">
            <button id="lang-th" type="button" class="lang-btn" onclick="switchLang('th')">TH</button>
            <button id="lang-en" type="button" class="lang-btn" onclick="switchLang('en')">EN</button>
        </div>
    </div>

    <main class="auth-shell z-10">
        <div class="auth-card">
            <header class="text-center mb-10">
                <h1 class="text-3xl font-bold tracking-tight mb-2" data-i18n="title">Welcome Back</h1>
                <p class="text-gray-400 text-sm" data-i18n="subtitle">Please enter your details to sign in.</p>
            </header>

            @if ($errors->any())
                <div style="margin-bottom: 12px; padding: 10px 12px; background: #fff1f6; color: #be185d; font-size: 13px; border-left: 3px solid #e83e8c;">
                    {{ $errors->first() }}
                </div>
            @elseif (session('status'))
                <div style="margin-bottom: 12px; padding: 10px 12px; background: #fff1f6; color: #be185d; font-size: 13px; border-left: 3px solid #e83e8c;">
                    {{ session('status') }}
                </div>
            @endif

            <form id="login-form" class="space-y-6" action="{{ route('login.store', ['lang' => request('lang', 'en')]) }}" method="POST">
                @csrf
                <input id="login-lang" type="hidden" name="lang" value="{{ request('lang', 'en') }}">

                <div class="space-y-2">
                    <label for="employee_id" class="text-xs font-semibold uppercase tracking-wider text-gray-500 ml-1" data-i18n="labelEmployeeId">Employee ID</label>
                    <input id="employee_id" name="employee_id" type="text" placeholder="user1" required value="{{ old('employee_id') }}"
                        class="input-field w-full px-4 py-3.5 rounded-xl text-sm bg-gray-50/50 focus:bg-white">
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center px-1">
                        <label for="password" class="text-xs font-semibold uppercase tracking-wider text-gray-500" data-i18n="labelPassword">Password</label>
                        <button id="forgot-password-link" type="button" class="text-xs font-medium text-pink-500 hover:underline" data-i18n="forgotPass">Forgot password?</button>
                    </div>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" placeholder="********" required
                            class="input-field w-full px-4 py-3.5 rounded-xl text-sm bg-gray-50/50 focus:bg-white">
                        <button type="button" class="toggle-password" data-toggle-target="password" aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full rounded-xl">
                    <span data-i18n="btnLogin">Sign In</span>
                </button>
            </form>
        </div>
    </main>

    <div class="mt-10 text-gray-300 text-[10px] uppercase tracking-[0.2em]">
        OKR-KPI System &copy; 2026
    </div>

    <div id="forgot-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel">
            <h3 class="modal-title" data-i18n="forgotModalTitle">Reset Password</h3>
            <p class="modal-text" data-i18n="forgotModalSubtitle">Enter username and verify your identity before setting a new password.</p>

            <div style="margin-top: 14px;">
                <label for="forgot_employee_id" data-i18n="labelEmployeeId" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Employee ID</label>
                <input id="forgot_employee_id" type="text" class="input-field" style="width:100%; padding:10px 12px; border-radius:10px;" placeholder="user1">
            </div>

            <div style="margin-top: 10px;">
                <label for="forgot_identity_code" data-i18n="labelIdentityCode" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">National ID / Social Security No.</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input id="forgot_identity_code" type="text" class="input-field" style="width:50%; min-width:180px; padding:10px 12px; border-radius:10px;">
                    <button id="verify-forgot-btn" type="button" data-i18n="btnVerifyIdentity" style="border:0; background:#111111; color:#ffffff; font-size:13px; font-weight:700; padding:9px 14px; border-radius:8px; cursor:pointer;">Verify</button>
                </div>
                <p id="forgot-identity-status" style="margin:6px 0 0; font-size:12px; color:#2d7a46; display:none;"></p>
            </div>

            <div style="display:grid; grid-template-columns:1fr; gap:10px; margin-top:10px;">
                <div>
                    <label for="forgot_new_password" style="display:flex; align-items:center; gap:6px; font-size:13px; font-weight:700; margin-bottom:6px;">
                        <span data-i18n="labelNewPassword">New Password</span>
                        <span data-i18n="hintPasswordMin8" style="font-size:11px; font-weight:500; color:#6b7280;">At least 8 characters</span>
                    </label>
                    <div class="password-wrap" style="width:100%;">
                        <input id="forgot_new_password" type="password" class="input-field" style="width:100%; padding:10px 44px 10px 12px; border-radius:10px; background:#f3f4f6;" disabled>
                        <button type="button" class="toggle-password" data-toggle-target="forgot_new_password" aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="forgot_new_password_confirmation" data-i18n="labelNewPasswordConfirm" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Confirm New Password</label>
                    <div class="password-wrap" style="width:100%;">
                        <input id="forgot_new_password_confirmation" type="password" class="input-field" style="width:100%; padding:10px 44px 10px 12px; border-radius:10px; background:#f3f4f6;" disabled>
                        <button type="button" class="toggle-password" data-toggle-target="forgot_new_password_confirmation" aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button id="forgot-reset-btn" type="button" class="btn-modal-primary" data-i18n="btnResetPassword">Reset Password</button>
                <button id="forgot-close-btn" type="button" class="btn-ghost" data-i18n="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <div id="confirm-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel">
            <h3 class="modal-title" data-i18n="modalConfirmTitle">Confirm Action</h3>
            <p id="confirm-message" class="modal-text"></p>
            <div class="modal-actions">
                <button id="confirm-ok" type="button" class="btn-modal-primary" data-i18n="modalConfirm">Confirm</button>
                <button id="confirm-cancel" type="button" class="btn-ghost" data-i18n="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <div id="alert-modal" class="modal-backdrop" aria-hidden="true">
        <div class="modal-panel">
            <h3 class="modal-title" data-i18n="modalAlertTitle">Notice</h3>
            <div class="alert-row">
                <span id="alert-icon" class="alert-icon">!</span>
                <p id="alert-message" class="modal-text" style="margin:0;"></p>
            </div>
            <div class="modal-actions">
                <button id="alert-close" type="button" class="btn-modal-primary" data-i18n="modalClose">Close</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const forgotVerifyRoute = @json(route('forgot.verify'));
        const forgotResetRoute = @json(route('forgot.reset'));
        const landingBaseUrl = @json(url('/'));
        const loginBaseUrl = @json(route('login.store'));

        const forgotModal = document.getElementById('forgot-modal');
        const forgotEmployeeInput = document.getElementById('forgot_employee_id');
        const forgotIdentityInput = document.getElementById('forgot_identity_code');
        const forgotIdentityStatus = document.getElementById('forgot-identity-status');
        const forgotNewPasswordInput = document.getElementById('forgot_new_password');
        const forgotNewPasswordConfirmInput = document.getElementById('forgot_new_password_confirmation');

        const confirmModal = document.getElementById('confirm-modal');
        const confirmMessage = document.getElementById('confirm-message');
        const confirmCancel = document.getElementById('confirm-cancel');
        const confirmOk = document.getElementById('confirm-ok');

        const alertModal = document.getElementById('alert-modal');
        const alertIcon = document.getElementById('alert-icon');
        const alertMessage = document.getElementById('alert-message');
        const alertClose = document.getElementById('alert-close');
        const passwordToggleButtons = document.querySelectorAll('.toggle-password');

        let currentLang = 'en';
        let pendingConfirmCallback = null;
        let forgotIdentityVerified = false;
        const eyeSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const eyeOffSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"></path><path d="M10.5 6.8A8.9 8.9 0 0 1 12 6c7 0 11 6 11 6a18.9 18.9 0 0 1-3.1 3.9"></path><path d="M6.7 6.7A18.5 18.5 0 0 0 1 12s4 7 11 7a10.8 10.8 0 0 0 4.8-1.1"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>';

        const translations = {
            en: {
                title: 'Welcome Back',
                subtitle: 'Please enter your details to sign in.',
                labelEmployeeId: 'Employee ID',
                labelPassword: 'Password',
                forgotPass: 'Forgot password?',
                btnLogin: 'Sign In',
                labelIdentityCode: 'National ID / Social Security No.',
                labelNewPassword: 'New Password',
                labelNewPasswordConfirm: 'Confirm New Password',
                hintPasswordMin8: 'At least 8 characters',
                btnVerifyIdentity: 'Verify',
                btnResetPassword: 'Reset Password',
                forgotModalTitle: 'Reset Password',
                forgotModalSubtitle: 'Enter username and verify your identity before setting a new password.',
                modalConfirmTitle: 'Confirm Action',
                modalAlertTitle: 'Notice',
                modalCancel: 'Cancel',
                modalConfirm: 'Confirm',
                modalClose: 'Close',
                confirmLogin: 'Do you want to sign in now?',
                confirmForgotVerify: 'Do you want to verify identity now?',
                confirmForgotReset: 'Do you want to reset password now?',
                identityVerified: 'Identity verification successful.',
                identityNotVerified: 'Please verify identity first.',
                identityCodeValid: 'National ID / Social Security No. is valid.',
                missingFields: 'Please complete all required fields.',
                resetSuccess: 'Password changed successfully.',
                networkVerifyError: 'Network error while verifying identity.',
                networkResetError: 'Network error while resetting password.',
                verifyFailed: 'Unable to verify identity.',
                resetFailed: 'Unable to reset password.'
            },
            th: {
                title: 'ยินดีต้อนรับ',
                subtitle: 'กรุณากรอกข้อมูลเพื่อเข้าสู่ระบบ',
                labelEmployeeId: 'รหัสพนักงาน',
                labelPassword: 'รหัสผ่าน',
                forgotPass: 'ลืมรหัสผ่าน?',
                btnLogin: 'เข้าสู่ระบบ',
                labelIdentityCode: 'รหัสบัตรประชาชน / บัตรประกันสังคม',
                labelNewPassword: 'รหัสผ่านใหม่',
                labelNewPasswordConfirm: 'ยืนยันรหัสผ่านใหม่',
                hintPasswordMin8: 'รหัสผ่าน 8 ตัวขึ้นไป',
                btnVerifyIdentity: 'ตรวจสอบ',
                btnResetPassword: 'เปลี่ยนรหัสผ่าน',
                forgotModalTitle: 'เปลี่ยนรหัสผ่าน',
                forgotModalSubtitle: 'กรอกชื่อผู้ใช้และตรวจสอบข้อมูลก่อนตั้งรหัสผ่านใหม่',
                modalConfirmTitle: 'ยืนยันการทำรายการ',
                modalAlertTitle: 'แจ้งเตือน',
                modalCancel: 'ยกเลิก',
                modalConfirm: 'ยืนยัน',
                modalClose: 'ปิด',
                confirmLogin: 'คุณต้องการเข้าสู่ระบบตอนนี้ใช่หรือไม่?',
                confirmForgotVerify: 'คุณต้องการตรวจสอบข้อมูลตอนนี้ใช่หรือไม่?',
                confirmForgotReset: 'คุณต้องการเปลี่ยนรหัสผ่านตอนนี้ใช่หรือไม่?',
                identityVerified: 'ตรวจสอบข้อมูลสำเร็จ',
                identityNotVerified: 'กรุณาตรวจสอบข้อมูลก่อน',
                identityCodeValid: 'รหัสบัตรประชาชน / บัตรประกันสังคม ถูกต้อง',
                missingFields: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                resetSuccess: 'เปลี่ยนรหัสผ่านสำเร็จ',
                networkVerifyError: 'เครือข่ายมีปัญหาระหว่างตรวจสอบข้อมูล',
                networkResetError: 'เครือข่ายมีปัญหาระหว่างเปลี่ยนรหัสผ่าน',
                verifyFailed: 'ไม่สามารถตรวจสอบข้อมูลได้',
                resetFailed: 'ไม่สามารถเปลี่ยนรหัสผ่านได้'
            }
        };

        function getText(key) {
            return (translations[currentLang] && translations[currentLang][key]) || (translations.en[key] || key);
        }

        function extractErrorMessage(data, fallback) {
            if (data && typeof data.message === 'string' && data.message.trim() !== '') {
                return data.message;
            }

            if (data && data.errors) {
                const firstField = Object.keys(data.errors)[0];
                if (firstField && Array.isArray(data.errors[firstField]) && data.errors[firstField][0]) {
                    return data.errors[firstField][0];
                }
            }

            return fallback;
        }

        function setToggleIcon(button, hidden) {
            button.innerHTML = hidden ? eyeSvg : eyeOffSvg;
        }

        function applyTranslations() {
            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.getAttribute('data-i18n');
                if (translations[currentLang] && translations[currentLang][key]) {
                    el.textContent = translations[currentLang][key];
                }
            });
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
            if (alertIcon) {
                const success = type === 'success';
                alertIcon.textContent = success ? '✓' : '!';
                alertIcon.classList.toggle('success', success);
            }
            alertMessage.textContent = message;
            alertModal.classList.add('show');
        }

        function closeAlert() {
            alertModal.classList.remove('show');
        }

        function setForgotPasswordEnabled(enabled) {
            forgotNewPasswordInput.disabled = !enabled;
            forgotNewPasswordConfirmInput.disabled = !enabled;
            forgotNewPasswordInput.style.background = enabled ? '#ffffff' : '#f3f4f6';
            forgotNewPasswordConfirmInput.style.background = enabled ? '#ffffff' : '#f3f4f6';
        }

        function resetForgotVerification() {
            forgotIdentityVerified = false;
            setForgotPasswordEnabled(false);
            forgotIdentityStatus.style.display = 'none';
            forgotIdentityStatus.textContent = '';
        }

        function updateLangNavigation(lang) {
            const homeLink = document.getElementById('home-link');
            if (homeLink) {
                const url = new URL(landingBaseUrl, window.location.origin);
                url.searchParams.set('lang', lang);
                homeLink.setAttribute('href', `${url.pathname}${url.search}${url.hash}`);
            }

            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                const url = new URL(loginBaseUrl, window.location.origin);
                url.searchParams.set('lang', lang);
                loginForm.setAttribute('action', `${url.pathname}${url.search}${url.hash}`);
            }

            const loginLangInput = document.getElementById('login-lang');
            if (loginLangInput) {
                loginLangInput.value = lang;
            }
        }

        function switchLang(lang) {
            currentLang = lang === 'th' ? 'th' : 'en';
            document.documentElement.lang = currentLang;
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

            applyTranslations();

            if (forgotIdentityVerified) {
                forgotIdentityStatus.textContent = getText('identityCodeValid');
                forgotIdentityStatus.style.display = 'block';
            }

            updateLangNavigation(currentLang);

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lang', currentLang);
            history.replaceState(null, '', currentUrl.toString());
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form.js-confirm-action');
            if (!form || form.dataset.confirmed === '1') return;

            event.preventDefault();
            const key = form.dataset.confirmKey || 'confirmLogin';
            openConfirm(getText(key), () => {
                form.dataset.confirmed = '1';
                form.submit();
            });
        });

        confirmCancel.addEventListener('click', closeConfirm);
        confirmOk.addEventListener('click', () => {
            const callback = pendingConfirmCallback;
            closeConfirm();
            if (typeof callback === 'function') callback();
        });
        alertClose.addEventListener('click', closeAlert);

        document.getElementById('forgot-password-link').addEventListener('click', () => {
            forgotEmployeeInput.value = document.getElementById('employee_id').value.trim();
            forgotIdentityInput.value = '';
            forgotNewPasswordInput.value = '';
            forgotNewPasswordConfirmInput.value = '';
            resetForgotVerification();
            forgotModal.classList.add('show');
        });

        document.getElementById('forgot-close-btn').addEventListener('click', () => {
            forgotModal.classList.remove('show');
        });

        forgotEmployeeInput.addEventListener('input', resetForgotVerification);
        forgotIdentityInput.addEventListener('input', resetForgotVerification);

        passwordToggleButtons.forEach((button) => {
            const targetId = button.getAttribute('data-toggle-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            setToggleIcon(button, input.type === 'password');
            button.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
                setToggleIcon(button, input.type === 'password');
            });
        });

        document.getElementById('verify-forgot-btn').addEventListener('click', () => {
            const employeeId = forgotEmployeeInput.value.trim();
            const identityCode = forgotIdentityInput.value.trim();

            if (!employeeId || !identityCode) {
                openAlert(getText('missingFields'));
                return;
            }

            openConfirm(getText('confirmForgotVerify'), async () => {
                try {
                    const response = await fetch(forgotVerifyRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            employee_id: employeeId,
                            identity_code: identityCode,
                            lang: currentLang,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        forgotIdentityVerified = false;
                        setForgotPasswordEnabled(false);
                        openAlert(extractErrorMessage(data, getText('verifyFailed')));
                        return;
                    }

                    forgotIdentityVerified = true;
                    setForgotPasswordEnabled(true);
                    forgotIdentityStatus.textContent = getText('identityCodeValid');
                    forgotIdentityStatus.style.display = 'block';
                    openAlert(data.message || getText('identityVerified'), 'success');
                } catch (error) {
                    forgotIdentityVerified = false;
                    setForgotPasswordEnabled(false);
                    openAlert(getText('networkVerifyError'));
                }
            });
        });

        document.getElementById('forgot-reset-btn').addEventListener('click', () => {
            const employeeId = forgotEmployeeInput.value.trim();
            const identityCode = forgotIdentityInput.value.trim();
            const newPassword = forgotNewPasswordInput.value;
            const newPasswordConfirmation = forgotNewPasswordConfirmInput.value;

            if (!forgotIdentityVerified) {
                openAlert(getText('identityNotVerified'));
                return;
            }

            if (!employeeId || !identityCode || !newPassword || !newPasswordConfirmation) {
                openAlert(getText('missingFields'));
                return;
            }

            openConfirm(getText('confirmForgotReset'), async () => {
                try {
                    const response = await fetch(forgotResetRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            employee_id: employeeId,
                            identity_code: identityCode,
                            new_password: newPassword,
                            new_password_confirmation: newPasswordConfirmation,
                            lang: currentLang,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        openAlert(extractErrorMessage(data, getText('resetFailed')));
                        return;
                    }

                    openAlert(data.message || getText('resetSuccess'), 'success');
                    forgotModal.classList.remove('show');
                    document.getElementById('employee_id').value = employeeId;
                } catch (error) {
                    openAlert(getText('networkResetError'));
                }
            });
        });

        const initialLang = new URLSearchParams(window.location.search).get('lang');
        switchLang(initialLang === 'th' ? 'th' : 'en');
        resetForgotVerification();
    </script>
</body>
</html>
