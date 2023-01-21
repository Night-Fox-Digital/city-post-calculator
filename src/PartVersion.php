<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PartVersion extends \App\FoxModel
{
    protected $table = 'part_versions';

    protected $fillable = ['part_id', 'sku_id', 'effective_date', 'end_date', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function part() {
        return $this->belongsTo('CityPost\Calculator\Part');
    }

	public function ledger() {
		return $this->belongsToMany('CityPost\Calculator\Ledger', 'ledger_part_version', 'part_version_id', 'ledger_id');
	}
}
