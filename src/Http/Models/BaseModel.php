<?php

namespace Impulse\Pulsifier\Model;

use Illuminate\Database\Eloquent\Model;
use Impulse\Pulsifier\Traits\HasPulseAttribute;

abstract class BaseModel extends Model
{
    use HasPulseAttribute;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // $this->fillable[] = 'guid';
        // $this->savable_relations[] = 'logs';
    }
}
