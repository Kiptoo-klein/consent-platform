<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case REVIEWER = 'reviewer';
    case WORKFLOW_OPERATOR = 'workflow_operator';
    case DOCUMENT_MANAGER = 'document_manager';
    case ORGANIZATION_ADMINISTRATOR = 'organization_administrator';

    /**
     * Higher roles inherit every capability of lower roles.
     */
    public function level(): int
    {
        return match ($this) {
            self::REVIEWER => 1,
            self::WORKFLOW_OPERATOR => 2,
            self::DOCUMENT_MANAGER => 3,
            self::ORGANIZATION_ADMINISTRATOR => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::REVIEWER => 'Auditor',
            self::WORKFLOW_OPERATOR => 'Staff',
            self::DOCUMENT_MANAGER => 'Consent Manager',
            self::ORGANIZATION_ADMINISTRATOR =>
                'Organization Admin',
        };
    }

    public function example(): string
    {
        return match ($this) {
            self::REVIEWER =>
                'Auditor or Compliance Officer',

            self::WORKFLOW_OPERATOR =>
                'Hospital Receptionist or HR Officer',

            self::DOCUMENT_MANAGER =>
                'Consent Coordinator or HR Manager',

            self::ORGANIZATION_ADMINISTRATOR =>
                'Hospital Administrator or Company Owner',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::REVIEWER =>
                'Can review permitted records, audit history, and download signed PDFs.',

            self::WORKFLOW_OPERATOR =>
                'Can launch kiosks or send signing requests using approved templates.',

            self::DOCUMENT_MANAGER =>
                'Can manage templates, signing workflows, records, and exports.',

            self::ORGANIZATION_ADMINISTRATOR =>
                'Can manage users, roles, branding, settings, and all organization workflows.',
        };
    }

    /**
     * Determine whether this role includes the requested role level.
     */
    public function includes(self $requiredRole): bool
    {
        return $this->level() >= $requiredRole->level();
    }

    /**
     * Roles ordered from highest organization access to lowest.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::ORGANIZATION_ADMINISTRATOR,
            self::DOCUMENT_MANAGER,
            self::WORKFLOW_OPERATOR,
            self::REVIEWER,
        ];
    }
}
