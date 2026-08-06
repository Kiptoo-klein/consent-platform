@if ($consentSession->signing_station_id !== null)
    @php
        $deliveryFeatureReady =
            $consentPdfDeliveryFeatureReady ?? false;

        $delivery = $consentPdfDelivery ?? null;

        /*
         * ConsentSession may expose PDF timestamps as raw database strings
         * when the model does not cast them to datetime. This formatter
         * safely supports strings, Carbon instances and other DateTime values.
         */
        $formatDeliveryDate = static function (
            mixed $value,
            string $fallback
        ): string {
            if (blank($value)) {
                return $fallback;
            }

            try {
                if ($value instanceof \DateTimeInterface) {
                    return \App\Support\DisplayTime::format($value, 'd/m/y H:i', (string) $value);
                }

                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d/m/y H:i');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $pdfReady =
            filled($consentSession->pdf_path)
            && filled($consentSession->pdf_generated_at);

        $recipient = trim(
            (string) $consentSession->signer_email
        );

        $hasValidRecipient =
            filter_var($recipient, FILTER_VALIDATE_EMAIL)
            !== false;

        $recordCompleted = $consentSession->isCompleted();

        $pdfStatusLabel = match (true) {
            ! $recordCompleted => 'Not started',
            $pdfReady => 'Ready',
            default => 'Generating',
        };

        $pdfStatusClasses = match ($pdfStatusLabel) {
            'Ready' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'Generating' => 'bg-amber-100 text-amber-800 ring-amber-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };

        $emailStatusLabel = match (true) {
            ! $deliveryFeatureReady => 'Setup incomplete',
            ! $recordCompleted => 'Not started',
            ! $hasValidRecipient => 'Skipped',
            $delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_SENT => 'Sent',
            $delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_FAILED => 'Failed',
            $delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_PROCESSING => 'Processing',
            $delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_PENDING => 'Queued',
            $pdfReady => 'Waiting',
            default => 'Waiting for PDF',
        };

        $emailStatusClasses = match ($emailStatusLabel) {
            'Sent' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'Failed' => 'bg-red-100 text-red-800 ring-red-200',
            'Processing', 'Queued', 'Waiting', 'Waiting for PDF' =>
                'bg-amber-100 text-amber-800 ring-amber-200',
            'Skipped' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'Setup incomplete' => 'bg-red-100 text-red-800 ring-red-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };

        $canRetryDelivery =
            $deliveryFeatureReady
            && $recordCompleted
            && $pdfReady
            && $hasValidRecipient
            && $delivery?->status
                !== \App\Models\ConsentPdfDelivery::STATUS_PROCESSING;

        $retryButtonLabel = match (true) {
            $delivery?->sent_at !== null => 'Send another copy',
            $delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_FAILED => 'Retry delivery',
            default => 'Send PDF copy',
        };
    @endphp

    <section
        id="signed-pdf-delivery-panel"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                        Public Signing Station
                    </p>

                    <h2 class="mt-1 text-lg font-bold text-gray-950">
                        Signed PDF delivery
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-gray-600">
                        Tracks generation of the stored signed PDF and delivery of a copy to the signer.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $pdfStatusClasses }}">
                        PDF: {{ $pdfStatusLabel }}
                    </span>

                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $emailStatusClasses }}">
                        Email: {{ $emailStatusLabel }}
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Recipient
                    </dt>

                    <dd class="mt-2 break-words text-sm font-semibold text-gray-900">
                        {{ $hasValidRecipient ? $recipient : 'Not provided' }}
                    </dd>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        PDF generated
                    </dt>

                    <dd class="mt-2 text-sm font-semibold text-gray-900">
                        {{ $formatDeliveryDate($consentSession->pdf_generated_at, 'Not yet') }}
                    </dd>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Delivery attempts
                    </dt>

                    <dd class="mt-2 text-sm font-semibold text-gray-900">
                        {{ $delivery?->attempts ?? 0 }}
                    </dd>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Last successful delivery
                    </dt>

                    <dd class="mt-2 text-sm font-semibold text-gray-900">
                        {{ $formatDeliveryDate($delivery?->sent_at, 'Not sent') }}
                    </dd>
                </div>
            </dl>

            @if (! $deliveryFeatureReady)
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Delivery tracking is not ready. Run
                    <code class="rounded bg-red-100 px-1.5 py-0.5">php artisan migrate</code>
                    and refresh this record.
                </div>
            @elseif (! $hasValidRecipient)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    The signed PDF was not emailed because the signer did not provide a valid email address.
                </div>
            @elseif (! $recordCompleted)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    PDF generation and delivery begin after the signer completes the consent.
                </div>
            @elseif (! $pdfReady)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    The record is complete, but the signed PDF is still being generated. The queue worker must be running.
                </div>
            @endif

            @if ($delivery?->status === \App\Models\ConsentPdfDelivery::STATUS_PROCESSING)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Delivery is currently being processed. Refresh this page shortly to see the result.
                </div>
            @endif

            @if ($delivery?->last_error)
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-bold text-red-900">
                        Last delivery error
                    </p>

                    <p class="mt-2 break-words text-sm leading-6 text-red-800">
                        {{ $delivery->last_error }}
                    </p>

                    @if ($delivery->failed_at)
                        <p class="mt-2 text-xs text-red-700">
                            Failed {{ $formatDeliveryDate($delivery->failed_at, 'Unknown time') }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-gray-500">
                    @if (config('mail.default') === 'log')
                        Development mode is using the log mailer. “Sent” means the email was written to the application log, not delivered to an inbox.
                    @else
                        Delivery uses the platform mail service configured in the application environment.
                    @endif
                </p>

                @if ($canRetryDelivery)
                    <form
                        method="POST"
                        action="{{ route(
                            'consent-sessions.signed-pdf.retry',
                            $consentSession
                        ) }}"
                        class="shrink-0"
                        x-data
                        data-signed-pdf-delivery-confirmation
                    >
                        @csrf

                        <button
                            type="button"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 sm:w-auto"
                            x-on:click="$dispatch(
                                'open-modal',
                                'signed-pdf-delivery-{{ $consentSession->id }}'
                            )"
                        >
                            {{ $retryButtonLabel }}
                        </button>

                        <x-action-confirmation-modal
                            name="signed-pdf-delivery-{{ $consentSession->id }}"
                            :title="$delivery?->sent_at
                                ? 'Send another signed PDF copy?'
                                : 'Queue signed PDF delivery?'"
                            :message="$delivery?->sent_at
                                ? 'Another signed PDF copy will be queued for delivery to this signer.'
                                : 'The completed signed PDF will be queued for email delivery to this signer.'"
                            :confirm-text="$delivery?->sent_at
                                ? 'Send another copy'
                                : 'Queue PDF delivery'"
                            variant="success"
                        />
                    </form>
                @endif
            </div>
        </div>
    </section>
@endif
