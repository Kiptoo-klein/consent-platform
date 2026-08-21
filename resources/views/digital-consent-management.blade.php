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
            'What Is Digital Consent Management? | '
            .$seoName;

        $seoDescription =
            'Learn how digital consent management helps organizations '
            .'create, distribute, sign and organize consent forms, '
            .'electronic signatures and consent records.';

        $seoHomeUrl =
            'https://econsent.site/';

        $seoCanonicalUrl =
            'https://econsent.site/digital-consent-management';

        $seoStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $seoHomeUrl.'#organization',
                    'name' => $seoName,
                    'url' => $seoHomeUrl,
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $seoHomeUrl.'#website',
                    'url' => $seoHomeUrl,
                    'name' => $seoName,
                    'publisher' => [
                        '@id' => $seoHomeUrl.'#organization',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $seoCanonicalUrl.'#webpage',
                    'url' => $seoCanonicalUrl,
                    'name' => $seoTitle,
                    'description' => $seoDescription,
                    'isPartOf' => [
                        '@id' => $seoHomeUrl.'#website',
                    ],
                    'about' => [
                        '@id' => $seoHomeUrl.'#organization',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $seoCanonicalUrl.'#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => $seoHomeUrl,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Digital Consent Management',
                            'item' => $seoCanonicalUrl,
                        ],
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

    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ $platformBrand['favicon_url'] ?? asset('favicon.svg') }}"
    >

    <link
        rel="preconnect"
        href="https://fonts.bunny.net"
    >

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
                    <strong>
                        {{ $platformBrand['platform_name'] ?? config('ui-brand.name', 'eConsent') }}
                    </strong>

                    <small>
                        Digital Consent Management
                    </small>
                </span>
            </a>

            <nav>
                <a
                    href="{{ route('digital-consent-management') }}"
                    aria-current="page"
                >
                    Digital consent
                </a>

                <a href="{{ url('/') }}#features">
                    Features
                </a>

                <a href="{{ url('/') }}#process">
                    How it works
                </a>

                <a href="{{ url('/') }}#security">
                    Security
                </a>

                <a href="{{ url('/') }}#contact">
                    Contact
                </a>
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
                    <a
                        href="{{ route('login') }}"
                        class="ec-login-link"
                    >
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
                    <span class="ec-kicker">
                        Digital consent management guide
                    </span>

                    <h1>
                        What is
                        <span>digital consent management?</span>
                    </h1>

                    <p>
                        Digital consent management is the process of creating,
                        presenting, signing and organizing consent records
                        electronically instead of relying on disconnected
                        paper forms and manual filing.
                    </p>

                    <div class="ec-hero-actions">
                        @auth
                            <a
                                href="{{ Auth::user()->platform_role_id
                                    ? route('platform.dashboard')
                                    : route('dashboard') }}"
                                class="ec-button ec-button-primary ec-button-large"
                            >
                                Open dashboard
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="ec-button ec-button-primary ec-button-large"
                                >
                                    Get started with eConsent
                                </a>
                            @endif

                            <a
                                href="{{ url('/') }}#process"
                                class="ec-button ec-button-secondary ec-button-large"
                            >
                                See how eConsent works
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="ec-hero-preview">
                    <div class="space-y-5">
                        <div>
                            <span class="ec-kicker">
                                A connected workflow
                            </span>

                            <h2 class="mt-3 text-2xl font-bold text-slate-900">
                                From consent form to organized record
                            </h2>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    1. Create
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Prepare reusable consent templates and
                                    collect the information you actually need.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    2. Present
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Share consent with an individual, a group
                                    or through a signing station.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    3. Sign
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Let the signer review the consent and
                                    complete the required information.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    4. Record
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Keep completed consent records organized
                                    for later review and export.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        The concept
                    </span>

                    <h2>
                        Digital consent is more than a signature on a screen.
                    </h2>

                    <p>
                        A complete digital consent process connects the
                        information a person reviews, the questions they
                        answer, the signature they provide and the resulting
                        consent record. Keeping those parts together makes the
                        workflow easier to manage than separate paper forms,
                        spreadsheets and email attachments.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Consistent consent forms
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Reusable templates help organizations present a
                            consistent consent document while still collecting
                            structured information such as names, contact
                            details and additional questions.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Clear signing workflows
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            A signer can review the consent, provide the
                            requested information and complete the signing
                            process without the organization having to move
                            data between several disconnected tools.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Organized consent records
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Completed records can be reviewed later and
                            exported when an organization needs a practical
                            record of the consent activity it has collected.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-dark-section">
            <div class="ec-container">
                <span class="ec-kicker light">
                    How eConsent supports the workflow
                </span>

                <h2>
                    One place for the main stages of consent collection.
                </h2>

                <p>
                    eConsent brings consent creation, electronic signing,
                    signing stations and consent records into one workflow so
                    organizations can spend less time moving information
                    between paper files and separate systems.
                </p>

                <div class="mt-10 grid gap-5 md:grid-cols-2">
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Consent templates
                        </h3>

                        <p class="mt-2">
                            Build reusable consent documents and structured
                            questions for recurring workflows.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Electronic signatures
                        </h3>

                        <p class="mt-2">
                            Give signers a digital path to review and complete
                            a consent record.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Signing stations
                        </h3>

                        <p class="mt-2">
                            Use a shared signing workflow when consent is being
                            collected in person.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Consent records and exports
                        </h3>

                        <p class="mt-2">
                            Review completed records and export consent data
                            when the information needs to be used elsewhere.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        When it is useful
                    </span>

                    <h2>
                        Digital consent management fits recurring consent
                        workflows.
                    </h2>

                    <p>
                        It is particularly useful when an organization
                        repeatedly needs to present clear information, collect
                        consent and retain an organized record of what was
                        completed.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            In-person consent
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Signing stations can support reception desks,
                            offices, events and other locations where people
                            complete consent on site.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Individual consent
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            A consent workflow can be prepared for one person
                            without requiring a physical form to be printed,
                            signed and filed manually.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Repeated organizational workflows
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Reusable templates help reduce repeated document
                            preparation when similar consent is collected
                            regularly.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Frequently asked questions
                    </span>

                    <h2>
                        Digital consent management FAQ
                    </h2>
                </div>

                <div class="mx-auto mt-10 max-w-4xl space-y-4">
                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What is digital consent management?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            Digital consent management is the electronic
                            process of preparing consent information,
                            collecting the required details and signature, and
                            keeping the resulting consent record organized.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            Is digital consent only an electronic signature?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            No. The signature is one part of the workflow.
                            Digital consent management also covers the consent
                            document, signer information, additional responses
                            and the completed record.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            Can digital consent be collected in person?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            Yes. eConsent includes signing-station workflows
                            for situations where a shared location is used to
                            collect consent in person.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What happens after a consent is completed?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            The completed consent becomes part of the
                            organization's consent records, where it can be
                            reviewed and exported according to the available
                            workflow.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <section class="ec-cta">
            <div class="ec-container ec-cta-card">
                <div>
                    <span class="ec-kicker light">
                        Ready to use digital consent?
                    </span>

                    <h2>
                        Bring your consent workflow into one organized
                        platform.
                    </h2>

                    <p>
                        Create consent templates, collect signatures and keep
                        completed consent records together with eConsent.
                    </p>
                </div>

                @auth
                    <a
                        href="{{ Auth::user()->platform_role_id
                            ? route('platform.dashboard')
                            : route('dashboard') }}"
                        class="ec-button ec-button-light ec-button-large whitespace-nowrap"
                    >
                        Open dashboard
                    </a>
                @else
                    <a
                        href="{{ Route::has('register')
                            ? route('register')
                            : route('login') }}"
                        class="ec-button ec-button-light ec-button-large whitespace-nowrap"
                    >
                        Get started
                    </a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="ec-footer">
        <div class="ec-container">
            <a href="{{ url('/') }}" class="ec-brand">
                <x-application-logo class="h-10 w-10"/>

                <span>
                    <strong>
                        {{ $platformBrand['platform_name'] ?? config('ui-brand.name', 'eConsent') }}
                    </strong>

                    <small>
                        Digital Consent Management
                    </small>
                </span>
            </a>

            <p>
                {{ $platformBrand['tagline'] ?? config('ui-brand.tagline') }}
            </p>

            <span>
                © {{ now()->year }}
            </span>
        </div>
    </footer>
</body>
</html>
