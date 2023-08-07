<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Terminal extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'ip_address',
        'authorization_key',
        'remote_id',
        'remote_password',
        'active',
        'responsible_name',
        'contact_primary',
        'contact_secondary',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'zipcode',
        'paygo_id',
        'paygo_login',
        'paygo_password',
    ];

    protected $guarded = [
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
