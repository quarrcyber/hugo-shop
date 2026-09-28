<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_enter_admin_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin')->assertForbidden();
    }

    public function test_staff_can_manage_catalog_but_not_users(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.users.index'))->assertOk();
    }

    public function test_api_admin_routes_apply_the_same_role_matrix(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']), ['shop']);
        $this->getJson('/api/v1/admin/overview')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'staff']), ['shop']);
        $this->getJson('/api/v1/admin/overview')->assertOk();
        $this->getJson('/api/v1/admin/users')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['shop']);
        $this->getJson('/api/v1/admin/users')->assertOk();
    }
}
