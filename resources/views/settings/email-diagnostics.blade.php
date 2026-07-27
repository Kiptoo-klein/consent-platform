<x-app-layout>
        <x-slot name="header">
            <div>
                <h2
                    class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100"
                >
                    Email Diagnostics
                </h2>

                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Test email delivery before connecting a branded domain
                </p>
            </div>
        </x-slot>

        @php
            $styles = [
                'pass' =>
                    'border-emerald-200 bg-emerald-50 text-emerald-800',
                'warning' =>
                    'border-amber-200 bg-amber-50 text-amber-800',
                'fail' =>
                    'border-red-200 bg-red-50 text-red-800',
                'deferred' =>
                    'border-blue-200 bg-blue-50 text-blue-800',
            ];

            $labels = [
                'pass' => 'Ready',
                'warning' => 'Review',
                'fail' => 'Missing',
                'deferred' => 'Later',
            ];
        @endphp

        <div class="py-8">
            <div
                class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            >
                @if (session('success'))
                    <div
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800"
                    >
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800"
                    >
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-6 lg:grid-cols-2">
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Current configuration
                        </h3>

                        <dl class="mt-5 space-y-4 text-sm">
                            @foreach ([
                                'Mailer' => $configuration['mailer'],
                                'SMTP host' => $configuration['host'],
                                'SMTP port' => $configuration['port'],
                                'Encryption' => $configuration['encryption'],
                                'Username' => $configuration['username'],
                                'Password' => $configuration['passwordConfigured']
                                    ? 'Configured and hidden'
                                    : 'Not configured',
                                'From address' => $configuration['fromAddress'],
                                'From name' => $configuration['fromName'],
                                'Queue' => $configuration['queue'],
                            ] as $label => $value)
                                <div
                                    class="flex items-start justify-between gap-5 border-b border-gray-100 pb-3 dark:border-gray-800"
                                >
                                    <dt
                                        class="font-medium text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $label }}
                                    </dt>

                                    <dd
                                        class="max-w-xs break-all text-right font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $value }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Send a test email
                        </h3>

                        <p
                            class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"
                        >
                            Use an email address you can check. SMTP passwords are read from the environment and are never displayed here.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('platform.email-diagnostics.test') }}"
                            class="mt-6 space-y-5"
                        >
                            @csrf

                            <div>
                                <label
                                    for="recipient"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    Recipient email
                                </label>

                                <input
                                    id="recipient"
                                    name="recipient"
                                    type="email"
                                    value="{{ old('recipient', auth()->user()->email) }}"
                                    required
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                            </div>

                            <div>
                                <label
                                    for="delivery_mode"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    Delivery method
                                </label>

                                <select
                                    id="delivery_mode"
                                    name="delivery_mode"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="immediate">
                                        Send immediately
                                    </option>

                                    <option value="queued">
                                        Send through queue
                                    </option>
                                </select>
                            </div>

                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="include_attachment"
                                    value="1"
                                    checked
                                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                >

                                <span>
                                    <span
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        Include test attachment
                                    </span>

                                    <span
                                        class="block text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Confirms that signed PDF attachments can be transported.
                                    </span>
                                </span>
                            </label>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Send test email
                            </button>
                        </form>
                    </section>
                </div>

                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div
                        class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3
                            class="font-bold text-gray-900 dark:text-white"
                        >
                            Readiness checks
                        </h3>
                    </div>

                    <div
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        @foreach ($checks as $check)
                            <div
                                class="grid gap-3 px-6 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"
                            >
                                <div>
                                    <p
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $check['label'] }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $check['details'] }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-bold {{ $styles[$check['status']] }}"
                                >
                                    {{ $labels[$check['status']] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-gray-200 bg-gray-950 p-6 text-gray-100 shadow-sm"
                >
                    <h3 class="font-bold">
                        Domain-free SMTP template
                    </h3>

                    <p
                        class="mt-2 text-sm leading-6 text-gray-400"
                    >
                        Use the email address and SMTP credentials supplied by your email provider. Keep the From address identical to the authenticated account during testing.
                    </p>

                    <pre
                        class="mt-5 overflow-x-auto rounded-xl bg-black/40 p-5 text-sm leading-7 text-emerald-300"
                    ><code>MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="${APP_NAME}"</code></pre>
                </section>

                <section
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-6 text-blue-900"
                >
                    <h3 class="font-bold">
                        Completed after purchasing a domain
                    </h3>

                    <p class="mt-2 text-sm leading-6">
                        The remaining production work will be domain verification, a branded From address, SPF, DKIM, DMARC and final deliverability testing.
                    </p>
                </section>
            </div>
        </div>
    </x-app-layout>
