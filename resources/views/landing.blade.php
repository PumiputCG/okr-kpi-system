<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OKR–KPI System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Noto Sans Thai', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            pink: '#e83e8c',
                            pinkHover: '#d2307c',
                            black: '#111111',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* ซ่อน Scrollbar เพื่อความสะอาดตา */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #e83e8c; }

        /* ลูกเล่นอนิเมชันลอยขึ้นลงสำหรับรูปภาพ (Gimmick) */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }
        /* หน่วงเวลาให้ลอยไม่พร้อมกัน */
        .delay-1 { animation-delay: 0s; }
        .delay-2 { animation-delay: 1s; }
        .delay-3 { animation-delay: 2s; }
        .delay-4 { animation-delay: 3s; }
    </style>
</head>
<body class="bg-pink-50 text-brand-black font-sans antialiased overflow-x-hidden">

    <header class="w-full bg-white/95 backdrop-blur-sm z-50 border-b border-gray-100 transition-all duration-300">
        <div class="w-full px-4 sm:px-6 lg:px-10 h-24 flex items-center">

            <div class="flex-1 min-w-0">
                <a id="header-logo-left" href="#" class="text-sm font-semibold tracking-wide text-brand-black whitespace-nowrap">
                    OKR - KPI System
                </a>
            </div>

            <nav class="hidden md:flex space-x-8 absolute left-1/2 transform -translate-x-1/2">
                <a id="nav-login-link" href="{{ route('welcome') }}?lang=en" class="text-base font-medium text-gray-600 hover:text-brand-pink transition-colors">Log in</a>
                <a id="nav-how-link" href="#how-it-works" class="text-base font-medium text-gray-600 hover:text-brand-pink transition-colors">How it works</a>
                <a id="nav-contact-link" href="#footer" class="text-base font-medium text-gray-600 hover:text-brand-pink transition-colors">Contact</a>
            </nav>

            <div class="flex-1 flex items-center justify-end pt-2">
                <a
                    href="http://192.168.7.12:8080/supavut_insight/index.html"
                    class="text-sm font-semibold px-2 py-1 rounded text-gray-500 hover:text-brand-black transition-colors whitespace-nowrap mr-2"
                >
                    SUPAVUT INSIGHT
                </a>
                <div class="inline-flex items-center gap-0.5">
                    <button id="lang-th" class="text-sm font-semibold px-1.5 py-1 rounded text-gray-400 hover:text-brand-pink transition-colors" onclick="switchLang('th')">TH</button>
                    <button id="lang-en" class="text-sm font-semibold px-1.5 py-1 rounded text-brand-pink transition-colors" onclick="switchLang('en')">EN</button>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section id="hero" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden flex flex-col justify-center min-h-screen bg-gradient-to-br from-pink-100 via-pink-50 to-pink-100">
            <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
                <h1 class="text-6xl lg:text-8xl font-bold tracking-tight mb-8" data-aos="zoom-in" data-aos-duration="1000" data-i18n="heroTitle">
                    OKR - KPI System
                </h1>
                <p class="text-lg lg:text-xl text-gray-500 max-w-2xl mx-auto mb-10" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200" data-i18n="heroDesc">
                    A streamlined platform to align Objectives and Key Results with performance KPIs. Drive clarity, focus, and measurable success across your entire organization.
                </p>
                <div class="flex flex-col items-center gap-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                    <a id="hero-login-link" href="{{ route('welcome') }}?lang=en" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium text-white bg-brand-pink hover:bg-brand-pinkHover rounded-md shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-0.5" data-i18n="heroBtn">
                        Log in to Platform
                    </a>
                    <a href="http://192.168.7.12:8080/GraphicOKR-KPI/index.html" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium text-brand-pink bg-white hover:bg-pink-50 border border-brand-pink/30 rounded-md shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-0.5" data-i18n="workflowBtn">
                        How it works
                    </a>
                </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-pink-200/40 via-pink-100/30 to-transparent pointer-events-none -z-10"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[900px] bg-brand-pink opacity-15 rounded-full blur-3xl pointer-events-none -z-10"></div>
        </section>

        <div class="w-full border-t border-gray-100"></div>

        <section id="how-it-works" class="py-28 bg-gradient-to-b from-white via-pink-50/40 to-white">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16" data-aos="fade-down">
                    <h2 class="text-4xl font-bold mb-4" data-i18n="howItWorksTitle">How it works</h2>
                    <p class="text-lg lg:text-xl text-gray-500 max-w-2xl mx-auto" data-i18n="howItWorksDesc">A focused, five-step flow to turn strategy into measurable execution.</p>
                </div>
            </div>

            <div class="space-y-0">
                    <article class="bg-white" data-aos="fade-up" data-aos-duration="800">
                        <div class="max-w-7xl mx-auto px-6 py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <div class="lg:col-span-5 flex items-start gap-4">
                                    <div class="w-12 h-12 shrink-0 rounded-xl bg-brand-pink/10 text-brand-pink font-black text-lg flex items-center justify-center">01</div>
                                    <div>
                                        <h3 class="text-2xl lg:text-3xl font-semibold mb-2 text-brand-black" data-i18n="step1Title">Set Goals and KPI</h3>
                                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed" data-i18n="step1Desc">
                                            Define clear goals and measurable KPIs aligned with the organization's vision and strategic direction.
                                        </p>
                                    </div>
                                </div>
                                <div class="lg:col-span-7">
                                    <video class="w-full aspect-video object-cover" autoplay muted loop playsinline controls preload="metadata">
                                        <source src="{{ asset('videos/okr-kpi-goal-kpi.mp4') }}" type="video/mp4">
                                        Your browser does not support HTML5 video.
                                    </video>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="bg-pink-100/70" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                        <div class="max-w-7xl mx-auto px-6 py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <div class="lg:col-span-5 lg:order-2 flex items-start gap-4">
                                    <div class="w-12 h-12 shrink-0 rounded-xl bg-brand-pink/15 text-brand-pink font-black text-lg flex items-center justify-center">02</div>
                                    <div>
                                        <h3 class="text-2xl lg:text-3xl font-semibold mb-2 text-brand-black" data-i18n="step2Title">Track Progress</h3>
                                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed" data-i18n="step2Desc">
                                            Regularly input data and check in on key results to maintain momentum and identify roadblocks early.
                                        </p>
                                    </div>
                                </div>
                                <div class="lg:col-span-7 lg:order-1 overflow-hidden">
                                    <video class="block w-full h-auto object-contain border-0 outline-none shadow-none bg-transparent" autoplay muted loop playsinline preload="metadata">
                                        <source src="{{ asset('videos/okr-kpi-track-progress.mp4') }}" type="video/mp4">
                                        Your browser does not support HTML5 video.
                                    </video>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="bg-white" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                        <div class="max-w-7xl mx-auto px-6 py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <div class="lg:col-span-5 flex items-start gap-4">
                                    <div class="w-12 h-12 shrink-0 rounded-xl bg-brand-pink/10 text-brand-pink font-black text-lg flex items-center justify-center">03</div>
                                    <div>
                                        <h3 class="text-2xl lg:text-3xl font-semibold mb-2 text-brand-black" data-i18n="step3Title">Track OKR Progress</h3>
                                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed" data-i18n="step3Desc">
                                            Monitor your department's OKR progress consistently and stay aligned with targets.
                                        </p>
                                    </div>
                                </div>
                                <div class="lg:col-span-7">
                                    <video class="w-full aspect-video object-cover" autoplay muted loop playsinline controls preload="metadata">
                                        <source src="{{ asset('videos/okr-kpi-track-okr.mp4') }}" type="video/mp4">
                                        Your browser does not support HTML5 video.
                                    </video>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="bg-pink-100/70" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                        <div class="max-w-7xl mx-auto px-6 py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <div class="lg:col-span-5 lg:order-2 flex items-start gap-4">
                                    <div class="w-12 h-12 shrink-0 rounded-xl bg-brand-pink/15 text-brand-pink font-black text-lg flex items-center justify-center">04</div>
                                    <div>
                                        <h3 class="text-2xl lg:text-3xl font-semibold mb-2 text-brand-black" data-i18n="step4Title">Company OKR Overview Tracking</h3>
                                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed" data-i18n="step4Desc">
                                            Consistently monitor company-wide OKR performance at the executive level.
                                        </p>
                                    </div>
                                </div>
                                <div class="lg:col-span-7 lg:order-1">
                                    <img
                                        src="{{ asset('images/okr-kpi-company-overview.jpg') }}"
                                        alt="Company OKR overview"
                                        class="w-full aspect-video object-contain"
                                        loading="lazy"
                                    >
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="bg-white" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                        <div class="max-w-7xl mx-auto px-6 py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <div class="lg:col-span-5 flex items-start gap-4">
                                    <div class="w-12 h-12 shrink-0 rounded-xl bg-brand-pink/10 text-brand-pink font-black text-lg flex items-center justify-center">05</div>
                                    <div>
                                        <h3 class="text-2xl lg:text-3xl font-semibold mb-2 text-brand-black" data-i18n="step5Title">OKR and KPI Documents</h3>
                                        <p class="text-base lg:text-lg text-gray-600 leading-relaxed" data-i18n="step5Desc">
                                            Receive annual OKR and KPI documents.
                                        </p>
                                    </div>
                                </div>
                                <div class="lg:col-span-7">
                                    <video class="w-full aspect-video object-cover" autoplay muted loop playsinline controls preload="metadata">
                                        <source src="{{ asset('videos/okr-kpi-documents.mp4') }}" type="video/mp4">
                                        Your browser does not support HTML5 video.
                                    </video>
                                </div>
                            </div>
                        </div>
                    </article>
            </div>
        </section>
    </main>

    <footer id="footer" class="bg-brand-black text-white py-10 border-t border-brand-black">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 items-center text-center md:text-left">
            
            <div>
                <span class="text-xl font-bold tracking-tight" data-i18n="logo">
                    OKR<span class="text-brand-pink">–</span>KPI System
                </span>
            </div>

            <div class="flex justify-center items-center group">
                <svg class="w-5 h-5 text-brand-pink mr-3 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <a href="mailto:hr.manager@supavut.com" class="text-base font-medium text-gray-300 hover:text-white transition-colors tracking-wide">
                    hr.manager@supavut.com
                </a>
            </div>

            <div class="text-sm text-gray-400 md:text-right" data-i18n="footerDev">
                Developed by <span class="text-white font-medium">Pumiput IT</span>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // เริ่มต้นการทำงาน AOS Animation
        AOS.init({
            once: false, // เปลี่ยนเป็น false เพื่อให้เห็นแอนิเมชันทุกครั้งที่เลื่อนขึ้นลง
            easing: 'ease-out-cubic',
        });

        // ฐานข้อมูลสำหรับระบบแปลภาษา
        const translations = {
            en: {
                logo: "OKR<span class='text-brand-pink'>–</span>KPI System",
                navLogin: "Log in",
                navHowItWorks: "How it works",
                navContact: "Contact",
                heroTitle: "OKR<span class='text-brand-pink'>–</span>KPI System",
                heroDesc: "A streamlined platform to align Objectives and Key Results with performance KPIs. Drive clarity, focus, and measurable success across your entire organization.",
                heroBtn: "Log in to Platform",
                workflowBtn: "View Workflow",
                howItWorksTitle: "How it works",
                howItWorksDesc: "A focused, five-step flow to turn strategy into measurable execution.",
                step1Title: "Set Goals and KPI",
                step1Desc: "Define clear goals and measurable KPIs aligned with the organization's vision and strategic direction.",
                step2Title: "Track Progress",
                step2Desc: "Regularly input data and check in on key results to maintain momentum and identify roadblocks early.",
                step3Title: "Track OKR Progress",
                step3Desc: "Monitor your department's OKR progress consistently and stay aligned with targets.",
                step4Title: "Company OKR Overview Tracking",
                step4Desc: "Consistently monitor company-wide OKR performance at the executive level.",
                step5Title: "OKR and KPI Documents",
                step5Desc: "Receive annual OKR and KPI documents.",
                footerDev: "Developed by <span class='text-white font-medium'>Pumiput IT</span>"
            },
            th: {
                logo: "OKR<span class='text-brand-pink'>–</span>KPI System",
                navLogin: "เข้าสู่ระบบ",
                navHowItWorks: "ขั้นตอนการทำงาน",
                navContact: "ติดต่อเรา",
                heroTitle: "OKR<span class='text-brand-pink'>–</span>KPI System",
                heroDesc: "แพลตฟอร์มที่ช่วยเชื่อมโยงวัตถุประสงค์ (OKR) และตัวชี้วัดประสิทธิภาพ (KPI) อย่างเป็นระบบ ขับเคลื่อนความชัดเจน มุ่งเน้นเป้าหมาย และวัดความสำเร็จได้ทั่วทั้งองค์กร",
                heroBtn: "เข้าสู่ระบบแพลตฟอร์ม",
                workflowBtn: "ดูภาพกระบวนการ",
                howItWorksTitle: "ขั้นตอนการทำงาน",
                howItWorksDesc: "กระบวนการ 5 ขั้นตอนที่ช่วยเปลี่ยนแผนกลยุทธ์ให้ติดตามผลได้จริง",
                step1Title: "กำหนดเป้าหมายและตัวชี้วัด (KPI)",
                step1Desc: "กำหนดเป้าหมายและตัวชี้วัดที่ชัดเจน สอดคล้องกับวิสัยทัศน์องค์กร และสามารถวัดผลความสำเร็จได้อย่างเป็นรูปธรรม",
                step2Title: "การติดตามความคืบหน้า KPIs",
                step2Desc: "ติดตามความคืบหน้า KPI ของผู้ใต้สังกัดได้อย่างสม่ำเสมอ",
                step3Title: "การติดตามความคืบหน้า OKR",
                step3Desc: "ติดตามความคืบหน้า OKR ของแผนกตนเองได้อย่างสม่ำเสมอ",
                step4Title: "การติดตามภาพรวม OKR ของบริษัท",
                step4Desc: "ติดตามภาพรวม OKR ของบริษัทได้อย่างสม่ำเสมอในระดับผู้บริหาร",
                step5Title: "เอกสาร OKR และ KPI",
                step5Desc: "ได้รับเอกสาร OKR และ KPI ประจำปี",
                footerDev: "พัฒนาโดย <span class='text-white font-medium'>Pumiput IT</span>"
            }
        };

        const welcomeBaseUrl = @json(route('welcome'));

        function updateLoginLinks(lang) {
            const loginUrl = `${welcomeBaseUrl}?lang=${encodeURIComponent(lang)}`;
            const navLoginLink = document.getElementById('nav-login-link');
            const heroLoginLink = document.getElementById('hero-login-link');

            if (navLoginLink) navLoginLink.href = loginUrl;
            if (heroLoginLink) heroLoginLink.href = loginUrl;
        }

        // ฟังก์ชันเปลี่ยนภาษา
        function switchLang(lang) {
            // อัปเดต UI ของปุ่มเปลี่ยนภาษา
            const thBtn = document.getElementById('lang-th');
            const enBtn = document.getElementById('lang-en');
            if (thBtn) thBtn.textContent = 'TH';
            if (enBtn) enBtn.textContent = 'EN';

            if (lang === 'th') {
                thBtn.classList.remove('text-gray-400');
                thBtn.classList.add('text-brand-pink');
                enBtn.classList.remove('text-brand-pink');
                enBtn.classList.add('text-gray-400');
            } else {
                enBtn.classList.remove('text-gray-400');
                enBtn.classList.add('text-brand-pink');
                thBtn.classList.remove('text-brand-pink');
                thBtn.classList.add('text-gray-400');
            }
            
            // แทนที่ข้อความทั้งหมดที่มี attribute data-i18n
            document.querySelectorAll('[data-i18n]').forEach(element => {
                const key = element.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    element.innerHTML = translations[lang][key];
                }
            });

            const navLabels = lang === 'th'
                ? { login: 'เข้าสู่ระบบ', how: 'ขั้นตอนการทำงาน', contact: 'ติดต่อ' }
                : { login: 'Log in', how: 'How it works', contact: 'Contact' };

            const navLoginLink = document.getElementById('nav-login-link');
            const navHowLink = document.getElementById('nav-how-link');
            const navContactLink = document.getElementById('nav-contact-link');

            if (navLoginLink) navLoginLink.textContent = navLabels.login;
            if (navHowLink) navHowLink.textContent = navLabels.how;
            if (navContactLink) navContactLink.textContent = navLabels.contact;

            updateLoginLinks(lang);

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lang', lang);
            history.replaceState(null, '', currentUrl.toString());
             
            // รีเฟรชแอนิเมชัน AOS เนื่องจากความยาวข้อความอาจเปลี่ยนไป
            setTimeout(() => { AOS.refresh(); }, 100);
        }

        const initialLang = new URLSearchParams(window.location.search).get('lang');
        switchLang(initialLang === 'th' ? 'th' : 'en');
    </script>
</body>
</html>
