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
        'verify_identity',
        'credit_eligibility',
        'verify_income',
        'review_options',
        'finalize',
        'sign_documents',
        'funding',
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
            'application-progress' => ['label' => 'Application in progress', 'description' => 'Application paused at income verification.', 'icon' => 'ti-clipboard-list'],
            'former-borrower' => ['label' => 'Former borrower', 'description' => 'No active loan, prior relationship, eligible to check.', 'icon' => 'ti-history'],
            'new-customer' => ['label' => 'New customer', 'description' => 'No prior loan and a clear path into originations.', 'icon' => 'ti-user-plus'],
        ];
    }

    public function applyPreset(Request $request, string $preset): array
    {
        $pendingPayment = $this->state($request)['payments']['pending'] ?? null;
        $state = $this->defaultState();
        $state['meta']['preset'] = $preset;
        $state['payments']['pending'] = $pendingPayment;
        $changes = match ($preset) {
            'prequalified-renewal' => ['offer' => ['type' => 'prequalified_renewal']],
            'past-due' => ['loans' => ['payment_status' => 'past_due_30'], 'offer' => ['type' => 'prequalified_renewal']],
            'application-progress' => [
                'offer' => ['type' => 'prequalified_renewal'],
                'origination' => ['active' => true, 'step' => 'verify_income', 'last_updated' => now()->toIso8601String()],
            ],
            'former-borrower' => [
                'customer' => ['type' => 'former'], 'loans' => ['count' => 0],
                'offer' => ['type' => 'check_for_offers'], 'vehicles' => ['count' => 0],
            ],
            'new-customer' => [
                'customer' => ['type' => 'new'], 'loans' => ['count' => 0],
                'offer' => ['type' => 'check_for_offers'], 'vehicles' => ['count' => 0],
                'wellness' => [
                    'credit_monitoring_enabled' => false,
                    'bank_connected' => false,
                    'high_utilization' => false,
                    'budget_warning' => false,
                ],
            ],
            default => [],
        };

        return $this->save($request, array_replace_recursive($state, $changes));
    }

    public function startApplication(Request $request): array
    {
        return $this->update($request, ['origination' => [
            'active' => true,
            'step' => 'application_started',
            'outcome' => null,
            'last_updated' => now()->toIso8601String(),
        ]]);
    }

    public function moveApplication(Request $request, int $direction): array
    {
        $state = $this->state($request);
        $current = $state['origination']['step'] ?? self::ORIGINATION_STEPS[0];
        $index = array_search($current, self::ORIGINATION_STEPS, true);
        $index = $index === false ? 0 : $index;
        $index = max(0, min(count(self::ORIGINATION_STEPS) - 1, $index + $direction));

        return $this->update($request, ['origination' => [
            'active' => true,
            'step' => self::ORIGINATION_STEPS[$index],
            'outcome' => null,
            'last_updated' => now()->toIso8601String(),
        ]]);
    }

    public function builderOptions(): array
    {
        return [
            'customer_types' => ['new' => 'New customer', 'former' => 'Former borrower', 'active' => 'Active borrower'],
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
        ];
    }

    public function defaultState(): array
    {
        return [
            'meta' => ['version' => 3, 'revision' => 1, 'preset' => self::DEFAULT_SCENARIO],
            'customer' => ['type' => 'active', 'first_name' => 'Jordan', 'last_name' => 'Davis'],
            'loans' => ['count' => 1, 'payment_status' => 'current'],
            'products' => ['savings' => false, 'credit_card' => false],
            'offer' => ['type' => 'check_for_offers'],
            'origination' => ['active' => false, 'step' => null, 'outcome' => null, 'last_updated' => null],
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
        $state['customer']['type'] = $this->allowed($state['customer']['type'], ['new', 'former', 'active'], 'active');
        $state['loans']['count'] = $state['customer']['type'] === 'active' ? max(0, min(2, (int) $state['loans']['count'])) : 0;
        $state['loans']['payment_status'] = $this->allowed($state['loans']['payment_status'], array_keys($this->builderOptions()['payment_statuses']), 'current');
        foreach (['savings', 'credit_card'] as $key) {
            $state['products'][$key] = filter_var($state['products'][$key], FILTER_VALIDATE_BOOL);
        }
        $state['offer']['type'] = $this->allowed($state['offer']['type'], array_keys($this->builderOptions()['offer_types']), 'none');
        $state['origination']['active'] = filter_var($state['origination']['active'], FILTER_VALIDATE_BOOL);
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

        $application = $state['origination']['active'] ? $this->applicationState($state['origination']) : null;
        $severeDelinquency = in_array($state['loans']['payment_status'], ['past_due_30', 'past_due_60', 'charged_off', 'bankruptcy'], true);
        $offer = $this->offerState($state['offer']['type'], filled($application) || $severeDelinquency);
        $wellness = $this->wellnessState($state['wellness']);

        return [
            'name' => $this->stateName($state),
            'description' => 'Customer-facing screens are derived from the shared prototype state.',
            'customer' => [
                'relationship_status' => $state['customer']['type'] . '_customer',
                'type' => $state['customer']['type'],
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
        $steps = [
            'application_started' => [10, 1, 'About you', 'Let\'s get started', 'Confirm a few details to begin your application.', 'Start application'],
            'confirm_information' => [20, 1, 'About you', 'Confirm your information', 'Review your contact and personal details.', 'Confirm and continue'],
            'verify_identity' => [30, 1, 'About you', 'Verify your identity', 'Confirm your mobile number and identity details.', 'Verify and continue'],
            'credit_eligibility' => [40, 2, 'Financial information', 'Check your eligibility', 'Review the permission for a soft credit check.', 'Check eligibility'],
            'verify_income' => [52, 2, 'Financial information', 'Finish verifying your income', 'Continue your application to see your available options.', 'Continue'],
            'review_options' => [65, 3, 'Your options', 'Your loan options are ready', 'Review your available options and continue when you are ready.', 'Review options'],
            'finalize' => [76, 4, 'Finalize', 'Finalize your application', 'Confirm your selection and review the final details.', 'Finalize application'],
            'sign_documents' => [86, 4, 'Finalize', 'Your documents are ready', 'Review and sign your documents to finish your loan.', 'Review documents'],
            'funding' => [94, 4, 'Finalize', 'Your loan is being finalized', 'We will let you know when your funds are ready.', 'View funding status'],
            'complete' => [100, 5, 'Complete', 'Your application is complete', 'Your new loan is ready. You can return home to manage your account.', 'Return home'],
        ];
        $step = $origination['step'] ?? 'application_started';
        [$percent, $phase, $phaseLabel, $headline, $summary, $cta] = $steps[$step] ?? $steps['application_started'];

        return [
            'id' => 62001, 'status' => $step === 'complete' ? 'complete' : 'in_progress', 'step' => $step,
            'technical_step' => ucwords(str_replace('_', ' ', $step)), 'progress_percent' => $percent,
            'phase' => $phase, 'phase_count' => 5, 'phase_label' => $phaseLabel, 'current_step' => $phaseLabel,
            'headline' => $headline, 'summary' => $summary, 'next_action' => $cta, 'cta' => $cta,
            'urgency' => in_array($step, ['verify_income', 'sign_documents'], true) ? 'action' : 'status',
            'last_updated' => $origination['last_updated'] ?? now()->toIso8601String(),
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
        if ($state['origination']['active']) return 'Application: ' . ucwords(str_replace('_', ' ', $state['origination']['step']));
        if (in_array($state['loans']['payment_status'], ['past_due_30', 'past_due_60', 'charged_off', 'bankruptcy'], true)) return $this->builderOptions()['payment_statuses'][$state['loans']['payment_status']];
        if ($state['offer']['type'] === 'prequalified_renewal') return 'Pre-qualified renewal';
        if ($state['customer']['type'] === 'former') return 'Former borrower';
        if ($state['customer']['type'] === 'new') return 'New customer';
        return $state['loans']['count'] === 2 ? 'Two active loans' : 'Healthy active borrower';
    }
}
