<?php

namespace App\Modules\Distribution\src\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
