<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;

class SettledDealInventoryAudit extends Model
{
    protected $table = 'settled_deals_inventory_audit';

    protected $guarded = ['id'];

	public function settled_deal() {
		return $this->belongsTo(SettledDeal::class);
	}

	public function part() {
		return $this->belongsTo(LegacyPart::class);
	}
}
