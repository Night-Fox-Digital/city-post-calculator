<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Segment extends Model
{
    protected $table = 'segments';

    protected $fillable = ['deal_id', 'height', 'width', 'type', 'mount', 'surface', 'start_post', 'finish_post', 'end_sleeve_type', 'end_sleeve_width', 'top_rail', 'turn_direction', 'turn_degrees', 'toprail_length', 'sleeve_upper_lower', 'custom_ordering'];

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

    public function railing() {
        return $this->belongsToMany('CityPost\Calculator\Railing', 'railing_segment', 'segment_id', 'railing_id');
    }    
}
