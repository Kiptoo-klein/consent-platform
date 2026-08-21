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
            'Electronic Consent Forms | Create & Sign Online | '
            .$seoName;

        $seoDescription =
            'Create, present, sign and organize electronic consent forms '
            .'with reusable templates, structured questions, in-person '
            .'signing and completed consent records.';

        $seoHomeUrl =
            'https://econsent.site/';

        $seoCanonicalUrl =
            'https://econsent.site/electronic-consent-forms';

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
                            'name' => 'Electronic Consent Forms',
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
                <a href="{{ route('digital-consent-management') }}">
                    Digital consent
                </a>

                <a
                    href="{{ route('electronic-consent-forms') }}"
                    aria-current="page"
                >
                    Consent forms
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
                        Electronic consent forms
                    </span>

                    <h1>
                        Electronic consent forms for
                        <span>clear, paperless workflows.</span>
                    </h1>

                    <p>
                        Create reusable consent forms, present them digitally,
                        collect the information and signature you need, and
                        keep the completed consent record organized without
                        relying on repeated paper preparation.
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
                                    Create an electronic consent form
                                </a>
                            @endif

                            <a
                                href="{{ route('digital-consent-management') }}"
                                class="ec-button ec-button-secondary ec-button-large"
                            >
                                Learn about digital consent
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="ec-hero-preview">
                    <div class="space-y-5">
                        <div>
                            <span class="ec-kicker">
                                One connected form
                            </span>

                            <h2 class="mt-3 text-2xl font-bold text-slate-900">
                                What an electronic consent form can include
                            </h2>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    Consent information
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Present the information the signer needs to
                                    review before giving consent.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    Signer details
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Collect names, contact details and other
                                    structured information required by the form.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    Additional questions
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Add reusable questions that match the
                                    organization's consent workflow.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    Signature and completed record
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Connect the completed signature with the
                                    information collected in the consent record.
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
                        Inside the form
                    </span>

                    <h2>
                        A consent form should keep the important parts
                        together.
                    </h2>

                    <p>
                        Electronic consent forms bring the information being
                        presented, the signer's responses and the resulting
                        record into one workflow rather than spreading them
                        across paper, email and separate files.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Information to review
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Reusable templates help present the same consent
                            information consistently whenever that workflow is
                            used again.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Structured responses
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Forms can collect the signer details and additional
                            questions needed for the particular consent
                            process.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Signature and record
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            The signature and completed information remain
                            connected to the resulting consent record for
                            later review.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-dark-section">
            <div class="ec-container">
                <span class="ec-kicker light">
                    From form to record
                </span>

                <h2>
                    Move from a reusable form to a completed consent record.
                </h2>

                <p>
                    eConsent keeps the main stages of the electronic consent
                    form workflow connected so organizations can avoid
                    repeatedly preparing and filing the same paperwork.
                </p>

                <div class="mt-10 grid gap-5 md:grid-cols-2">
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            1. Create
                        </h3>

                        <p class="mt-2">
                            Build a reusable consent template with the content
                            and questions required for the workflow.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            2. Present
                        </h3>

                        <p class="mt-2">
                            Present the form to an individual or make it
                            available through an in-person signing station.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            3. Sign
                        </h3>

                        <p class="mt-2">
                            Let the signer review the consent, answer the
                            required questions and provide a signature.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            4. Keep the record
                        </h3>

                        <p class="mt-2">
                            Store the completed consent record so it can be
                            reviewed, downloaded or included in an export.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Paper vs electronic
                    </span>

                    <h2>
                        The same consent process without repeated paper
                        handling.
                    </h2>

                    <p>
                        Paper consent can work, but recurring workflows often
                        require forms to be prepared, signed, filed and found
                        again manually. Electronic consent keeps those stages
                        in a connected digital process.
                    </p>
                </div>

                <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <span class="ec-kicker">
                            Paper workflow
                        </span>

                        <h3 class="mt-4 text-xl font-bold text-slate-900">
                            Repeated manual handling
                        </h3>

                        <ul class="mt-5 space-y-3 leading-7 text-slate-600">
                            <li>Prepare or print the form again.</li>
                            <li>Collect handwritten information and signatures.</li>
                            <li>File completed documents manually.</li>
                            <li>Find and organize records when needed later.</li>
                        </ul>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="ec-kicker">
                            Electronic workflow
                        </span>

                        <h3 class="mt-4 text-xl font-bold text-slate-900">
                            Reusable and organized
                        </h3>

                        <ul class="mt-5 space-y-3 leading-7 text-slate-600">
                            <li>Reuse a prepared consent template.</li>
                            <li>Collect structured information digitally.</li>
                            <li>Connect the signature to the completed record.</li>
                            <li>Review or export records from one workflow.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Ways to collect consent
                    </span>

                    <h2>
                        Use the form in the way the consent process happens.
                    </h2>

                    <p>
                        Electronic forms can support one-person consent,
                        in-person collection and recurring organizational
                        workflows without requiring a different paper process
                        for each situation.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Individual consent
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Prepare a consent record for one person and give
                            them a focused digital path to review and sign it.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            In-person signing
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Signing stations support locations where people
                            need to review and complete consent on site.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Recurring forms
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Reuse consent templates instead of rebuilding the
                            same document and questions for every signer.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        After signing
                    </span>

                    <h2>
                        A completed form becomes an organized consent record.
                    </h2>

                    <p>
                        The workflow continues after the signature. Completed
                        information can remain available as a consent record,
                        signed PDF and structured export when those formats are
                        needed.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Consent record
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Review the completed consent and the information
                            captured through its original form.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Signed PDF
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Generate a consistent PDF representation of the
                            completed consent record.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Data exports
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Export selected consent information when the
                            organization needs to use collected data elsewhere.
                        </p>
                    </article>
                </div>

                <p class="mx-auto mt-8 max-w-3xl text-center leading-7 text-slate-600">
                    Want the broader workflow explanation?
                    <a
                        href="{{ route('digital-consent-management') }}"
                        class="font-semibold text-teal-700 underline"
                    >
                        Read the digital consent management guide.
                    </a>
                </p>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Frequently asked questions
                    </span>

                    <h2>
                        Electronic consent forms FAQ
                    </h2>
                </div>

                <div class="mx-auto mt-10 max-w-4xl space-y-4">
                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What is an electronic consent form?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            An electronic consent form presents consent
                            information digitally and lets the signer provide
                            the required details and signature without using a
                            paper form.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            How is an electronic consent form different from a
                            paper form?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            The information being presented may serve the same
                            purpose, but the electronic workflow can connect
                            the form, responses, signature and completed record
                            without manual printing and filing.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            Can electronic consent forms be used in person?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            Yes. eConsent includes signing-station workflows
                            for situations where consent is being collected at
                            a shared physical location.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What happens after an electronic consent form is
                            signed?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            The completed information becomes part of the
                            consent record and can be reviewed through the
                            available record, PDF and export workflows.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <section class="ec-cta">
            <div class="ec-container ec-cta-card">
                <div>
                    <span class="ec-kicker light">
                        Ready to replace repeated paper forms?
                    </span>

                    <h2>
                        Create reusable electronic consent forms with eConsent.
                    </h2>

                    <p>
                        Build the form once, collect consent digitally and keep
                        completed records organized in the same platform.
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
