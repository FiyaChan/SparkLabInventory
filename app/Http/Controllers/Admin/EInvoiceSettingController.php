<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EInvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EInvoiceSettingController extends Controller
{
    /**
     * Show e-Invoice settings form.
     */
    public function index()
    {
        $this->authorizeSettingsAccess();

        $settings = EInvoiceSetting::current();

        return view('admin.einvoices.settings', compact('settings'));
    }

    /**
     * Update e-Invoice company tax profile & API configurations.
     */
    public function update(Request $request)
    {
        $this->authorizeSettingsAccess();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_tin' => ['required', 'string', 'max:30'],
            'company_reg_no' => ['required', 'string', 'max:30'],
            'company_sst_no' => ['nullable', 'string', 'max:30'],
            'msic_code' => ['required', 'string', 'max:10'],
            'msic_description' => ['required', 'string', 'max:255'],
            'business_activity' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:3'],
            'mode' => ['required', 'in:simulation,sandbox,production'],
            'lhdn_client_id' => ['nullable', 'string', 'max:255'],
            'lhdn_client_secret' => ['nullable', 'string', 'max:255'],
            'auto_generate_on_payment' => ['nullable', 'boolean'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_tax_type_code' => ['required', 'string', 'max:10'],
        ]);

        $validated['auto_generate_on_payment'] = $request->has('auto_generate_on_payment');

        $settings = EInvoiceSetting::current();
        $settings->update($validated);

        ActivityLog::record('einvoice.settings_updated', [
            'by' => Auth::id(),
            'mode' => $settings->mode,
            'tin' => $settings->company_tin,
        ]);

        return back()->with('status', 'LHDN e-Invoice configuration saved successfully!');
    }

    protected function authorizeSettingsAccess(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('user.manage') && ! $user->hasAnyRole(['admin', 'superadmin']))) {
            abort(403, 'Unauthorized access to e-Invoice settings.');
        }
    }
}
