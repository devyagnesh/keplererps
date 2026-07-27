<?php

namespace App\Services;

use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Customer portal authentication and order/invoice listings.
 */
class CustomerPortalService
{
    public function attempt(string $email, string $password): User
    {
        $user = User::query()
            ->where('email', $email)
            ->whereNotNull('party_id')
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match a customer portal account.',
            ]);
        }

        if (! $user->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => 'This portal account is inactive or outside its validity window.',
            ]);
        }

        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }

    public function partyFor(User $user): Party
    {
        return Party::query()->findOrFail((int) $user->party_id);
    }

    /**
     * @return \Illuminate\Support\Collection<int, SalesOrder>
     */
    public function orders(User $user)
    {
        return SalesOrder::query()
            ->where('customer_id', $user->party_id)
            ->orderByDesc('document_date')
            ->limit(100)
            ->get(['id', 'document_no', 'document_date', 'status', 'grand_total']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, SalesInvoice>
     */
    public function invoices(User $user)
    {
        return SalesInvoice::query()
            ->where('customer_id', $user->party_id)
            ->orderByDesc('document_date')
            ->limit(100)
            ->get(['id', 'document_no', 'document_date', 'status', 'grand_total']);
    }

    /**
     * Link an existing user to a customer party for portal access.
     */
    public function linkUserToParty(int $userId, int $partyId): User
    {
        $user = User::query()->findOrFail($userId);
        Party::query()->findOrFail($partyId);
        $user->forceFill(['party_id' => $partyId])->save();

        return $user->fresh();
    }
}
