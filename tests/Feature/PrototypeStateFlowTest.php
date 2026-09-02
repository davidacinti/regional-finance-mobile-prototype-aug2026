<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrototypeStateFlowTest extends TestCase
{
    public function test_lendingtree_lite_entry_authenticates_into_restricted_application_dashboard(): void
    {
        $this->post('/prototype/presets/lendingtree-prequalified')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Your prequalified loan offers')
            ->assertSee('Regional Finance')
            ->assertSee('$8,500');

        $this->post('/lite/select-offer')->assertRedirect('/');
        $this->get('/')->assertSee('Verify your phone');
        $this->post('/lite/send-code')->assertRedirect('/');
        $this->get('/')->assertSee('Enter verification code');
        $this->post('/lite/verify-code')->assertRedirect('/');
        $this->get('/')
            ->assertSee('Complete your application')
            ->assertSee('Authorize credit review')
            ->assertSee('hard credit inquiry');
        $this->post('/lite/continue-offer')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome, Michael')
            ->assertSee('Verify your income')
            ->assertSee('data-nav-item="home"', false)
            ->assertSee('data-nav-item="application"', false)
            ->assertDontSee('data-nav-item="explore"', false)
            ->assertDontSee('data-nav-item="wellness"', false)
            ->assertDontSee('data-nav-item="loan"', false);

        $this->get('/profile')->assertRedirect('/');
        $this->get('/payments/new')->assertRedirect('/');
        $this->get('/documents')->assertRedirect('/');
    }

    public function test_lite_application_tasks_progress_through_greenville_closing(): void
    {
        $this->post('/prototype/presets/lendingtree-prequalified');
        $this->post('/lite/select-offer');
        $this->post('/lite/send-code');
        $this->post('/lite/verify-code');
        $this->post('/lite/continue-offer');

        $this->get('/applications/62001/income')
            ->assertOk()
            ->assertSee('Take photo')
            ->assertSee('Upload file');
        $this->post('/applications/62001/income', ['income_document' => 'paystub.pdf'])->assertRedirect('/');
        $this->get('/')->assertSee('Upload vehicle photos')->assertSee('Income verification');

        $this->get('/applications/62001/vehicle-photos')
            ->assertOk()
            ->assertSee('Passenger side')
            ->assertSee('VIN / dashboard');
        $this->post('/applications/62001/vehicle-photos')->assertRedirect('/');
        $this->get('/')->assertSee('Schedule your closing');

        $this->get('/applications/62001/closing')
            ->assertOk()
            ->assertSee('Greenville Branch')
            ->assertSee('1450 Woodruff Rd')
            ->assertDontSee('Danbury');
        $this->post('/applications/62001/closing', [
            'appointment_date' => '2026-09-10',
            'appointment_time' => '10:30 AM',
        ])->assertRedirect('/');

        $this->get('/')
            ->assertSee('Closing scheduled')
            ->assertSee('Greenville Branch')
            ->assertSee('View appointment')
            ->assertDontSee('Danbury');
        $this->get('/applications/62001/closing')
            ->assertSee("You're scheduled", false)
            ->assertSee('Thursday, September 10')
            ->assertSee('Greenville Branch');
    }

    public function test_scenario_builder_can_jump_to_any_origination_lite_stage(): void
    {
        $this->get('/scenarios')
            ->assertOk()
            ->assertSee('New customer - LendingTree')
            ->assertSee('Origination lite - LendingTree')
            ->assertSee('Dashboard - closing required');

        $this->postJson('/prototype/state', [
            'experience' => ['mode' => 'origination_lite'],
            'lite' => ['stage' => 'closing_ready', 'prequalified_amount' => 12000],
        ])->assertOk()
            ->assertJsonPath('state.experience.mode', 'origination_lite')
            ->assertJsonPath('state.loans.count', 0)
            ->assertJsonPath('state.products.savings', false)
            ->assertJsonPath('state.products.credit_card', false);

        $this->get('/')
            ->assertSee('$12,000')
            ->assertSee('Schedule your closing')
            ->assertSee('data-nav-item="application"', false);
    }

    public function test_password_setup_graduates_lite_customer_into_full_authenticated_app_without_losing_progress(): void
    {
        $this->post('/prototype/presets/lendingtree-prequalified');
        $this->post('/lite/select-offer');
        $this->post('/lite/send-code');
        $this->post('/lite/verify-code');
        $this->post('/lite/continue-offer');

        $this->post('/account/set-password')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Hi, Michael')
            ->assertSee('data-menu-toggle', false)
            ->assertSee('View profile')
            ->assertSee('Document Center')
            ->assertSee('Settings')
            ->assertSee('Support')
            ->assertSee('Verify your income')
            ->assertSee('Upload income document')
            ->assertSee('/applications/62001/income', false)
            ->assertDontSee('lite-dashboard', false)
            ->assertDontSee('Save your account');

        $this->get('/profile')->assertOk()->assertSee('Michael Reed');
        $this->get('/applications/62001')->assertRedirect('/applications/62001/income');
        $this->get('/applications/62001/income')->assertOk()->assertSee('Verify your income');

        $this->post('/applications/62001/income', ['income_document' => 'paystub.pdf'])->assertRedirect('/');
        $this->get('/')
            ->assertSee('Hi, Michael')
            ->assertSee('Upload vehicle photos')
            ->assertSee('data-menu-toggle', false)
            ->assertDontSee('lite-dashboard', false);
    }

    public function test_scenario_builder_and_default_dashboard_render(): void
    {
        $this->get('/scenarios')
            ->assertOk()
            ->assertSee('Scenario Builder')
            ->assertSee('Quick presets')
            ->assertSee('Savings account')
            ->assertSee('Credit card')
            ->assertDontSee('/prototype/presets/former-borrower', false)
            ->assertDontSee('/prototype/presets/new-customer', false)
            ->assertDontSee('name="customer[type]"', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('AI Assistant')
            ->assertDontSee('<span>Notifications</span>', false)
            ->assertSee('Hi, Jordan')
            ->assertSee('Personal loan')
            ->assertSee('Vehicle estimate')
            ->assertSee('assets/img/illustrations/fleet-car.png', false)
            ->assertSee('ti-car', false)
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
            ->assertJsonPath('state.origination.step', 'application_started');

        $this->postJson('/prototype/state', [
            'loans' => ['count' => 0, 'payment_status' => 'current'],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 688, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk()
            ->assertJsonPath('state.meta.preset', 'custom')
            ->assertJsonMissingPath('state.customer.type')
            ->assertJsonPath('state.loans.count', 0);
    }

    public function test_explicit_builder_save_redirects_to_rendered_home(): void
    {
        $this->post('/prototype/state', [
            'loans' => ['count' => 0, 'payment_status' => 'current'],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 688, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('See what you qualify for')
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

    public function test_compact_delinquent_loan_keeps_urgent_status_on_the_product_card(): void
    {
        $this->postJson('/prototype/state', [
            'loans' => ['count' => 1, 'payment_status' => 'past_due_30'],
            'products' => ['savings' => true, 'credit_card' => false],
            'offer' => ['type' => 'none'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 642, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('account-product-card compact loan-product past-due', false)
            ->assertSee('Past due')
            ->assertSee('$428.00');
    }

    public function test_selecting_a_preset_resets_transient_state_and_loads_that_experience(): void
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
            ->assertJsonPath('state.meta.preset', 'prequalified-renewal')
            ->assertJsonPath('state.payments.pending', null);

        $this->get('/')
            ->assertOk()
            ->assertSee('A new loan option is ready')
            ->assertDontSee('pending-payment-dashboard', false);
    }

    public function test_standard_application_progresses_to_pending_funding(): void
    {
        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

        $this->get('/applications/62001')
            ->assertOk()
            ->assertSee('Personal loans up to')
            ->assertSee('$25,000');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/applications/62001')->assertSee('Confirm your information');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/applications/62001')->assertSee('Pre-qualify with no score impact');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/')->assertSee('Your loan options are ready');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/applications/62001')->assertSee('Verify your income');

        foreach (['Where should we send your funds?', 'Review and sign', "You're good to go"] as $headline) {
            $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
            $this->get('/applications/62001')->assertSee($headline);
        }

        $this->post('/applications/62001/advance')->assertRedirect('/');
        $this->get('/')
            ->assertSee('pending-funding-product', false)
            ->assertSee('Funding')
            ->assertSee('$3,500.00');
    }

    public function test_prequalified_application_uses_hard_pull_and_skips_income(): void
    {
        $this->post('/prototype/presets/prequalified-renewal')->assertRedirect('/');
        $this->post('/applications/start')->assertRedirect('/applications/62001');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/applications/62001')
            ->assertSee('hard credit inquiry')
            ->assertSee('complete this application');

        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->post('/applications/62001/advance')->assertRedirect('/applications/62001');

        $this->get('/applications/62001')
            ->assertSee('Where should we send your funds?')
            ->assertDontSee('Verify your income');
    }

    public function test_application_transition_urls_are_safe_when_opened_directly(): void
    {
        $this->get('/applications/62001/advance')->assertRedirect('/applications/62001');
        $this->get('/applications/62001/previous')->assertRedirect('/applications/62001');
    }

    public function test_application_and_product_state_control_bottom_navigation(): void
    {
        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('data-nav-item="home"', false)
            ->assertSee('data-nav-item="application"', false)
            ->assertDontSee('data-nav-item="explore"', false)
            ->assertDontSee('data-nav-item="wellness"', false);

        $this->postJson('/prototype/state', [
            'loans' => ['count' => 1, 'payment_status' => 'current'],
            'products' => ['savings' => true, 'credit_card' => true],
            'offer' => ['type' => 'none'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 642, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 0],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk();

        $this->get('/products/savings')
            ->assertOk()
            ->assertSee('data-nav-item="loan"', false)
            ->assertSee('data-nav-item="savings"', false)
            ->assertSee('data-nav-item="credit-card"', false)
            ->assertDontSee('data-nav-item="explore"', false)
            ->assertDontSee('data-nav-item="wellness"', false);
    }

    public function test_offer_marketplace_and_zero_vehicle_state_are_clickable(): void
    {
        $this->get('/offers')
            ->assertOk()
            ->assertSee('Products and options picked for you')
            ->assertSee('Protection & benefits', false);

        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

        $this->get('/assets')
            ->assertOk()
            ->assertSee('Track a vehicle')
            ->assertSee('Add vehicle');
    }

    public function test_application_customer_does_not_assume_credit_bank_or_vehicle_data(): void
    {
        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

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
        $this->post('/prototype/presets/application-progress')->assertRedirect('/');

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
            '/products/savings',
            '/products/credit-card',
        ] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/profile')
            ->assertSee('Contact information')
            ->assertSee('Career')
            ->assertSee('Trusted contact')
            ->assertSee('data-profile-edit-button', false)
            ->assertDontSee('Communication preferences')
            ->assertDontSee('Date of birth')
            ->assertDontSee('Last 4 of SSN');
    }

    public function test_multiple_products_render_as_a_compact_vertical_stack_in_product_order(): void
    {
        $this->postJson('/prototype/state', [
            'loans' => ['count' => 2, 'payment_status' => 'current'],
            'products' => ['savings' => true, 'credit_card' => true],
            'offer' => ['type' => 'none'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 642, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk()
            ->assertJsonPath('state.loans.count', 2)
            ->assertJsonPath('state.products.savings', true)
            ->assertJsonPath('state.products.credit_card', true);

        $this->get('/')
            ->assertOk()
            ->assertSee('product-stack is-compact', false)
            ->assertDontSee('loan-strip', false)
            ->assertSeeInOrder([
                'Personal loan',
                'Home improvement loan',
                'Regional Savings',
                'Regional Credit Card',
            ])
            ->assertSee('$0.00')
            ->assertSee('/products/savings', false)
            ->assertSee('/products/credit-card', false);
    }

    public function test_a_single_product_uses_the_expanded_account_card(): void
    {
        $this->postJson('/prototype/state', [
            'loans' => ['count' => 0, 'payment_status' => 'current'],
            'products' => ['savings' => true, 'credit_card' => false],
            'offer' => ['type' => 'none'],
            'origination' => ['active' => false],
            'wellness' => ['credit_score' => 642, 'credit_score_change' => 'increase'],
            'vehicles' => ['count' => 0],
            'protection' => ['enabled' => false, 'context' => 'auto'],
        ])->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('product-stack is-expanded', false)
            ->assertSee('deposit-product-card', false)
            ->assertDontSee('account-product-card compact savings-product', false);

        $this->get('/products/savings')
            ->assertOk()
            ->assertSee('This would be the full savings account experience.');

        $this->get('/products/credit-card')
            ->assertOk()
            ->assertSee('This would be the full credit card account experience.');
    }
}
