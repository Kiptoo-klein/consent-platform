<?php

namespace App\Enums;

enum OrganizationSubscriptionStatus: string
{
    case EVALUATION = 'evaluation';
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
}
