<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\JsonResource;

class EstablishmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'country_id' => $this->country_id,
            'department_id' => $this->department_id,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'address' => $this->address,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'code' => $this->code,
            'number' => $this->number,
            'trade_address' => $this->trade_address,
            'web_address' => $this->web_address,
            'aditional_information' => $this->aditional_information,
            'identity_document_type_id' => $this->identity_document_type_id,
            'logo' => $this->logo ? asset($this->logo) : null,
            'active' => $this->active,
            'is_own' => ($this->is_own == 1) ? true : false,
        ];
    }
}
