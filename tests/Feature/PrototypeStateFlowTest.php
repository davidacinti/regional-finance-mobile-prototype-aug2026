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
            ->assertSee('Personal loan');
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

    public function test_delinquency_suppresses_acquisition_content(): void
    {
        $this->post('/prototype/presets/past-due')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Payment past due')
            ->assertDontSee('A new loan option is ready')
            ->assertDontSee('offer-interstitial', false);
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
