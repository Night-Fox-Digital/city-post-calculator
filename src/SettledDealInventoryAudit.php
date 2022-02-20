<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettledDealInventoryAudit extends Model
{
    use HasFactory;

    protected $table = 'settled_deals_inventory_audit';

    protected $guarded = ['id'];

	public function settled_deal() {
		return $this->belongsTo(SettledDeal::class);
	}

	public function part() {
		return $this->belongsTo(Part::class);
	}
}
