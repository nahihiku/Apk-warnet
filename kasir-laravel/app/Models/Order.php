<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function billingPackage()
    {
        return $this->belongsTo(BillingPackage::class);
    }
}
