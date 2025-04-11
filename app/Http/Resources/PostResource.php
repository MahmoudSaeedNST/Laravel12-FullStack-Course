<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            
            'title' => $this->title,
            'summary' => substr($this->content, 0, 50) . '...',
            'length' => strlen($this->content)
        ];
    }
    
}
