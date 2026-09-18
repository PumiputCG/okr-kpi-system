<!DOCTYPE html>
<html lang="{{ $lang === 'en' ? 'en' : 'th' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | OKR-KPI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-pink: #e83e8c;
            --brand-pink-hover: #d2307c;
            --brand-black: #111111;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', 'Noto Sans Thai', sans-serif;
            color: var(--brand-black);
            background:
                radial-gradient(circle at 12% 20%, rgba(232, 62, 140, 0.08), transparent 40%),
                radial-gradient(circle at 85% 82%, rgba(232, 62, 140, 0.08), transparent 42%),
                #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .lang-wrap {
            position: fixed;
            top: 24px;
            right: 24px;
            display: inline-flex;
            align-items: center;
            gap: 1px;
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

        .panel {
            width: min(92vw, 620px);
            background: #ffffff;
            border: 1px solid #f5d3e4;
            border-radius: 22px;
            box-shadow: 0 20px 48px rgba(17, 17, 17, 0.12);
            padding: 34px 26px;
            text-align: center;
        }

        .symbol {
            width: 92px;
            height: 92px;
            margin: 0 auto 18px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e83e8c 0%, #111111 100%);
            color: #ffffff;
            box-shadow: 0 14px 30px rgba(232, 62, 140, 0.28);
        }

        .message {
            margin: 0;
            font-size: clamp(20px, 2.8vw, 28px);
            line-height: 1.45;
            font-weight: 700;
        }

        .actions {
            margin-top: 24px;
            display: flex;
            justify-content: center;
        }

        .logout-btn {
            border: 0;
            background: var(--brand-pink);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s ease;
        }

        .logout-btn:hover {
            background: var(--brand-pink-hover);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="lang-wrap" aria-label="Language switcher">
        <button id="lang-th" class="lang-btn" type="button" onclick="switchLang('th')">TH</button>
        <button id="lang-en" class="lang-btn" type="button" onclick="switchLang('en')">EN</button>
    </div>

    <main class="panel">
        <div class="symbol" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 3h6l1 5H8l1-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M7 10.5h10l2 10.5H5L7 10.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M8 14h8M7 18h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </div>

        <p class="message" data-i18n="message"></p>

        <div class="actions">
            <form id="logout-form" method="POST" action="{{ route('logout', ['lang' => $lang]) }}">
                @csrf
                <button type="submit" class="logout-btn" data-i18n="backBtn"></button>
            </form>
        </div>
    </main>

    <script>
        const translations = {
            th: {
                message: 'ขณะนี้ปิดปรับปรุงเซิฟเวอร์ ขออภัยด้วยครับ',
                backBtn: 'กลับสู่หน้าเข้าสู่ระบบ'
            },
            en: {
                message: 'The server is currently under maintenance. We apologize for the inconvenience.',
                backBtn: 'Back to Sign In'
            }
        };

        const logoutBaseUrl = @json(route('logout'));
        let currentLang = @json($lang === 'en' ? 'en' : 'th');

        function applyTranslations() {
            document.documentElement.lang = currentLang;

            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.getAttribute('data-i18n');
                el.textContent = translations[currentLang][key] || translations.en[key] || key;
            });

            const thBtn = document.getElementById('lang-th');
            const enBtn = document.getElementById('lang-en');
            thBtn.classList.toggle('active', currentLang === 'th');
            enBtn.classList.toggle('active', currentLang === 'en');

            const logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                const logoutUrl = new URL(logoutBaseUrl, window.location.origin);
                logoutUrl.searchParams.set('lang', currentLang);
                logoutForm.setAttribute('action', `${logoutUrl.pathname}${logoutUrl.search}${logoutUrl.hash}`);
            }
        }

        function switchLang(lang) {
            currentLang = lang === 'en' ? 'en' : 'th';
            applyTranslations();

            const url = new URL(window.location.href);
            url.searchParams.set('lang', currentLang);
            history.replaceState(null, '', url.toString());
        }

        applyTranslations();
    </script>
</body>
</html>