function initializeBulkConsentDeadline() {
    const dateInput =
        document.getElementById(
            'expires_date'
        );

    const timeInput =
        document.getElementById(
            'expires_time'
        );

    if (
        ! dateInput
        || ! timeInput
    ) {
        return;
    }

    const synchronizeTimeField = () => {
        const hasDate =
            dateInput.value.trim() !== '';

        timeInput.disabled =
            ! hasDate;

        timeInput.setAttribute(
            'aria-disabled',
            hasDate
                ? 'false'
                : 'true'
        );

        timeInput.classList.toggle(
            'cursor-not-allowed',
            ! hasDate
        );

        timeInput.classList.toggle(
            'bg-gray-100',
            ! hasDate
        );

        timeInput.classList.toggle(
            'text-gray-400',
            ! hasDate
        );

        if (! hasDate) {
            /*
             * A time without a date is not a valid deadline.
             * Clear it so the optional deadline can be omitted.
             */
            timeInput.value = '';
        }
    };

    dateInput.addEventListener(
        'change',
        synchronizeTimeField
    );

    dateInput.addEventListener(
        'input',
        synchronizeTimeField
    );

    synchronizeTimeField();
}

if (
    document.readyState
    === 'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initializeBulkConsentDeadline,
        {
            once: true,
        }
    );
} else {
    initializeBulkConsentDeadline();
}
