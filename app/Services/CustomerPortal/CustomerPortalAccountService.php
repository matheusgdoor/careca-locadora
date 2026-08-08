<?php

namespace App\Services\CustomerPortal;

use App\Models\BusinessPartner;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class CustomerPortalAccountService
{
    public function resolvePartner(string $document, string $email): BusinessPartner
    {
        $organizationId = config('careca-public.organization_id');
        $document = preg_replace('/\D+/', '', $document) ?: '';

        $partner = BusinessPartner::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('document', $document)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();

        if (! $partner || ! $partner->isCustomer()) {
            throw ValidationException::withMessages([
                'document' => 'Não localizamos um cliente com este CPF/CNPJ e e-mail.',
            ]);
        }

        return $partner;
    }

    public function createOrLink(BusinessPartner $partner, string $password): User
    {
        $email = mb_strtolower(trim((string) $partner->email));

        $user = User::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $partner->organization_id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            return User::query()->create([
                'organization_id' => $partner->organization_id,
                'name' => $partner->display_name,
                'email' => $email,
                'cpf' => strlen((string) $partner->document) === 11
                    ? preg_replace('/\D+/', '', (string) $partner->document)
                    : null,
                'password' => $password,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'status' => 'active',
                'activated_at' => now(),
                'locale' => 'pt_BR',
                'timezone' => 'America/Cuiaba',
                'metadata' => [
                    'portal_only' => true,
                    'portal_business_partner_id' => $partner->id,
                ],
            ]);
        }

        $metadata = $user->metadata ?? [];
        $linked = $metadata['portal_business_partner_id'] ?? null;

        if (filled($linked) && $linked !== $partner->id) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já está vinculado a outro cadastro.',
            ]);
        }

        $metadata['portal_business_partner_id'] = $partner->id;

        $user->forceFill([
            'metadata' => $metadata,
            'password' => $password,
        ])->save();

        return $user->fresh();
    }

    public function partnerForUser(User $user): ?BusinessPartner
    {
        $partnerId = data_get($user->metadata, 'portal_business_partner_id');

        if (blank($partnerId)) {
            return null;
        }

        return BusinessPartner::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->whereKey($partnerId)
            ->first();
    }

    public function authenticate(string $identifier, string $password): User
    {
        $organizationId = config('careca-public.organization_id');
        $identifier = trim($identifier);
        $document = preg_replace('/\D+/', '', $identifier) ?: '';

        $user = null;

        if (str_contains($identifier, '@')) {
            $user = User::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($identifier)])
                ->first();
        } elseif (filled($document)) {
            $partner = BusinessPartner::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('document', $document)
                ->first();

            if ($partner) {
                $user = User::query()
                    ->withoutGlobalScopes()
                    ->where('organization_id', $organizationId)
                    ->where('metadata->portal_business_partner_id', $partner->id)
                    ->first();
            }
        }

        if (
            ! $user
            || ! Hash::check($password, (string) $user->password)
            || $user->status !== 'active'
            || filled($user->blocked_at)
            || blank(data_get($user->metadata, 'portal_business_partner_id'))
        ) {
            throw ValidationException::withMessages([
                'identifier' => 'CPF/CNPJ, e-mail ou senha inválidos.',
            ]);
        }

        return $user;
    }
}
