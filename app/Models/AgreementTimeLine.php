<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgreementTimeLine extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['transcription', 'agreement_id'];

    protected $searchableFields = ['*'];

    protected $table = 'agreement_time_lines';

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }
}
