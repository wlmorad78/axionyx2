<?php

namespace App\Modules\Distribution\src\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'national_id' => $this->national_id,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'target_amount' => (float) $this->target_amount,
            'commission_type' => $this->commission_type,
            'commission_value' => (float) $this->commission_value,
            'commission_rate' => (float) $this->commission_rate,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'company' => new \App\Http\Resources\CRM\V2\CompanyResource($this->whenLoaded('company')),
            'branch' => new \App\Http\Resources\CRM\V2\BranchResource($this->whenLoaded('branch')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'sales_team' => new SalesTeamResource($this->whenLoaded('salesTeam')),
            'supervisor' => new SalesmanResource($this->whenLoaded('supervisor')),
            'subordinates' => SalesmanResource::collection($this->whenLoaded('subordinates')),
        ];
    }
}
