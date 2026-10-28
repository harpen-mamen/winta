<?php

namespace Tests\Feature;

use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    public function test_login_page_is_available_in_indonesian(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Monitoring BOS SMP Sangihe')
            ->assertSee('Masuk');
    }

    public function test_guests_cannot_open_the_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
