<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettledDeal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function deal() {
    	return $this->belongsTo(Deal::class);
	}

	public function inventory_audit() {
    	return $this->hasMany(SettledDealInventoryAudit::class);
	}
}
