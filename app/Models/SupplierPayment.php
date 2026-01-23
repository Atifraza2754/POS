<?php

namespace App\Models;

use App\Models\Party\Party;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'party_id',
        'amount',
        'remaining_amount',
        'payment_for',
        'payment_type',
        'payment_note',
        'payment_date',
        'created_by',
        'updated_by',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


}
