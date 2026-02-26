<?php

namespace App\Services;

use App\Models\SellerNotification;
use App\Models\SponsorRequest;
use App\Models\Transaction;

class SellerNotificationService
{
    /**
     * Create "order" notifications for each seller in the transaction.
     */
    public static function notifyOrderCreated(Transaction $transaction): void
    {
        $transaction->load(['sellLines.product.owner']);
        $sellerIds = $transaction->sellLines->pluck('product.owner_id')->unique()->filter();

        $orderId = $transaction->invoice_no ?? $transaction->id;
        $status = $transaction->status ?? 'pending';

        foreach ($sellerIds as $sellerId) {
            SellerNotification::firstOrCreate(
                [
                    'user_id' => $sellerId,
                    'type' => 'order',
                    'entity_id' => $transaction->id,
                ],
                [
                    'entity_type' => Transaction::class,
                    'title' => 'Order Update',
                    'description' => "Order #{$orderId} update ({$status})",
                    'read' => 0,
                ]
            );
        }
    }

    /**
     * Create "payment" notifications for each seller in the transaction.
     */
    public static function notifyPaymentReceived(Transaction $transaction): void
    {
        $transaction->load(['sellLines.product.owner', 'payments']);
        $sellerIds = $transaction->sellLines->pluck('product.owner_id')->unique()->filter();

        $orderId = $transaction->invoice_no ?? $transaction->id;
        $total = $transaction->sellLines->reduce(function ($sum, $line) {
            $isSponsored = (bool) ($line->sponsor_request_id ?? $line->sponsor_request ?? null);

            return $isSponsored ? $sum : $sum + (float) ($line->subtotal ?? 0);
        }, 0.0);

        $description = '£'.number_format($total, 2).' received for Order #'.$orderId;

        foreach ($sellerIds as $sellerId) {
            SellerNotification::firstOrCreate(
                [
                    'user_id' => $sellerId,
                    'type' => 'payment',
                    'entity_id' => $transaction->id,
                ],
                [
                    'entity_type' => Transaction::class,
                    'title' => 'Payment Received',
                    'description' => $description,
                    'read' => 0,
                ]
            );
        }
    }

    /**
     * Create "request" notification for the product owner (seller).
     */
    public static function notifySponsorRequestCreated(SponsorRequest $sponsorRequest): void
    {
        $sponsorRequest->load(['product.owner']);
        $sellerId = $sponsorRequest->product?->owner_id;
        if (! $sellerId) {
            return;
        }

        $requesterName = trim(($sponsorRequest->first_name ?? '').' '.($sponsorRequest->last_name ?? '')) ?: 'Someone';
        $productTitle = $sponsorRequest->product?->title ?? 'your item';
        $description = "{$requesterName} wants to claim {$productTitle}";

        SellerNotification::firstOrCreate(
            [
                'user_id' => $sellerId,
                'type' => 'request',
                'entity_id' => $sponsorRequest->id,
            ],
            [
                'entity_type' => SponsorRequest::class,
                'title' => 'New Request for Your Item',
                'description' => $description,
                'read' => 0,
            ]
        );
    }
}
