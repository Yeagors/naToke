<?php

namespace App\Http\Requests;

use App\Models\Battery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $batteryId = $this->route('battery')?->id;

        return [
            'car_model' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'string', 'max:50'],
            'vin' => ['required', 'string', 'max:150', Rule::unique(Battery::class)->ignore($batteryId)],
            'callsign' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'car_model' => 'модель',
            'capacity' => 'ёмкость',
            'vin' => 'вин-номер',
            'callsign' => 'позывной',
        ];
    }
}
