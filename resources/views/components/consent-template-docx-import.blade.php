<div
    id="word-import"
    class="mb-8"
    x-data="{
        dragging: false,
        importing: false,
        error: '',
        success: '',
        warnings: [],

        async importDocument(file) {
            this.error = '';
            this.success = '';
            this.warnings = [];

            if (! file) {
                return;
            }

            if (
                ! file.name
                    .toLowerCase()
                    .endsWith('.docx')
            ) {
                this.error =
                    'Only Microsoft Word .docx files are supported.';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                this.error =
                    'The Word document must not exceed 10 MB.';
                return;
            }

            const content =
                document.getElementById(
                    'content'
                );

            if (! content) {
                this.error =
                    'The consent text field could not be found.';
                return;
            }

            if (
                content.value.trim() !== ''
                && ! window.confirm(
                    'Replace the current consent text with the imported Word document?'
                )
            ) {
                return;
            }

            const formData =
                new FormData();

            formData.append(
                'document',
                file
            );

            const csrfToken =
                this.$root
                    .closest('form')
                    ?.querySelector(
                        'input[name=&quot;_token&quot;]'
                    )
                    ?.value
                || document
                    .querySelector(
                        'meta[name=&quot;csrf-token&quot;]'
                    )
                    ?.getAttribute(
                        'content'
                    );

            if (! csrfToken) {
                this.error =
                    'The security token could not be found. Refresh the page and try again.';
                return;
            }

            this.importing = true;

            try {
                const response = await fetch(
                    @js(
                        route(
                            'consent-templates.import-docx'
                        )
                    ),
                    {
                        method: 'POST',
                        headers: {
                            Accept:
                                'application/json',
                            'X-CSRF-TOKEN':
                                csrfToken,
                        },
                        body: formData,
                    }
                );

                const data =
                    await response.json();

                if (! response.ok) {
                    const validationError =
                        data.errors
                        && data.errors.document
                        && data.errors.document[0];

                    throw new Error(
                        validationError
                        || data.message
                        || 'The Word document could not be imported.'
                    );
                }

                content.value =
                    data.content;

                content.dispatchEvent(
                    new Event(
                        'input',
                        {
                            bubbles: true,
                        }
                    )
                );

                const title =
                    document.getElementById(
                        'title'
                    );

                if (
                    title
                    && title.value.trim() === ''
                    && data.suggested_title
                ) {
                    title.value =
                        data.suggested_title;

                    title.dispatchEvent(
                        new Event(
                            'input',
                            {
                                bubbles: true,
                            }
                        )
                    );
                }

                this.warnings =
                    data.warnings || [];

                this.success =
                    `${data.filename} imported successfully (${data.character_count.toLocaleString()} characters). Review the text before saving.`;
            } catch (error) {
                this.error =
                    error.message
                    || 'The Word document could not be imported.';
            } finally {
                this.importing = false;

                if (this.$refs.docxFile) {
                    this.$refs.docxFile.value =
                        '';
                }
            }
        },
    }"
>
    <label class="block font-semibold mb-2">
        Import from Word
    </label>

    <input
        x-ref="docxFile"
        type="file"
        class="hidden"
        accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        @change="importDocument(
            $event.target.files[0]
        )"
    >

    <button
        type="button"
        class="flex w-full flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition"
        :class="dragging
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-300 bg-gray-50 hover:border-blue-400 hover:bg-blue-50/50'"
        :disabled="importing"
        @click="$refs.docxFile.click()"
        @dragenter.prevent="dragging = true"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="
            dragging = false;
            importDocument(
                $event.dataTransfer.files[0]
            );
        "
    >
        <span class="text-base font-semibold text-gray-900">
            Drop a Word document here
        </span>

        <span class="mt-1 text-sm text-gray-600">
            or click to select a .docx file
        </span>

        <span class="mt-2 text-xs text-gray-500">
            Maximum 10 MB. The document is converted
            to editable consent text and is not retained.
        </span>

        <span
            x-show="importing"
            x-cloak
            class="mt-3 text-sm font-semibold text-blue-700"
        >
            Importing document…
        </span>
    </button>

    <div
        x-show="success"
        x-cloak
        class="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
        x-text="success"
    ></div>

    <div
        x-show="error"
        x-cloak
        class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        x-text="error"
    ></div>

    <ul
        x-show="warnings.length > 0"
        x-cloak
        class="mt-3 list-disc space-y-1 rounded-lg border border-amber-200 bg-amber-50 px-8 py-3 text-sm text-amber-900"
    >
        <template
            x-for="warning in warnings"
            :key="warning"
        >
            <li x-text="warning"></li>
        </template>
    </ul>
</div>
