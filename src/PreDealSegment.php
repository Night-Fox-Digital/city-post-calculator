<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PreDealSegment extends Model
{
    protected $table = 'pre_deal_segments';

    protected $fillable = ['pre_deal_id', 'height', 'width', 'top_rail', 'toprail_length', 'type', 'mount', 'surface', 'start_post', 'finish_post', 'end_sleeve_type', 'end_sleeve_width', 'turn_direction', 'turn_degrees', 'sleeve_upper_lower', 'custom_ordering'];

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

    public function railing() {
        return $this->belongsToMany('CityPost\Calculator\PreDealRailing', 'pre_deal_segment_railing', 'pre_deal_segment_id', 'railing_id');
    }    
}
