<?php

namespace App\Http\Controllers;

use App\Services\DashboardModuleService;
use App\Services\PrototypeScenarioService;
use Carbon\Carbon;
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
        $scenarioId = $request->session()->get('prototype_scenario', PrototypeScenarioService::DEFAULT_SCENARIO);
        $scenario = $this->scenarios->find($scenarioId);

        return view('prototype.index', [
            'scenarioId' => $scenarioId,
            'scenario' => $scenario,
            'modules' => $this->modules->build(
                $scenario,
                (bool) $request->session()->get("prototype_interstitial_dismissed.$scenarioId", false)
            ),
        ]);
    }

    public function dashboard(Request $request): View
    {
        return $this->index($request);
    }

    public function scenarios(): View
    {
        return view('prototype.scenarios', [
            'groups' => $this->scenarios->groups(),
            'activeScenarioId' => session('prototype_scenario', PrototypeScenarioService::DEFAULT_SCENARIO),
        ]);
    }

    public function selectScenario(Request $request, string $scenario): RedirectResponse
    {
        $request->session()->put('prototype_scenario', $scenario);

        return redirect()->route('prototype.index');
    }

    public function dismissInterstitial(Request $request): RedirectResponse
    {
        $scenarioId = $request->session()->get('prototype_scenario', PrototypeScenarioService::DEFAULT_SCENARIO);
        $request->session()->put("prototype_interstitial_dismissed.$scenarioId", true);

        return back();
    }

    public function detail(Request $request, string $type, ?string $id = null): View
    {
        $scenario = $this->scenarios->find($request->session()->get('prototype_scenario'));

        return view('prototype.detail', [
            'type' => $type,
            'id' => $id,
            'scenario' => $scenario,
            'scheduledPayment' => $request->session()->get('prototype_scheduled_payment'),
            'paymentStatus' => $request->session()->get('prototype_payment_status'),
            'notifications' => $this->notifications($scenario, $request),
        ]);
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
        $scenario = $this->scenarios->find($request->session()->get('prototype_scenario'));
        $notificationIds = array_column($this->notifications($scenario, $request), 'id');

        $request->session()->put('prototype_read_notifications', $notificationIds);

        return redirect()->route('prototype.notifications');
    }

    public function schedulePayment(Request $request): RedirectResponse
    {
        $scenario = $this->scenarios->find($request->session()->get('prototype_scenario'));
        $loan = $scenario['loans'][0] ?? [];
        $minimumDue = (float) (($loan['past_due_amount'] ?? 0) > 0 ? $loan['past_due_amount'] : ($loan['next_payment_amount'] ?? 0));
        $amount = (float) $request->input('amount', $minimumDue);
        $paymentDate = Carbon::parse($request->input('payment_date', now()->toDateString()))->format('Y-m-d');
        $accountMode = $request->input('account_mode', 'saved');

        $request->session()->put('prototype_scheduled_payment', [
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
        ]);

        $request->session()->flash('prototype_payment_status', 'scheduled');

        return redirect()->route('prototype.payment');
    }

    public function cancelPayment(Request $request): RedirectResponse
    {
        $request->session()->forget('prototype_scheduled_payment');
        $request->session()->flash('prototype_payment_status', 'cancelled');

        return redirect()->route('prototype.payment');
    }

    private function notifications(array $scenario, Request $request): array
    {
        $read = $request->session()->get('prototype_read_notifications', []);
        $application = $scenario['application'] ?? null;
        $loan = $scenario['loans'][0] ?? [];
        $scheduledPayment = $request->session()->get('prototype_scheduled_payment');

        $notifications = [
            [
                'id' => 'payment-reminder',
                'type' => 'Payment',
                'title' => 'Payment reminder',
                'body' => '$' . number_format((float) ($loan['next_payment_amount'] ?? 214), 2) . ' is due soon on your personal loan.',
                'time' => 'Today',
                'icon' => 'ti-calendar-dollar',
                'url' => route('prototype.payment'),
            ],
            [
                'id' => 'money-hub-score',
                'type' => 'Money Hub',
                'title' => 'Credit score update',
                'body' => 'Your credit score moved ' . (($scenario['financial_wellness']['credit_score_change'] ?? 0) >= 0 ? 'up' : 'down') . ' ' . abs((int) ($scenario['financial_wellness']['credit_score_change'] ?? 0)) . ' points.',
                'time' => '2 hours ago',
                'icon' => 'ti-chart-line',
                'url' => route('prototype.wellness'),
            ],
            [
                'id' => 'branch-help',
                'type' => 'Branch',
                'title' => 'Your branch is available',
                'body' => ($scenario['branch']['name'] ?? 'Your local branch') . ' can help with payments, documents, and account questions.',
                'time' => 'Yesterday',
                'icon' => 'ti-map-pin',
                'url' => route('prototype.support'),
            ],
            [
                'id' => 'offer-check',
                'type' => 'Offers',
                'title' => 'Check for available options',
                'body' => 'See available loan options with no impact to your credit score.',
                'time' => '3 days ago',
                'icon' => 'ti-sparkles',
                'url' => route('prototype.offers'),
            ],
        ];

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

    public function settings(): View
    {
        return view('prototype.settings');
    }
}
