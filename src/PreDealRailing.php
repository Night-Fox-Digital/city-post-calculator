<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PreDealRailing extends Model
{
    protected $table = 'pre_deal_railings';

    protected $fillable = ['pre_deal_id', 'name', 'color', 'steel', 'custom_ordering'];

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

    public function segment() {
        return $this->belongsToMany('CityPost\Calculator\PreDealSegment', 'pre_deal_segment_railing', 'railing_id', 'pre_deal_segment_id');
    }    
}
