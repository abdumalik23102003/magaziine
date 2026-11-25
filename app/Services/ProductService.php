<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Product;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private PartnerService $partners) {}

    /** Hamkor yaratadi: brend tekshiriladi, mahsulot pending bo'ladi (adminniki darhol approved). */
    public function createForPartner(array $data, User $user): Product
    {
        $this->partners->assertOwnsBrand($user, $data['brand_id'] ?? null);

        $product = new Product($data);
        $product->forceFill([
            'approval_status' => $user->isAdmin() ? ApprovalStatus::Approved : ApprovalStatus::Pending,
        ])->save();

        return $product;
    }

    /** Hamkor: qoralama yoki rad etilgan mahsulotni qayta ko'rib chiqishga yuboradi. */
    public function submit(Product $product): Product
    {
        $this->expect($product, [ApprovalStatus::Draft, ApprovalStatus::Rejected], 'submitted for review');

        return $this->move($product, ApprovalStatus::Pending, [
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approve(Product $product, User $reviewer): Product
    {
        $this->expect($product, [ApprovalStatus::Pending], 'approved');

        return $this->move($product, ApprovalStatus::Approved, [
            'rejection_reason' => null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(Product $product, User $reviewer, string $reason): Product
    {
        $this->expect($product, [ApprovalStatus::Pending], 'rejected');

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A rejection reason is required.');
        }

        return $this->move($product, ApprovalStatus::Rejected, [
            'rejection_reason' => Str::limit($reason, 1000, ''),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Tasdiqlangan mahsulotning kuzatiladigan qismi o'zgargan: config/partner.php ruxsat bersa, pending ga qaytadi.
     * $area: name | description | images | variants.
     */
    public function reapproveIfWatched(Product $product, string $area): bool
    {
        if (! config('partner.reapprove_on_change') || ! config("partner.watch.{$area}")) {
            return false;
        }

        if ($product->approval_status !== ApprovalStatus::Approved) {
            return false;
        }

        $product->forceFill([
            'approval_status' => ApprovalStatus::Pending,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->saveQuietly();

        return true;
    }

    /** @param  array<int, ApprovalStatus>  $allowed */
    private function expect(Product $product, array $allowed, string $action): void
    {
        if (! in_array($product->approval_status, $allowed, true)) {
            throw new DomainException("A {$product->approval_status->value} product cannot be {$action}.");
        }
    }

    /** @param  array<string, mixed>  $attributes */
    private function move(Product $product, ApprovalStatus $status, array $attributes): Product
    {
        $product->forceFill(['approval_status' => $status] + $attributes)->save();

        return $product;
    }
}
