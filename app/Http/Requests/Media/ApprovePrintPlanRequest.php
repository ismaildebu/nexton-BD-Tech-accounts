<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePrintPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-print-planning.approve') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'adjusted_quantity' => ['nullable', 'integer', 'min:0'],
            // Required only when adjusted_quantity differs from the
            // system recommendation — enforced in PrintPlanningService::approve(),
            // since that comparison needs the plan's own recommended_quantity.
            'adjustment_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
