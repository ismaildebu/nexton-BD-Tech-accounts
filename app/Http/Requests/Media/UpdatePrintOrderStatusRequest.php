<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\PrintOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrintOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-print-orders.approve') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                PrintOrder::STATUS_PRINTING,
                PrintOrder::STATUS_PRINTED,
                PrintOrder::STATUS_RECEIVED,
                PrintOrder::STATUS_CANCELLED,
            ])],
            'printed_quantity'  => ['required_if:status,' . PrintOrder::STATUS_PRINTED, 'nullable', 'integer', 'min:0'],
            'received_quantity' => ['required_if:status,' . PrintOrder::STATUS_RECEIVED, 'nullable', 'integer', 'min:0'],
        ];
    }
}
