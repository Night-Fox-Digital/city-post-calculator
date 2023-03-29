<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;

class SettledDeal extends Model
{

    protected $guarded = ['id'];

    public function deal() {
    	return $this->belongsTo(Deal::class);
	}

	public function inventory_audit() {
    	return $this->hasMany(SettledDealInventoryAudit::class);
	}
}
