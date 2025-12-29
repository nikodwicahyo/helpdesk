<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_can_view_the_login_form()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In');
    }

    /** @test */
    public function users_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertRedirect('/user/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function teknisi_is_redirected_to_teknisi_dashboard()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'teknisi',
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertRedirect('/teknisi/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function admin_helpdesk_is_redirected_to_admin_helpdesk_dashboard()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'admin_helpdesk',
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/helpdesk/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function admin_aplikasi_is_redirected_to_admin_aplikasi_dashboard()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'admin_aplikasi',
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/aplikasi/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function users_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    /** @test */
    public function remember_me_functionality_works()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'nip' => $user->nip,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
        
        // Assert cookie is set? Laravel handles this, assertAuthenticated is mostly enough
        $response->assertCookie(Auth::getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
    }
}
