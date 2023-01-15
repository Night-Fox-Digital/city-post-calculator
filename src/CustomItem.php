<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomItem extends Model
{
    protected $table = 'custom_items';

    protected $fillable = ['deal_id', 'description', 'quantity', 'retail_price', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function deal() {
        return $this->belongsTo('CityPost\Calculator\Deal');
    }

    public function part() {
        return $this->belongsToMany('CityPost\Calculator\LegacyPart', 'custom_item_part', 'custom_item_id', 'part_id');
    }
}
