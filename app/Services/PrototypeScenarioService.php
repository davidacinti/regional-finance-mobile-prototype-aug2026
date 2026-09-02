<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PrototypeScenarioService
{
    public const DEFAULT_SCENARIO = 'healthy-active';

    private const SESSION_KEY = 'prototype_app_state';

    private const ORIGINATION_STEPS = [
        'application_started',
        'confirm_information',
        'credit_eligibility',
        'review_options',
        'verify_income',
        'funding_destination',
        'sign_documents',
        'complete',
    ];

    private const LITE_STAGES = [
        'lendingtree_offer',
        'regional_offer',
        'otp_phone',
        'otp_code',
        'income_verification',
        'vehicle_photos',
        'closing_ready',
        'closing_scheduled',
        'complete',
    ];

    public function state(Request $request): array
    {
        return $this->normalize($request->session()->get(self::SESSION_KEY, $this->defaultState()));
    }

    public function scenario(Request $request): array
    {
        return $this->derive($this->state($request));
    }

    public function save(Request $request, array $state): array
    {
        $state = $this->normalize($state);
        $state['meta']['revision'] = (int) round(microtime(true) * 1000);
        $request->session()->put(self::SESSION_KEY, $state);
        $request->session()->forget('prototype_interstitial_dismissed');

        return $state;
    }

    public function update(Request $request, array $changes): array
    {
        return $this->save($request, array_replace_recursive($this->state($request), $changes));
    }

    public function reset(Request $request): array
    {
        $request->session()->forget([
            self::SESSION_KEY,
            'prototype_interstitial_dismissed',
            'prototype_scheduled_payment',
            'prototype_payment_status',
            'prototype_autopay_enrollment',
            'prototype_read_notifications',
        ]);

        return $this->save($request, $this->defaultState());
    }

    public function presets(): array
    {
        return [
            'healthy-active' => ['label' => 'Healthy active borrower', 'description' => 'One current loan with standard wellness and one vehicle.', 'icon' => 'ti-circle-check'],
            'prequalified-renewal' => ['label' => 'Pre-qualified renewal', 'description' => 'Current borrower with the strongest personalized offer.', 'icon' => 'ti-award'],
            'past-due' => ['label' => 'Past-due customer', 'description' => 'One loan 30 days past due; servicing takes priority.', 'icon' => 'ti-alert-triangle'],
            'application-progress' => ['label' => 'Application in progress', 'description' => 'New customer paused at income verification.', 'icon' => 'ti-clipboard-list'],
            'lendingtree-prequalified' => ['label' => 'New customer - LendingTree', 'description' => 'Pre-qualified applicant entering the origination lite experience.', 'icon' => 'ti-plant'],
        ];
    }

    public function applyPreset(Request $request, string $preset): array
    {
        $request->session()->forget([
            'prototype_scheduled_payment',
            'prototype_payment_status',
            'prototype_autopay_enrollment',
            'prototype_read_notifications',
        ]);
        $state = $this->defaultState();
        $state['meta']['preset'] = $preset;
        $changes = match ($preset) {
            'prequalified-renewal' => ['offer' => ['type' => 'prequalified_renewal']],
            'past-due' => ['loans' => ['payment_status' => 'past_due_30'], 'offer' => ['type' => 'prequalified_renewal']],
            'application-progress' => [
                'loans' => ['count' => 0],
                'offer' => ['type' => 'none'],
                'origination' => ['active' => true, 'step' => 'application_started', 'journey' => 'standard', 'last_updated' => now()->toIso8601String()],
                'vehicles' => ['count' => 0],
                'wellness' => [
                    'credit_monitoring_enabled' => false,
                    'bank_connected' => false,
                    'high_utilization' => false,
                    'budget_warning' => false,
                ],
            ],
            'lendingtree-prequalified' => [
                'customer' => ['first_name' => 'Michael', 'last_name' => 'Reed'],
                'experience' => ['mode' => 'origination_lite', 'entry_channel' => 'lendingtree', 'authentication' => 'phone_otp'],
                'loans' => ['count' => 0],
                'products' => ['savings' => false, 'credit_card' => false],
                'offer' => ['type' => 'none'],
                'origination' => ['active' => true, 'step' => 'application_started', 'journey' => 'prequalified', 'last_updated' => now()->toIso8601String()],
                'lite' => ['stage' => 'lendingtree_offer', 'prequalified_amount' => 8500, 'password_created' => false, 'income_document' => null, 'vehicle_photos' => [], 'appointment' => null],
                'vehicles' => ['count' => 0],
                'wellness' => ['credit_monitoring_enabled' => false, 'bank_connected' => false, 'high_utilization' => false, 'budget_warning' => false],
                'protection' => ['enabled' => false],
            ],
            default => [],
        };

        return $this->save($request, array_replace_recursive($state, $changes));
    }

    public function startApplication(Request $request): array
    {
        $state = $this->state($request);
        $journey = ($state['offer']['type'] ?? null) === 'prequalified_renewal' ? 'prequalified' : 'standard';

        return $this->update($request, ['origination' => [
            'active' => true,
            'step' => 'application_started',
            'journey' => $journey,
            'selected_offer' => null,
            'outcome' => null,
            'last_updated' => now()->toIso8601String(),
        ]]);
    }

    public function moveApplication(Request $request, int $direction): array
    {
        $state = $this->state($request);
        $current = $state['origination']['step'] ?? self::ORIGINATION_STEPS[0];
        $steps = self::ORIGINATION_STEPS;
        if (($state['origination']['journey'] ?? 'standard') === 'prequalified') {
            $steps = array_values(array_filter($steps, fn (string $step) => $step !== 'verify_income'));
        }
        $index = array_search($current, $steps, true);
        $index = $index === false ? 0 : $index;
        $index = max(0, min(count($steps) - 1, $index + $direction));

        $changes = [
            'active' => true,
            'step' => $steps[$index],
            'outcome' => $steps[$index] === 'complete' ? 'approved' : null,
            'last_updated' => now()->toIso8601String(),
        ];
        if ($current === 'review_options' && $direction > 0) {
            $changes['selected_offer'] = ['amount' => 3500, 'payment' => 168, 'term' => 24];
        }

        return $this->update($request, ['origination' => $changes]);
    }

    public function updateLiteStage(Request $request, string $stage, array $changes = []): array
    {
        $stage = $this->allowed($stage, self::LITE_STAGES, 'lendingtree_offer');

        return $this->update($request, [
            'experience' => ['mode' => 'origination_lite', 'entry_channel' => 'lendingtree', 'authentication' => 'phone_otp'],
            'lite' => array_replace($changes, ['stage' => $stage]),
            'origination' => ['active' => true, 'journey' => 'prequalified', 'last_updated' => now()->toIso8601String()],
        ]);
    }

    public function builderOptions(): array
    {
        return [
            'offer_types' => [
                'none' => 'None',
                'prequalified_renewal' => 'Pre-qualified renewal',
                'invite_to_apply' => 'Invite to apply',
                'check_for_offers' => 'Check for offers',
            ],
            'payment_statuses' => [
                'current' => 'Current',
                'due_soon' => 'Payment due soon',
                'due_today' => 'Payment due today',
                'past_due_1_29' => '1-29 days past due',
                'past_due_30' => '30 days past due',
                'past_due_60' => '60+ days past due',
                'charged_off' => 'Charged off',
                'bankruptcy' => 'Bankruptcy servicing',
            ],
            'origination_steps' => array_combine(self::ORIGINATION_STEPS, array_map(
                fn (string $step) => ucwords(str_replace('_', ' ', $step)),
                self::ORIGINATION_STEPS
            )),
            'experience_modes' => [
                'full' => 'Standard customer app',
                'origination_lite' => 'Origination lite - LendingTree',
            ],
            'lite_stages' => [
                'lendingtree_offer' => 'LendingTree offer',
                'regional_offer' => 'Regional welcome and offer',
                'otp_phone' => 'Phone verification',
                'otp_code' => 'Enter OTP code',
                'income_verification' => 'Dashboard - income required',
                'vehicle_photos' => 'Dashboard - vehicle photos required',
                'closing_ready' => 'Dashboard - closing required',
                'closing_scheduled' => 'Closing scheduled',
                'complete' => 'Ready to close',
            ],
        ];
    }

    public function defaultState(): array
    {
        return [
            'meta' => ['version' => 5, 'revision' => 1, 'preset' => self::DEFAULT_SCENARIO],
            'customer' => ['first_name' => 'Jordan', 'last_name' => 'Davis'],
            'experience' => ['mode' => 'full', 'entry_channel' => 'direct', 'authentication' => 'password'],
            'loans' => ['count' => 1, 'payment_status' => 'current'],
            'products' => ['savings' => false, 'credit_card' => false],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false, 'step' => null, 'journey' => 'standard', 'selected_offer' => null, 'outcome' => null, 'last_updated' => null],
            'lite' => ['stage' => 'lendingtree_offer', 'prequalified_amount' => 8500, 'password_created' => false, 'income_document' => null, 'vehicle_photos' => [], 'appointment' => null],
            'wellness' => [
                'credit_monitoring_enabled' => true,
                'credit_score' => 642,
                'credit_score_change' => 'increase',
                'high_utilization' => false,
                'budget_warning' => false,
                'cash_flow' => 'normal',
                'spending_trend' => 'normal',
                'bank_connected' => true,
            ],
            'vehicles' => ['count' => 1],
            'protection' => ['enabled' => true, 'context' => 'auto'],
            'payments' => ['pending' => null],
        ];
    }

    private function normalize(array $state): array
    {
        $state = array_replace_recursive($this->defaultState(), $state);
        unset($state['customer']['type']);
        $state['experience']['mode'] = $this->allowed($state['experience']['mode'], ['full', 'origination_lite'], 'full');
        $state['experience']['entry_channel'] = $state['experience']['mode'] === 'origination_lite' ? 'lendingtree' : 'direct';
        $state['experience']['authentication'] = $state['experience']['mode'] === 'origination_lite' ? 'phone_otp' : 'password';
        $state['loans']['count'] = max(0, min(2, (int) $state['loans']['count']));
        $state['loans']['payment_status'] = $this->allowed($state['loans']['payment_status'], array_keys($this->builderOptions()['payment_statuses']), 'current');
        foreach (['savings', 'credit_card'] as $key) {
            $state['products'][$key] = filter_var($state['products'][$key], FILTER_VALIDATE_BOOL);
        }
        $state['offer']['type'] = $this->allowed($state['offer']['type'], array_keys($this->builderOptions()['offer_types']), 'none');
        $state['origination']['active'] = filter_var($state['origination']['active'], FILTER_VALIDATE_BOOL);
        $state['origination']['journey'] = $this->allowed($state['origination']['journey'], ['standard', 'prequalified'], 'standard');
        $state['origination']['step'] = $state['origination']['active']
            ? $this->allowed($state['origination']['step'], self::ORIGINATION_STEPS, 'application_started')
            : null;
        $state['wellness']['credit_score'] = max(300, min(850, (int) $state['wellness']['credit_score']));
        $state['wellness']['credit_score_change'] = $this->allowed($state['wellness']['credit_score_change'], ['decrease', 'none', 'increase'], 'increase');
        $state['wellness']['cash_flow'] = $this->allowed($state['wellness']['cash_flow'], ['low', 'normal', 'strong'], 'normal');
        $state['wellness']['spending_trend'] = $this->allowed($state['wellness']['spending_trend'], ['down', 'normal', 'up'], 'normal');
        foreach (['credit_monitoring_enabled', 'high_utilization', 'budget_warning', 'bank_connected'] as $key) {
            $state['wellness'][$key] = filter_var($state['wellness'][$key], FILTER_VALIDATE_BOOL);
        }
        $state['vehicles']['count'] = max(0, min(3, (int) $state['vehicles']['count']));
        $state['protection']['enabled'] = filter_var($state['protection']['enabled'], FILTER_VALIDATE_BOOL);
        $state['protection']['context'] = $this->allowed($state['protection']['context'], ['loan', 'home_auto', 'auto'], 'auto');
        $state['lite']['stage'] = $this->allowed($state['lite']['stage'], self::LITE_STAGES, 'lendingtree_offer');
        $state['lite']['prequalified_amount'] = max(500, min(25000, (int) $state['lite']['prequalified_amount']));
        $state['lite']['password_created'] = filter_var($state['lite']['password_created'], FILTER_VALIDATE_BOOL);
        $state['lite']['income_document'] = is_string($state['lite']['income_document']) ? $state['lite']['income_document'] : null;
        $state['lite']['vehicle_photos'] = is_array($state['lite']['vehicle_photos']) ? array_values($state['lite']['vehicle_photos']) : [];
        $state['lite']['appointment'] = is_array($state['lite']['appointment']) ? $state['lite']['appointment'] : null;

        if ($state['experience']['mode'] === 'origination_lite') {
            $state['loans']['count'] = 0;
            $state['products'] = ['savings' => false, 'credit_card' => false];
            $state['offer']['type'] = 'none';
            $state['origination']['active'] = true;
            $state['origination']['step'] = 'application_started';
            $state['origination']['journey'] = 'prequalified';
            $state['wellness']['credit_monitoring_enabled'] = false;
            $state['wellness']['bank_connected'] = false;
            $state['vehicles']['count'] = 0;
            $state['protection']['enabled'] = false;
        }
        if (is_array($state['payments']['pending'])) {
            $state['payments']['pending'] = array_replace([
                'id' => 'PMT-DEMO',
                'loan_id' => 1002841,
                'loan_name' => 'Personal loan',
                'amount' => 0.0,
                'minimum_due' => 0.0,
                'payment_date' => now()->toDateString(),
                'status' => 'Pending',
                'account' => ['name' => 'Checking', 'label' => 'Checking - 4203', 'routing' => null],
                'warning' => null,
            ], $state['payments']['pending']);
        } else {
            $state['payments']['pending'] = null;
        }

        return $state;
    }

    private function allowed(mixed $value, array $allowed, mixed $fallback): mixed
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function derive(array $state): array
    {
        $today = CarbonImmutable::today();
        $payment = $this->paymentState($state['loans']['payment_status'], $today);
        $loanTemplates = [
            ['id' => 1002841, 'name' => 'Personal loan', 'balance' => 4825.35, 'next_payment_amount' => 214.00, 'autopay_enabled' => true, 'original_principal' => 10000.00, 'apr' => 24.90],
            ['id' => 1009912, 'name' => 'Home improvement loan', 'balance' => 7240.18, 'next_payment_amount' => 288.00, 'autopay_enabled' => false, 'original_principal' => 12500.00, 'apr' => 21.75],
        ];
        $loans = [];
        foreach (array_slice($loanTemplates, 0, $state['loans']['count']) as $index => $loan) {
            $isPrimaryLoan = $index === 0;
            $loanStatus = $isPrimaryLoan ? $payment['loan_status'] : 'current';
            $amountDue = $isPrimaryLoan && ($payment['is_late'] || in_array($state['loans']['payment_status'], ['due_soon', 'due_today'], true))
                ? ($payment['past_due_amount'] > 0 ? $payment['past_due_amount'] : $loan['next_payment_amount'])
                : 0;
            $loans[] = array_replace($loan, [
                'status' => $loanStatus,
                'amount_due' => $amountDue,
                'next_payment_date' => $isPrimaryLoan ? $payment['due_date'] : $today->addDays(21)->toDateString(),
                'past_due_amount' => $isPrimaryLoan ? $payment['past_due_amount'] : 0,
                'autopay_enabled' => $isPrimaryLoan && $payment['loan_status'] === 'current' ? $loan['autopay_enabled'] : false,
            ]);
        }

        $liteMode = $state['experience']['mode'] === 'origination_lite';
        $application = $liteMode
            ? $this->liteApplicationState($state['lite'])
            : ($state['origination']['active'] ? $this->applicationState($state['origination']) : null);
        $severeDelinquency = in_array($state['loans']['payment_status'], ['past_due_30', 'past_due_60', 'charged_off', 'bankruptcy'], true);
        $offer = $this->offerState($state['offer']['type'], filled($application) || $severeDelinquency);
        $wellness = $this->wellnessState($state['wellness']);

        return [
            'name' => $this->stateName($state),
            'description' => 'Customer-facing screens are derived from the shared prototype state.',
            'customer' => [
                'relationship_status' => $liteMode ? 'applicant' : 'customer',
                'first_name' => $state['customer']['first_name'],
                'last_name' => $state['customer']['last_name'],
            ],
            'loans' => $loans,
            'products' => [
                'savings' => $state['products']['savings'] ? [
                    'id' => 'savings-4203',
                    'type' => 'savings',
                    'name' => 'Regional Savings',
                    'balance' => 8350.72,
                    'available_balance' => 8350.72,
                    'apy' => 0.45,
                    'last_four' => '4203',
                    'status' => 'Active',
                ] : null,
                'credit_card' => $state['products']['credit_card'] ? [
                    'id' => 'credit-card-8842',
                    'type' => 'credit_card',
                    'name' => 'Regional Credit Card',
                    'balance' => 1248.42,
                    'amount_due' => 0,
                    'due_date' => '2026-09-12',
                    'available_credit' => 3751.58,
                    'credit_limit' => 5000.00,
                    'last_four' => '8842',
                    'status' => 'Current',
                ] : null,
            ],
            'application' => $application,
            'experience' => $state['experience'],
            'lite' => $state['lite'],
            'offer' => $offer,
            'financial_wellness' => $wellness,
            'assets' => ['vehicles' => array_slice($this->vehicleTemplates(), 0, $state['vehicles']['count'])],
            'protection' => $state['protection'],
            'payments' => $state['payments'],
            'branch' => [
                'name' => 'Greenville Branch',
                'address' => '1450 Woodruff Rd, Greenville, SC 29607',
                'hours' => 'Mon-Fri 9:00 AM-6:00 PM',
                'phone' => '(864) 555-0148',
                'manager' => 'Maria Thompson',
                'lat' => 34.8334,
                'lng' => -82.3075,
            ],
            'alerts' => [
                'payment_due_soon' => in_array($state['loans']['payment_status'], ['due_soon', 'due_today'], true),
                'late_payment' => $payment['is_late'],
            ],
            'default_status' => $payment['default_status'],
            'servicing_alert' => $payment['servicing_alert'],
            'interstitial' => $payment['is_critical'] ? 'late_payment' : ($offer['interstitial'] ?? null),
            'app_state' => $state,
        ];
    }

    private function paymentState(string $status, CarbonImmutable $today): array
    {
        $states = [
            'current' => ['current', 14, 0, false, false, 'Current', 'You\'re all caught up'],
            'due_soon' => ['current', 5, 0, false, false, 'Current', 'Payment due soon'],
            'due_today' => ['current', 0, 0, false, false, 'Due today', 'Payment due today'],
            'past_due_1_29' => ['past_due', -12, 214, true, false, '12 days past due', 'Payment past due'],
            'past_due_30' => ['past_due', -30, 428, true, true, '30 days past due', 'Payment past due'],
            'past_due_60' => ['past_due', -65, 642, true, true, '60+ days past due', 'Your account needs attention'],
            'charged_off' => ['charged_off', -120, 1284, true, true, 'Charged off', 'Account charged off'],
            'bankruptcy' => ['bankruptcy', 0, 0, true, true, 'Servicing review', 'Account under servicing review'],
        ];
        [$loanStatus, $offset, $pastDue, $isLate, $critical, $label, $title] = $states[$status] ?? $states['current'];
        $contact = in_array($status, ['charged_off', 'bankruptcy'], true);

        return [
            'loan_status' => $loanStatus,
            'due_date' => $today->addDays($offset)->toDateString(),
            'past_due_amount' => $pastDue,
            'is_late' => $isLate,
            'is_critical' => $critical,
            'default_status' => ['stage' => $status, 'label' => $label, 'days_past_due' => abs(min(0, $offset))],
            'servicing_alert' => [
                'title' => $title,
                'body' => $contact ? 'Some online actions may be limited. Please contact us for account support.' : '$pastDue was due $dueDate.',
                'modal_title' => $title,
                'modal_body' => $contact ? 'Please contact our servicing team to review the next steps for this account.' : '$pastDue was due $dueDate. Making a payment can help bring your account current.',
                'cta' => $contact ? 'Contact support' : 'Make a payment',
                'tone' => $critical ? 'critical' : 'urgent',
            ],
        ];
    }

    private function offerState(string $type, bool $suppressed): array
    {
        $offers = [
            'prequalified_renewal' => [
                'type' => 'prequalified_renewal', 'eyebrow' => 'Pre-qualified', 'headline' => 'A new loan option is ready',
                'body' => "You're pre-qualified to see your renewal option.", 'cta' => 'View my option',
                'highlight' => "Viewing your option won't impact your credit score.", 'amount' => 3500, 'interstitial' => 'prequalified',
            ],
            'invite_to_apply' => [
                'type' => 'invite_to_apply', 'eyebrow' => 'For you', 'headline' => 'You may have a renewal option',
                'body' => 'See if renewing your Regional Finance loan could be right for you.', 'cta' => 'See if I qualify',
                'highlight' => "Checking won't impact your credit score.", 'amount' => null, 'interstitial' => 'invite_to_apply',
            ],
            'check_for_offers' => [
                'type' => 'check_for_offers', 'eyebrow' => 'Explore', 'headline' => 'See your loan options',
                'body' => 'Check for personalized options in minutes.', 'cta' => 'Check offers',
                'highlight' => 'No impact to your credit score to check.', 'amount' => null, 'interstitial' => null,
            ],
            'none' => ['type' => 'none', 'status' => 'none', 'headline' => null, 'body' => null, 'cta' => null, 'highlight' => null],
        ];
        $offer = $offers[$type] ?? $offers['none'];
        $offer['status'] = $suppressed || $type === 'none' ? 'suppressed' : 'available';
        $offer['credit_impact'] = $type === 'none' ? null : 'soft_pull';

        return $offer;
    }

    private function applicationState(array $origination): array
    {
        $prequalified = ($origination['journey'] ?? 'standard') === 'prequalified';
        $steps = [
            'application_started' => [8, 1, 'Explore', $prequalified ? 'Your pre-qualified option is ready' : 'A personal loan for what comes next', $prequalified ? 'Review your pre-qualified path and continue when you are ready.' : 'See loan options with a quick, guided application.', 'Continue'],
            'confirm_information' => [20, 1, 'About you', 'Confirm your information', 'Review your contact and personal details.', 'Confirm and continue'],
            'credit_eligibility' => [35, 2, 'Credit review', $prequalified ? 'Complete your application' : 'Check your eligibility', $prequalified ? 'Authorize the credit review required to complete your selected application.' : 'See whether you pre-qualify without impacting your credit score.', $prequalified ? 'Authorize and continue' : 'Check eligibility'],
            'review_options' => [52, 3, 'Your options', 'Your loan options are ready', 'Choose the amount and payment that work best for you.', 'Choose this option'],
            'verify_income' => [64, 3, 'Income', 'Verify your income', 'One quick verification helps us finalize your selected option.', 'Verify income'],
            'funding_destination' => [76, 4, 'Funding', 'Where should we send your funds?', 'Choose or add the account that will receive your loan proceeds.', 'Use this account'],
            'sign_documents' => [88, 4, 'E-sign', 'Review and sign', 'Review your final loan documents and provide your electronic signature.', 'Sign and finish'],
            'complete' => [100, 5, 'Complete', 'You\'re good to go', 'Your loan is approved and pending funding.', 'Return home'],
        ];
        $step = $origination['step'] ?? 'application_started';
        [$percent, $phase, $phaseLabel, $headline, $summary, $cta] = $steps[$step] ?? $steps['application_started'];

        return [
            'id' => 62001, 'status' => $step === 'complete' ? 'pending_funding' : 'in_progress', 'step' => $step,
            'technical_step' => ucwords(str_replace('_', ' ', $step)), 'progress_percent' => $percent,
            'phase' => $phase, 'phase_count' => 5, 'phase_label' => $phaseLabel, 'current_step' => $phaseLabel,
            'headline' => $headline, 'summary' => $summary, 'next_action' => $cta, 'cta' => $cta,
            'journey' => $prequalified ? 'prequalified' : 'standard',
            'prequalified' => $prequalified,
            'income_required' => ! $prequalified,
            'selected_offer' => $origination['selected_offer'] ?? null,
            'urgency' => in_array($step, ['verify_income', 'sign_documents'], true) ? 'action' : 'status',
            'last_updated' => $origination['last_updated'] ?? now()->toIso8601String(),
        ];
    }

    private function liteApplicationState(array $lite): array
    {
        $stage = $lite['stage'] ?? 'lendingtree_offer';
        $stageIndex = array_search($stage, self::LITE_STAGES, true);
        $stageIndex = $stageIndex === false ? 0 : $stageIndex;
        $incomeIndex = array_search('income_verification', self::LITE_STAGES, true);
        $vehicleIndex = array_search('vehicle_photos', self::LITE_STAGES, true);
        $closingIndex = array_search('closing_ready', self::LITE_STAGES, true);
        $scheduledIndex = array_search('closing_scheduled', self::LITE_STAGES, true);

        $taskStatus = static function (int $current, int $required, string $active = 'Action required'): string {
            if ($current < $required) return 'Not available yet';
            if ($current === $required) return $active;
            return 'Complete';
        };

        $nextSteps = [
            'income_verification' => ['key' => 'income', 'eyebrow' => 'Next step', 'headline' => 'Verify your income', 'body' => 'Upload your most recent paystub so we can finish reviewing your application.', 'cta' => 'Upload income document', 'icon' => 'ti-file-upload'],
            'vehicle_photos' => ['key' => 'vehicle', 'eyebrow' => 'Next step', 'headline' => 'Upload vehicle photos', 'body' => 'We need a few photos of your vehicle before we can finalize your loan.', 'cta' => 'Upload vehicle photos', 'icon' => 'ti-camera'],
            'closing_ready' => ['key' => 'closing', 'eyebrow' => 'Next step', 'headline' => 'Schedule your closing', 'body' => 'Your application is ready for the final step. Choose a time to complete your closing.', 'cta' => 'Schedule appointment', 'icon' => 'ti-calendar-event'],
        ];

        return [
            'id' => 62001,
            'status' => $stage === 'complete' ? 'ready_to_close' : 'in_progress',
            'step' => $stage,
            'headline' => match ($stage) {
                'closing_scheduled' => 'Closing scheduled',
                'complete' => 'Ready to close',
                default => 'Almost there',
            },
            'summary' => 'Complete the steps below to finish your loan.',
            'cta' => $nextSteps[$stage]['cta'] ?? 'View application',
            'prequalified' => true,
            'prequalified_amount' => (int) ($lite['prequalified_amount'] ?? 8500),
            'authenticated' => $stageIndex >= $incomeIndex,
            'password_created' => (bool) ($lite['password_created'] ?? false),
            'next_step' => $nextSteps[$stage] ?? null,
            'appointment' => $lite['appointment'] ?? null,
            'progress_percent' => match (true) {
                $stageIndex >= $scheduledIndex => 100,
                $stageIndex >= $closingIndex => 75,
                $stageIndex >= $vehicleIndex => 50,
                $stageIndex >= $incomeIndex => 25,
                default => 10,
            },
            'tasks' => [
                ['key' => 'prequalified', 'label' => 'Prequalified', 'status' => 'Complete'],
                ['key' => 'income', 'label' => 'Income verification', 'status' => $taskStatus($stageIndex, $incomeIndex)],
                ['key' => 'vehicle', 'label' => 'Vehicle photos', 'status' => $taskStatus($stageIndex, $vehicleIndex)],
                ['key' => 'closing', 'label' => 'Closing appointment', 'status' => $stageIndex === $scheduledIndex ? 'Scheduled' : $taskStatus($stageIndex, $closingIndex)],
            ],
        ];
    }

    private function wellnessState(array $wellness): array
    {
        $creditAvailable = (bool) $wellness['credit_monitoring_enabled'];
        $bankConnected = (bool) $wellness['bank_connected'];
        $change = match ($wellness['credit_score_change']) { 'decrease' => -18, 'none' => 0, default => 8 };
        $spending = match ($wellness['spending_trend']) { 'up' => 3357.34, 'down' => 2488.10, default => 2845.20 };
        $cashFlow = match ($wellness['cash_flow']) { 'low' => ['Low cushion', 82.15], 'strong' => ['Strong cash flow', 742.60], default => ['On track', 288.35] };
        $insights = [];
        if ($bankConnected && $wellness['spending_trend'] === 'up') $insights[] = ['icon' => 'ti-trending-up', 'title' => 'Spending is higher this month', 'body' => "You've spent 18% more than your typical month.", 'cta' => 'View spending'];
        if ($creditAvailable && $wellness['high_utilization']) $insights[] = ['icon' => 'ti-percentage', 'title' => 'Credit utilization is high', 'body' => 'Lower balances may help improve your credit profile.', 'cta' => 'See credit details'];
        if ($creditAvailable && $change > 0) $insights[] = ['icon' => 'ti-chart-line', 'title' => 'Nice work', 'body' => "Your credit score increased {$change} points.", 'cta' => 'View score'];
        if ($bankConnected && $wellness['budget_warning']) $insights[] = ['icon' => 'ti-alert-circle', 'title' => 'A budget is nearing its limit', 'body' => 'Groceries are at 85% of this month\'s target.', 'cta' => 'View budgets'];
        if ($bankConnected && $wellness['cash_flow'] === 'low') $insights[] = ['icon' => 'ti-calendar-dollar', 'title' => 'Bills are coming up', 'body' => 'You have $986 in expected bills over the next two weeks.', 'cta' => 'View cash flow'];
        if ($insights === [] && !$creditAvailable && !$bankConnected) $insights[] = ['icon' => 'ti-sparkles', 'title' => 'Build your financial snapshot', 'body' => 'More insights will appear as your account information becomes available.', 'cta' => 'Learn more'];
        if ($insights === []) $insights[] = ['icon' => 'ti-bulb', 'title' => 'Your finances are on track', 'body' => 'Spending and upcoming bills are within your usual range.', 'cta' => 'View snapshot'];

        return [
            'credit_monitoring_enabled' => $creditAvailable,
            'credit_score' => $creditAvailable ? $wellness['credit_score'] : null,
            'credit_score_change' => $creditAvailable ? $change : null,
            'bank_connected' => $bankConnected, 'monthly_spending' => $bankConnected ? $spending : null,
            'cash_flow_status' => $bankConnected ? $cashFlow[0] : 'Not connected',
            'cash_flow_cushion' => $bankConnected ? $cashFlow[1] : null,
            'high_utilization' => $wellness['high_utilization'], 'budget_warning' => $wellness['budget_warning'],
            'spending_trend' => $wellness['spending_trend'], 'insights' => $insights,
        ];
    }

    private function vehicleTemplates(): array
    {
        return [
            ['year' => 2021, 'make' => 'Toyota', 'model' => 'Camry', 'trim' => 'SE', 'mileage' => '42,180', 'estimated_value' => 21800, 'estimated_equity' => 3900, 'last_updated' => now()->subDays(2)->toDateString(), 'status' => 'Value updated', 'nickname' => 'Jordan\'s Camry'],
            ['year' => 2019, 'make' => 'Ford', 'model' => 'F-150', 'trim' => 'XLT', 'mileage' => '68,940', 'estimated_value' => 26750, 'estimated_equity' => 6450, 'last_updated' => now()->subDays(5)->toDateString(), 'status' => 'Tracked', 'nickname' => 'Weekend truck'],
            ['year' => 2017, 'make' => 'Honda', 'model' => 'CR-V', 'trim' => 'EX', 'mileage' => 'Mileage needed', 'estimated_value' => 16400, 'estimated_equity' => 2100, 'last_updated' => now()->subDays(18)->toDateString(), 'status' => 'Needs mileage', 'nickname' => 'Family SUV'],
        ];
    }

    private function stateName(array $state): string
    {
        if ($state['experience']['mode'] === 'origination_lite') return 'LendingTree applicant: ' . ($this->builderOptions()['lite_stages'][$state['lite']['stage']] ?? 'Origination lite');
        if ($state['origination']['active']) return 'Application: ' . ucwords(str_replace('_', ' ', $state['origination']['step']));
        if (in_array($state['loans']['payment_status'], ['past_due_30', 'past_due_60', 'charged_off', 'bankruptcy'], true)) return $this->builderOptions()['payment_statuses'][$state['loans']['payment_status']];
        if ($state['offer']['type'] === 'prequalified_renewal') return 'Pre-qualified renewal';
        if ($state['loans']['count'] === 0) return 'No active loans';
        return $state['loans']['count'] === 2 ? 'Two active loans' : 'Healthy active borrower';
    }
}
