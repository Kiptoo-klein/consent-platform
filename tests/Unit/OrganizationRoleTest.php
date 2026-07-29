<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use PHPUnit\Framework\TestCase;

class OrganizationRoleTest extends TestCase
{
    public function test_it_defines_the_organization_role_hierarchy(): void
    {
        $this->assertSame(1, OrganizationRole::REVIEWER->level());
        $this->assertSame(
            2,
            OrganizationRole::WORKFLOW_OPERATOR->level()
        );
        $this->assertSame(
            3,
            OrganizationRole::DOCUMENT_MANAGER->level()
        );
        $this->assertSame(
            4,
            OrganizationRole::ORGANIZATION_ADMINISTRATOR->level()
        );
    }

    public function test_higher_roles_include_lower_roles(): void
    {
        $administrator =
            OrganizationRole::ORGANIZATION_ADMINISTRATOR;

        $this->assertTrue(
            $administrator->includes(
                OrganizationRole::DOCUMENT_MANAGER
            )
        );

        $this->assertTrue(
            $administrator->includes(
                OrganizationRole::WORKFLOW_OPERATOR
            )
        );

        $this->assertTrue(
            $administrator->includes(
                OrganizationRole::REVIEWER
            )
        );

        $this->assertTrue(
            OrganizationRole::DOCUMENT_MANAGER->includes(
                OrganizationRole::WORKFLOW_OPERATOR
            )
        );

        $this->assertTrue(
            OrganizationRole::WORKFLOW_OPERATOR->includes(
                OrganizationRole::REVIEWER
            )
        );
    }

    public function test_lower_roles_do_not_include_higher_roles(): void
    {
        $this->assertFalse(
            OrganizationRole::REVIEWER->includes(
                OrganizationRole::WORKFLOW_OPERATOR
            )
        );

        $this->assertFalse(
            OrganizationRole::WORKFLOW_OPERATOR->includes(
                OrganizationRole::DOCUMENT_MANAGER
            )
        );

        $this->assertFalse(
            OrganizationRole::DOCUMENT_MANAGER->includes(
                OrganizationRole::ORGANIZATION_ADMINISTRATOR
            )
        );
    }

    public function test_each_role_has_clear_organization_facing_metadata(): void
    {
        foreach (OrganizationRole::cases() as $role) {
            $this->assertNotSame('', $role->label());
            $this->assertNotSame('', $role->example());
            $this->assertNotSame('', $role->description());
        }

        $this->assertSame(
            'Hospital Receptionist or HR Officer',
            OrganizationRole::WORKFLOW_OPERATOR->example()
        );

        $this->assertSame(
            'Auditor or Compliance Officer',
            OrganizationRole::REVIEWER->example()
        );
    }

    public function test_role_labels_preserve_existing_names(): void
    {
        $this->assertSame(
            'Auditor',
            OrganizationRole::REVIEWER->label()
        );

        $this->assertSame(
            'Staff',
            OrganizationRole::WORKFLOW_OPERATOR->label()
        );

        $this->assertSame(
            'Consent Manager',
            OrganizationRole::DOCUMENT_MANAGER->label()
        );

        $this->assertSame(
            'Organization Admin',
            OrganizationRole::ORGANIZATION_ADMINISTRATOR->label()
        );
    }

    public function test_roles_are_ordered_from_highest_to_lowest(): void
    {
        $this->assertSame(
            [
                OrganizationRole::ORGANIZATION_ADMINISTRATOR,
                OrganizationRole::DOCUMENT_MANAGER,
                OrganizationRole::WORKFLOW_OPERATOR,
                OrganizationRole::REVIEWER,
            ],
            OrganizationRole::ordered()
        );
    }

    public function test_platform_administrator_is_not_an_organization_role(): void
    {
        $values = array_map(
            static fn (OrganizationRole $role): string =>
                $role->value,
            OrganizationRole::cases()
        );

        $this->assertNotContains('platform_administrator', $values);
        $this->assertCount(4, $values);
    }
}
