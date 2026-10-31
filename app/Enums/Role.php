<?php

namespace App\Enums;

enum Role: string
{
    case Visitor = 'visitante';
    case Customer = 'cliente';
    case Agent = 'agente';
    case Supplier = 'proveedor';
    case Support = 'soporte';
    case Editor = 'editor';
    case Finance = 'finanzas';
    case Admin = 'administrador';
    case SuperAdmin = 'superadministrador';

    public function requiresTwoFactor(): bool
    {
        return in_array($this, [self::Admin, self::SuperAdmin, self::Finance], true);
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Visitor => [],
            self::Customer => ['reservations.view-own'],
            self::Agent => ['quotes.manage', 'inquiries.manage', 'bookings.manage'],
            self::Supplier => ['inventory.manage-own'],
            self::Support => ['support.manage', 'inquiries.manage', 'bookings.manage'],
            self::Editor => ['content.manage'],
            self::Finance => ['finance.manage', 'payments.view'],
            self::Admin => ['operations.manage', 'providers.manage', 'services.manage', 'content.manage', 'inquiries.manage', 'bookings.manage', 'audit.view', 'payments.view'],
            self::SuperAdmin => ['operations.manage', 'settings.manage', 'providers.manage', 'services.manage', 'content.manage', 'inquiries.manage', 'bookings.manage', 'audit.view', 'payments.view'],
        };
    }
}
