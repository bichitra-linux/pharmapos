<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->meta_title ?? 'PharmaPOS: Pharmacy POS for Nepal' }}</title>
    <meta name="description" content="{{ $page->meta_description ?? 'Cloud-based pharmacy Point-of-Sale and inventory management system built for Nepali pharmacies.' }}">
    <link rel="icon" type="image/png" href="{{ asset('pharmapos-logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    <style>
        :root {
            --brand: oklch(0.55 0.13 50);
            --brand-deep: oklch(0.28 0.06 260);
            --brand-green: oklch(0.46 0.17 145);
            --brand-soft: oklch(0.93 0.04 50);
            --surface: oklch(0.99 0.005 50);
            --surface-2: oklch(0.96 0.007 50);
            --ink: oklch(0.18 0.012 50);
            --ink-2: oklch(0.42 0.010 50);
            --rule: oklch(0.88 0.008 50);
            --good: oklch(0.50 0.16 145);
            --danger: oklch(0.55 0.22 25);
            --radius: 0.625rem;
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --surface: oklch(0.15 0.015 50);
                --surface-2: oklch(0.20 0.018 50);
                --ink: oklch(0.92 0.005 50);
                --ink-2: oklch(0.68 0.008 50);
                --rule: oklch(0.30 0.012 50);
                --brand: oklch(0.62 0.11 50);
                --brand-deep: oklch(0.22 0.06 260);
                --brand-green: oklch(0.56 0.19 145);
                --brand-soft: oklch(0.25 0.05 50);
            }
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
        body { margin: 0; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; color: var(--ink); background: var(--surface); font-size: 1rem; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        .skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100; transform: translateY(-5rem); background: var(--ink); color: var(--surface); padding: .75rem 1rem; border-radius: var(--radius); }
        .skip-link:focus { transform: translateY(0); }
        .container { width: min(76rem, calc(100% - 2rem)); margin-inline: auto; }
        .nav { position: sticky; top: 0; z-index: 50; background: color-mix(in oklch, var(--surface) 92%, transparent); border-bottom: 1px solid var(--rule); backdrop-filter: blur(10px); }
        .nav-inner { min-height: 4.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .logo { display: inline-flex; align-items: center; text-decoration: none; font-weight: 800; letter-spacing: -0.03em; font-size: 1.25rem; }
        .nav-links { display: flex; align-items: center; gap: 1.25rem; }
        .nav-links a, .signin { min-height: 2.75rem; display: inline-flex; align-items: center; color: var(--ink-2); font-size: .925rem; font-weight: 600; text-decoration: none; }
        .nav-links a:hover, .signin:hover { color: var(--ink); }
        .nav-actions { display: flex; align-items: center; gap: .75rem; }
        .menu-button { display: none; min-width: 2.75rem; min-height: 2.75rem; border: 1px solid var(--rule); border-radius: var(--radius); color: var(--ink); background: var(--surface); font: inherit; }
        .mobile-menu { display: none; border-top: 1px solid var(--rule); padding: .75rem 0 1rem; }
        .mobile-menu a { display: flex; min-height: 2.75rem; align-items: center; text-decoration: none; color: var(--ink-2); font-weight: 600; }
        .btn { min-height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: var(--radius); border: 1px solid transparent; padding: .75rem 1rem; font-weight: 700; font-size: .925rem; text-decoration: none; transition: transform 180ms var(--ease), background 180ms var(--ease), border-color 180ms var(--ease); }
        .btn:focus-visible, .menu-button:focus-visible, .faq-question:focus-visible, .mobile-menu a:focus-visible, .nav-links a:focus-visible, .signin:focus-visible { outline: 2px solid var(--brand-deep); outline-offset: 2px; }
        .btn-primary { color: oklch(0.99 0.005 50); background: var(--brand-deep); }
        .btn-primary:hover { background: var(--brand); transform: translateY(-1px); }
        .btn-secondary { border-color: var(--rule); color: var(--ink); background: var(--surface); }
        .btn-secondary:hover { border-color: var(--brand); }
        .section { padding: 5rem 0; }
        .section-title { max-width: 46rem; margin: 0 auto 2.5rem; text-align: center; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { max-width: 13ch; margin-bottom: 1.25rem; font-size: clamp(2.75rem, 8vw, 5.25rem); line-height: .95; letter-spacing: -0.07em; }
        h2 { margin-bottom: .75rem; font-size: clamp(2rem, 4vw, 3rem); line-height: 1.05; letter-spacing: -0.05em; }
        h3 { margin-bottom: .5rem; font-size: 1.25rem; letter-spacing: -0.03em; }
        p { color: var(--ink-2); }
        .eyebrow { display: inline-flex; align-items: center; min-height: 2rem; margin-bottom: 1.5rem; padding: .35rem .75rem; border: 1px solid color-mix(in oklch, var(--brand) 30%, var(--rule)); border-radius: 999px; color: var(--brand-deep); background: var(--brand-soft); font-size: .8rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        @media (prefers-color-scheme: dark) { .eyebrow { color: var(--brand); } }
        .hero { padding: 6rem 0 4rem; }
        .hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(24rem, .85fr); gap: 3rem; align-items: center; }
        .hero-copy > p { max-width: 42rem; font-size: clamp(1.125rem, 2vw, 1.35rem); line-height: 1.6; }
        .hero-ctas, .chips { display: flex; flex-wrap: wrap; gap: .75rem; }
        .hero-ctas { margin: 2rem 0 1.5rem; }
        .chip { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.25rem; padding: .4rem .7rem; border: 1px solid var(--rule); border-radius: 999px; color: var(--ink-2); background: var(--surface-2); font-size: .85rem; font-weight: 700; }
        .chip::before { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: var(--good); }
        .pos-panel { border: 1px solid var(--rule); border-radius: 1rem; background: var(--surface-2); overflow: hidden; box-shadow: 0 24px 70px color-mix(in oklch, var(--brand-deep) 12%, transparent); }
        .pos-top { display: flex; gap: .4rem; padding: .85rem; border-bottom: 1px solid var(--rule); }
        .dot { width: .7rem; height: .7rem; border-radius: 50%; background: var(--rule); }
        .pos-body { display: grid; grid-template-columns: 1fr .75fr; gap: 1rem; padding: 1rem; }
        .panel-card { min-height: 10rem; border: 1px solid var(--rule); border-radius: .75rem; background: var(--surface); padding: 1rem; }
        .panel-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .6rem 0; border-bottom: 1px solid var(--rule); color: var(--ink-2); font-size: .85rem; font-variant-numeric: tabular-nums; }
        .panel-row strong { color: var(--ink); }
        .schedule { color: var(--surface); background: var(--danger); border-radius: 999px; padding: .1rem .45rem; font-size: .75rem; font-weight: 800; }
        .compliance-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .compliance-item, .module, .pricing-card, .quote-card, .faq-item { border: 1px solid var(--rule); border-radius: 1rem; background: var(--surface-2); }
        .compliance-item { padding: 1rem; }
        .compliance-item b { display: block; margin-bottom: .25rem; }
        .modules { display: grid; gap: 1rem; }
        .module { display: grid; grid-template-columns: .85fr 1.15fr; gap: 1rem; padding: 1.25rem; align-items: center; }
        .module-number { color: var(--brand-deep); font-weight: 800; letter-spacing: .08em; }
        .terminal { border-radius: .75rem; background: oklch(0.18 0.025 50); color: oklch(0.92 0.005 50); padding: 1rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; overflow: hidden; }
        .terminal-line { display: flex; justify-content: space-between; gap: 1rem; padding: .35rem 0; border-bottom: 1px solid oklch(0.30 0.02 50); }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; counter-reset: steps; }
        .step { position: relative; padding: 1.25rem 0 0; }
        .step::before { counter-increment: steps; content: counter(steps); display: grid; place-items: center; width: 3rem; height: 3rem; margin-bottom: 1rem; border-radius: 50%; color: oklch(0.99 0.005 50); background: var(--brand-deep); font-weight: 800; }
        .workflow { display: flex; gap: .75rem; overflow-x: auto; padding-bottom: .75rem; scroll-snap-type: x mandatory; }
        .node { min-width: 11rem; scroll-snap-align: start; padding: 1rem; border: 1px solid var(--rule); border-radius: .9rem; background: var(--surface-2); font-weight: 800; }
        .node::after { content: '->'; float: right; color: var(--brand); }
        .node:last-child::after { content: ''; }
        .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .pricing-card { padding: 1.5rem; }
        .pricing-card:nth-child(2) { border-color: var(--brand); box-shadow: inset 0 0 0 1px var(--brand); }
        .price { margin: 1rem 0 .25rem; color: var(--ink); font-size: 2rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -0.05em; }
        .price span { color: var(--ink-2); font-size: 1rem; font-weight: 600; }
        .features { list-style: none; padding: 0; margin: 1.5rem 0; }
        .features li { padding: .45rem 0; color: var(--ink-2); }
        .features li::before { content: '+ '; color: var(--good); font-weight: 800; }
        .quote-card { max-width: 56rem; margin: 0 auto; padding: 2rem; }
        .quote { color: var(--ink); font-size: clamp(1.4rem, 3vw, 2.25rem); line-height: 1.15; letter-spacing: -0.04em; }
        .attribution { display: grid; gap: .5rem; margin-top: 1.5rem; }
        .attribution-row { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: .75rem; color: var(--ink-2); font-size: .9rem; }
        .faq-list { max-width: 52rem; margin: 0 auto; display: grid; gap: .75rem; }
        .faq-item { padding: 0 1rem; }
        .faq-question { width: 100%; min-height: 3.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border: 0; background: transparent; color: var(--ink); font: inherit; font-weight: 800; text-align: left; cursor: pointer; }
        .faq-answer { display: none; padding-bottom: 1rem; }
        .faq-item.open .faq-answer { display: block; }
        .cta { background: var(--brand-deep); color: oklch(0.99 0.005 50); }
        .cta p, .cta h2 { color: inherit; }
        .cta .btn { background: oklch(0.99 0.005 50); color: var(--brand-deep); }
        .footer { padding: 3rem 0 1.5rem; border-top: 1px solid var(--rule); }
        .footer-grid { display: grid; grid-template-columns: 1.5fr repeat(3, 1fr); gap: 2rem; }
        .footer a { display: block; margin: .4rem 0; color: var(--ink-2); text-decoration: none; }
        .footer-bottom { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--rule); color: var(--ink-2); font-size: .85rem; }
        @media (max-width: 1023px) {
            .nav-links, .signin, .nav-actions .btn { display: none; }
            .menu-button { display: inline-flex; align-items: center; justify-content: center; }
            .mobile-menu.open { display: block; }
            .hero-grid, .module { grid-template-columns: 1fr; }
            .compliance-grid, .pricing-grid, .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 720px) {
            .section { padding: 3.5rem 0; }
            .hero { padding-top: 4rem; }
            .pos-body, .compliance-grid, .steps, .pricing-grid, .footer-grid { grid-template-columns: 1fr; }
            .attribution-row { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
@php
    $sections = collect($page->content['sections'] ?? [])->keyBy('type');
    $data = fn (string $type, array $fallback = []) => array_replace_recursive($fallback, (array) (($sections->get($type)['data'] ?? [])));
    $nav = $data('nav', ['logo_text' => 'PharmaPOS', 'signin_text' => 'Sign In', 'signin_url' => '/login', 'cta_text' => 'Start Trial', 'cta_url' => '/register']);
    $hero = $data('hero', ['eyebrow' => 'Made for Nepal Pharmacies', 'heading' => 'The pharmacy POS built for Nepal', 'subheading' => 'Batch tracking, prescription management, narcotics register, and digital payments in one counter-first system.', 'primary_cta_text' => 'Start 14-day trial', 'primary_cta_url' => '/register', 'secondary_cta_text' => 'See workflow', 'secondary_cta_url' => '#workflow', 'trust_signals' => ['Schedule-aware', 'VAT + PAN invoiced', 'eSewa + Khalti + Fonepay', 'BS + AD dates']]);
    $compliance = $data('compliance_bar', ['items' => [['label' => 'DDA ready', 'detail' => 'Registers stay close to the sale.'], ['label' => 'PAN/VAT', 'detail' => 'Invoices fit Nepali accounting.'], ['label' => 'Schedule H/H1/X', 'detail' => 'Sensitive medicines stay visible.'], ['label' => 'BS + AD dates', 'detail' => 'Both calendars are readable.']]]);
    $modules = $data('module_showcase', ['heading' => 'The organized pharmacy shelf, digitized', 'subheading' => 'Counter work, stock work, and compliance stay in the same flow.', 'modules' => [['number' => '01', 'title' => 'Dispense at counter', 'description' => 'Scan, check schedule status, attach prescription, and take payment without switching screens.'], ['number' => '02', 'title' => 'Track batch inventory', 'description' => 'See expiry, stock, purchase price, selling price, and reorder state before the sale.'], ['number' => '03', 'title' => 'Keep compliance ready', 'description' => 'Narcotics register, VAT invoices, and audit trails are created as work happens.']]]);
    $how = $data('how_it_works', ['heading' => 'How it works', 'steps' => [['title' => 'Set up your pharmacy', 'description' => 'Add pharmacy details, import medicines, and configure payment methods.'], ['title' => 'Start dispensing', 'description' => 'Scan barcodes, check prescriptions, and process sales quickly.'], ['title' => 'Stay compliant', 'description' => 'Reports, expiry alerts, and registers are ready when needed.']]]);
    $workflow = $data('workflow_diagram', ['heading' => 'One line from purchase to report', 'nodes' => [['label' => 'Receive purchase'], ['label' => 'Track expiry'], ['label' => 'Scan sale'], ['label' => 'Collect payment'], ['label' => 'Update ledger'], ['label' => 'File reports']]]);
    $pricing = $data('pricing', ['heading' => 'Simple, transparent pricing', 'subheading' => 'No hidden fees. No setup costs.']);
    $signal = $data('operator_signal', ['heading' => 'Built for counter speed', 'quote' => 'The safest sale is the one where stock, expiry, prescription, and payment are checked in one place.', 'attribution_rows' => [['name' => 'Pharmacist workflow', 'pharmacy' => 'Counter-first dispensing', 'location' => 'Nepal', 'since' => 'Daily use']]]);
    $faq = $data('faq', ['heading' => 'Frequently asked questions', 'items' => [['question' => 'Do I need special hardware?', 'answer' => 'A computer, thermal receipt printer, and barcode scanner are enough to start.'], ['question' => 'Is PharmaPOS compliant with Nepal drug regulations?', 'answer' => 'It supports narcotics registers, schedule tracking, and VAT/PAN invoice workflows.'], ['question' => 'Can I use it for multiple outlets?', 'answer' => 'Yes. Professional and Enterprise plans support outlet-level stock with centralized reporting.'], ['question' => 'What payment gateways are supported?', 'answer' => 'eSewa, Khalti, Fonepay, cash, and card workflows are supported.'], ['question' => 'Is there a free trial?', 'answer' => 'Yes. Start with a 14-day trial. No credit card required.']]]);
    $cta = $data('cta', ['heading' => 'Start your 14-day trial', 'subheading' => 'Bring counter sales, stock, and compliance into one pharmacy workflow.', 'button_text' => 'Start trial', 'button_url' => '/register']);
    $footer = $data('footer', ['logo_text' => 'PharmaPOS', 'description' => 'A cloud-based pharmacy POS and inventory system built for Nepal.', 'links' => [['label' => 'Privacy', 'url' => '#'], ['label' => 'Terms', 'url' => '#'], ['label' => 'Contact', 'url' => '#']]]);
@endphp
<a href="#main" class="skip-link">Skip to main content</a>
<nav class="nav" aria-label="Primary navigation">
    <div class="container nav-inner">
<a class="logo" href="/" aria-label="PharmaPOS home"><img src="{{ asset('pharmapos-logo.png') }}" alt="PharmaPOS" height="32"></a>
        <div class="nav-links" aria-label="Page sections">
            <a href="#features">Features</a><a href="#workflow">Workflow</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a>
        </div>
        <div class="nav-actions">
            <a class="signin" href="{{ $nav['signin_url'] }}">{{ $nav['signin_text'] }}</a>
            <a class="btn btn-primary" href="{{ $nav['cta_url'] }}">{{ $nav['cta_text'] }}</a>
            <button id="menu-button" class="menu-button" type="button" aria-expanded="false" aria-controls="mobile-menu">Menu</button>
        </div>
    </div>
    <div class="container mobile-menu" id="mobile-menu">
        <a href="#features">Features</a><a href="#workflow">Workflow</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a><a href="{{ $nav['signin_url'] }}">{{ $nav['signin_text'] }}</a><a href="{{ $nav['cta_url'] }}">{{ $nav['cta_text'] }}</a>
    </div>
</nav>
<main id="main">
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">{{ $hero['eyebrow'] }}</span>
                <h1>{{ $hero['heading'] }}</h1>
                <p>{{ $hero['subheading'] }}</p>
                <div class="hero-ctas"><a class="btn btn-primary" href="{{ $hero['primary_cta_url'] }}">{{ $hero['primary_cta_text'] }}</a><a class="btn btn-secondary" href="{{ $hero['secondary_cta_url'] }}">{{ $hero['secondary_cta_text'] }}</a></div>
                <div class="chips">@foreach ($hero['trust_signals'] ?? [] as $signalItem)<span class="chip">{{ $signalItem }}</span>@endforeach</div>
            </div>
            <div class="pos-panel" aria-label="PharmaPOS counter workflow preview">
                <div class="pos-top"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
                <div class="pos-body"><div class="panel-card"><div class="panel-row"><strong>Amoxicillin 500mg</strong><span>Batch A42</span></div><div class="panel-row"><strong>Cetirizine 10mg</strong><span>Stock 73</span></div><div class="panel-row"><strong>Tramadol</strong><span class="schedule">H1</span></div><div class="panel-row"><strong>Total</strong><span>रू 325</span></div></div><div class="panel-card"><div class="panel-row"><strong>Prescription</strong><span>Attached</span></div><div class="panel-row"><strong>Expiry</strong><span>2027-03</span></div><div class="panel-row"><strong>Payment</strong><span>eSewa</span></div></div></div>
            </div>
        </div>
    </section>
    <section class="section" id="compliance"><div class="container compliance-grid">@foreach ($compliance['items'] ?? [] as $item)<div class="compliance-item"><b>{{ $item['label'] ?? '' }}</b><p>{{ $item['detail'] ?? '' }}</p></div>@endforeach</div></section>
    <section class="section" id="features"><div class="container"><div class="section-title"><h2>{{ $modules['heading'] }}</h2><p>{{ $modules['subheading'] ?? '' }}</p></div><div class="modules">@foreach ($modules['modules'] ?? [] as $module)<article class="module"><div><div class="module-number">{{ $module['number'] ?? $loop->iteration }}</div><h3>{{ $module['title'] ?? '' }}</h3><p>{{ $module['description'] ?? '' }}</p></div><div class="terminal" aria-hidden="true"><div class="terminal-line"><span>batch</span><span>expiry</span><span>stock</span></div><div class="terminal-line"><span>AMX-A42</span><span>2027-03</span><span>128</span></div><div class="terminal-line"><span>CTZ-B19</span><span>2026-11</span><span>73</span></div><div class="terminal-line"><span>TRM-H1</span><span>verify Rx</span><span>12</span></div></div></article>@endforeach</div></div></section>
    <section class="section" id="how-it-works"><div class="container"><div class="section-title"><h2>{{ $how['heading'] }}</h2></div><div class="steps">@foreach ($how['steps'] ?? [] as $step)<article class="step"><h3>{{ $step['title'] ?? '' }}</h3><p>{{ $step['description'] ?? '' }}</p></article>@endforeach</div></div></section>
    <section class="section" id="workflow"><div class="container"><div class="section-title"><h2>{{ $workflow['heading'] }}</h2></div><div class="workflow" aria-label="Pharmacy workflow">@foreach ($workflow['nodes'] ?? [] as $node)<div class="node">{{ $node['label'] ?? '' }}</div>@endforeach</div></div></section>
    <section class="section" id="pricing"><div class="container"><div class="section-title"><h2>{{ $pricing['heading'] }}</h2><p>{{ $pricing['subheading'] ?? '' }}</p></div><div class="pricing-grid">@forelse ($plans as $plan)<article class="pricing-card"><h3>{{ $plan->name }}</h3><div class="price">रू {{ number_format((float) $plan->price_monthly, 0, '.', ',') }} <span>/ month</span></div><p>रू {{ number_format((float) $plan->price_yearly, 0, '.', ',') }} yearly</p><ul class="features"><li>{{ $plan->max_outlets }} outlet{{ $plan->max_outlets > 1 ? 's' : '' }}</li><li>Up to {{ $plan->max_users }} users</li><li>{{ $plan->max_medicines >= 999999 ? 'Unlimited' : number_format($plan->max_medicines) }} medicines</li><li>Batch and expiry tracking</li><li>VAT invoice workflow</li></ul><a class="btn btn-primary" href="/register" style="width:100%">Get started</a></article>@empty <p>No active plans yet. Contact support for pricing.</p> @endforelse</div></div></section>
    <section class="section" id="operator-signal"><div class="container"><div class="section-title"><h2>{{ $signal['heading'] }}</h2></div><div class="quote-card"><p class="quote">&quot;{{ $signal['quote'] }}&quot;</p><div class="attribution">@foreach ($signal['attribution_rows'] ?? [] as $row)<div class="attribution-row"><strong>{{ $row['name'] ?? '' }}</strong><span>{{ $row['pharmacy'] ?? '' }}</span><span>{{ $row['location'] ?? '' }}</span><span>{{ $row['since'] ?? '' }}</span></div>@endforeach</div></div></div></section>
    <section class="section" id="faq"><div class="container"><div class="section-title"><h2>{{ $faq['heading'] }}</h2></div><div class="faq-list">@foreach ($faq['items'] ?? [] as $item)<div class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-panel-{{ $loop->index }}">{{ $item['question'] ?? '' }}<span aria-hidden="true">+</span></button><div class="faq-answer" id="faq-panel-{{ $loop->index }}"><p>{{ $item['answer'] ?? '' }}</p></div></div>@endforeach</div></div></section>
    <section class="section cta"><div class="container section-title"><h2>{{ $cta['heading'] }}</h2><p>{{ $cta['subheading'] }}</p><a class="btn" href="{{ $cta['button_url'] }}">{{ $cta['button_text'] }}</a></div></section>
</main>
<footer class="footer"><div class="container"><div class="footer-grid"><div><a class="logo" href="/" aria-label="PharmaPOS home"><img src="{{ asset('pharmapos-logo.png') }}" alt="PharmaPOS" height="32"></a><p>{{ $footer['description'] }}</p></div><div><h3>Product</h3><a href="#features">Features</a><a href="#pricing">Pricing</a></div><div><h3>Compliance</h3><a href="#compliance">DDA workflow</a><a href="#faq">FAQ</a></div><div><h3>Company</h3>@foreach ($footer['links'] ?? [] as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</div></div><div class="footer-bottom">&copy; {{ date('Y') }} PharmaPOS. Made for Nepal's pharmacies.</div></div></footer>
<script>
    document.getElementById('menu-button')?.addEventListener('click', function () {
        var menu = document.getElementById('mobile-menu');
        var isOpen = menu.classList.toggle('open');
        this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.querySelectorAll('.faq-question').forEach(function (button) {
        button.addEventListener('click', function () {
            var item = button.closest('.faq-item');
            var isOpen = item.classList.toggle('open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
</script>
</body>
</html>
