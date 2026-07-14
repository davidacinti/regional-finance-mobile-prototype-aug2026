<?php

namespace App\Services;

class DashboardModuleService
{
    public function build(array $scenario, bool $lateInterstitialDismissed): array
    {
        $hasLatePayment = $scenario['alerts']['late_payment'] ?? false;
        $application = $scenario['application'] ?? null;
        $offer = $scenario['offer'] ?? null;
        $wellness = $scenario['financial_wellness'] ?? [];
        $vehicles = $scenario['assets']['vehicles'] ?? [];

        return [
            'show_late_interstitial' => $hasLatePayment && ($scenario['interstitial'] ?? null) === 'late_payment' && ! $lateInterstitialDismissed,
            'show_payment_due_banner' => ($scenario['alerts']['payment_due_soon'] ?? false) && ! $hasLatePayment,
            'show_late_banner' => $hasLatePayment,
            'show_application' => filled($application),
            'show_loans' => count($scenario['loans'] ?? []) > 0,
            'show_offer' => ! $hasLatePayment && ! filled($application) && ($offer['status'] ?? null) === 'available',
            'show_credit_score' => (bool) ($wellness['credit_monitoring_enabled'] ?? false),
            'show_spending' => (bool) ($wellness['bank_connected'] ?? false),
            'show_vehicle' => count($vehicles) > 0,
            'primary_action' => $this->primaryAction($scenario),
        ];
    }

    private function primaryAction(array $scenario): array
    {
        if ($scenario['alerts']['late_payment'] ?? false) {
            return ['label' => 'Make a payment', 'url' => route('prototype.payment')];
        }

        if (filled($scenario['application'] ?? null)) {
            return ['label' => $scenario['application']['cta'], 'url' => route('prototype.application', $scenario['application']['id'])];
        }

        return ['label' => 'Check for offers', 'url' => route('prototype.offers')];
    }
}
