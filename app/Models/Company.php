<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'address',
        'colored_logo',
        'light_logo',
        'signature',
        'active_sms_api',
        'state_id',
        'bank_details',
        'tax_number',
        'show_discount',
        'allow_negative_stock_billing',
    ];
}
