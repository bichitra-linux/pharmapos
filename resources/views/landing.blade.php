<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->meta_title ?? 'PharmaPOS — Pharmacy POS for Nepal' }}</title>
    <meta name="description" content="{{ $page->meta_description ?? 'Cloud-based pharmacy Point-of-Sale and inventory management system built for the Nepali pharmaceutical retail market.' }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.875rem;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            color: oklch(0.15 0.015 260);
            background: oklch(1 0 0);
        }

        h1, h2, h3, h4 { font-weight: 700; line-height: 1.2; }
        h1 { font-size: 2.5rem; }
        h2 { font-size: 1.75rem; }
        h3 { font-size: 1.125rem; }

        .container { width: 100%; max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; border-radius: 0.375rem;
            font-weight: 500; font-size: 0.875rem; line-height: 1;
            text-decoration: none; cursor: pointer;
            transition: all 150ms ease-out;
        }
        .btn:focus-visible { outline: 2px solid oklch(0.53 0.18 250); outline-offset: 2px; }
        .btn-primary {
            background: oklch(0.45 0.16 250); color: white;
        }
        .btn-primary:hover { background: oklch(0.38 0.14 250); }
        .btn-secondary {
            background: transparent; color: oklch(0.15 0.015 260);
            border: 1px solid oklch(0.88 0.005 260);
        }
        .btn-secondary:hover { background: oklch(0.98 0.003 260); }

        @media (prefers-color-scheme: dark) {
            body { background: oklch(0.12 0.015 260); color: oklch(0.95 0.005 260); }
            .btn-primary { background: oklch(0.58 0.14 250); }
            .btn-primary:hover { background: oklch(0.50 0.14 250); }
            .btn-secondary { border-color: oklch(0.30 0.015 260); color: oklch(0.90 0.005 260); }
            .btn-secondary:hover { background: oklch(0.20 0.015 260); }
        }

        .section { padding: 4rem 0; }

        .reveal { opacity: 0; transform: translateY(1rem); transition: opacity 400ms ease-out, transform 400ms ease-out; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        .nav { position: fixed; top: 0; left: 0; right: 0; z-index: 50; padding: 0.75rem 0; background: transparent; transition: all 200ms ease-out; }
        .nav.scrolled { background: oklch(1 0 0 / 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid oklch(0.88 0.005 260); }
        @media (prefers-color-scheme: dark) {
            .nav.scrolled { background: oklch(0.12 0.015 260 / 0.85); border-color: oklch(0.30 0.015 260); }
        }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; }
        .nav-links { display: flex; align-items: center; gap: 1.5rem; }
        .nav-links a { color: oklch(0.50 0.015 260); text-decoration: none; font-weight: 500; transition: color 150ms ease-out; }
        .nav-links a:hover { color: oklch(0.15 0.015 260); }
        @media (prefers-color-scheme: dark) {
            .nav-links a { color: oklch(0.65 0.015 260); }
            .nav-links a:hover { color: oklch(0.95 0.005 260); }
        }

        .logo-text { font-size: 1.25rem; font-weight: 700; color: oklch(0.15 0.015 260); text-decoration: none; }
        @media (prefers-color-scheme: dark) { .logo-text { color: oklch(0.95 0.005 260); } }

        .hero { padding: 7rem 0 4rem; min-height: 85vh; display: flex; align-items: center; }
        .hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; }
        .hero-eyebrow {
            display: inline-block; padding: 0.25rem 0.75rem;
            background: oklch(0.93 0.04 250); color: oklch(0.45 0.16 250);
            border-radius: 999px; font-size: 0.75rem; font-weight: 600;
            margin-bottom: 1rem; animation: pulse-badge 3s ease-in-out infinite;
        }
        @keyframes pulse-badge { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        .hero-heading { font-size: 3rem; font-weight: 800; line-height: 1.1; margin-bottom: 1rem; }
        .hero-sub { color: oklch(0.50 0.015 260); font-size: 1.0625rem; margin-bottom: 2rem; max-width: 32rem; }
        .hero-ctas { display: flex; gap: 0.75rem; margin-bottom: 2.5rem; }
        .hero-bullets { display: flex; gap: 1rem; flex-wrap: wrap; }
        .hero-bullet { display: flex; align-items: center; gap: 0.5rem; color: oklch(0.50 0.015 260); font-size: 0.8125rem; }
        .hero-bullet svg { width: 1rem; height: 1rem; color: oklch(0.52 0.18 145); flex-shrink: 0; }

        .hero-visual {
            background: oklch(0.25 0.05 260); border-radius: 0.75rem;
            padding: 2rem; aspect-ratio: 4/3;
            display: flex; flex-direction: column; justify-content: space-between;
            position: relative; overflow: hidden;
        }
        .hero-receipt {
            background: oklch(1 0 0); border-radius: 0.375rem; padding: 1rem;
            font-family: 'Courier New', monospace; font-size: 0.75rem; line-height: 1.6;
        }
        .receipt-line { opacity: 0; transform: translateY(4px); }
        .receipt-line:nth-child(1) { animation: print-line 0.3s ease-out 0.1s forwards; }
        .receipt-line:nth-child(2) { animation: print-line 0.3s ease-out 0.4s forwards; }
        .receipt-line:nth-child(3) { animation: print-line 0.3s ease-out 0.7s forwards; }
        .receipt-line:nth-child(4) { animation: print-line 0.3s ease-out 1.0s forwards; }
        @keyframes print-line { to { opacity: 1; transform: translateY(0); } }
        .receipt-line.total { border-top: 1px dashed oklch(0.88 0.005 260); padding-top: 0.5rem; margin-top: 0.25rem; font-weight: 700; }

        .receipt-head { position: absolute; width: 2px; height: 10px; background: oklch(0.45 0.16 250); border-radius: 1px; top: 2rem; right: 2rem; animation: print-head 2s ease-in-out infinite; }
        @keyframes print-head { 0%, 100% { top: 2rem; } 50% { top: 6rem; } }

        .hero-stock {
            display: flex; align-items: center; gap: 0.75rem; padding: 1rem 0 0;
            border-top: 1px solid oklch(0.88 0.005 260 / 0.2); margin-top: 1rem;
            color: oklch(0.70 0.01 260); font-size: 0.8125rem;
        }
        .stock-bar {
            flex: 1; height: 6px; background: oklch(0.30 0.05 260); border-radius: 3px; overflow: hidden;
        }
        .stock-fill {
            height: 100%; width: 0%; background: oklch(0.52 0.18 145); border-radius: 3px;
            animation: stock-fill 2s ease-out 1.5s forwards;
        }
        @keyframes stock-fill { to { width: 73%; } }

        .trusted-by { text-align: center; }
        .trusted-by h2 { font-size: 1rem; font-weight: 500; color: oklch(0.50 0.015 260); margin-bottom: 2rem; }
        .logo-strip { display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; }
        .logo-item { opacity: 0; transform: translateY(0.5rem); transition: all 400ms ease-out; }
        .logo-item.visible { opacity: 0.7; transform: translateY(0); }
        .logo-item:hover { opacity: 1; }
        .logo-placeholder {
            width: 8rem; height: 2.5rem; background: oklch(0.88 0.005 260);
            border-radius: 0.375rem; display: flex; align-items: center; justify-content: center;
            color: oklch(0.50 0.015 260); font-size: 0.75rem; font-weight: 500;
        }
        @media (prefers-color-scheme: dark) { .logo-placeholder { background: oklch(0.25 0.015 260); } }

        .features-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem;
        }
        .feature-card { background: oklch(0.98 0.003 260); border: 1px solid oklch(0.88 0.005 260); border-radius: 0.75rem; padding: 2rem; }
        .feature-card:nth-child(1) { grid-row: span 2; }
        @media (prefers-color-scheme: dark) { .feature-card { background: oklch(0.18 0.015 260); border-color: oklch(0.30 0.015 260); } }
        .feature-icon { width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: oklch(0.93 0.04 250); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        @media (prefers-color-scheme: dark) { .feature-icon { background: oklch(0.25 0.05 260); } }
        .feature-card h3 { margin-bottom: 0.5rem; }
        .feature-card p { color: oklch(0.50 0.015 260); }

        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; position: relative; }
        .steps::before { content: ''; position: absolute; top: 2rem; left: 15%; right: 15%; height: 2px; background: oklch(0.88 0.005 260); }
        @media (prefers-color-scheme: dark) { .steps::before { background: oklch(0.30 0.015 260); } }
        .step { text-align: center; position: relative; }
        .step-num { width: 4rem; height: 4rem; border-radius: 50%; background: oklch(0.45 0.16 250); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; margin: 0 auto 1rem; position: relative; z-index: 1; }
        @media (prefers-color-scheme: dark) { .step-num { background: oklch(0.58 0.14 250); } }
        .step h3 { margin-bottom: 0.5rem; }
        .step p { color: oklch(0.50 0.015 260); }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; text-align: center; }
        .stat-value { font-size: 2.5rem; font-weight: 800; color: oklch(0.45 0.16 250); }
        @media (prefers-color-scheme: dark) { .stat-value { color: oklch(0.65 0.15 250); } }
        .stat-label { color: oklch(0.50 0.015 260); font-size: 0.9375rem; margin-top: 0.25rem; }

        .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .pricing-card {
            border: 1px solid oklch(0.88 0.005 260); border-radius: 0.75rem; padding: 2rem;
            background: oklch(0.98 0.003 260);
        }
        .pricing-card.highlighted { border-color: oklch(0.53 0.18 250); background: white; box-shadow: 0 0 0 1px oklch(0.53 0.18 250); }
        .pricing-card.highlighted .btn-primary { background: oklch(0.53 0.18 250); }
        @media (prefers-color-scheme: dark) { .pricing-card { background: oklch(0.18 0.015 260); border-color: oklch(0.30 0.015 260); } .pricing-card.highlighted { border-color: oklch(0.65 0.15 250); background: oklch(0.20 0.02 260); } }
        .pricing-name { font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem; }
        .pricing-price { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.25rem; }
        .pricing-price span { font-size: 1rem; font-weight: 400; color: oklch(0.50 0.015 260); }
        .pricing-desc { color: oklch(0.50 0.015 260); margin-bottom: 1.5rem; }
        .pricing-features { list-style: none; margin-bottom: 2rem; }
        .pricing-features li { padding: 0.5rem 0; border-bottom: 1px solid oklch(0.88 0.005 260); display: flex; align-items: center; gap: 0.5rem; }
        .pricing-features li:last-child { border-bottom: none; }
        @media (prefers-color-scheme: dark) { .pricing-features li { border-color: oklch(0.30 0.015 260); } }
        .pricing-features li::before { content: '✓'; color: oklch(0.52 0.18 145); font-weight: 700; }

        .testimonial-carousel { position: relative; min-height: 10rem; }
        .testimonial-card {
            position: absolute; inset: 0; opacity: 0;
            background: oklch(0.98 0.003 260); border: 1px solid oklch(0.88 0.005 260);
            border-radius: 0.75rem; padding: 2rem; transition: opacity 500ms ease-out;
        }
        .testimonial-card.active { opacity: 1; position: relative; }
        @media (prefers-color-scheme: dark) { .testimonial-card { background: oklch(0.18 0.015 260); border-color: oklch(0.30 0.015 260); } }
        .testimonial-quote { font-size: 1.0625rem; line-height: 1.7; color: oklch(0.15 0.015 260); margin-bottom: 1rem; font-style: italic; }
        @media (prefers-color-scheme: dark) { .testimonial-quote { color: oklch(0.90 0.005 260); } }
        .testimonial-author { font-weight: 600; }
        .testimonial-title { color: oklch(0.50 0.015 260); }
        .testimonial-dots { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; }
        .testimonial-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: oklch(0.88 0.005 260); border: none; cursor: pointer; transition: background 150ms ease-out; padding: 0; }
        .testimonial-dot.active { background: oklch(0.53 0.18 250); }
        @media (prefers-color-scheme: dark) { .testimonial-dot { background: oklch(0.40 0.015 260); } .testimonial-dot.active { background: oklch(0.65 0.15 250); } }

        .faq-list { max-width: 48rem; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid oklch(0.88 0.005 260); }
        @media (prefers-color-scheme: dark) { .faq-item { border-color: oklch(0.30 0.015 260); } }
        .faq-question {
            width: 100%; display: flex; justify-content: space-between; align-items: center;
            padding: 1.25rem 0; background: none; border: none;
            font-size: 1rem; font-weight: 500; cursor: pointer; text-align: left;
            color: oklch(0.15 0.015 260); transition: color 150ms ease-out;
        }
        @media (prefers-color-scheme: dark) { .faq-question { color: oklch(0.95 0.005 260); } }
        .faq-question:hover { color: oklch(0.53 0.18 250); }
        .faq-arrow { transition: transform 200ms ease-out; font-size: 1.25rem; }
        .faq-item.open .faq-arrow { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 300ms ease-out, padding 300ms ease-out; }
        .faq-item.open .faq-answer { max-height: 10rem; padding-bottom: 1.25rem; }
        .faq-answer p { color: oklch(0.50 0.015 260); line-height: 1.7; }

        .cta-section { background: oklch(0.45 0.16 250); color: white; text-align: center; padding: 5rem 0; }
        @media (prefers-color-scheme: dark) { .cta-section { background: oklch(0.58 0.14 250); } }
        .cta-section h2 { color: white; margin-bottom: 0.75rem; }
        .cta-section p { color: oklch(0.80 0.04 250); margin-bottom: 2rem; font-size: 1.0625rem; }
        .cta-section .btn { background: white; color: oklch(0.45 0.16 250); border: none; }
        .cta-section .btn:hover { background: oklch(0.95 0.005 260); }
        .cta-arrow { transition: transform 150ms ease-out; }
        .cta-section .btn:hover .cta-arrow { transform: translateX(4px); }

        .footer { padding: 3rem 0 1.5rem; border-top: 1px solid oklch(0.88 0.005 260); }
        @media (prefers-color-scheme: dark) { .footer { border-color: oklch(0.30 0.015 260); } }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .footer-desc { color: oklch(0.50 0.015 260); max-width: 24rem; margin-top: 0.75rem; }
        .footer-links { display: flex; gap: 1.5rem; justify-content: flex-end; }
        .footer-links a { color: oklch(0.50 0.015 260); text-decoration: none; font-size: 0.875rem; transition: color 150ms ease-out; }
        .footer-links a:hover { color: oklch(0.15 0.015 260); }
        @media (prefers-color-scheme: dark) { .footer-links a:hover { color: oklch(0.95 0.005 260); } }
        .footer-bottom { border-top: 1px solid oklch(0.88 0.005 260); padding-top: 1.5rem; text-align: center; color: oklch(0.50 0.015 260); font-size: 0.75rem; }
        @media (prefers-color-scheme: dark) { .footer-bottom { border-color: oklch(0.30 0.015 260); } }

        @media (max-width: 768px) {
            h1 { font-size: 1.75rem; }
            h2 { font-size: 1.375rem; }
            .hero-grid { grid-template-columns: 1fr; }
            .hero-visual { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .features-grid .feature-card:nth-child(1) { grid-row: auto; }
            .steps { grid-template-columns: 1fr; gap: 1.5rem; }
            .steps::before { display: none; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .stat-value { font-size: 1.75rem; }
            .pricing-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-links { justify-content: flex-start; }
            .nav-links { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    @php $sections = $page->content['sections'] ?? []; @endphp

    @foreach ($sections as $section)
        @php $d = $section['data'] ?? []; @endphp

        @if ($section['type'] === 'nav')
        <nav class="nav" id="site-nav" role="banner">
            <div class="container nav-inner">
                <a href="/" class="logo-text">{{ $d['logo_text'] ?? 'PharmaPOS' }}</a>
                <div class="nav-links">
                    <a href="#features">{{ __('Features') }}</a>
                    <a href="#how-it-works">{{ __('How It Works') }}</a>
                    <a href="#pricing">{{ __('Pricing') }}</a>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <a href="{{ $d['signin_url'] ?? '/login' }}" style="color:oklch(0.50 0.015 260);text-decoration:none;font-weight:500;font-size:0.875rem">{{ $d['signin_text'] ?? 'Sign In' }}</a>
                    <a href="{{ $d['cta_url'] ?? '/register' }}" class="btn btn-primary" style="padding:0.5rem 1rem">{{ $d['cta_text'] ?? 'Start Free Trial' }}</a>
                </div>
            </div>
        </nav>

        @elseif ($section['type'] === 'hero')
        <section class="hero">
            <div class="container">
                <div class="hero-grid">
                    <div>
                        @if (!empty($d['eyebrow']))
                        <span class="hero-eyebrow">{{ $d['eyebrow'] }}</span>
                        @endif
                        <h1 class="hero-heading">{{ $d['heading'] }}</h1>
                        <p class="hero-sub">{{ $d['subheading'] }}</p>
                        <div class="hero-ctas">
                            <a href="{{ $d['primary_cta_url'] ?? '/register' }}" class="btn btn-primary">{{ $d['primary_cta_text'] ?? 'Start Free Trial' }}</a>
                            @if (!empty($d['secondary_cta_text']))
                            <a href="{{ $d['secondary_cta_url'] ?? '#how-it-works' }}" class="btn btn-secondary">{{ $d['secondary_cta_text'] }}</a>
                            @endif
                        </div>
                        @if (!empty($d['trust_bullets']))
                        <div class="hero-bullets">
                            @foreach ($d['trust_bullets'] as $bullet)
                            <span class="hero-bullet">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $bullet }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="hero-visual">
                        <div class="hero-receipt">
                            <div class="receipt-line">PharmaPOS</div>
                            <div class="receipt-line">#INV-2024-001                    NPR</div>
                            <div class="receipt-line">  Amoxicillin 500mg x 10        250</div>
                            <div class="receipt-line">  Cetirizine 10mg x 5            75</div>
                            <div class="receipt-line total">TOTAL                     325</div>
                            <div class="receipt-head"></div>
                        </div>
                        <div class="hero-stock">
                            <span style="flex-shrink:0">Stock level</span>
                            <div class="stock-bar"><div class="stock-fill"></div></div>
                            <span style="flex-shrink:0">73%</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'trusted_by')
        <section class="section trusted-by" id="trusted-by">
            <div class="container">
                <h2>{{ $d['heading'] }}</h2>
                <div class="logo-strip">
                    @for ($i = 0; $i < 5; $i++)
                    <div class="logo-item" data-stagger="{{ $i }}">
                        <div class="logo-placeholder">Pharmacy {{ chr(65 + $i) }}</div>
                    </div>
                    @endfor
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'features')
        <section class="section" id="features">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:3rem">
                    <h2>{{ $d['heading'] }}</h2>
                    @if (!empty($d['subheading']))
                    <p style="color:oklch(0.50 0.015 260);margin-top:0.5rem">{{ $d['subheading'] }}</p>
                    @endif
                </div>
                <div class="features-grid">
                    @foreach ($d['items'] ?? [] as $i => $item)
                    <div class="feature-card reveal" data-delay="{{ $i * 60 }}">
                        <div class="feature-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:oklch(0.45 0.16 250)">
                                @if ($item['icon'] === 'package')
                                <path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                                @elseif ($item['icon'] === 'file-text')
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                                @elseif ($item['icon'] === 'shield')
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                @elseif ($item['icon'] === 'credit-card')
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                                @elseif ($item['icon'] === 'bar-chart')
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                                @else
                                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                                @endif
                            </svg>
                        </div>
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'how_it_works')
        <section class="section" id="how-it-works">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:3rem">
                    <h2>{{ $d['heading'] }}</h2>
                </div>
                <div class="steps">
                    @foreach ($d['steps'] ?? [] as $i => $step)
                    <div class="step reveal" data-delay="{{ $i * 100 }}">
                        <div class="step-num">{{ $i + 1 }}</div>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'stats')
        <section class="section" id="stats">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:3rem">
                    <h2>{{ $d['heading'] }}</h2>
                </div>
                <div class="stats-grid" id="stats-grid">
                    @foreach ($d['items'] ?? [] as $i => $item)
                    <div class="reveal" data-delay="{{ $i * 80 }}">
                        <div class="stat-value">
                            <span class="counter" data-target="{{ $item['key'] }}">0</span>{{ $item['suffix'] ?? '' }}
                        </div>
                        <div class="stat-label">{{ $item['label'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'pricing')
        <section class="section" id="pricing">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:3rem">
                    <h2>{{ $d['heading'] }}</h2>
                    @if (!empty($d['subheading']))
                    <p style="color:oklch(0.50 0.015 260);margin-top:0.5rem">{{ $d['subheading'] }}</p>
                    @endif
                </div>
                <div class="pricing-grid" id="pricing-grid">
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'testimonials')
        <section class="section" id="testimonials">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:3rem">
                    <h2>{{ $d['heading'] }}</h2>
                </div>
                <div class="testimonial-carousel" id="testimonial-carousel">
                    @foreach ($d['items'] ?? [] as $i => $item)
                    <div class="testimonial-card {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}">
                        <p class="testimonial-quote">"{{ $item['quote'] }}"</p>
                        <p><span class="testimonial-author">{{ $item['author'] }}</span>, <span class="testimonial-title">{{ $item['title'] }}</span></p>
                    </div>
                    @endforeach
                </div>
                @if (count($d['items'] ?? []) > 1)
                <div class="testimonial-dots" id="testimonial-dots">
                    @foreach ($d['items'] as $i => $item)
                    <button class="testimonial-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}" aria-label="Testimonial {{ $i + 1 }}"></button>
                    @endforeach
                </div>
                @endif
            </div>
        </section>

        @elseif ($section['type'] === 'faq')
        <section class="section" id="faq">
            <div class="container">
                <div class="reveal" style="text-align:center;margin-bottom:2rem">
                    <h2>{{ $d['heading'] }}</h2>
                </div>
                <div class="faq-list">
                    @foreach ($d['items'] ?? [] as $i => $item)
                    <div class="faq-item reveal" data-delay="{{ $i * 40 }}">
                        <button class="faq-question" aria-expanded="false">
                            {{ $item['question'] }}
                            <span class="faq-arrow">▼</span>
                        </button>
                        <div class="faq-answer"><p>{{ $item['answer'] }}</p></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        @elseif ($section['type'] === 'cta')
        <section class="cta-section">
            <div class="container">
                <h2>{{ $d['heading'] }}</h2>
                <p>{{ $d['subheading'] }}</p>
                <a href="{{ $d['button_url'] ?? '/register' }}" class="btn" style="background:white;color:oklch(0.45 0.16 250)">
                    {{ $d['button_text'] ?? 'Get Started' }}
                    <span class="cta-arrow">→</span>
                </a>
            </div>
        </section>

        @elseif ($section['type'] === 'footer')
        <footer class="footer">
            <div class="container">
                <div class="footer-grid">
                    <div>
                        <span class="logo-text">{{ $d['logo_text'] ?? 'PharmaPOS' }}</span>
                        <p class="footer-desc">{{ $d['description'] }}</p>
                    </div>
                    <div class="footer-links">
                        @foreach ($d['links'] ?? [] as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="footer-bottom">
                    &copy; {{ date('Y') }} PharmaPOS. All rights reserved. &mdash; Made for Nepal's pharmacies.
                </div>
            </div>
        </footer>
        @endif

    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Sticky nav
            var nav = document.getElementById('site-nav');
            if (nav) {
                window.addEventListener('scroll', function () {
                    nav.classList.toggle('scrolled', window.scrollY > 80);
                }, { passive: true });
            }

            // Scroll reveal
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var delay = parseInt(entry.target.getAttribute('data-delay')) || 0;
                        setTimeout(function () { entry.target.classList.add('visible'); }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });

            // Logo strip stagger
            document.querySelectorAll('.logo-item').forEach(function (el) {
                var i = parseInt(el.getAttribute('data-stagger')) || 0;
                observer.observe(el);
                el.setAttribute('data-delay', i * 80);
            });

            // FAQ accordion
            document.querySelectorAll('.faq-question').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item = btn.closest('.faq-item');
                    var isOpen = item.classList.contains('open');
                    document.querySelectorAll('.faq-item.open').forEach(function (o) { o.classList.remove('open'); o.querySelector('.faq-question').setAttribute('aria-expanded', 'false'); });
                    if (!isOpen) { item.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); }
                });
            });

            // Testimonial carousel
            var carousel = document.getElementById('testimonial-carousel');
            var dots = document.getElementById('testimonial-dots');
            if (carousel && dots) {
                var cards = carousel.querySelectorAll('.testimonial-card');
                var current = 0;
                function showTestimonial(index) {
                    cards.forEach(function (c) { c.classList.remove('active'); });
                    dots.querySelectorAll('.testimonial-dot').forEach(function (d) { d.classList.remove('active'); });
                    cards[index].classList.add('active');
                    dots.querySelector('.testimonial-dot[data-index="' + index + '"]').classList.add('active');
                    current = index;
                }
                dots.addEventListener('click', function (e) {
                    if (e.target.classList.contains('testimonial-dot')) {
                        showTestimonial(parseInt(e.target.getAttribute('data-index')));
                    }
                });
                if (cards.length > 1) {
                    setInterval(function () { showTestimonial((current + 1) % cards.length); }, 6000);
                }
            }

            // Counter animation
            var counters = document.querySelectorAll('.counter');
            if (counters.length > 0) {
                var counterObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var el = entry.target;
                            var key = el.getAttribute('data-target');
                            fetch('/api/public/stats')
                                .then(function (r) { return r.json(); })
                                .then(function (res) {
                                    var target = res.data && res.data[key] ? parseInt(res.data[key]) : 0;
                                    var start = 0;
                                    var duration = 1500;
                                    var startTime = null;
                                    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
                                    function animate(timestamp) {
                                        if (!startTime) startTime = timestamp;
                                        var elapsed = timestamp - startTime;
                                        var progress = Math.min(elapsed / duration, 1);
                                        var eased = easeOutCubic(progress);
                                        el.textContent = Math.floor(start + (target - start) * eased);
                                        if (progress < 1) { requestAnimationFrame(animate); }
                                    }
                                    requestAnimationFrame(animate);
                                })['catch'](function () { el.textContent = '—'; });
                            counterObserver.unobserve(el);
                        }
                    });
                }, { threshold: 0.5 });
                counters.forEach(function (el) { counterObserver.observe(el); });
            }

            // Pricing cards (load from API)
            var pricingGrid = document.getElementById('pricing-grid');
            if (pricingGrid) {
                fetch('/api/public/plans')
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        var plans = res.data || [];
                        if (plans.length === 0) {
                            plans = [
                                { name: 'Starter', price_monthly: 1500, price_yearly: 15000, max_outlets: 1, max_users: 2, max_medicines: 2000 },
                                { name: 'Professional', price_monthly: 3500, price_yearly: 35000, max_outlets: 2, max_users: 5, max_medicines: 10000 },
                                { name: 'Enterprise', price_monthly: 7000, price_yearly: 70000, max_outlets: 5, max_users: 15, max_medicines: 999999 },
                            ];
                        }
                        pricingGrid.innerHTML = plans.map(function (plan, i) {
                            var highlighted = i === 1 ? ' highlighted' : '';
                            var maxMeds = plan.max_medicines >= 999999 ? 'Unlimited' : plan.max_medicines.toLocaleString();
                            return '<div class="pricing-card' + highlighted + '">' +
                                '<div class="pricing-name">' + plan.name + '</div>' +
                                '<div class="pricing-price">रू ' + Number(plan.price_monthly).toLocaleString() + '<span>/month</span></div>' +
                                '<div class="pricing-desc">रू ' + Number(plan.price_yearly).toLocaleString() + '/year</div>' +
                                '<ul class="pricing-features">' +
                                '<li>' + plan.max_outlets + ' outlet' + (plan.max_outlets > 1 ? 's' : '') + '</li>' +
                                '<li>Up to ' + plan.max_users + ' users</li>' +
                                '<li>Up to ' + maxMeds + ' medicines</li>' +
                                '<li>Batch &amp; expiry tracking</li>' +
                                '<li>VAT compliance</li>' +
                                '</ul>' +
                                '<a href="/register" class="btn btn-primary" style="width:100%;justify-content:center">Get Started</a>' +
                                '</div>';
                        }).join('');
                    })['catch'](function () { pricingGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:oklch(0.50 0.015 260)">Unable to load pricing. Please refresh.</p>'; });
            }
        });
    </script>
</body>
</html>
