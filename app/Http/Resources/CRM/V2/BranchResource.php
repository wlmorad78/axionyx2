<?php

namespace App\Http\Resources\CRM\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,
        ];
    }
}
