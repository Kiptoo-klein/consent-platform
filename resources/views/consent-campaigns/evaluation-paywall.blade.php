<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bulk Consent Campaigns
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Send one consent request to multiple recipients.
            </p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
                <div class="bg-violet-50 px-6 py-6 sm:px-8">
                    <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-violet-800">
                        Paid feature
                    </span>

                    <h1 class="mt-4 text-2xl font-bold text-gray-950">
                        Bulk campaigns are available on paid plans
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-700">
                        Your free evaluation includes individual signing emails
                        so you can test the complete consent workflow. Upgrade
                        when you are ready to send one campaign to multiple
                        recipients.
                    </p>
                </div>

                <div class="space-y-5 px-6 py-6 sm:px-8">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <p class="font-semibold text-gray-950">
                            Free evaluation
                        </p>

                        <p class="mt-1 text-sm leading-6 text-gray-600">
                            Send up to 5 lifetime individual signing emails.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a
                            href="{{ route('organization-subscription-plans.index') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            View subscription plans
                        </a>

                        <a
                            href="{{ route('consent-templates.new') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Send an individual consent
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
