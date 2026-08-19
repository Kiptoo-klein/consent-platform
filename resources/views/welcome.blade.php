<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $seoName =
            $platformBrand['platform_name']
            ?? config('ui-brand.name', 'eConsent');

        $seoTitle =
            $seoName
            .' | Digital Consent Management Software';

        $seoDescription =
            'Create, send, sign and securely manage digital consent forms, '
            .'electronic signatures, signing stations and consent records '
            .'with '.$seoName.'.';

        $seoCanonicalUrl = 'https://econsent.site/';

        $seoStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $seoCanonicalUrl.'#organization',
                    'name' => $seoName,
                    'url' => $seoCanonicalUrl,
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $seoCanonicalUrl.'#website',
                    'url' => $seoCanonicalUrl,
                    'name' => $seoName,
                    'description' => $seoDescription,
                    'publisher' => [
                        '@id' => $seoCanonicalUrl.'#organization',
                    ],
                ],
            ],
        ];
    @endphp

    <title>{{ $seoTitle }}</title>

    <meta
        name="description"
        content="{{ $seoDescription }}"
    >

    <link
        rel="canonical"
        href="{{ $seoCanonicalUrl }}"
    >

    <meta
        property="og:type"
        content="website"
    >
    <meta
        property="og:site_name"
        content="{{ $seoName }}"
    >
    <meta
        property="og:title"
        content="{{ $seoTitle }}"
    >
    <meta
        property="og:description"
        content="{{ $seoDescription }}"
    >
    <meta
        property="og:url"
        content="{{ $seoCanonicalUrl }}"
    >

    <meta
        name="twitter:card"
        content="summary"
    >
    <meta
        name="twitter:title"
        content="{{ $seoTitle }}"
    >
    <meta
        name="twitter:description"
        content="{{ $seoDescription }}"
    >

    <script type="application/ld+json">{!! json_encode(
        $seoStructuredData,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    ) !!}</script>

    <link rel="icon" type="image/svg+xml" href="{{ $platformBrand['favicon_url'] ?? asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="econsent-landing font-sans antialiased">
    <header class="ec-header">
        <div class="ec-container ec-nav">
            <a href="{{ url('/') }}" class="ec-brand">
                <x-application-logo class="h-11 w-11"/>

                <span>
                    <strong>{{ $platformBrand['platform_name'] ?? config('ui-brand.name', 'eConsent') }}</strong>
                    <small>Digital Consent Management</small>
                </span>
            </a>

            <nav>
                <a href="#features">Features</a>
                <a href="#process">How it works</a>
                <a href="#security">Security</a>
                <a href="#contact">Contact</a>
            </nav>

            <div class="ec-nav-actions">
                @auth
                    <a
                        href="{{ Auth::user()->platform_role_id
                            ? route('platform.dashboard')
                            : route('dashboard') }}"
                        class="ec-button ec-button-primary"
                    >
                        Open dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="ec-login-link">
                        Log in
                    </a>

                    @if (Route::has('register'))
                        <a
                            href="{{ route('register') }}"
                            class="ec-button ec-button-primary"
                        >
                            Get started
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="ec-hero">
            <div class="ec-container ec-hero-grid">
                <div>
                    <span class="ec-kicker">Professional digital consent</span>

                    <h1>
                        Digital consent management that feels
                        <span>clear, secure and human.</span>
                    </h1>

                    <p>
                        Create, send, sign and securely manage digital consent
                        forms and records from one professional platform built
                        for organizations that value trust and accountability.
                    </p>

                    <div class="ec-hero-actions">
                        @auth
                            <a
                                href="{{ Auth::user()->platform_role_id
                                    ? route('platform.dashboard')
                                    : route('dashboard') }}"
                                class="ec-button ec-button-primary ec-button-large"
                            >
                                Go to dashboard
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="ec-button ec-button-primary ec-button-large"
                                >
                                    Create an account
                                </a>
                            @endif

                            <a
                                href="{{ route('login') }}"
                                class="ec-button ec-button-secondary ec-button-large"
                            >
                                Sign in
                            </a>
                        @endauth
                    </div>

                    <div class="ec-trust-row">
                        <div>
                            <strong>Secure</strong>
                            <span>Controlled access</span>
                        </div>

                        <div>
                            <strong>Traceable</strong>
                            <span>Complete audit history</span>
                        </div>

                        <div>
                            <strong>Efficient</strong>
                            <span>Paperless workflows</span>
                        </div>
                    </div>
                </div>

                <div class="ec-hero-preview">
                    <div class="ec-preview-window">
                        <div class="ec-preview-top">
                            <span></span><span></span><span></span>
                            <strong>Consent overview</strong>
                        </div>

                        <div class="ec-preview-content">
                            <aside>
                                <div class="ec-preview-brand">
                                    <x-application-logo class="h-8 w-8"/>
                                    <strong>eConsent</strong>
                                </div>

                                <span class="active">Overview</span>
                                <span>Templates</span>
                                <span>Consent records</span>
                                <span>Signing stations</span>
                                <span>Analytics</span>
                            </aside>

                            <section>
                                <small>Monday overview</small>
                                <h3>Your consent operations are on track.</h3>

                                <div class="ec-preview-stats">
                                    <article>
                                        <span>Completed</span>
                                        <strong>1,248</strong>
                                        <small>+18% this month</small>
                                    </article>

                                    <article>
                                        <span>Active stations</span>
                                        <strong>12</strong>
                                        <small>All operational</small>
                                    </article>

                                    <article>
                                        <span>Completion rate</span>
                                        <strong>94%</strong>
                                        <small>Excellent</small>
                                    </article>
                                </div>

                                <div class="ec-preview-table">
                                    <header>
                                        <strong>Recent activity</strong>
                                        <span>View all</span>
                                    </header>

                                    <div>
                                        <b>Visitor consent</b>
                                        <span class="success">Completed</span>
                                    </div>

                                    <div>
                                        <b>Media release</b>
                                        <span class="pending">Pending</span>
                                    </div>

                                    <div>
                                        <b>Service agreement</b>
                                        <span class="success">Completed</span>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">Everything in one place</span>
                    <h2>Built to make consent easier to manage.</h2>
                    <p>
                        Give administrators, teams and signers a clear experience
                        from template creation to signed record delivery.
                    </p>
                </div>

                <div class="ec-feature-grid">
                    <article>
                        <span>✦</span>
                        <h3>Flexible templates</h3>
                        <p>Create reusable consent templates with the exact information your process needs.</p>
                    </article>

                    <article>
                        <span>⌁</span>
                        <h3>Signing stations</h3>
                        <p>Launch secure public or kiosk signing experiences for visitors and clients.</p>
                    </article>

                    <article>
                        <span>✓</span>
                        <h3>Signed PDF records</h3>
                        <p>Generate consistent signed documents with delivery and audit information.</p>
                    </article>

                    <article>
                        <span>◎</span>
                        <h3>Organization control</h3>
                        <p>Separate organizations, users and permissions while preserving platform oversight.</p>
                    </article>

                    <article>
                        <span>↗</span>
                        <h3>Useful analytics</h3>
                        <p>Track completion, abandonment, station activity and consent performance.</p>
                    </article>

                    <article>
                        <span>◇</span>
                        <h3>Operational readiness</h3>
                        <p>Monitor backups, queues, security and production readiness.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="process" class="ec-dark-section">
            <div class="ec-container ec-process-grid">
                <div>
                    <span class="ec-kicker light">A simpler process</span>
                    <h2>From document to signed record in four clear steps.</h2>
                    <p>
                        Keep the workflow organized while giving signers a
                        focused and easy-to-understand experience.
                    </p>
                </div>

                <ol>
                    <li><span>01</span><div><strong>Create</strong><p>Build and publish the consent template.</p></div></li>
                    <li><span>02</span><div><strong>Present</strong><p>Open it through a secure link or station.</p></div></li>
                    <li><span>03</span><div><strong>Sign</strong><p>Collect details, acknowledgement and signature.</p></div></li>
                    <li><span>04</span><div><strong>Manage</strong><p>Store, search, export and review records.</p></div></li>
                </ol>
            </div>
        </section>

        <section id="security" class="ec-section">
            <div class="ec-container ec-security-grid">
                <div class="ec-security-logo">
                    <x-application-logo class="h-32 w-32"/>
                </div>

                <div>
                    <span class="ec-kicker">Security and accountability</span>
                    <h2>Professional controls behind every consent record.</h2>
                    <p>
                        Role-based access, organization separation, secure public
                        tokens, audit trails, verified backups and production
                        monitoring protect the consent lifecycle.
                    </p>

                    <ul>
                        <li>Role and permission controls</li>
                        <li>Organization-level data separation</li>
                        <li>Secure signing links and station tokens</li>
                        <li>Signed PDF and delivery records</li>
                        <li>Backup and production monitoring</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="contact" class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">Contact eConsent</span>

                    <h2>Questions about setup, subscriptions or support?</h2>

                    <p>
                        Reach us directly for help getting started,
                        choosing a plan or resolving a technical issue.
                    </p>
                </div>

                <div class="ec-feature-grid">
                    <article>
                        <span>@</span>

                        <h3>Email support</h3>

                        <p>
                            Send a message and we will respond as soon as
                            possible.
                        </p>

                        <a
                            href="mailto:kleinluche@gmail.com"
                            class="ec-button ec-button-secondary"
                        >
                            kleinluche@gmail.com
                        </a>
                    </article>

                    <article>
                        <span>☎</span>

                        <h3>Call us</h3>

                        <p>
                            Speak to us directly about your eConsent
                            account or organization.
                        </p>

                        <a
                            href="tel:+254716583388"
                            class="ec-button ec-button-secondary"
                        >
                            +254 716 583 388
                        </a>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-cta">
            <div class="ec-container ec-cta-card">
                <div>
                    <span class="ec-kicker light">Ready to begin?</span>
                    <h2>Move your consent process beyond paperwork.</h2>
                    <p>
                        Create a more professional experience for your
                        organization and every person who signs.
                    </p>
                </div>

                @auth
                    <a
                        href="{{ Auth::user()->platform_role_id
                            ? route('platform.dashboard')
                            : route('dashboard') }}"
                        class="ec-button ec-button-light ec-button-large"
                    >
                        Open dashboard
                    </a>
                @else
                    <a
                        href="{{ Route::has('register')
                            ? route('register')
                            : route('login') }}"
                        class="ec-button ec-button-light ec-button-large"
                    >
                        Get started
                    </a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="ec-footer">
        <div class="ec-container">
            <div class="ec-brand">
                <x-application-logo class="h-10 w-10"/>

                <span>
                    <strong>{{ $platformBrand['platform_name'] ?? config('ui-brand.name', 'eConsent') }}</strong>
                    <small>Digital Consent Management</small>
                </span>
            </div>

            <p>{{ $platformBrand['tagline'] ?? config('ui-brand.tagline') }}</p>

            <span>© {{ now()->year }}</span>
        </div>
    </footer>
</body>
</html>
