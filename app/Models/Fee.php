<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    // Gi-specify ang table name gikan sa imong migration
    protected $table = 'student_fees';

    protected $fillable = [
        'student_id',
        'fee_name',
        'amount',
        'semester',
        'status',
    ];
}