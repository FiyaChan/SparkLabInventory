<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('report.view');
    }

    public function rules(): array
    {
        return [
            // 'before_or_equal:today' stops someone requesting a report for
            // a future date range, which would just be an empty/meaningless
            // result set — cheap validation that avoids a wasted query.
            'from' => ['nullable', 'date', 'before_or_equal:to'],
            'to' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
