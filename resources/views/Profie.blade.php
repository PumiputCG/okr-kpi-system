@extends('layout')

@section('title', 'Profile')
@section('page_heading_key', 'profileTitle')
@section('page_heading', 'Profile')
@section('page_desc_key', 'profileSubtitle')
@section('page_description', 'Review your account details and update your account safely.')

@section('content')
    @php
        $profileImage = $user?->profile_picture ? asset($user->profile_picture) : null;
    @endphp

    <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 14px;">
        <section style="background: #ffffff; padding: 20px 22px; box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);">
            <h2 data-i18n="profileSectionUser" style="margin: 0 0 14px; font-size: 18px;">User Information</h2>

            <div style="display: grid; grid-template-columns: 190px 1fr; gap: 9px 14px; font-size: 14px;">
                <div data-i18n="labelFullNameTh" style="font-weight: 700;">Full Name (TH)</div>
                <div>{{ $user?->full_name_th ?? '-' }}</div>

                <div data-i18n="labelFullNameEn" style="font-weight: 700;">Full Name (EN)</div>
                <div>{{ $user?->full_name_en ?? '-' }}</div>

                <div data-i18n="labelUsernameProfile" style="font-weight: 700;">Username</div>
                <div>{{ $user?->employee_code ?? '-' }}</div>

                <div data-i18n="labelPositionProfile" style="font-weight: 700;">Position</div>
                <div>{{ $user?->position ?? '-' }}</div>

                <div data-i18n="labelDepartment" style="font-weight: 700;">Department</div>
                <div>
                    @if($user?->department)
                        @if($user?->dept_abbr_hr)
                            {{ $user->dept_abbr_hr }} ({{ $user->department }})
                        @else
                            {{ $user->department }}
                        @endif
                    @else
                        -
                    @endif
                </div>

                <div data-i18n="labelRoleProfile" style="font-weight: 700;">Role</div>
                <div>{{ $user?->role ?? '-' }}</div>
            </div>
        </section>

        <section style="background: #ffffff; padding: 20px 22px; box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);">
            <h2 data-i18n="profileSectionPhoto" style="margin: 0 0 14px; font-size: 18px;">Profile Photo</h2>

            @if ($profileImage)
                <img src="{{ $profileImage }}" alt="Profile photo" style="width: 160px; height: 160px; object-fit: cover; display: block; background: #f3f4f6;">
            @else
                <div data-i18n="photoPlaceholder" style="width: 160px; height: 160px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: #6b7280; font-size: 13px;">
                    No Photo
                </div>
            @endif

            <form id="photo-form" action="{{ route('profile.photo.update', ['lang' => request('lang', 'en')]) }}" method="POST" enctype="multipart/form-data" style="margin-top: 14px;">
                @csrf
                <input id="profile_picture" name="profile_picture" type="file"
                    accept=".jpg,.jpeg,.png,.webp,.heic,.heif,image/jpeg,image/png,image/webp,image/heic,image/heif"
                    style="width: 100%; padding: 8px 0; font-size: 14px;">
                <p data-i18n="profilePhotoHint" style="margin: 6px 0 0; font-size: 12px; color: #6b7280;">
                    Supported: .jpg .jpeg .png .webp .heic .heif, up to 50MB
                </p>
                <p data-i18n="profilePhotoAutoHint" style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">
                    Choose a photo and it will update immediately.
                </p>
            </form>
        </section>
    </div>

    <section style="margin-top: 14px; background: #ffffff; padding: 20px 22px 320px; box-shadow: 0 10px 24px rgba(17, 17, 17, 0.06);">
        <h2 data-i18n="profileSectionSecurity" style="margin: 0 0 14px; font-size: 18px;">Security</h2>

        <form id="password-form" class="js-confirm-action" action="{{ route('profile.password.update', ['lang' => request('lang', 'en')]) }}" method="POST" data-confirm-key="confirmSavePassword">
            @csrf

            <div style="display: grid; grid-template-columns: minmax(200px, 50%) auto; column-gap: 8px; row-gap: 12px; align-items: start;">
                <div>
                    <label data-i18n="labelIdentityCode" for="identity_code" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700;">
                        National ID / Social Security No.
                    </label>
                    <input id="identity_code" name="identity_code" type="text" value="{{ old('identity_code') }}"
                        style="width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid #e5e7eb; background: #ffffff;">
                    <p id="identity-hint" data-i18n="hintIdentityCode" style="margin: 6px 0 0; font-size: 12px; color: #6b7280;">
                        Enter and verify National ID / Social Security No. first.
                    </p>
                </div>
                <div style="padding-top: 24px;">
                    <button id="verify-identity-btn" type="button" data-i18n="btnVerifyIdentity"
                        style="width: 96px; border: 0; background: #111111; color: #ffffff; font-size: 13px; font-weight: 700; padding: 10px 0; cursor: pointer;">
                        Verify
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                    <div>
                        <label for="new_password" style="display:flex; align-items:center; gap:6px; margin-bottom:6px; font-size:13px; font-weight:700;">
                            <span data-i18n="labelNewPassword">New Password</span>
                            <span data-i18n="hintPasswordMin8" style="font-size:11px; font-weight:500; color:#6b7280;">At least 8 characters</span>
                        </label>
                        <div style="position: relative; width: 100%;">
                            <input id="new_password" name="new_password" type="password"
                                style="width: 100%; padding: 10px 42px 10px 12px; font-size: 14px; border: 1px solid #e5e7eb; background: #f3f4f6;"
                                disabled>
                            <button type="button" class="toggle-password" data-toggle-target="new_password"
                                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); border:0; background:transparent; color:#6b7280; cursor:pointer; padding:0; display:flex; align-items:center;">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label data-i18n="labelNewPasswordConfirm" for="new_password_confirmation" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700;">
                            Confirm New Password
                        </label>
                        <div style="position: relative; width: 100%;">
                            <input id="new_password_confirmation" name="new_password_confirmation" type="password"
                                style="width: 100%; padding: 10px 42px 10px 12px; font-size: 14px; border: 1px solid #e5e7eb; background: #f3f4f6;"
                                disabled>
                            <button type="button" class="toggle-password" data-toggle-target="new_password_confirmation"
                                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); border:0; background:transparent; color:#6b7280; cursor:pointer; padding:0; display:flex; align-items:center;">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div style="padding-top: 24px;">
                    <button type="submit" data-i18n="btnSave"
                        style="width: 96px; border: 0; background: #e83e8c; color: #ffffff; font-size: 14px; font-weight: 700; padding: 10px 0; cursor: pointer;">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </section>
    <script>
        const photoForm = document.getElementById('photo-form');
        const profilePictureInput = document.getElementById('profile_picture');
        const identityInput = document.getElementById('identity_code');
        const verifyIdentityBtn = document.getElementById('verify-identity-btn');
        const newPasswordInput = document.getElementById('new_password');
        const newPasswordConfirmInput = document.getElementById('new_password_confirmation');
        const passwordForm = document.getElementById('password-form');
        const identityHint = document.getElementById('identity-hint');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const toggleButtons = document.querySelectorAll('.toggle-password');

        let identityVerified = false;
        const eyeSvg = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const eyeOffSvg = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"></path><path d="M10.5 6.8A8.9 8.9 0 0 1 12 6c7 0 11 6 11 6a18.9 18.9 0 0 1-3.1 3.9"></path><path d="M6.7 6.7A18.5 18.5 0 0 0 1 12s4 7 11 7a10.8 10.8 0 0 0 4.8-1.1"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>';

        function textByLang(thText, enText) {
            if (window.AppModal && typeof window.AppModal.getLang === 'function') {
                return window.AppModal.getLang() === 'th' ? thText : enText;
            }

            return enText;
        }

        function setToggleIcon(button, hidden) {
            button.innerHTML = hidden ? eyeSvg : eyeOffSvg;
        }

        function setPasswordFieldsEnabled(enabled) {
            newPasswordInput.disabled = !enabled;
            newPasswordConfirmInput.disabled = !enabled;
            if (enabled) {
                newPasswordInput.style.background = '#ffffff';
                newPasswordConfirmInput.style.background = '#ffffff';
                identityHint.textContent = window.AppModal
                    ? window.AppModal.getText('identityCodeValid')
                    : textByLang('รหัสบัตรประชาชน / บัตรประกันสังคม ถูกต้อง', 'National ID / Social Security No. is valid.');
                identityHint.style.color = '#2d7a46';
            } else {
                newPasswordInput.style.background = '#f3f4f6';
                newPasswordConfirmInput.style.background = '#f3f4f6';
                identityHint.textContent = window.AppModal
                    ? window.AppModal.getText('hintIdentityCode')
                    : textByLang(
                        'กรอกรหัสบัตรประชาชน / บัตรประกันสังคม แล้วกดตรวจสอบก่อน',
                        'Enter and verify National ID / Social Security No. first.'
                    );
                identityHint.style.color = '#6b7280';
            }
        }

        function resetVerificationState() {
            identityVerified = false;
            setPasswordFieldsEnabled(false);
        }

        identityInput.addEventListener('input', resetVerificationState);
        resetVerificationState();

        if (photoForm && profilePictureInput) {
            profilePictureInput.addEventListener('change', () => {
                if (!profilePictureInput.files || profilePictureInput.files.length === 0) {
                    return;
                }

                window.AppModal.confirm(window.AppModal.getText('confirmSavePhoto'), () => {
                    photoForm.submit();
                });
            });
        }

        toggleButtons.forEach((button) => {
            const targetId = button.getAttribute('data-toggle-target');
            const targetInput = document.getElementById(targetId);
            if (!targetInput) return;

            setToggleIcon(button, targetInput.type === 'password');
            button.addEventListener('click', () => {
                targetInput.type = targetInput.type === 'password' ? 'text' : 'password';
                setToggleIcon(button, targetInput.type === 'password');
            });
        });

        verifyIdentityBtn.addEventListener('click', () => {
            const identityCode = identityInput.value.trim();
            if (identityCode === '') {
                window.AppModal.alert(window.AppModal.getText('hintIdentityCode'));
                return;
            }

            window.AppModal.confirm(window.AppModal.getText('confirmVerifyIdentity'), async () => {
                try {
                    const response = await fetch(@json(route('profile.verifyIdentity')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            identity_code: identityCode,
                            lang: window.AppModal && typeof window.AppModal.getLang === 'function'
                                ? window.AppModal.getLang()
                                : 'en',
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        identityVerified = false;
                        setPasswordFieldsEnabled(false);
                        window.AppModal.alert(data.message || window.AppModal.getText('verifyFailed'));
                        return;
                    }

                    identityVerified = true;
                    setPasswordFieldsEnabled(true);
                    window.AppModal.alert(data.message || window.AppModal.getText('identityVerified'), 'success');
                } catch (error) {
                    identityVerified = false;
                    setPasswordFieldsEnabled(false);
                    window.AppModal.alert(window.AppModal.getText('networkVerifyError'));
                }
            });
        });

        passwordForm.addEventListener('submit', (event) => {
            if (!identityVerified) {
                event.preventDefault();
                window.AppModal.alert(window.AppModal.getText('identityNotVerified'));
            }
        });
    </script>
@endsection
