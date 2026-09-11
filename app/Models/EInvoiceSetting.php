<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceSetting extends Model
{
    use HasFactory;

    protected $table = 'e_invoice_settings';

    protected $fillable = [
        'company_name',
        'company_tin',
        'company_reg_no',
        'company_sst_no',
        'msic_code',
        'msic_description',
        'business_activity',
        'contact_email',
        'contact_phone',
        'address_line1',
        'address_line2',
        'postal_code',
        'city',
        'state',
        'country',
        'mode',
        'lhdn_client_id',
        'lhdn_client_secret',
        'auto_generate_on_payment',
        'default_tax_rate',
        'default_tax_type_code',
    ];

    protected function casts(): array
    {
        return [
            'auto_generate_on_payment' => 'boolean',
            'default_tax_rate' => 'decimal:2',
        ];
    }

    /**
     * Singleton instance helper for settings.
     */
    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => config('app.name', 'SparkLab Kids Science'),
                'company_tin' => 'C25890123040',
                'company_reg_no' => '202601009988',
                'company_sst_no' => 'W10-2401-32000001',
                'msic_code' => '47630',
                'msic_description' => 'Retail sale of games, toys and educational scientific laboratory equipment in specialized stores',
                'business_activity' => 'Retail sale of educational kits and laboratory equipment',
                'contact_email' => 'einvoice@sparklab.my',
                'contact_phone' => '+60 3-8888 1234',
                'address_line1' => 'Lot 4.12, Discovery Mall, Jalan Ilmu',
                'address_line2' => 'Presint 5',
                'postal_code' => '62000',
                'city' => 'Putrajaya',
                'state' => 'Wilayah Persekutuan Putrajaya',
                'country' => 'MYS',
                'mode' => 'simulation',
                'auto_generate_on_payment' => true,
                'default_tax_rate' => 0.00,
                'default_tax_type_code' => '06',
            ]
        );
    }
}
