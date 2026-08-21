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
            'Online Consent Forms | Collect Consent Online | '
            .$seoName;

        $seoDescription =
            'Collect consent online with reusable forms, structured '
            .'questions and electronic signatures, then keep completed '
            .'consent records organized with eConsent.';

        $seoHomeUrl =
            'https://econsent.site/';

        $seoCanonicalUrl =
            'https://econsent.site/online-consent-forms';

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
                            'name' => 'Online Consent Forms',
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

                <a href="{{ route('electronic-consent-forms') }}">
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
                        Online consent forms
                    </span>

                    <h1>
                        Online consent forms for
                        <span>simple, paperless signing.</span>
                    </h1>

                    <p>
                        Give people a digital way to review consent
                        information, provide the details you need and sign
                        without requiring the consent process to happen on
                        paper or at the same physical location.
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
                                    Start collecting consent online
                                </a>
                            @endif

                            <a
                                href="{{ route('electronic-consent-forms') }}"
                                class="ec-button ec-button-secondary ec-button-large"
                            >
                                Explore electronic consent forms
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="ec-hero-preview">
                    <div class="space-y-5">
                        <div>
                            <span class="ec-kicker">
                                Online signer journey
                            </span>

                            <h2 class="mt-3 text-2xl font-bold text-slate-900">
                                From consent form to completed record
                            </h2>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    1. Access
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    The signer opens the consent process
                                    digitally rather than receiving a paper
                                    document.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    2. Review
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    Consent information and required questions
                                    are presented as part of the same workflow.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    3. Sign
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    The signer provides the required details
                                    and electronic signature.
                                </span>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <strong class="block text-slate-900">
                                    4. Record
                                </strong>

                                <span class="mt-1 block text-sm text-slate-600">
                                    The completed information stays connected
                                    to the resulting consent record.
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
                        Remote consent collection
                    </span>

                    <h2>
                        Collect consent when the signer is not standing in
                        front of you.
                    </h2>

                    <p>
                        Online consent forms make it possible to move the
                        review and signing process onto the web while keeping
                        the information collected through the form connected
                        to the completed consent record.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Digital access
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Signers can work through the consent process
                            digitally instead of waiting for a paper form to
                            be handed to them.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Structured completion
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Required signer details and consent questions can
                            be collected as part of the same online workflow.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900">
                            Organized records
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Completed information can remain available with
                            the consent record instead of becoming a separate
                            file that needs to be organized manually.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-dark-section">
            <div class="ec-container">
                <span class="ec-kicker light">
                    From invitation to record
                </span>

                <h2>
                    Keep the online signing journey connected.
                </h2>

                <p>
                    The goal is not simply to put a paper form on a screen.
                    A useful online consent workflow connects the information
                    being presented with the person completing it and the
                    record created afterward.
                </p>

                <div class="mt-10 grid gap-5 md:grid-cols-2">
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Present the consent
                        </h3>

                        <p class="mt-2">
                            Use a prepared consent template so the signer sees
                            the information intended for that workflow.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Collect the answers
                        </h3>

                        <p class="mt-2">
                            Ask for signer details and any additional
                            structured information needed before completion.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Capture the signature
                        </h3>

                        <p class="mt-2">
                            Keep the electronic signature within the same
                            consent process instead of handling it separately.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="text-lg font-bold text-white">
                            Keep the result
                        </h3>

                        <p class="mt-2">
                            Store the completed consent so it can be reviewed
                            again when the organization needs the record.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Online or in person
                    </span>

                    <h2>
                        Use the signing method that fits the situation.
                    </h2>

                    <p>
                        Some consent happens remotely and some happens at a
                        physical location. Digital workflows can support both
                        without forcing every signer through the same setting.
                    </p>
                </div>

                <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="ec-kicker">
                            Online consent
                        </span>

                        <h3 class="mt-4 text-xl font-bold text-slate-900">
                            For a signer completing consent remotely
                        </h3>

                        <p class="mt-4 leading-7 text-slate-600">
                            Use an online workflow when the person needs to
                            review and complete their consent away from your
                            physical location.
                        </p>

                        <ul class="mt-5 space-y-3 leading-7 text-slate-600">
                            <li>Digital review of consent information.</li>
                            <li>Structured signer details and questions.</li>
                            <li>Electronic signature.</li>
                            <li>Completed digital consent record.</li>
                        </ul>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <span class="ec-kicker">
                            In-person consent
                        </span>

                        <h3 class="mt-4 text-xl font-bold text-slate-900">
                            For consent collected at your location
                        </h3>

                        <p class="mt-4 leading-7 text-slate-600">
                            Signing stations support situations where people
                            arrive at a shared location and need to complete
                            the consent process there.
                        </p>

                        <ul class="mt-5 space-y-3 leading-7 text-slate-600">
                            <li>Shared physical signing location.</li>
                            <li>Digital consent review.</li>
                            <li>Signer questions and signature.</li>
                            <li>Completed consent record.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        Where online consent helps
                    </span>

                    <h2>
                        Useful when consent needs to happen before, between or
                        away from in-person interactions.
                    </h2>

                    <p>
                        Online forms are particularly useful when the signer
                        and the organization do not need to be in the same
                        place for the consent process to be completed.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Individual consent
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Give one person a focused consent process without
                            preparing a separate paper packet.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Before an in-person visit
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Where the workflow allows it, consent can be
                            completed digitally before the signer arrives at
                            the physical location.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Repeated workflows
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Reuse prepared consent templates instead of
                            rebuilding the same information whenever the same
                            type of consent is collected.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ec-section">
            <div class="ec-container">
                <div class="ec-section-heading">
                    <span class="ec-kicker">
                        After submission
                    </span>

                    <h2>
                        Keep more than just the signature.
                    </h2>

                    <p>
                        A completed online consent workflow can preserve the
                        form information, signer responses and signature as
                        part of the resulting consent record.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Completed record
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Review the consent record together with the
                            information captured during completion.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Signed PDF
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Create a PDF representation of the completed
                            consent when a portable document is needed.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-7">
                        <h3 class="text-xl font-bold text-slate-900">
                            Structured exports
                        </h3>

                        <p class="mt-3 leading-7 text-slate-600">
                            Use available export workflows when selected
                            consent information needs to be reviewed outside
                            the individual record.
                        </p>
                    </article>
                </div>

                <div class="mx-auto mt-8 max-w-3xl space-y-3 text-center leading-7 text-slate-600">
                    <p>
                        Looking for the form itself?
                        <a
                            href="{{ route('electronic-consent-forms') }}"
                            class="font-semibold text-teal-700 underline"
                        >
                            Read about electronic consent forms.
                        </a>
                    </p>

                    <p>
                        Looking for the broader management process?
                        <a
                            href="{{ route('digital-consent-management') }}"
                            class="font-semibold text-teal-700 underline"
                        >
                            Read the digital consent management guide.
                        </a>
                    </p>
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
                        Online consent forms FAQ
                    </h2>
                </div>

                <div class="mx-auto mt-10 max-w-4xl space-y-4">
                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What is an online consent form?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            An online consent form lets a person review consent
                            information, provide required details and complete
                            the signing process digitally rather than using a
                            paper document.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            Can consent be collected remotely?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            eConsent supports digital workflows that allow
                            consent to be completed without requiring every
                            signer to use an in-person signing station.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            Can I still collect consent in person?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            Yes. Signing stations support in-person workflows,
                            while online consent can be used when the signer is
                            completing the process remotely.
                        </p>
                    </details>

                    <details class="rounded-2xl border border-slate-200 bg-white p-6">
                        <summary class="cursor-pointer font-bold text-slate-900">
                            What happens after the online form is signed?
                        </summary>

                        <p class="mt-4 leading-7 text-slate-600">
                            The submitted information becomes part of the
                            completed consent record and can be used through
                            the platform's available record, PDF and export
                            workflows.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <section class="ec-cta">
            <div class="ec-container ec-cta-card">
                <div>
                    <span class="ec-kicker light">
                        Ready to collect consent online?
                    </span>

                    <h2>
                        Give signers a clear digital path from review to
                        completed consent.
                    </h2>

                    <p>
                        Reuse your consent forms, collect information and
                        signatures digitally, and keep the resulting records
                        organized with eConsent.
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
