<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'customer',
        'reference',
        'billets',
        'amount',
        'installment',
        'token',
        'transaction',
        'method',
        'payment_type',
        'receipt',
        'terminal_id',
        'status'
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'billets' => 'array',
//        'customer_origin' => 'array',
    ];

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    public function getBilletsAttribute($value)
    {
        return json_decode($value);
    }
}
