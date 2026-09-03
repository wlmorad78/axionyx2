<?php

namespace App\Http\Resources\CRM\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'address_line' => $this->address_line ?? $this->address,
            'governorate_id' => $this->governorate_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id ?? $this->area_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
