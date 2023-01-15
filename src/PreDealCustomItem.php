<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PreDealCustomItem extends Model
{
    protected $table = 'pre_deal_custom_items';

    protected $fillable = ['pre_deal_id', 'description', 'quantity', 'retail_price', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function pre_deal() {
        return $this->belongsTo('CityPost\Calculator\PreDeal');
    }

    public function part() {
        return $this->belongsToMany('CityPost\Calculator\LegacyPart', 'part_pre_deal_custom_item', 'pre_deal_custom_item_id', 'part_id');
    }
}
