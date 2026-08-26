<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrototypeStateFlowTest extends TestCase
{
    public function test_scenario_builder_and_default_dashboard_render(): void
    {
        $this->get('/scenarios')
            ->assertOk()
            ->assertSee('Scenario Builder')
            ->assertSee('Quick presets');

        $this->get('/')
            ->assertOk()
            ->assertSee('Hi, Jordan')
            ->assertSee('Personal loan')
            ->assertSee('Vehicle estimate')
            ->assertDontSee('Track your cars')
            ->assertSee('data-branch-map', false);
    }

    public function test_prequalified_offer_drives_dashboard_and_interstitial(): void
    {
        $this->post('/prototype/presets/prequalified-renewal')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('A new loan option is ready')
            ->assertSee('View my option')
            ->assertSee('role="dialog"', false);
    }

    public function test_builder_changes_and_presets_can_save_without_navigation(): void
    {
        $this->postJson('/prototype/presets/application-progress')
            ->assertOk()
            ->assertJsonPath('state.meta.preset', 'application-progress')
            ->assertJsonPath('state.origination.step', 'verify_income');

        $this->postJson('/prototype/state', [
            'customer' => ['type' => 'former'],
            'loans' => ['count' => 0, 'payment_status' => 'current'],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 688, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk()
            ->assertJsonPath('state.meta.preset', 'custom')
            ->assertJsonPath('state.customer.type', 'former')
            ->assertJsonPath('state.loans.count', 0);
    }

    public function test_explicit_builder_save_redirects_to_rendered_home(): void
    {
        $this->post('/prototype/state', [
            'customer' => ['type' => 'former'],
            'loans' => ['count' => 0, 'payment_status' => 'current'],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 688, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Need funds again?')
            ->assertDontSee('Personal loan');
    }

    public function test_delinquency_suppresses_acquisition_content(): void
    {
        $this->post('/prototype/presets/past-due')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Payment past due')
            ->assertDontSee('A new loan option is ready')
            ->assertDontSee('offer-interstitial', false);
    }

    public function test_pending_payment_persists_across_pages_and_presets_until_reset(): void
    {
        $paymentDate = now()->addDays(2)->toDateString();

        $this->post('/payments', [
            'amount' => '214.00',
            'payment_date' => $paymentDate,
            'account_mode' => 'saved',
            'saved_account' => 'Primary Checking - 4203',
        ])->assertRedirect('/payments/new');

        $this->get('/')
            ->assertOk()
            ->assertSee('Pending payment')
            ->assertSee('$214.00 scheduled');

        $this->get('/loans/1002841')
            ->assertOk()
            ->assertSee('Payment pending')
            ->assertSee('View pending payment');

        $this->postJson('/prototype/presets/prequalified-renewal')
            ->assertOk()
            ->assertJsonPath('state.payments.pending.amount', 214);

        $this->get('/scenarios')
            ->assertOk()
            ->assertSee('This payment remains in the prototype until it is cancelled or the scenario is reset.');

        $this->post('/prototype/reset')->assertRedirect('/scenarios');
        $this->get('/')->assertDontSee('pending-payment-dashboard', false);
    }

    public function test_application_resumes_and_moves_one_step_at_a_time(): void
    {
        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

        $this->get('/applications/62001')
            ->assertOk()
            ->assertSee('Finish verifying your income');

        $this->post('/applications/62001/previous')->assertRedirect('/applications/62001');

        $this->get('/applications/62001')
            ->assertOk()
            ->assertSee('Check your eligibility');
    }

    public function test_offer_marketplace_and_zero_vehicle_state_are_clickable(): void
    {
        $this->get('/offers')
            ->assertOk()
            ->assertSee('Products and options picked for you')
            ->assertSee('Protection & benefits', false);

        $this->post('/prototype/presets/new-customer')->assertRedirect('/');

        $this->get('/assets')
            ->assertOk()
            ->assertSee('Track a vehicle')
            ->assertSee('Add vehicle');
    }

    public function test_new_customer_does_not_assume_credit_bank_or_vehicle_data(): void
    {
        $this->post('/prototype/presets/new-customer')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Financial health')
            ->assertDontSee('<strong>642</strong>', false)
            ->assertDontSee('Track your cars');

        $this->get('/financial-wellness')
            ->assertOk()
            ->assertSee('No score yet')
            ->assertSee('Not available yet')
            ->assertSee('Not connected')
            ->assertDontSee('2,845.20');

        $this->get('/notifications')
            ->assertOk()
            ->assertDontSee('Credit score update');
    }

    public function test_add_vehicle_action_updates_shared_scenario_state(): void
    {
        $this->post('/prototype/presets/new-customer')->assertRedirect('/');

        $this->post('/assets')->assertRedirect('/assets');

        $this->get('/assets')
            ->assertOk()
            ->assertSee('Vehicle added')
            ->assertSee('2021 Toyota Camry')
            ->assertDontSee('Track a vehicle');

        $this->post('/assets')->assertRedirect('/assets');

        $this->get('/assets')
            ->assertOk()
            ->assertSee('2019 Ford F-150');
    }

    public function test_customer_facing_secondary_pages_render_from_shared_state(): void
    {
        foreach ([
            '/loans/1002841',
            '/offers',
            '/protection-benefits',
            '/financial-wellness',
            '/assets',
            '/profile',
            '/documents',
            '/notifications',
            '/settings',
            '/support',
            '/payments/new',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
