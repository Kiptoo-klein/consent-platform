const BULK_RECIPIENT_SELECTOR =
    '[data-bulk-recipient-list]';

function initializeBulkRecipientList(list) {
    if (
        list.dataset
            .bulkRecipientListReady
        === 'true'
    ) {
        return;
    }

    const template =
        document.getElementById(
            list.dataset
                .recipientTemplateId
            ?? 'recipient-row-template'
        );

    const addButton =
        document.getElementById(
            list.dataset
                .addRecipientButtonId
            ?? 'add-recipient'
        );

    const countLabel =
        document.getElementById(
            list.dataset
                .recipientCountId
            ?? 'recipient-count'
        );

    if (
        ! template
        || ! addButton
        || ! countLabel
    ) {
        return;
    }

    const maximum =
        Number(
            list.dataset
                .maximumRecipients
        ) || 20;

    const rows = () =>
        Array.from(
            list.querySelectorAll(
                '[data-recipient-row]'
            )
        );

    const refreshRows = () => {
        const currentRows = rows();

        currentRows.forEach(
            (row, index) => {
                const number =
                    row.querySelector(
                        '[data-recipient-number]'
                    );

                if (number) {
                    number.textContent =
                        String(index + 1);
                }

                row
                    .querySelectorAll(
                        '[data-recipient-field]'
                    )
                    .forEach((field) => {
                        const key =
                            field.dataset
                                .recipientField;

                        field.name =
                            `recipients[${index}][${key}]`;
                    });

                const removeButton =
                    row.querySelector(
                        '.remove-recipient'
                    );

                if (removeButton) {
                    const onlyRow =
                        currentRows.length
                        === 1;

                    removeButton.disabled =
                        onlyRow;

                    removeButton.classList.toggle(
                        'opacity-50',
                        onlyRow
                    );

                    removeButton.classList.toggle(
                        'cursor-not-allowed',
                        onlyRow
                    );
                }
            }
        );

        const count =
            currentRows.length;

        countLabel.textContent =
            `${count} of ${maximum}`;

        const atMaximum =
            count >= maximum;

        addButton.disabled =
            atMaximum;

        addButton.setAttribute(
            'aria-disabled',
            atMaximum
                ? 'true'
                : 'false'
        );

        addButton.classList.toggle(
            'opacity-50',
            atMaximum
        );

        addButton.classList.toggle(
            'cursor-not-allowed',
            atMaximum
        );
    };

    addButton.addEventListener(
        'click',
        (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (
                rows().length
                >= maximum
            ) {
                return;
            }

            const fragment =
                template.content
                    .cloneNode(true);

            /*
             * Append only. Existing rows and form values
             * are never cleared, replaced, reset or submitted.
             */
            list.appendChild(fragment);
            refreshRows();
        }
    );

    list.addEventListener(
        'click',
        (event) => {
            const removeButton =
                event.target.closest(
                    '.remove-recipient'
                );

            if (! removeButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (
                rows().length
                === 1
            ) {
                return;
            }

            removeButton
                .closest(
                    '[data-recipient-row]'
                )
                ?.remove();

            refreshRows();
        }
    );

    list.dataset
        .bulkRecipientListReady =
        'true';

    refreshRows();
}

function initializeBulkRecipientLists() {
    document
        .querySelectorAll(
            BULK_RECIPIENT_SELECTOR
        )
        .forEach(
            initializeBulkRecipientList
        );
}

if (
    document.readyState
    === 'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initializeBulkRecipientLists,
        {
            once: true,
        }
    );
} else {
    initializeBulkRecipientLists();
}
