<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'path', 'original_name', 'alt', 'width', 'height', 'size'];

    public function url(): string
    {
        return route('media.show', ['media' => $this, 'v' => $this->updated_at?->getTimestamp()]);
    }
}
