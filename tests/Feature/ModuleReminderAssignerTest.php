<?php

namespace Tests\Feature;

use Mockery;
use App\User;
use App\Module;
use Tests\TestCase;
use App\Http\Helpers\InfusionsoftHelper;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ModuleReminderAssignerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();

        app()->bind(InfusionsoftHelper::class, function ($app) {
            return Mockery::mock(InfusionsoftHelper::class, function ($mock) {
                $mock
                    ->shouldReceive('__construct')
                    ->zeroOrMoreTimes();
                $mock
                    ->shouldReceive('authorize')
                    ->zeroOrMoreTimes()
                    ->andReturn('Success');
                $mock
                    ->shouldReceive('getAllTags')
                    ->zeroOrMoreTimes()
                    ->andReturn([]);
                $mock
                    ->shouldReceive('createContact')
                    ->zeroOrMoreTimes()
                    ->andReturn(true);
                $mock
                    ->shouldReceive('getContact')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(function ($email) {
                        switch ($email) {
                            case 'no_products@test.test':
                                return ['Id' => '11', '_Products' => ''];
                                break;
                            case 'no_records@test.test':
                                return false;
                                break;
                            default:
                                return ['Id' => '21', '_Products' => 'ipa,iea'];
                        }
                    });

                $mock
                    ->shouldReceive('addTag')
                    ->zeroOrMoreTimes()
                    ->andReturn(true);
            });
        });
    }

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
    public function testWithValidEmailButNoRemoteAccount()
    {
        $user = factory(User::class)->create([
            'email' => 'no_records@test.test',
        ]);
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'User has no infusionsoft account']);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailButNoProducts()
    {
        $user = factory(User::class)->create([
            'email' => 'no_products@test.test',
        ]);
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'User has no purchased products']);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailButNoCompletedModules()
    {
        $user = factory(User::class)->create();
        app()->make(InfusionsoftHelper::class)->createContact([
            'Email' => $user->email,
            "_Products" => 'ipa,iea'
        ]);
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Tag submitted successfully: Start IPA Module 1 Reminders']);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailButExpectIpaModule6()
    {
        $user = factory(User::class)->create();
        app()->make(InfusionsoftHelper::class)->createContact([
            'Email' => $user->email,
            "_Products" => 'ipa,iea'
        ]);
        $user->completed_modules()
            ->attach(Module::where('course_key', 'ipa')->limit(3)->get());
        $user->completed_modules()
            ->attach(Module::where('name', 'IPA Module 5')->first());
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Tag submitted successfully: Start IPA Module 6 Reminders']);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailButExpectIeaModule7()
    {
        $user = factory(User::class)->create();
        app()->make(InfusionsoftHelper::class)->createContact([
            'Email' => $user->email,
            "_Products" => 'ipa,iea'
        ]);
        $user->completed_modules()
            ->attach(Module::where('course_key', 'ipa')->limit(3)->get());
        $user->completed_modules()
            ->attach(Module::where('name', 'IPA Module 7')->first());
        $user->completed_modules()
            ->attach(Module::where('name', 'IEA Module 6')->first());
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Tag submitted successfully: Start IEA Module 7 Reminders']);
    }

    /**
     * Testing that Module Reminder Assigner validation is correct;
     *
     * @return void
     */
    public function testWithValidEmailButAllModulesCompleted()
    {
        $user = factory(User::class)->create();
        app()->make(InfusionsoftHelper::class)->createContact([
            'Email' => $user->email,
            "_Products" => 'ipa,iea'
        ]);
        $user->completed_modules()
            ->attach(Module::where('course_key', 'ipa')->get());
        $user->completed_modules()
            ->attach(Module::where('course_key', 'iea')->get());
        $response = $this->post('/api/module_reminder_assigner', ['email' => $user->email]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Tag submitted successfully: Module reminders completed']);
    }
}
