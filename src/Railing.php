<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Railing extends Model
{
    protected $table = 'railings';

    protected $fillable = ['deal_id', 'name', 'color', 'steel', 'custom_ordering'];

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

    public function segment() {
        return $this->belongsToMany('CityPost\Calculator\Segment', 'railing_segment', 'railing_id', 'segment_id');
    }    
}
