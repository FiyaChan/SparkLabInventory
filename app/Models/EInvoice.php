<?php

namespace App\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EInvoice extends Model
{
    use HasFactory;

    protected $table = 'e_invoices';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'irbm_unique_id',
        'submission_uid',
        'invoice_type',
        'source',
        'status',
        'supplier_tin',
        'supplier_name',
        'supplier_id_type',
        'supplier_id_value',
        'supplier_msic_code',
        'supplier_msic_desc',
        'supplier_sst_no',
        'supplier_email',
        'supplier_phone',
        'supplier_address',
        'buyer_tin',
        'buyer_name',
        'buyer_id_type',
        'buyer_id_value',
        'buyer_sst_no',
        'buyer_email',
        'buyer_phone',
        'buyer_address',
        'currency_code',
        'subtotal_amount',
        'discount_amount',
        'tax_rate',
        'tax_type_code',
        'tax_amount',
        'total_payable_amount',
        'document_hash',
        'qr_code_url',
        'ubl_payload',
        'lhdn_response',
        'issued_at',
        'validated_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_payable_amount' => 'decimal:2',
            'ubl_payload' => 'array',
            'lhdn_response' => 'array',
            'issued_at' => 'datetime',
            'validated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Generate inline SVG QR Code for this e-Invoice.
     */
    public function getQrCodeSvg(int $size = 150): string
    {
        if (empty($this->qr_code_url)) {
            return '';
        }

        try {
            $renderer = new ImageRenderer(
                new RendererStyle($size, 0),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            return $writer->writeString($this->qr_code_url);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get QR Code as base64 Data URI (useful for DOMPDF and thermal printers).
     */
    public function getQrCodeDataUri(int $size = 140): string
    {
        $svg = $this->getQrCodeSvg($size);
        if (empty($svg)) {
            return '';
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Human-friendly status badge styling.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'valid' => 'bg-success-subtle text-success border-success-subtle',
            'submitted' => 'bg-info-subtle text-info border-info-subtle',
            'pending' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
            'cancelled' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
            'invalid' => 'bg-danger-subtle text-danger border-danger-subtle',
            default => 'bg-light text-dark',
        };
    }
}
