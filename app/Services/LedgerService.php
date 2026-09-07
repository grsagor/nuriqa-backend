<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;

class LedgerService
{
    /**
     * Record auditable money classification for a checkout transaction.
     *
     * @return array<int, LedgerEntry>
     */
    public function recordCheckout(Transaction $transaction): array
    {
        $transaction->loadMissing(['sellLines.product', 'user']);
        $entries = [];

        foreach ($transaction->sellLines as $line) {
            $contribution = (float) ($line->contribution_amount ?? $line->subtotal ?? 0);
            if ($contribution > 0) {
                $entries[] = $this->write(
                    $transaction,
                    $line->product?->owner_id,
                    $line->product?->allowsFlexibleContribution()
                        ? LedgerEntry::TYPE_CONTRIBUTION
                        : LedgerEntry::TYPE_ITEM_PRICE,
                    'credit',
                    $contribution,
                    $line,
                );
            }

            $adminFee = (float) ($line->platform_fee_amount ?? 0);
            if ($adminFee > 0) {
                $entries[] = $this->write(
                    $transaction,
                    null,
                    LedgerEntry::TYPE_ADMIN_FEE,
                    'credit',
                    $adminFee,
                    $line,
                );
            }

            $cause = (float) ($line->cause_allocation_amount ?? $line->donation_amount ?? 0)
                + (float) ($line->voluntary_donation_amount ?? 0);
            if ($cause > 0) {
                $entries[] = $this->write(
                    $transaction,
                    null,
                    LedgerEntry::TYPE_CAUSE_ALLOCATION,
                    'credit',
                    $cause,
                    $line,
                    ['cause_id' => $line->cause_id],
                );
            }
        }

        $delivery = (float) ($transaction->delivery_fee ?? 0);
        if ($delivery > 0) {
            $entries[] = $this->write(
                $transaction,
                null,
                LedgerEntry::TYPE_DELIVERY,
                'credit',
                $delivery,
                $transaction,
                ['delivery_payer' => $transaction->delivery_payer],
            );
        }

        return $entries;
    }

    public function recordRefund(Transaction $transaction, float $amount, ?User $actor = null): LedgerEntry
    {
        return $this->write(
            $transaction,
            $transaction->user_id,
            LedgerEntry::TYPE_REFUND,
            'debit',
            $amount,
            $transaction,
            ['actor_id' => $actor?->id],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function write(
        Transaction $transaction,
        ?int $userId,
        string $entryType,
        string $direction,
        float $amount,
        mixed $reference = null,
        array $meta = [],
    ): LedgerEntry {
        return LedgerEntry::query()->create([
            'transaction_id' => $transaction->id,
            'user_id' => $userId,
            'entry_type' => $entryType,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'currency' => 'GBP',
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference->id ?? null,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
