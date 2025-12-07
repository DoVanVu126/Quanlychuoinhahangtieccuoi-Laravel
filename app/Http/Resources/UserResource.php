<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'full_name' => $this->full_name,
            'address' => $this->address,
            'image_url' => $this->image_url, // Có thể dùng $this->image_url ? asset('storage/'.$this->image_url) : null
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}
