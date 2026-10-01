<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use App\Modules\Tenancy\Models\UserEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Validator;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

/**
 * 2026-10-01 decision: an email must belong to at most one account across
 * the whole country deployment, not just within its own tenant (see
 * docs/decisions.md). The real guarantee is the unique(email) constraint on
 * `users` itself — Postgres enforces it regardless of RLS (see the
 * make_users_email_globally_unique migration). The user_emails registry
 * exists only to give a friendly validation error before that constraint is
 * ever hit; these tests cover both layers.
 */
class GlobalEmailUniquenessTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_user_emails_registry_is_populated_when_a_user_is_created(): void
    {
        $this->assertTrue(UserEmail::where('email', $this->userA->email)->where('user_id', $this->userA->id)->exists());
        $this->assertTrue(UserEmail::where('email', $this->userB->email)->where('user_id', $this->userB->id)->exists());
    }

    public function test_the_database_rejects_a_second_tenants_user_reusing_the_first_tenants_email(): void
    {
        $this->expectException(QueryException::class);

        $this->asTenantOf($this->userB, function () {
            User::factory()->for($this->tenantB)->create(['email' => $this->userA->email]);
        });
    }

    public function test_validation_rule_flags_an_email_already_used_by_another_tenant(): void
    {
        $validator = $this->makeValidator(['email' => $this->userA->email]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validation_rule_allows_an_email_no_one_is_using(): void
    {
        $validator = $this->makeValidator(['email' => 'nobody-yet@example.test']);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_rule_ignores_the_users_own_current_email_on_update(): void
    {
        $validator = ValidatorFacade::make(
            ['email' => $this->userA->email],
            ['email' => [ValidationRule::unique('user_emails', 'email')->ignore($this->userA->id, 'user_id')]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_updating_a_users_email_keeps_the_registry_in_sync(): void
    {
        $oldEmail = $this->userA->email;

        $this->asTenantOf($this->userA, function () {
            $this->userA->update(['email' => 'renamed@example.test']);
        });

        $this->assertFalse(UserEmail::where('email', $oldEmail)->exists());
        $this->assertTrue(UserEmail::where('email', 'renamed@example.test')->where('user_id', $this->userA->id)->exists());
    }

    public function test_deleting_a_user_frees_their_email_for_a_new_account_elsewhere(): void
    {
        $freedEmail = $this->userA->email;

        $this->asTenantOf($this->userA, fn () => $this->userA->delete());

        $this->assertFalse(UserEmail::where('email', $freedEmail)->exists());

        $newUser = $this->asTenantOf($this->userB, fn () => User::factory()->for($this->tenantB)->create(['email' => $freedEmail]));

        $this->assertTrue(UserEmail::where('email', $freedEmail)->where('user_id', $newUser->id)->exists());
    }

    private function makeValidator(array $data): Validator
    {
        return ValidatorFacade::make(
            $data,
            ['email' => [ValidationRule::unique('user_emails', 'email')]],
        );
    }
}
