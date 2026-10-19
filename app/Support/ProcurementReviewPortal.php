<?php

namespace App\Support;

/**
 * School Administrator owns ATP, Purchase Order, and Request Check review
 * (see TODO.md procurement flow). Accounting keeps Liquidation / funds history.
 */
class ProcurementReviewPortal
{
    public const PREFIX = 'school-admin';

    public const BASE = '/school-admin';

    public const LAYOUT = 'layouts.admin-layout';

    public const ROLE = WorkflowNotifier::ROLE_SCHOOL_ADMIN;

    public const APPROVER_LABEL = 'School Administrator';

    public const LOG_LEVEL = 'School Administrator';

    public const SESSION_ATP_STATUS = 'school_admin.atp_status';

    public const SESSION_PO_STATUS = 'school_admin.po_status';

    public const SESSION_RFC_STATUS = 'school_admin.rfc_status';

    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path === '' ? self::BASE : self::BASE.'/'.$path;
    }

    public static function atp(?int $id = null): string
    {
        return $id
            ? self::url('authority-to-purchase/'.$id)
            : self::url('authority-to-purchase');
    }

    public static function purchaseOrder(?int $id = null): string
    {
        return $id
            ? self::url('purchase-orders/'.$id)
            : self::url('purchase-orders');
    }

    public static function requestCheck(?int $id = null): string
    {
        return $id
            ? self::url('request-check/'.$id)
            : self::url('request-check');
    }

    public static function disbursementVoucher(?int $id = null): string
    {
        return $id
            ? self::url('disbursement-vouchers/'.$id)
            : self::url('disbursement-vouchers');
    }

    public static function ris(int $id): string
    {
        return self::url('ris/'.$id);
    }

    /** Document types reviewed on this portal (not Accounting). */
    public static function ownsDocumentType(string $type): bool
    {
        return in_array(strtoupper($type), ['ATP', 'PO', 'RFC', 'DV'], true);
    }
}
