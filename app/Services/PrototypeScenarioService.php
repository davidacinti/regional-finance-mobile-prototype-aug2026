<?php

namespace App\Services;

class PrototypeScenarioService
{
    public const DEFAULT_SCENARIO = 'present-one-loan';

    public function all(): array
    {
        return $this->scenarios();
    }

    public function find(?string $id): array
    {
        $scenarios = $this->scenarios();

        return $scenarios[$id] ?? $scenarios[self::DEFAULT_SCENARIO];
    }

    public function groups(): array
    {
        $groups = [
            'Present borrowers' => [],
            'Former borrowers' => [],
        ];

        foreach ($this->scenarios() as $id => $scenario) {
            $groups[$scenario['group']][$id] = $scenario;
        }

        return $groups;
    }

    private function scenarios(): array
    {
        $baseLoan = [
            'id' => 1002841,
            'name' => 'Personal loan',
            'status' => 'current',
            'balance' => 4825.35,
            'next_payment_amount' => 214.00,
            'next_payment_date' => '2026-07-18',
            'past_due_amount' => 0,
            'autopay_enabled' => true,
        ];

        $baseWellness = [
            'credit_monitoring_enabled' => true,
            'credit_score' => 642,
            'credit_score_change' => 8,
            'bank_connected' => true,
            'monthly_spending' => 2845.20,
            'cash_flow_status' => 'On track',
        ];

        $presentBase = [
            'group' => 'Present borrowers',
            'customer' => ['relationship_status' => 'present_borrower', 'first_name' => 'Jordan'],
            'loans' => [$baseLoan],
            'application' => null,
            'offer' => ['type' => 'check_for_offers', 'status' => 'available', 'amount' => null, 'expires_at' => null, 'credit_impact' => 'soft_pull'],
            'financial_wellness' => $baseWellness,
            'assets' => ['vehicles' => []],
            'branch' => [
                'name' => 'Greenville Branch',
                'address' => '1450 Woodruff Rd, Greenville, SC 29607',
                'hours' => 'Mon-Fri 9:00 AM-6:00 PM',
                'phone' => '(864) 555-0148',
                'manager' => 'Maria Thompson',
                'lat' => 34.8334,
                'lng' => -82.3075,
            ],
            'alerts' => ['payment_due_soon' => false, 'late_payment' => false],
            'interstitial' => null,
        ];

        $defaultScenario = function (string $name, string $description, int $daysPastDue, string $dueDate, float $pastDueAmount, array $overrides = []) use ($presentBase, $baseLoan) {
            $label = "{$daysPastDue} days past due";
            $severityTitle = $daysPastDue >= 90 ? 'Immediate attention needed' : 'Payment past due';

            return array_replace_recursive($presentBase, [
                'group' => 'Credit & default scenarios',
                'name' => $name,
                'description' => $description,
                'modules' => ['Servicing alert', 'Payment CTA', 'Loan card', 'Offers suppressed'],
                'message' => null,
                'loans' => [array_replace($baseLoan, [
                    'status' => 'past_due',
                    'next_payment_date' => $dueDate,
                    'past_due_amount' => $pastDueAmount,
                    'autopay_enabled' => false,
                ])],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
                'alerts' => ['late_payment' => true],
                'default_status' => [
                    'stage' => 'past_due',
                    'label' => $label,
                    'days_past_due' => $daysPastDue,
                ],
                'servicing_alert' => [
                    'title' => $severityTitle,
                    'body' => '$pastDue was due $dueDate. Making a payment can help bring your account current.',
                    'modal_title' => 'Your payment is past due',
                    'modal_body' => '$pastDue was due $dueDate. Making a payment can help bring your account current.',
                    'cta' => 'Make a payment',
                    'tone' => $daysPastDue >= 90 ? 'critical' : 'urgent',
                ],
                'interstitial' => 'late_payment',
            ], $overrides);
        };

        $formerBase = array_replace($presentBase, [
            'group' => 'Former borrowers',
            'customer' => ['relationship_status' => 'former_borrower', 'first_name' => 'Taylor'],
            'loans' => [],
            'message' => ['title' => 'Welcome back, Taylor', 'body' => 'See what options may be available when you are ready.'],
        ]);

        $originationBase = array_replace_recursive($presentBase, [
            'group' => 'Originations',
            'message' => null,
            'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
        ]);

        return [
            'present-one-loan' => array_replace_recursive($presentBase, [
                'name' => 'One active loan',
                'description' => 'Current borrower with one active loan and standard wellness content.',
                'modules' => ['Loan card', 'Check offers', 'Credit score', 'Spending insights'],
                'message' => ['title' => 'Good afternoon, Jordan', 'body' => 'Your account is current and your credit score is up 8 points.'],
            ]),
            'present-due-soon' => array_replace_recursive($presentBase, [
                'name' => 'Payment due soon',
                'description' => 'Current borrower with a payment due within five days.',
                'modules' => ['Due banner', 'Payment CTA', 'Loan card', 'Offers secondary'],
                'message' => ['title' => 'Upcoming payment', 'body' => 'Your next payment is due July 18.'],
                'alerts' => ['payment_due_soon' => true],
            ]),
            'present-late-payment' => array_replace_recursive($presentBase, [
                'name' => 'Late payment',
                'description' => 'Past-due account where servicing actions override offers.',
                'modules' => ['Late modal', 'Late banner', 'Payment CTA', 'Loan card'],
                'message' => null,
                'loans' => [array_replace($baseLoan, [
                    'status' => 'past_due',
                    'next_payment_date' => '2026-07-03',
                    'past_due_amount' => 214.00,
                    'autopay_enabled' => false,
                ])],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
                'alerts' => ['late_payment' => true],
                'default_status' => ['stage' => 'past_due', 'label' => 'Past due', 'days_past_due' => 11],
                'servicing_alert' => [
                    'title' => 'Payment past due',
                    'body' => '$pastDue was due $dueDate.',
                    'modal_title' => 'Your payment is past due',
                    'modal_body' => '$pastDue was due $dueDate. Making a payment can help bring your account current.',
                    'cta' => 'Make a payment',
                    'tone' => 'urgent',
                ],
                'interstitial' => 'late_payment',
            ]),
            'default-30-days-past-due' => $defaultScenario(
                '30 days past due',
                'Borrower is one full cycle past due; offers are suppressed and payment help is primary.',
                30,
                '2026-06-14',
                428.00,
                ['modules' => ['30-day alert', 'Payment CTA', 'Loan card', 'Offers suppressed']]
            ),
            'default-60-days-past-due' => $defaultScenario(
                '60 days past due',
                'Borrower has missed multiple payment cycles and needs a higher-priority collections message.',
                60,
                '2026-05-15',
                642.00,
                [
                    'modules' => ['60-day alert', 'Payment CTA', 'Loan card', 'Offers suppressed'],
                    'servicing_alert' => [
                        'title' => 'Your account needs attention',
                        'modal_title' => 'Your account is 60 days past due',
                        'modal_body' => '$pastDue is past due. A payment today may help avoid additional account impacts.',
                    ],
                ]
            ),
            'default-90-days-past-due' => $defaultScenario(
                '90 days past due',
                'Borrower is seriously delinquent; urgent servicing content and support options take over.',
                90,
                '2026-04-15',
                856.00,
                [
                    'modules' => ['90-day alert', 'Urgent CTA', 'Loan card', 'Support options'],
                    'servicing_alert' => [
                        'title' => 'Urgent account action needed',
                        'modal_title' => 'Your account is 90 days past due',
                        'modal_body' => '$pastDue is past due. Please make a payment or contact us to discuss options.',
                    ],
                ]
            ),
            'default-charged-off' => array_replace_recursive($presentBase, [
                'group' => 'Credit & default scenarios',
                'name' => 'Charged off',
                'description' => 'Account has charged off; promotional and application content is suppressed.',
                'modules' => ['Charge-off alert', 'Contact support', 'Loan card', 'Offers suppressed'],
                'message' => null,
                'loans' => [array_replace($baseLoan, [
                    'status' => 'charged_off',
                    'next_payment_date' => '2026-03-15',
                    'past_due_amount' => 1284.00,
                    'autopay_enabled' => false,
                ])],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
                'alerts' => ['late_payment' => true],
                'default_status' => ['stage' => 'charged_off', 'label' => 'Charged off', 'days_past_due' => 120],
                'servicing_alert' => [
                    'title' => 'Account charged off',
                    'body' => 'This account requires support from our servicing team.',
                    'modal_title' => 'This account has charged off',
                    'modal_body' => 'Online payment options may be limited. Please contact us to review next steps for this account.',
                    'cta' => 'Contact support',
                    'tone' => 'critical',
                ],
                'interstitial' => 'late_payment',
            ]),
            'default-bankruptcy' => array_replace_recursive($presentBase, [
                'group' => 'Credit & default scenarios',
                'name' => 'Bankruptcy servicing',
                'description' => 'Customer has a bankruptcy servicing flag; experience uses careful support-oriented language.',
                'modules' => ['Bankruptcy notice', 'Contact support', 'Loan card', 'Offers suppressed'],
                'message' => null,
                'loans' => [array_replace($baseLoan, [
                    'status' => 'bankruptcy',
                    'next_payment_date' => '2026-07-03',
                    'past_due_amount' => 0,
                    'autopay_enabled' => false,
                ])],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
                'alerts' => ['late_payment' => true],
                'default_status' => ['stage' => 'bankruptcy', 'label' => 'Servicing review', 'days_past_due' => null],
                'servicing_alert' => [
                    'title' => 'Account under servicing review',
                    'body' => 'Some online actions may be limited. Please contact us for account support.',
                    'modal_title' => 'Your account needs specialized support',
                    'modal_body' => 'Because this account is under servicing review, some online actions may be limited. Please contact us for help with next steps.',
                    'cta' => 'Contact support',
                    'tone' => 'notice',
                ],
                'interstitial' => 'late_payment',
            ]),
            'credit-score-drop' => array_replace_recursive($presentBase, [
                'group' => 'Credit & default scenarios',
                'name' => 'Credit score drop',
                'description' => 'Current borrower with credit monitoring enabled and a notable score decrease.',
                'modules' => ['Credit score alert', 'Loan card', 'Money Hub', 'Check offers'],
                'message' => ['title' => 'Your credit score changed', 'body' => 'Your score is down 18 points. Review what may have changed.'],
                'financial_wellness' => array_replace($baseWellness, ['credit_score' => 606, 'credit_score_change' => -18, 'cash_flow_status' => 'Review recommended']),
            ]),
            'credit-utilization-high' => array_replace_recursive($presentBase, [
                'group' => 'Credit & default scenarios',
                'name' => 'High credit utilization',
                'description' => 'Connected customer with financial-health content warning about elevated utilization.',
                'modules' => ['Utilization insight', 'Credit score', 'Spending insights', 'Loan card'],
                'message' => ['title' => 'Credit usage is trending high', 'body' => 'Your Money Hub has tips that may help you manage balances.'],
                'financial_wellness' => array_replace($baseWellness, ['credit_score' => 624, 'credit_score_change' => -6, 'monthly_spending' => 3618.90, 'cash_flow_status' => 'Tighter than usual']),
            ]),
            'present-two-loans' => array_replace_recursive($presentBase, [
                'name' => 'Two active loans',
                'description' => 'Current borrower with two active accounts.',
                'modules' => ['Two loan cards', 'View all accounts', 'Check offers', 'Wellness'],
                'loans' => [
                    $baseLoan,
                    array_replace($baseLoan, [
                        'id' => 1009912,
                        'name' => 'Home improvement loan',
                        'balance' => 7350.80,
                        'next_payment_amount' => 288.00,
                        'next_payment_date' => '2026-07-25',
                        'autopay_enabled' => false,
                    ]),
                ],
            ]),
            'present-pcpq' => array_replace_recursive($presentBase, [
                'name' => 'PCPQ available',
                'description' => 'Current borrower with a live prequalified offer.',
                'modules' => ['Loan card', 'Prequalified offer', 'Wellness'],
                'offer' => ['type' => 'prequalified', 'status' => 'available', 'amount' => 3500, 'expires_at' => '2026-08-12', 'credit_impact' => 'soft_pull'],
            ]),
            'present-application-in-progress' => array_replace_recursive($presentBase, [
                'name' => 'Application in progress',
                'description' => 'Current borrower has started an additional loan application.',
                'modules' => ['Application tile', 'Next step', 'Loan card'],
                'application' => ['id' => 50021, 'status' => 'in_progress', 'progress_percent' => 62, 'current_step' => 'Income review', 'next_action' => 'Upload proof of income', 'cta' => 'Continue application'],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
                'message' => ['title' => 'Finish your application', 'body' => 'A few details are needed before we can continue reviewing it.'],
            ]),
            'present-approved-not-signed' => array_replace_recursive($presentBase, [
                'name' => 'Approved, not signed',
                'description' => 'Current borrower has been approved and needs to sign documents.',
                'modules' => ['Approval tile', 'Review and sign', 'Loan card'],
                'application' => ['id' => 50044, 'status' => 'approved', 'progress_percent' => 90, 'current_step' => 'Documents ready', 'next_action' => 'Review and sign your loan documents', 'cta' => 'Review and sign', 'expires_at' => '2026-07-20'],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
            ]),
            'origination-new-customer-started' => array_replace_recursive(array_replace($originationBase, [
                'customer' => ['relationship_status' => 'new_customer', 'first_name' => 'Avery'],
                'loans' => [],
            ]), [
                'name' => 'New customer, application started',
                'description' => 'New customer is in the origination process and does not have a loan yet.',
                'modules' => ['Application action card', 'No active loan', 'Required info', 'Branch support'],
                'message' => null,
                'application' => [
                    'id' => 62001,
                    'status' => 'in_progress',
                    'headline' => 'Finish your loan application',
                    'summary' => 'You are almost ready to submit. Complete the remaining application details so Regional Finance can review your request.',
                    'progress_percent' => 42,
                    'current_step' => 'Application details',
                    'next_action' => 'Add income, housing, and contact details',
                    'cta' => 'Continue application',
                    'urgency' => 'action',
                    'due_by' => '2026-07-21',
                ],
                'financial_wellness' => array_replace($baseWellness, [
                    'credit_monitoring_enabled' => false,
                    'bank_connected' => false,
                    'monthly_spending' => 0,
                    'cash_flow_status' => 'Not connected',
                ]),
            ]),
            'origination-started-needs-action' => array_replace_recursive($originationBase, [
                'name' => 'Started application',
                'description' => 'Borrower started a new application and needs to complete required information.',
                'modules' => ['Application action card', 'Required info', 'Loan card', 'Offers suppressed'],
                'application' => [
                    'id' => 61001,
                    'status' => 'in_progress',
                    'headline' => 'Finish your loan application',
                    'summary' => 'You started an application. Complete the next step so we can keep it moving.',
                    'progress_percent' => 35,
                    'current_step' => 'Personal details',
                    'next_action' => 'Add your housing and income information',
                    'cta' => 'Continue application',
                    'urgency' => 'action',
                ],
            ]),
            'origination-documents-needed' => array_replace_recursive($originationBase, [
                'name' => 'Documents needed',
                'description' => 'Application is paused until the customer uploads supporting documents.',
                'modules' => ['Document request', 'Upload CTA', 'Loan card', 'Offers suppressed'],
                'application' => [
                    'id' => 61018,
                    'status' => 'needs_documents',
                    'headline' => 'We need one more document',
                    'summary' => 'Upload proof of income so we can continue reviewing your application.',
                    'progress_percent' => 58,
                    'current_step' => 'Income verification',
                    'next_action' => 'Upload proof of income',
                    'cta' => 'Upload document',
                    'due_by' => '2026-07-19',
                    'urgency' => 'urgent',
                ],
            ]),
            'origination-bank-verification' => array_replace_recursive($originationBase, [
                'name' => 'Bank verification',
                'description' => 'Customer needs to verify a bank account before the application can continue.',
                'modules' => ['Bank verification', 'Connect bank CTA', 'Loan card'],
                'application' => [
                    'id' => 61027,
                    'status' => 'bank_verification',
                    'headline' => 'Verify your bank account',
                    'summary' => 'Confirm where funds should be deposited if your application is approved.',
                    'progress_percent' => 64,
                    'current_step' => 'Bank verification',
                    'next_action' => 'Connect or confirm a bank account',
                    'cta' => 'Verify bank',
                    'urgency' => 'action',
                ],
            ]),
            'origination-under-review' => array_replace_recursive($originationBase, [
                'name' => 'Submitted under review',
                'description' => 'Application was submitted; customer can track status but does not need to act yet.',
                'modules' => ['Status tracker', 'Review timeline', 'Loan card'],
                'application' => [
                    'id' => 61033,
                    'status' => 'under_review',
                    'headline' => 'Your application is under review',
                    'summary' => 'No action is needed right now. We will let you know when there is an update.',
                    'progress_percent' => 72,
                    'current_step' => 'Review in progress',
                    'next_action' => 'Check application status',
                    'cta' => 'View status',
                    'urgency' => 'status',
                ],
            ]),
            'origination-approved-sign' => array_replace_recursive($originationBase, [
                'name' => 'Approved, sign documents',
                'description' => 'Application is approved and ready for the customer to review and sign.',
                'modules' => ['Approval action card', 'Sign CTA', 'Loan card'],
                'application' => [
                    'id' => 61048,
                    'status' => 'approved',
                    'headline' => 'You are approved',
                    'summary' => 'Review and sign your loan documents to finish setting up your loan.',
                    'progress_percent' => 92,
                    'current_step' => 'Documents ready',
                    'next_action' => 'Review and sign your loan documents',
                    'cta' => 'Review and sign',
                    'expires_at' => '2026-07-22',
                    'urgency' => 'approved',
                ],
            ]),
            'origination-decision-not-approved' => array_replace_recursive($originationBase, [
                'name' => 'Application not approved',
                'description' => 'Application received a decision; dashboard provides next steps and support.',
                'modules' => ['Decision notice', 'Next steps', 'Loan card'],
                'application' => [
                    'id' => 61059,
                    'status' => 'not_approved',
                    'headline' => 'Application decision available',
                    'summary' => 'We are unable to approve this application right now. You can review next steps and available support.',
                    'progress_percent' => 100,
                    'current_step' => 'Decision available',
                    'next_action' => 'Review your decision notice',
                    'cta' => 'View decision',
                    'urgency' => 'notice',
                ],
            ]),
            'former-no-loan' => array_replace_recursive($formerBase, [
                'name' => 'No active loan',
                'description' => 'Former borrower with no current account and standard eligibility.',
                'modules' => ['Welcome back', 'Check offers', 'Credit score', 'Education'],
            ]),
            'former-pcpq' => array_replace_recursive($formerBase, [
                'name' => 'PCPQ available',
                'description' => 'Former borrower with a live prequalified offer.',
                'modules' => ['Welcome back', 'Prequalified offer', 'Wellness'],
                'offer' => ['type' => 'prequalified', 'status' => 'available', 'amount' => 4200, 'expires_at' => '2026-08-09', 'credit_impact' => 'soft_pull'],
            ]),
            'former-application-in-progress' => array_replace_recursive($formerBase, [
                'name' => 'Application in progress',
                'description' => 'Former borrower has an unfinished digital application.',
                'modules' => ['Application tile', 'Next step', 'No loan card'],
                'application' => ['id' => 50102, 'status' => 'in_progress', 'progress_percent' => 48, 'current_step' => 'Bank verification', 'next_action' => 'Confirm your bank account', 'cta' => 'Continue application'],
                'offer' => ['type' => 'none', 'status' => 'suppressed', 'amount' => null, 'expires_at' => null, 'credit_impact' => null],
            ]),
            'wellness-connected' => array_replace_recursive($presentBase, [
                'name' => 'Financial wellness connected',
                'description' => 'Current borrower with credit monitoring, bank connection, and spending insights.',
                'modules' => ['Loan card', 'Credit score', 'Spending summary', 'Cash flow'],
                'financial_wellness' => array_replace($baseWellness, ['credit_score' => 681, 'credit_score_change' => 14, 'monthly_spending' => 3128.42, 'cash_flow_status' => 'Positive cash flow']),
                'message' => ['title' => 'New financial-health insights', 'body' => 'Your spending is tracking lower than last month.'],
            ]),
            'vehicle-connected' => array_replace_recursive($presentBase, [
                'name' => 'Vehicle connected',
                'description' => 'Current borrower with a connected vehicle and estimated equity.',
                'modules' => ['Loan card', 'Vehicle value', 'Estimated equity', 'Offers'],
                'assets' => ['vehicles' => [[
                    'year' => 2022,
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'estimated_value' => 23800,
                    'estimated_equity' => 6150,
                    'last_updated' => '2026-07-10',
                ]]],
            ]),
        ];
    }
}
