<?php

namespace App\Services;

class DashboardModuleService
{
    public function build(array $scenario, bool $interstitialDismissed): array
    {
        $hasLatePayment = $scenario['alerts']['late_payment'] ?? false;
        $application = $scenario['application'] ?? null;
        $offer = $scenario['offer'] ?? null;
        $wellness = $scenario['financial_wellness'] ?? [];
        $vehicles = $scenario['assets']['vehicles'] ?? [];
        $interstitial = $scenario['interstitial'] ?? null;
        $pendingFunding = ($application['status'] ?? null) === 'pending_funding';

        return [
            'show_late_interstitial' => $hasLatePayment && $interstitial === 'late_payment' && ! $interstitialDismissed,
            'show_offer_interstitial' => ! $hasLatePayment && ! filled($application)
                && in_array($interstitial, ['prequalified', 'invite_to_apply'], true) && ! $interstitialDismissed,
            'show_payment_due_banner' => ($scenario['alerts']['payment_due_soon'] ?? false) && ! $hasLatePayment,
            'show_late_banner' => $hasLatePayment,
            'show_application' => filled($application) && ! $pendingFunding,
            'show_pending_funding' => $pendingFunding,
            'show_loans' => count($scenario['loans'] ?? []) > 0,
            'show_offer' => ! $hasLatePayment && ! filled($application) && ($offer['status'] ?? null) === 'available',
            'show_credit_score' => (bool) ($wellness['credit_monitoring_enabled'] ?? false),
            'show_spending' => (bool) ($wellness['bank_connected'] ?? false),
            'show_vehicle' => count($vehicles) > 0,
            'primary_action' => $this->primaryAction($scenario),
            'next_best_action' => $this->nextBestAction($scenario),
        ];
    }

    private function primaryAction(array $scenario): array
    {
        if ($scenario['alerts']['late_payment'] ?? false) {
            return ['label' => 'Make a payment', 'url' => route('prototype.payment')];
        }

        if (filled($scenario['application'] ?? null) && ($scenario['application']['status'] ?? null) !== 'pending_funding') {
            return ['label' => $scenario['application']['cta'], 'url' => $this->applicationUrl($scenario['application'])];
        }

        return ['label' => 'Check for offers', 'url' => route('prototype.offers')];
    }

    private function nextBestAction(array $scenario): ?array
    {
        if ($scenario['alerts']['late_payment'] ?? false) {
            return null;
        }

        if (filled($scenario['application'] ?? null)) {
            $application = $scenario['application'];
            $nextStep = $application['next_step'] ?? null;

            return [
                'variant' => 'application',
                'eyebrow' => 'Application in progress',
                'headline' => $nextStep['headline'] ?? $application['headline'],
                'body' => $nextStep['body'] ?? $application['summary'],
                'cta' => $nextStep['cta'] ?? $application['cta'],
                'url' => $this->applicationUrl($application),
                'icon' => 'ti-clipboard-list',
            ];
        }

        $offer = $scenario['offer'] ?? [];
        if (($offer['status'] ?? null) === 'available') {
            return [
                'variant' => $offer['type'] ?? 'check_for_offers',
                'eyebrow' => $offer['eyebrow'] ?? 'For you',
                'headline' => $offer['headline'],
                'body' => $offer['body'],
                'cta' => $offer['cta'],
                'url' => route('prototype.application.start'),
                'icon' => ($offer['type'] ?? '') === 'prequalified_renewal' ? 'ti-award' : 'ti-sparkles',
                'method' => 'post',
            ];
        }

        if (($scenario['protection']['enabled'] ?? false) && count($scenario['loans'] ?? []) > 0) {
            return [
                'variant' => 'protection',
                'eyebrow' => 'Protection & benefits',
                'headline' => count($scenario['assets']['vehicles'] ?? []) > 0 ? 'Benefits for life on the road' : "Protect what you've financed",
                'body' => 'Explore optional protection and benefits available to Regional Finance customers.',
                'cta' => 'Explore protection',
                'url' => route('prototype.protection'),
                'icon' => 'ti-shield-check',
            ];
        }

        return null;
    }

    private function applicationUrl(array $application): string
    {
        return match ($application['next_step']['key'] ?? null) {
            'income' => route('prototype.lite.income'),
            'vehicle' => route('prototype.lite.vehicle'),
            'closing' => route('prototype.lite.closing'),
            default => route('prototype.application', $application['id']),
        };
    }
}
