<?php

namespace App\Http\Resources\CRM\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'pos_code' => $this->pos_code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'national_id' => $this->national_id,
            'responsible_person' => $this->responsible_person,
            'location_mark' => $this->location_mark,
            'tax_number' => $this->tax_number,
            'commercial_register' => $this->commercial_register,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'has_whatsapp' => $this->has_whatsapp,
            'whatsapp_number' => $this->whatsapp_number,
            'email' => $this->email,
            'credit_limit' => (float) $this->credit_limit,
            'payment_term_days' => $this->payment_term_days,
            'account_type' => $this->account_type,
            'trade_program_type' => $this->trade_program_type,
            'pos_material' => $this->pos_material,
            'cus_sings' => $this->cus_sings,
            'address_line' => $this->address_line,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'average_withdrawals' => (float) $this->average_withdrawals,
            'opening_balance' => (float) $this->opening_balance,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'default_salesman_id' => $this->default_salesman_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'company' => new \App\Http\Resources\CRM\V2\CompanyResource($this->whenLoaded('company')),
            'default_salesman' => new \App\Modules\Distribution\src\Resources\SalesmanResource($this->whenLoaded('defaultSalesman')),
            'customer_group' => new \App\Http\Resources\CRM\V2\CustomerGroupResource($this->whenLoaded('customerGroup')),
            'customer_class' => new \App\Http\Resources\CRM\V2\CustomerClassResource($this->whenLoaded('customerClass')),
            'customer_type' => new \App\Http\Resources\CRM\V2\CustomerTypeResource($this->whenLoaded('customerType')),
            'customer_account_type' => new \App\Http\Resources\CRM\V2\CustomerAccountTypeResource($this->whenLoaded('customerAccountType')),
            'trade_program_type' => new \App\Http\Resources\CRM\V2\TradeProgramTypeResource($this->whenLoaded('tradeProgramType')),
            'governorate' => new \App\Http\Resources\CRM\V2\GovernorateResource($this->whenLoaded('governorate')),
            'city' => new \App\Http\Resources\CRM\V2\CityResource($this->whenLoaded('city')),
            'area' => new \App\Http\Resources\CRM\V2\DistrictResource($this->whenLoaded('area')),
            'addresses' => \App\Http\Resources\CRM\V2\CustomerAddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => \App\Http\Resources\CRM\V2\CustomerContactResource::collection($this->whenLoaded('contacts')),
        ];
    }
}
