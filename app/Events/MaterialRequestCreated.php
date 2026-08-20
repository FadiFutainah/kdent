<?php

namespace App\Events;

use App\Models\MaterialRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaterialRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MaterialRequest $request
    ) {}
    
}