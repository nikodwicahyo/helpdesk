<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;

class RegisterTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function registration_page_is_accessible()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Register');
    }

    /** @test */
    public function users_can_register_with_valid_data()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@kemlu.go.id',
            'nip' => '199001012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'unit_kerja' => 'Pusdatin',
            'jabatan' => 'Pranata Komputer',
            'no_hp' => '081234567890',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@kemlu.go.id',
            'nip' => '199001012020011001',
        ]);

        $response->assertRedirect('/user/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function registration_fails_with_duplicate_nip()
    {
        User::factory()->create(['nip' => '199001012020011001']);

        $response = $this->post('/register', [
            'name' => 'Other User',
            'email' => 'other@kemlu.go.id',
            'nip' => '199001012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    /** @test */
    public function registration_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@kemlu.go.id']);

        $response = $this->post('/register', [
            'name' => 'Other User',
            'email' => 'test@kemlu.go.id',
            'nip' => '199001012020011002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
