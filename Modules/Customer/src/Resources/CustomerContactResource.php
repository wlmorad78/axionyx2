<?php

namespace App\Modules\Customer\src\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'position' => $this->position,
        ];
    }
}
