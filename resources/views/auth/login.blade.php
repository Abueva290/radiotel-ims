<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · Radiotel IMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Plain CSS (prefixed "lg-") so this page looks the same without a Tailwind rebuild --}}
    <style>
        .lg-root {
            --navy-900: #0b1220;
            --navy-800: #111a2e;
            --navy-700: #1e293b;
            --emerald: #34d399;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            min-height: 100vh;
            display: flex;
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: #f1f5f9;
        }
        .lg-root *, .lg-root *::before, .lg-root *::after { box-sizing: border-box; }

        /* ---------- Brand side ---------- */
        .lg-brand {
            display: none;
            position: relative;
            z-index: 2;
            flex: 0 0 56%;
            overflow: hidden;
            color: #fff;
            padding: 3.5rem 4.5rem 2.5rem 4rem;
            flex-direction: column;
            justify-content: center;
            border-radius: 0 7rem 7rem 0;
            background:
                radial-gradient(900px 560px at 88% -8%, rgba(52, 211, 153, .24), transparent 60%),
                radial-gradient(760px 640px at -12% 108%, rgba(56, 189, 248, .20), transparent 60%),
                linear-gradient(155deg, #223049 0%, var(--navy-800) 52%, var(--navy-900) 100%);
            box-shadow: 40px 0 90px -30px rgba(11, 18, 32, .55);
        }
        /* soft dot texture */
        .lg-brand::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .10) 1px, transparent 1px);
            background-size: 26px 26px;
            -webkit-mask-image: radial-gradient(ellipse at 72% 22%, #000 0%, transparent 62%);
                    mask-image: radial-gradient(ellipse at 72% 22%, #000 0%, transparent 62%);
            pointer-events: none;
        }

        /* decorative shapes */
        .lg-shapes { position: absolute; inset: 0; pointer-events: none; overflow: hidden; border-radius: inherit; }
        .lg-shapes > * { position: absolute; }

        /* signal arcs radiating from the bottom-left corner (a nod to radio waves) */
        .lg-arcs { left: 0; bottom: 0; width: 38rem; height: 38rem; transform: translate(-24%, 24%); }
        .lg-arcs svg { width: 100%; height: 100%; overflow: visible; }

        /* big gradient disc, bleeding off the bottom-right */
        .lg-disc {
            right: -9rem; bottom: -9rem; width: 26rem; height: 26rem; border-radius: 50%;
            background: linear-gradient(145deg, rgba(52, 211, 153, .30), rgba(56, 189, 248, .10) 60%, transparent);
            border: 1px solid rgba(255, 255, 255, .10);
        }
        .lg-disc::after {
            content: ''; position: absolute; inset: 2.5rem; border-radius: 50%;
            border: 1px dashed rgba(255, 255, 255, .16);
        }

        /* the emblem: logo surrounded by turning rounded squares and a ring */
        .lg-orbit {
            top: 4.5rem; right: 5.5rem;
            width: clamp(10rem, 12vw, 15rem);
            aspect-ratio: 1;
            display: grid; place-items: center;
        }
        .lg-orbit .sq, .lg-orbit .ring { position: absolute; }
        .lg-orbit .sq { border-radius: 28%; border: 1px solid rgba(255, 255, 255, .13); }
        .lg-orbit .sq.a { inset: -22%; transform: rotate(14deg); }
        .lg-orbit .sq.b { inset: -46%; transform: rotate(-9deg); border-color: rgba(52, 211, 153, .20); }
        .lg-orbit .sq.c { inset: -72%; transform: rotate(22deg); border-color: rgba(255, 255, 255, .06); }
        .lg-orbit .ring { inset: -10%; border-radius: 50%; border: 1px dashed rgba(255, 255, 255, .22); }
        .lg-orbit-glow {
            position: absolute; inset: 0; border-radius: 50%;
            background: radial-gradient(circle, rgba(52, 211, 153, .45), transparent 68%);
            filter: blur(30px);
        }
        /* minimal "RT" letter-mark: R in white, T in a green-to-blue gradient, signal arcs in sky blue */
        .lg-mark {
            position: relative;
            width: 100%; height: auto; aspect-ratio: 1;
            overflow: visible;
            filter: drop-shadow(0 14px 34px rgba(52, 211, 153, .30));
        }
        .lg-mark path { fill: none; stroke-linecap: round; stroke-linejoin: round; }
        .lg-mark .r   { stroke: #fff; stroke-width: 15; }
        .lg-mark .t   { stroke: url(#lg-grad); stroke-width: 15; }
        .lg-mark .arc { stroke: #38bdf8; stroke-width: 7; }
        .lg-mark .arc.two { stroke: #6ee7b7; opacity: .85; }
        .lg-mark .draw { stroke-dasharray: 1; stroke-dashoffset: 1; animation: lg-draw .9s cubic-bezier(.6, .1, .2, 1) forwards; }
        .lg-mark .d2 { animation-delay: .25s; }
        .lg-mark .d3 { animation-delay: .5s; }
        .lg-mark .d4 { animation-delay: .75s; animation-duration: .5s; }
        @keyframes lg-draw { 0% { stroke-dashoffset: 1; opacity: 0; } 8% { opacity: 1; } 100% { stroke-dashoffset: 0; opacity: 1; } }

        /* floating accents */
        .lg-pill {
            left: 46%; top: 17%; width: 5.5rem; height: 1.7rem; border-radius: 999px;
            border: 1.5px solid rgba(52, 211, 153, .55);
            background: linear-gradient(90deg, rgba(52, 211, 153, .35), transparent);
            animation: lg-float 7s ease-in-out infinite;
        }
        .lg-dot {
            right: 8%; top: 40%; width: 1.1rem; height: 1.1rem; border-radius: 50%;
            background: #38bdf8; box-shadow: 0 0 22px rgba(56, 189, 248, .8);
            animation: lg-float 6s ease-in-out -2s infinite;
        }
        .lg-dot.two {
            left: 12%; top: 11%; width: .6rem; height: .6rem; background: var(--emerald); box-shadow: 0 0 16px rgba(52, 211, 153, .9);
            animation-duration: 8s; animation-delay: -4s;
        }
        .lg-plus { right: 16%; top: 54%; width: 1.5rem; height: 1.5rem; animation: lg-float 9s ease-in-out -1s infinite; }
        .lg-plus::before, .lg-plus::after { content: ''; position: absolute; background: rgba(255, 255, 255, .5); border-radius: 2px; }
        .lg-plus::before { left: 0; right: 0; top: calc(50% - 1px); height: 2px; }
        .lg-plus::after  { top: 0; bottom: 0; left: calc(50% - 1px); width: 2px; }
        .lg-tri {
            left: 6%; top: 46%; width: 0; height: 0;
            border-left: .8rem solid transparent; border-right: .8rem solid transparent; border-bottom: 1.4rem solid rgba(255, 255, 255, .13);
            transform: rotate(-14deg);
        }
        .lg-bar { right: 12%; bottom: 30%; width: 7rem; height: .55rem; border-radius: 999px; background: linear-gradient(90deg, rgba(255,255,255,.22), rgba(255,255,255,0)); transform: rotate(-24deg); }

        .lg-hero { position: relative; max-width: 33rem; margin-top: 4rem; }
        .lg-eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .4rem .9rem; border-radius: 999px;
            font-size: .78rem; font-weight: 500; letter-spacing: .02em;
            color: #a7f3d0;
            background: rgba(52, 211, 153, .10);
            border: 1px solid rgba(52, 211, 153, .25);
        }
        .lg-eyebrow i { width: .4rem; height: .4rem; border-radius: 50%; background: var(--emerald); box-shadow: 0 0 10px var(--emerald); }
        .lg-hero h1 {
            margin: 1.2rem 0 0;
            font-size: clamp(2.3rem, 3.1vw, 3.3rem);
            line-height: 1.07; font-weight: 700; letter-spacing: -.025em;
        }
        .lg-hero h1 span {
            white-space: nowrap;
            background: linear-gradient(90deg, #6ee7b7, #38bdf8);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lg-hero p { margin: 1.2rem 0 0; color: #cbd5e1; font-size: 1.05rem; line-height: 1.6; max-width: 28rem; }

        .lg-features { margin-top: 2.4rem; display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; max-width: 33rem; }
        .lg-feature {
            display: flex; align-items: center; gap: .8rem;
            padding: .85rem 1.05rem;
            border-radius: 20px;
            font-size: .92rem; font-weight: 500; line-height: 1.25;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
            -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
            transition: transform .2s ease, background .2s ease, border-color .2s ease;
        }
        /* alternate corner shapes for a less boxy rhythm */
        .lg-feature:nth-child(2) { border-radius: 20px 20px 20px 6px; }
        .lg-feature:nth-child(3) { border-radius: 20px 6px 20px 20px; }
        .lg-feature:hover { transform: translateY(-2px); background: rgba(255, 255, 255, .10); border-color: rgba(52, 211, 153, .35); }
        .lg-feature svg {
            flex: none; width: 2.2rem; height: 2.2rem; padding: .52rem;
            border-radius: 14px; color: var(--emerald);
            background: rgba(52, 211, 153, .13);
        }

        .lg-address { position: absolute; z-index: 1; left: 4rem; bottom: 2.25rem; margin: 0; font-size: .75rem; color: #94a3b8; }

        /* ---------- Form side ---------- */
        .lg-form-side {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            margin-left: -7rem;           /* tucks under the rounded edge of the brand panel */
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1.5rem;
            overflow: hidden;
            background:
                radial-gradient(640px 440px at 100% 0%, rgba(52, 211, 153, .14), transparent 60%),
                radial-gradient(560px 440px at 20% 100%, rgba(56, 189, 248, .14), transparent 60%),
                #f1f5f9;
        }
        /* soft shapes on the form side */
        .lg-side-shapes { position: absolute; inset: 0; pointer-events: none; }
        .lg-side-shapes > * { position: absolute; }
        .lg-side-shapes .c1 { top: -7rem; right: -6rem; width: 22rem; height: 22rem; border-radius: 50%; background: linear-gradient(145deg, rgba(52, 211, 153, .20), rgba(56, 189, 248, .06)); }
        .lg-side-shapes .c2 { bottom: -9rem; right: 12%; width: 20rem; height: 20rem; border-radius: 50%; border: 1px solid rgba(100, 116, 139, .18); }
        .lg-side-shapes .c2::after { content: ''; position: absolute; inset: 2rem; border-radius: 50%; border: 1px dashed rgba(100, 116, 139, .20); }
        .lg-side-shapes .sq { bottom: 14%; right: 4%; width: 6rem; height: 6rem; border-radius: 28%; transform: rotate(16deg); border: 1px solid rgba(52, 211, 153, .35); background: rgba(52, 211, 153, .06); }
        .lg-side-shapes .dots { top: 14%; left: 14%; width: 7rem; height: 7rem; background-image: radial-gradient(rgba(100, 116, 139, .35) 1.5px, transparent 1.5px); background-size: 16px 16px; }
        .lg-wrap { position: relative; width: 100%; max-width: 33rem; animation: lg-rise .6s cubic-bezier(.2, .7, .2, 1) both; }

        .lg-mobile-brand { display: none; text-align: center; margin-bottom: 1.5rem; }
        .lg-mobile-brand b { display: block; font-size: 1.1rem; margin-top: .6rem; }
        .lg-mobile-brand span { font-size: .8rem; color: var(--muted); }

        .lg-card {
            padding: 3rem 2.75rem 2.5rem;
            border-radius: 32px;
            background: rgba(255, 255, 255, .86);
            -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, .9);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 24px 60px -20px rgba(15, 23, 42, .25);
        }
        .lg-card h2 { margin: 0; font-size: 2.1rem; font-weight: 700; letter-spacing: -.02em; }
        .lg-sub { margin: .45rem 0 2rem; color: var(--muted); font-size: 1rem; }

        .lg-status {
            margin-bottom: 1.1rem; padding: .7rem 1rem; border-radius: 14px;
            font-size: .875rem; color: #047857;
            background: #ecfdf5; border: 1px solid #a7f3d0;
        }

        .lg-field { margin-bottom: 1.3rem; }
        .lg-field label { display: block; margin-bottom: .4rem; font-size: .85rem; font-weight: 600; color: #334155; }
        .lg-input-wrap { position: relative; }
        .lg-input-wrap > svg {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            width: 1.15rem; height: 1.15rem; color: #94a3b8; pointer-events: none;
            transition: color .2s ease;
        }
        .lg-input {
            width: 100%;
            height: 3.4rem;
            padding: 0 1rem 0 3rem;
            font: inherit; font-size: 1rem; color: var(--ink);
            border-radius: 18px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            outline: none;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .lg-input::placeholder { color: #94a3b8; }
        .lg-input:hover { border-color: #cbd5e1; }
        .lg-input:focus {
            background: #fff;
            border-color: var(--emerald);
            box-shadow: 0 0 0 4px rgba(52, 211, 153, .18);
        }
        .lg-input-wrap:focus-within > svg { color: #059669; }
        .lg-input.has-toggle { padding-right: 4.5rem; }
        .lg-toggle {
            position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
            padding: .4rem .7rem; border: 0; border-radius: 10px; cursor: pointer;
            font: inherit; font-size: .78rem; font-weight: 600; color: var(--muted); background: transparent;
            transition: background .2s ease, color .2s ease;
        }
        .lg-toggle:hover { background: #e2e8f0; color: var(--ink); }
        .lg-error { margin: .4rem 0 0 .25rem; font-size: .8rem; color: #dc2626; }

        .lg-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: .25rem 0 1.5rem; }
        .lg-check { display: inline-flex; align-items: center; gap: .55rem; font-size: .875rem; color: #475569; cursor: pointer; flex: none; }
        .lg-check input {
            appearance: none; -webkit-appearance: none; margin: 0;
            width: 1.15rem; height: 1.15rem; border-radius: 6px;
            border: 1.5px solid #cbd5e1; background: #fff; cursor: pointer;
            display: grid; place-items: center;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .lg-check input:checked {
            background: var(--navy-700) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2334d399' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center / 80% no-repeat;
            border-color: var(--navy-700);
        }
        .lg-check input:focus-visible { box-shadow: 0 0 0 4px rgba(52, 211, 153, .25); outline: none; }
        .lg-forgot { font-size: .78rem; line-height: 1.35; color: #94a3b8; text-align: right; }

        .lg-btn {
            position: relative; width: 100%; height: 3.5rem;
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            border: 0; border-radius: 18px; cursor: pointer;
            font: inherit; font-size: 1.05rem; font-weight: 600; color: #fff;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 10px 24px -8px rgba(15, 23, 42, .6), inset 0 1px 0 rgba(255, 255, 255, .12);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .lg-btn svg { width: 1.15rem; height: 1.15rem; color: var(--emerald); transition: transform .2s ease; }
        .lg-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 30px -10px rgba(15, 23, 42, .65), inset 0 1px 0 rgba(255, 255, 255, .14); }
        .lg-btn:hover svg { transform: translateX(3px); }
        .lg-btn:active { transform: translateY(0); }
        .lg-btn:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(52, 211, 153, .3), 0 10px 24px -8px rgba(15, 23, 42, .6); }

        .lg-foot { margin-top: 1.5rem; text-align: center; font-size: .75rem; color: #94a3b8; }

        @keyframes lg-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @keyframes lg-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            .lg-wrap { animation: none; }
            .lg-feature, .lg-btn, .lg-btn svg { transition: none; }
            .lg-pill, .lg-dot, .lg-plus { animation: none; }
            .lg-mark .draw { animation: none; stroke-dashoffset: 0; }
        }

        /* ---------- Breakpoints ---------- */
        @media (min-width: 1024px) {
            .lg-brand { display: flex; }
            .lg-form-side { padding: 2rem 2.5rem 2rem 9.5rem; }
        }
        @media (max-width: 1023px) {
            .lg-form-side { margin-left: 0; }
            .lg-mobile-brand { display: block; }
        }
        @media (max-width: 1365px) {
            .lg-orbit, .lg-bar { display: none; } /* keep the headline clear on smaller laptops */
            .lg-brand { padding-left: 3rem; padding-right: 3.5rem; border-radius: 0 5rem 5rem 0; }
            .lg-address { left: 3rem; }
        }
        @media (max-width: 1279px) {
            .lg-pill, .lg-dot, .lg-plus, .lg-tri { display: none; } /* fewer accents when space is tight */
            .lg-features { grid-template-columns: 1fr; }
        }
        @media (max-width: 420px) {
            .lg-card { padding: 1.75rem 1.25rem 1.5rem; border-radius: 24px; }
            .lg-row { flex-direction: column; align-items: flex-start; }
            .lg-forgot { text-align: left; }
        }
    </style>
</head>
<body class="font-sans antialiased">
<div class="lg-root">

    {{-- Left: brand panel --}}
    <div class="lg-brand">
        <div class="lg-shapes" aria-hidden="true">
            <div class="lg-arcs">
                <svg viewBox="0 0 400 400" fill="none" stroke-linecap="round">
                    <circle cx="0" cy="400" r="120" stroke="rgba(255,255,255,.16)" stroke-width="1.5"/>
                    <circle cx="0" cy="400" r="190" stroke="rgba(52,211,153,.45)" stroke-width="2.5"/>
                    <circle cx="0" cy="400" r="260" stroke="rgba(255,255,255,.12)" stroke-width="1.5"/>
                    <circle cx="0" cy="400" r="330" stroke="rgba(56,189,248,.28)" stroke-width="1.5" stroke-dasharray="3 9"/>
                </svg>
            </div>
            <div class="lg-disc"></div>
            <div class="lg-orbit">
                <div class="sq c"></div><div class="sq b"></div><div class="sq a"></div><div class="ring"></div>
                <div class="lg-orbit-glow"></div>
                <svg class="lg-mark" viewBox="34 6 160 160" role="img" aria-label="Radiotel RT">
                    <defs>
                        <linearGradient id="lg-grad" gradientUnits="userSpaceOnUse" x1="104" y1="40" x2="172" y2="152">
                            <stop offset="0" stop-color="#6ee7b7"/>
                            <stop offset="1" stop-color="#38bdf8"/>
                        </linearGradient>
                    </defs>
                    {{-- R: stem, bowl and leg --}}
                    <path class="r draw" pathLength="1" d="M58 152V56"/>
                    <path class="r draw d2" pathLength="1" d="M58 56h26a24 24 0 0 1 0 48H58"/>
                    <path class="r draw d3" pathLength="1" d="M82 104l28 48"/>
                    {{-- T: bar and stem, leaning into the R --}}
                    <path class="t draw d2" pathLength="1" d="M104 40h68"/>
                    <path class="t draw d3" pathLength="1" d="M138 40v112"/>
                    {{-- signal arcs from the end of the T bar --}}
                    <path class="arc draw d4" pathLength="1" d="M176 28a16 16 0 0 1 16 16"/>
                    <path class="arc two draw d4" pathLength="1" d="M176 12a32 32 0 0 1 32 32"/>
                </svg>
            </div>
            <div class="lg-pill"></div>
            <div class="lg-dot"></div>
            <div class="lg-dot two"></div>
            <div class="lg-plus"></div>
            <div class="lg-tri"></div>
            <div class="lg-bar"></div>
        </div>

        <div class="lg-hero">
            <span class="lg-eyebrow"><i></i> Radiotel Electronics Sales &amp; Services Co.</span>
            <h1>Inventory &amp; Sales<br><span>Management System</span></h1>
            <p>Sales, inventory, repairs, receivables, and payables in one place.</p>

            <div class="lg-features">
                <div class="lg-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4.5A1.5 1.5 0 0 1 6.5 3h11A1.5 1.5 0 0 1 19 4.5V21l-3-2-2 2-2-2-2 2-2-2-3 2Z"/><path d="M9 8h6M9 12h6"/></svg>
                    Sales &amp; Invoicing
                </div>
                <div class="lg-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>
                    Inventory &amp; Low-Stock Alerts
                </div>
                <div class="lg-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.1L3.5 17.2a1.8 1.8 0 0 0 2.6 2.6l5.8-5.8a4 4 0 0 0 5.1-5.4l-2.5 2.5-2.1-.5-.5-2.1 2.5-2.5Z"/></svg>
                    Repair &amp; Service
                </div>
                <div class="lg-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H18a1 1 0 0 1 1 1v2"/><path d="M3 7.5V17a2 2 0 0 0 2 2h13a1 1 0 0 0 1-1v-3"/><path d="M21 10.5h-4a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    Receivables &amp; Payables
                </div>
            </div>
        </div>

        <p class="lg-address">Door 2, Tionko Bldg., Elpidio Quirino Ave., Poblacion District, Davao City</p>
    </div>

    {{-- Right: login form --}}
    <div class="lg-form-side">
        <div class="lg-side-shapes" aria-hidden="true">
            <div class="c1"></div><div class="c2"></div><div class="sq"></div><div class="dots"></div>
        </div>

        <div class="lg-wrap">

            {{-- Brand for small screens --}}
            <div class="lg-mobile-brand">
                <x-app-logo class="lg-logo-mobile" style="width:3.75rem;height:3.75rem;border-radius:28%;box-shadow:0 14px 28px -10px rgba(15,23,42,.45)" />
                <b>Radiotel IMS</b>
                <span>Inventory &amp; Sales Management System</span>
            </div>

            <div class="lg-card">
                <h2>Welcome back</h2>
                <p class="lg-sub">Sign in with your company account.</p>

                @if (session('status'))
                    <div class="lg-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="lg-field">
                        <label for="email">Email</label>
                        <div class="lg-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3.5 7.5 8.5 6 8.5-6"/></svg>
                            <input id="email" class="lg-input" type="email" name="email" value="{{ old('email') }}"
                                   required autofocus autocomplete="username" placeholder="name@radiotel.ph">
                        </div>
                        @error('email') <p class="lg-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="lg-field" x-data="{ show: false }">
                        <label for="password">Password</label>
                        <div class="lg-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="3"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                            <input id="password" class="lg-input has-toggle" :type="show ? 'text' : 'password'" type="password" name="password"
                                   required autocomplete="current-password" placeholder="Enter your password">
                            <button type="button" class="lg-toggle" @click="show = !show" x-text="show ? 'Hide' : 'Show'">Show</button>
                        </div>
                        @error('password') <p class="lg-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="lg-row">
                        <label for="remember_me" class="lg-check">
                            <input id="remember_me" type="checkbox" name="remember">
                            Remember me
                        </label>
                        <span class="lg-forgot">Forgot password? Contact the Operational Manager.</span>
                    </div>

                    <button type="submit" class="lg-btn">
                        Sign In
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>
            </div>

            <p class="lg-foot">Radiotel Electronics Sales &amp; Services Co. · IMS v1.0</p>
        </div>
    </div>
</div>
</body>
</html>