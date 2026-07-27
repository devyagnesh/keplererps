<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\GstType;
use App\Models\Party;
use App\Models\PartyAddress;
use App\Models\PartyContact;
use App\Models\State;
use App\Repositories\Interfaces\PartyRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for party (customer/supplier) master — M01.
 */
class PartyService
{
    public function __construct(
        protected PartyRepositoryInterface $repository,
        protected NumberingService $numbering
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): Party
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a party with contacts and optional extra addresses.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Party
    {
        return DB::transaction(function () use ($data): Party {
            $contacts = $data['contacts'] ?? [];
            $addresses = $data['addresses'] ?? [];
            unset($data['contacts'], $data['addresses'], $data['bank_account_number_confirmation']);

            $data = $this->normalizePartyPayload($data);
            $data['party_code'] = $this->numbering->next(DocumentSeriesType::Party);

            $duplicate = $this->repository->findByName($data['party_name']);
            $party = $this->repository->create($data);
            $this->syncContacts($party, $contacts);
            $this->syncAddresses($party, $addresses);

            if ($duplicate !== null) {
                // Duplicate name is a warning only (SRS) — returned via flash/message layer by controller.
                $party->setAttribute('duplicate_warning', $duplicate->party_code);
            }

            return $party->fresh(['billingState', 'contacts', 'addresses']);
        });
    }

    /**
     * Update a party and nested contacts/addresses.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Party
    {
        return DB::transaction(function () use ($id, $data): Party {
            $party = $this->repository->findById($id);
            $contacts = $data['contacts'] ?? [];
            $addresses = $data['addresses'] ?? [];
            unset($data['contacts'], $data['addresses'], $data['bank_account_number_confirmation']);

            if ($party->has_transactions) {
                unset($data['party_code']);
            }

            $data = $this->normalizePartyPayload($data, $party);
            $updated = $this->repository->update($id, $data);
            $this->syncContacts($updated, $contacts);
            $this->syncAddresses($updated, $addresses);

            return $updated->fresh(['billingState', 'contacts', 'addresses']);
        });
    }

    /**
     * Soft-delete only when no transactions reference the party.
     *
     * @throws ValidationException
     */
    public function delete(int $id): bool
    {
        $party = $this->repository->findById($id);

        if ($party->has_transactions) {
            throw ValidationException::withMessages([
                'party' => 'This party is referenced by transactions and cannot be deleted. Set status to Inactive instead.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Derive tax hints from GSTIN / company state (US-M01-03).
     *
     * @return array{state_id: int|null, tax_type: string|null, state_code: string|null}
     */
    public function resolveGstinHints(string $gstin, ?int $companyStateId = null): array
    {
        $gstin = strtoupper(trim($gstin));
        $stateCode = substr($gstin, 0, 2);
        $state = State::query()->where('code', $stateCode)->where('is_active', true)->first();

        $taxType = null;
        if ($state !== null && $companyStateId !== null) {
            $taxType = (int) $state->id === (int) $companyStateId ? 'cgst_sgst' : 'igst';
        }

        return [
            'state_id' => $state?->id,
            'state_code' => $stateCode,
            'tax_type' => $taxType,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function normalizePartyPayload(array $data, ?Party $existing = null): array
    {
        if (isset($data['pan'])) {
            $data['pan'] = $data['pan'] !== null && $data['pan'] !== '' ? strtoupper((string) $data['pan']) : null;
        }
        if (isset($data['gstin'])) {
            $data['gstin'] = $data['gstin'] !== null && $data['gstin'] !== '' ? strtoupper((string) $data['gstin']) : null;
        }
        if (isset($data['bank_ifsc'])) {
            $data['bank_ifsc'] = $data['bank_ifsc'] !== null && $data['bank_ifsc'] !== ''
                ? strtoupper((string) $data['bank_ifsc'])
                : null;
        }

        $gstType = GstType::from((string) $data['gst_type']);
        if ($gstType === GstType::Registered && empty($data['gstin'])) {
            throw ValidationException::withMessages([
                'gstin' => 'GSTIN is required when GST Type is Registered.',
            ]);
        }

        if (! empty($data['gstin']) && ! empty($data['billing_state_id'])) {
            $state = State::query()->find((int) $data['billing_state_id']);
            if ($state && substr((string) $data['gstin'], 0, 2) !== $state->code) {
                throw ValidationException::withMessages([
                    'billing_state_id' => 'State does not match GSTIN.',
                ]);
            }
        }

        if (
            $existing !== null
            && $existing->has_transactions
            && isset($data['billing_state_id'])
            && (int) $data['billing_state_id'] !== (int) $existing->billing_state_id
        ) {
            // Warning only — past documents keep original tax treatment (BR-03).
            $existing->setAttribute('state_change_warning', true);
        }

        return $data;
    }

    /**
     * Replace party contacts atomically.
     *
     * @param  list<array<string, mixed>>  $contacts
     *
     * @throws ValidationException
     */
    protected function syncContacts(Party $party, array $contacts): void
    {
        if (count($contacts) < 1) {
            throw ValidationException::withMessages([
                'contacts' => 'At least one contact person is required.',
            ]);
        }

        $party->contacts()->delete();

        foreach ($contacts as $index => $contact) {
            $optIn = ! empty($contact['whatsapp_opt_in']);
            PartyContact::query()->create([
                'party_id' => $party->id,
                'name' => $contact['name'],
                'mobile' => preg_replace('/[\s\-]/', '', (string) $contact['mobile']),
                'email' => isset($contact['email']) ? strtolower(trim((string) $contact['email'])) : null,
                'designation' => $contact['designation'] ?? null,
                'whatsapp_opt_in' => $optIn,
                'whatsapp_opt_in_at' => $optIn ? now() : null,
                'is_primary' => $index === 0 || ! empty($contact['is_primary']),
            ]);
        }
    }

    /**
     * Replace optional extra addresses; enforce one default per type.
     *
     * @param  list<array<string, mixed>>  $addresses
     *
     * @throws ValidationException
     */
    protected function syncAddresses(Party $party, array $addresses): void
    {
        $party->addresses()->delete();

        $defaults = [];
        foreach ($addresses as $address) {
            $type = (string) $address['address_type'];
            $isDefault = ! empty($address['is_default']);
            if ($isDefault) {
                if (isset($defaults[$type])) {
                    throw ValidationException::withMessages([
                        'addresses' => 'Exactly one default address is allowed per type.',
                    ]);
                }
                $defaults[$type] = true;
            }

            PartyAddress::query()->create([
                'party_id' => $party->id,
                'address_type' => $type,
                'label' => $address['label'] ?? null,
                'line1' => $address['line1'],
                'line2' => $address['line2'] ?? null,
                'city' => $address['city'],
                'state_id' => $address['state_id'],
                'pin_code' => $address['pin_code'],
                'country' => $address['country'] ?? 'India',
                'is_default' => $isDefault,
            ]);
        }

        foreach (collect($addresses)->groupBy('address_type') as $type => $group) {
            if ($group->isNotEmpty() && ! $group->contains(fn ($row) => ! empty($row['is_default']))) {
                throw ValidationException::withMessages([
                    'addresses' => 'Exactly one address must be marked default per type when addresses exist.',
                ]);
            }
        }
    }
}
