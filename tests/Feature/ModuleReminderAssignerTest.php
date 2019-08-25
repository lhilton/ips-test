<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleReminderAssignerTest extends TestCase
{
    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithoutEmail()
    {
        $response = $this->post('/api/module_reminder_assigner');
        $response->assertStatus(422);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithNonEmailValue()
    {
        $response = $this->post('/api/module_reminder_assigner', ['email' => 'test_value']);
        $response->assertStatus(422);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithNonExistingEmailValue()
    {
        $uniqid = uniqid();
        $email = $uniqid . '@' . $uniqid . '.com';
        $response = $this->post('/api/module_reminder_assigner', ['email' => $email]);
        $response->assertStatus(422);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailValue()
    {
        $uniqid = uniqid();
        $user = User::create([
            'name' => 'Test ' . $uniqid,
            'email' => $uniqid . '@phpunit.test',
            'password' => bcrypt($uniqid)
        ]);
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => null]);
    }
}
