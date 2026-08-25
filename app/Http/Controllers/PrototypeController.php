<?php

namespace App\Http\Controllers;

use App\Services\DashboardModuleService;
use App\Services\PrototypeScenarioService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrototypeController extends Controller
{
    public function __construct(
        private readonly PrototypeScenarioService $scenarios,
        private readonly DashboardModuleService $modules,
    ) {
    }

    public function index(Request $request): View
    {
        $state = $this->scenarios->state($request);
        $scenario = $this->scenarios->scenario($request);

        return view('prototype.index', [
            'scenarioId' => $state['meta']['preset'] ?? PrototypeScenarioService::DEFAULT_SCENARIO,
            'appState' => $state,
            'scenario' => $scenario,
            'modules' => $this->modules->build(
                $scenario,
                (bool) $request->session()->get('prototype_interstitial_dismissed', false)
            ),
            'autopayOverride' => $request->session()->get('prototype_autopay_enrollment'),
            'scheduledPayment' => $state['payments']['pending'] ?? null,
        ]);
    }

    public function dashboard(Request $request): View
    {
        return $this->index($request);
    }

    public function scenarios(Request $request): View
    {
        return view('prototype.scenarios', [
            'appState' => $this->scenarios->state($request),
            'presets' => $this->scenarios->presets(),
            'options' => $this->scenarios->builderOptions(),
        ]);
    }

    public function selectScenario(Request $request, string $scenario): RedirectResponse
    {
        $legacyPresets = [
            'present-one-loan' => 'healthy-active',
            'present-pcpq' => 'prequalified-renewal',
            'default-30-days-past-due' => 'past-due',
            'present-application-in-progress' => 'application-progress',
            'former-no-loan' => 'former-borrower',
            'origination-new-customer-started' => 'new-customer',
        ];
        $this->scenarios->applyPreset($request, $legacyPresets[$scenario] ?? $scenario);

        return redirect()->route('prototype.index');
    }

    public function updateState(Request $request): JsonResponse|RedirectResponse
    {
        $state = $this->scenarios->update($request, [
            'customer' => ['type' => $request->input('customer.type', 'active')],
            'loans' => [
                'count' => $request->integer('loans.count', 1),
                'payment_status' => $request->input('loans.payment_status', 'current'),
            ],
            'offer' => ['type' => $request->input('offer.type', 'none')],
            'origination' => [
                'active' => $request->boolean('origination.active'),
                'step' => $request->input('origination.step'),
                'last_updated' => now()->toIso8601String(),
            ],
            'wellness' => [
                'credit_monitoring_enabled' => $request->boolean('wellness.credit_monitoring_enabled'),
                'credit_score' => $request->integer('wellness.credit_score', 642),
                'credit_score_change' => $request->input('wellness.credit_score_change', 'increase'),
                'high_utilization' => $request->boolean('wellness.high_utilization'),
                'budget_warning' => $request->boolean('wellness.budget_warning'),
                'cash_flow' => $request->input('wellness.cash_flow', 'normal'),
                'spending_trend' => $request->input('wellness.spending_trend', 'normal'),
                'bank_connected' => $request->boolean('wellness.bank_connected'),
            ],
            'vehicles' => ['count' => $request->integer('vehicles.count', 0)],
            'protection' => [
                'enabled' => $request->boolean('protection.enabled'),
                'context' => $request->input('protection.context', 'auto'),
            ],
            'meta' => ['preset' => 'custom'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['state' => $state]);
        }

        return redirect()->route('prototype.index')->with('prototype_state_saved', true);
    }

    public function applyPreset(Request $request, string $preset): JsonResponse|RedirectResponse
    {
        $state = $this->scenarios->applyPreset($request, $preset);

        if ($request->expectsJson()) {
            return response()->json(['state' => $state]);
        }

        return redirect()->route('prototype.index');
    }

    public function resetPrototype(Request $request): RedirectResponse
    {
        $this->scenarios->reset($request);

        return redirect()->route('prototype.scenarios');
    }

    public function syncState(Request $request): RedirectResponse
    {
        $state = $request->input('state');
        if (is_array($state)) {
            $this->scenarios->save($request, $state);
        }

        return back();
    }

    public function dismissInterstitial(Request $request): RedirectResponse
    {
        $request->session()->put('prototype_interstitial_dismissed', true);

        return back();
    }

    public function detail(Request $request, string $type, ?string $id = null): View
    {
        $scenario = $this->scenarios->scenario($request);

        return view('prototype.detail', [
            'type' => $type,
            'id' => $id,
            'scenario' => $scenario,
            'appState' => $this->scenarios->state($request),
            'scheduledPayment' => $scenario['payments']['pending'] ?? null,
            'paymentStatus' => $request->session()->get('prototype_payment_status'),
            'notifications' => $this->notifications($scenario, $request),
            'autopayOverride' => $request->session()->get('prototype_autopay_enrollment'),
            'autopayStatus' => $request->session()->get('prototype_autopay_status'),
        ]);
    }

    public function startApplication(Request $request): RedirectResponse
    {
        $state = $this->scenarios->startApplication($request);

        return redirect()->route('prototype.application', 62001)
            ->with('prototype_application_started', $state['origination']['step']);
    }

    public function advanceApplication(Request $request, string $application): RedirectResponse
    {
        $state = $this->scenarios->state($request);
        if (($state['origination']['step'] ?? null) === 'complete') {
            return redirect()->route('prototype.index');
        }

        $this->scenarios->moveApplication($request, 1);

        return redirect()->route('prototype.application', $application);
    }

    public function previousApplication(Request $request, string $application): RedirectResponse
    {
        $this->scenarios->moveApplication($request, -1);

        return redirect()->route('prototype.application', $application);
    }

    public function enrollAutopay(Request $request, string $loan): RedirectResponse
    {
        $mode = $request->input('autopay_mode', 'minimum') === 'extra' ? 'extra' : 'minimum';
        $additionalAmount = $mode === 'extra' ? (float) $request->input('additional_amount', 0) : 0.0;
        $accountLabel = $request->input('account_mode') === 'new'
            ? ($request->input('new_account_name') ?: 'New checking account') . ' • ' . substr((string) $request->input('new_account_number', '0000'), -4)
            : $request->input('saved_account', 'Primary Checking • 4203');

        $request->session()->put('prototype_autopay_enrollment', [
            'loan_id' => (int) $loan,
            'enrolled' => true,
            'mode' => $mode,
            'additional_amount' => $additionalAmount,
            'account_label' => $accountLabel,
        ]);

        $request->session()->flash('prototype_autopay_status', 'enrolled');

        return redirect()->route('prototype.loan.autopay', $loan);
    }

    public function cancelAutopay(Request $request, string $loan): RedirectResponse
    {
        $request->session()->put('prototype_autopay_enrollment', [
            'loan_id' => (int) $loan,
            'enrolled' => false,
            'mode' => null,
            'additional_amount' => 0.0,
            'account_label' => null,
        ]);

        $request->session()->flash('prototype_autopay_status', 'cancelled');

        return redirect()->route('prototype.loan.autopay', $loan);
    }

    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $read = $request->session()->get('prototype_read_notifications', []);
        $read[] = $notification;

        $request->session()->put('prototype_read_notifications', array_values(array_unique($read)));

        return redirect()->route('prototype.notifications');
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $scenario = $this->scenarios->scenario($request);
        $notificationIds = array_column($this->notifications($scenario, $request), 'id');

        $request->session()->put('prototype_read_notifications', $notificationIds);

        return redirect()->route('prototype.notifications');
    }

    public function schedulePayment(Request $request): RedirectResponse
    {
        $scenario = $this->scenarios->scenario($request);
        $loan = $scenario['loans'][0] ?? [];
        $minimumDue = (float) (($loan['past_due_amount'] ?? 0) > 0 ? $loan['past_due_amount'] : ($loan['next_payment_amount'] ?? 0));
        $amount = (float) $request->input('amount', $minimumDue);
        $paymentDate = Carbon::parse($request->input('payment_date', now()->toDateString()))->format('Y-m-d');
        $accountMode = $request->input('account_mode', 'saved');

        $scheduledPayment = [
            'id' => 'PMT-' . now()->format('His'),
            'loan_id' => $loan['id'] ?? 1002841,
            'loan_name' => $loan['name'] ?? 'Personal loan',
            'amount' => $amount,
            'minimum_due' => $minimumDue,
            'payment_date' => $paymentDate,
            'status' => 'Pending',
            'account' => $accountMode === 'new'
                ? [
                    'name' => $request->input('new_account_name') ?: 'New checking account',
                    'label' => ($request->input('new_account_name') ?: 'New checking account') . ' • ' . substr((string) $request->input('new_account_number', '0000'), -4),
                    'routing' => $request->input('routing_number'),
                ]
                : [
                    'name' => 'Checking',
                    'label' => $request->input('saved_account', 'Checking • 4203'),
                    'routing' => null,
                ],
            'warning' => $amount < $minimumDue
                ? 'This payment is scheduled, but it will not satisfy the minimum payment due.'
                : null,
        ];

        $this->scenarios->update($request, ['payments' => ['pending' => $scheduledPayment]]);
        $request->session()->forget('prototype_scheduled_payment');

        $request->session()->flash('prototype_payment_status', 'scheduled');

        return redirect()->route('prototype.payment');
    }

    public function cancelPayment(Request $request): RedirectResponse
    {
        $this->scenarios->update($request, ['payments' => ['pending' => null]]);
        $request->session()->forget('prototype_scheduled_payment');
        $request->session()->flash('prototype_payment_status', 'cancelled');

        return redirect()->route('prototype.payment');
    }

    public function addVehicle(Request $request): RedirectResponse
    {
        $vehicleCount = (int) ($this->scenarios->state($request)['vehicles']['count'] ?? 0);

        if ($vehicleCount < 3) {
            $this->scenarios->update($request, ['vehicles' => ['count' => $vehicleCount + 1]]);
            $request->session()->flash('prototype_vehicle_added', true);
        }

        return redirect()->route('prototype.assets');
    }

    private function notifications(array $scenario, Request $request): array
    {
        $read = $request->session()->get('prototype_read_notifications', []);
        $application = $scenario['application'] ?? null;
        $loan = $scenario['loans'][0] ?? [];
        $offer = $scenario['offer'] ?? [];
        $wellness = $scenario['financial_wellness'] ?? [];
        $scheduledPayment = $scenario['payments']['pending'] ?? null;

        $notifications = [
            [
                'id' => 'branch-help',
                'type' => 'Branch',
                'title' => 'Your branch is available',
                'body' => ($scenario['branch']['name'] ?? 'Your local branch') . ' can help with payments, documents, and account questions.',
                'time' => 'Yesterday',
                'icon' => 'ti-map-pin',
                'url' => route('prototype.support'),
            ],
        ];

        if ($wellness['credit_monitoring_enabled'] ?? false) {
            array_unshift($notifications, [
                'id' => 'money-hub-score',
                'type' => 'Money Hub',
                'title' => 'Credit score update',
                'body' => ($wellness['credit_score_change'] ?? 0) === 0
                    ? 'Your credit score is holding steady.'
                    : 'Your credit score moved ' . (($wellness['credit_score_change'] ?? 0) > 0 ? 'up' : 'down') . ' ' . abs((int) ($wellness['credit_score_change'] ?? 0)) . ' points.',
                'time' => '2 hours ago',
                'icon' => 'ti-chart-line',
                'url' => route('prototype.wellness'),
            ]);
        }

        if ($loan !== []) {
            array_unshift($notifications, [
                'id' => ($scenario['alerts']['late_payment'] ?? false) ? 'payment-past-due' : 'payment-reminder',
                'type' => 'Payment',
                'title' => ($scenario['alerts']['late_payment'] ?? false) ? 'Your account needs attention' : 'Payment reminder',
                'body' => '$' . number_format((float) ($loan['next_payment_amount'] ?? 214), 2) . (($scenario['alerts']['late_payment'] ?? false) ? ' is past due.' : ' is due soon on your personal loan.'),
                'time' => 'Today',
                'icon' => ($scenario['alerts']['late_payment'] ?? false) ? 'ti-alert-triangle' : 'ti-calendar-dollar',
                'url' => route('prototype.payment'),
            ]);
        }

        if ($application) {
            array_unshift($notifications, [
                'id' => 'application-next-step',
                'type' => 'Application',
                'title' => $application['headline'] ?? 'Application update',
                'body' => $application['next_action'] ?? 'Continue your application.',
                'time' => 'Just now',
                'icon' => 'ti-clipboard-list',
                'url' => route('prototype.application', $application['id']),
            ]);
        } elseif (($offer['status'] ?? null) === 'available') {
            $notifications[] = [
                'id' => 'offer-' . ($offer['type'] ?? 'available'),
                'type' => 'Explore',
                'title' => $offer['headline'] ?? 'See available options',
                'body' => $offer['highlight'] ?? 'Checking options will not impact your credit score.',
                'time' => '3 days ago',
                'icon' => ($offer['type'] ?? null) === 'prequalified_renewal' ? 'ti-award' : 'ti-sparkles',
                'url' => route('prototype.offers'),
            ];
        }

        if ($scheduledPayment) {
            array_unshift($notifications, [
                'id' => 'scheduled-payment',
                'type' => 'Payment',
                'title' => 'Payment scheduled',
                'body' => '$' . number_format((float) $scheduledPayment['amount'], 2) . ' is pending for ' . Carbon::parse($scheduledPayment['payment_date'])->format('M j, Y') . '.',
                'time' => 'Just now',
                'icon' => 'ti-clock-check',
                'url' => route('prototype.payment'),
            ]);
        }

        return array_map(function (array $notification) use ($read) {
            $notification['read'] = in_array($notification['id'], $read, true);

            return $notification;
        }, $notifications);
    }

    public function settings(Request $request): View
    {
        return $this->detail($request, 'settings');
    }
}
