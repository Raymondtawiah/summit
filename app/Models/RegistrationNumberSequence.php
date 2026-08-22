<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationNumberSequence extends Model
{
    use HasFactory;

    protected $table = 'registration_number_sequences';

    public $timestamps = true;

    protected $fillable = ['year', 'sequence'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sequence' => 'integer',
        ];
    }
}
