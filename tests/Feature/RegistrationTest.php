<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_redirects_to_the_reviewed_application_options(): void
    {
        $this->get('/register')->assertRedirect(route('website.join'));

        $this->get(route('website.join'))
            ->assertOk()
            ->assertSee('Become a supplier')
            ->assertSee('Become a delivery partner')
            ->assertSee('created by an EduKit administrator');
    }

    public function test_public_registration_cannot_create_an_account(): void
    {
        $this->post('/register', [
            'name' => 'Unapproved User',
            'email' => 'unapproved@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(405);

        $this->assertDatabaseMissing('users', ['email' => 'unapproved@example.com']);
    }
}
