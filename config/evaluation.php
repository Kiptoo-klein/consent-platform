<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free Evaluation Workspace Limits
    |--------------------------------------------------------------------------
    |
    | These limits are independent of the paid Basic plan. Changing a paid
    | subscription plan must never silently alter the free evaluation offer.
    |
    */

    'limits' => [
        'users' => 2,

        /*
         * Only one additional organization user can exist alongside the
         * Organization Admin. Each non-admin role is therefore capped at one.
         */
        'consent_managers' => 1,
        'staff' => 1,
        'auditors' => 1,

        'active_kiosks' => 1,
        'consent_templates' => 5,
        'signed_consents' => 5,

        /*
         * Invitation-email enforcement is added separately in Stage 2B.
         */
        'invitation_emails' => 5,
    ],

];
