<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    Payment Settings
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Configure the payment information shown on issued
                    subscription invoices.
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.billing.index'
                ) }}"
                class="inline-flex w-fit items-center justify-center rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
            >
                Billing Management
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-300 bg-green-50 px-5 py-4 text-sm font-bold text-green-900">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-red-900">
                    <p class="font-bold">
                        The payment settings could not be saved.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-6 text-amber-950 shadow-sm">
                <h2 class="font-extrabold">
                    Settings are copied when an invoice is issued
                </h2>

                <p class="mt-2 text-sm font-semibold leading-6">
                    Updating these details later will not silently change
                    the payment instructions already attached to an older
                    issued invoice.
                </p>
            </section>

            <form
                method="POST"
                action="{{ route(
                    'platform.subscription-payment-settings.update'
                ) }}"
                class="space-y-6"
            >
                @csrf
                @method('PATCH')

                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                    <div class="flex items-start gap-3">
                        <input
                            type="hidden"
                            name="mpesa_enabled"
                            value="0"
                        >

                        <input
                            id="mpesa_enabled"
                            name="mpesa_enabled"
                            type="checkbox"
                            value="1"
                            @checked(
                                (string) old(
                                    'mpesa_enabled',
                                    $settings[
                                        'mpesa_enabled'
                                    ]
                                        ? '1'
                                        : '0'
                                ) === '1'
                            )
                            class="mt-1 rounded border-gray-300 text-teal-700 shadow-sm focus:ring-teal-600"
                        >

                        <div>
                            <label
                                for="mpesa_enabled"
                                class="text-lg font-extrabold text-gray-950 dark:text-white"
                            >
                                Enable M-Pesa payments
                            </label>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Show M-Pesa details on newly issued invoices.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label
                                for="mpesa_type"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                M-Pesa payment type
                            </label>

                            <select
                                id="mpesa_type"
                                name="mpesa_type"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">
                                    Select payment type
                                </option>

                                <option
                                    value="paybill"
                                    @selected(
                                        old(
                                            'mpesa_type',
                                            $settings[
                                                'mpesa_type'
                                            ]
                                        ) === 'paybill'
                                    )
                                >
                                    Paybill
                                </option>

                                <option
                                    value="till"
                                    @selected(
                                        old(
                                            'mpesa_type',
                                            $settings[
                                                'mpesa_type'
                                            ]
                                        ) === 'till'
                                    )
                                >
                                    Till number
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="mpesa_business_number"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Paybill or Till number
                            </label>

                            <input
                                id="mpesa_business_number"
                                name="mpesa_business_number"
                                type="text"
                                maxlength="50"
                                value="{{ old(
                                    'mpesa_business_number',
                                    $settings[
                                        'mpesa_business_number'
                                    ]
                                ) }}"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="mpesa_account_reference_instructions"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Account reference instructions
                            </label>

                            <textarea
                                id="mpesa_account_reference_instructions"
                                name="mpesa_account_reference_instructions"
                                rows="3"
                                maxlength="5000"
                                placeholder="For example: Use the invoice number as the account reference."
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old(
                                'mpesa_account_reference_instructions',
                                $settings[
                                    'mpesa_account_reference_instructions'
                                ]
                            ) }}</textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="mpesa_instructions"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Additional M-Pesa instructions
                            </label>

                            <textarea
                                id="mpesa_instructions"
                                name="mpesa_instructions"
                                rows="3"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old(
                                'mpesa_instructions',
                                $settings[
                                    'mpesa_instructions'
                                ]
                            ) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                    <div class="flex items-start gap-3">
                        <input
                            type="hidden"
                            name="bank_enabled"
                            value="0"
                        >

                        <input
                            id="bank_enabled"
                            name="bank_enabled"
                            type="checkbox"
                            value="1"
                            @checked(
                                (string) old(
                                    'bank_enabled',
                                    $settings[
                                        'bank_enabled'
                                    ]
                                        ? '1'
                                        : '0'
                                ) === '1'
                            )
                            class="mt-1 rounded border-gray-300 text-indigo-700 shadow-sm focus:ring-indigo-600"
                        >

                        <div>
                            <label
                                for="bank_enabled"
                                class="text-lg font-extrabold text-gray-950 dark:text-white"
                            >
                                Enable bank-transfer payments
                            </label>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Show bank details on newly issued invoices.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        @foreach ([
                            [
                                'name' => 'bank_name',
                                'label' => 'Bank name',
                            ],
                            [
                                'name' => 'bank_account_name',
                                'label' => 'Account name',
                            ],
                            [
                                'name' => 'bank_account_number',
                                'label' => 'Account number',
                            ],
                            [
                                'name' => 'bank_branch',
                                'label' => 'Branch',
                            ],
                            [
                                'name' => 'bank_swift_code',
                                'label' => 'SWIFT / BIC code',
                            ],
                        ] as $field)
                            <div>
                                <label
                                    for="{{ $field['name'] }}"
                                    class="block text-sm font-bold text-gray-900 dark:text-white"
                                >
                                    {{ $field['label'] }}
                                </label>

                                <input
                                    id="{{ $field['name'] }}"
                                    name="{{ $field['name'] }}"
                                    type="text"
                                    value="{{ old(
                                        $field['name'],
                                        $settings[
                                            $field['name']
                                        ]
                                    ) }}"
                                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-700 focus:ring-indigo-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                            </div>
                        @endforeach

                        <div class="sm:col-span-2">
                            <label
                                for="bank_reference_instructions"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Transfer reference instructions
                            </label>

                            <textarea
                                id="bank_reference_instructions"
                                name="bank_reference_instructions"
                                rows="3"
                                maxlength="5000"
                                placeholder="For example: Include the invoice number in the transfer reference."
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-700 focus:ring-indigo-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old(
                                'bank_reference_instructions',
                                $settings[
                                    'bank_reference_instructions'
                                ]
                            ) }}</textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="bank_instructions"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Additional bank instructions
                            </label>

                            <textarea
                                id="bank_instructions"
                                name="bank_instructions"
                                rows="3"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-700 focus:ring-indigo-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old(
                                'bank_instructions',
                                $settings[
                                    'bank_instructions'
                                ]
                            ) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                    <h2 class="text-lg font-extrabold text-gray-950 dark:text-white">
                        Billing contact and general instructions
                    </h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label
                                for="billing_contact_email"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Billing email
                            </label>

                            <input
                                id="billing_contact_email"
                                name="billing_contact_email"
                                type="email"
                                value="{{ old(
                                    'billing_contact_email',
                                    $settings[
                                        'billing_contact_email'
                                    ]
                                ) }}"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div>
                            <label
                                for="billing_contact_phone"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Billing phone
                            </label>

                            <input
                                id="billing_contact_phone"
                                name="billing_contact_phone"
                                type="text"
                                maxlength="50"
                                value="{{ old(
                                    'billing_contact_phone',
                                    $settings[
                                        'billing_contact_phone'
                                    ]
                                ) }}"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="additional_instructions"
                                class="block text-sm font-bold text-gray-900 dark:text-white"
                            >
                                Additional payment instructions
                            </label>

                            <textarea
                                id="additional_instructions"
                                name="additional_instructions"
                                rows="4"
                                maxlength="10000"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ old(
                                'additional_instructions',
                                $settings[
                                    'additional_instructions'
                                ]
                            ) }}</textarea>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl border px-6 py-3 text-sm font-extrabold shadow-sm transition hover:opacity-90"
                        style="background-color:#0f766e !important;color:#ffffff !important;border-color:#115e59 !important;"
                    >
                        Save Payment Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
