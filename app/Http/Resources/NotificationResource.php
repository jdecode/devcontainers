<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Str;

/** @mixin DatabaseNotification */
class NotificationResource extends JsonResource
{
    /**
     * @inheritDoc
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => Str::afterLast($this->type, '\\'),
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
