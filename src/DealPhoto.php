<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DealPhoto extends Model
{
    protected $table = 'deal_photos';

    protected $fillable = ['deal_id', 'path', 'filename', 'original_filename', 'caption', 'order'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('order', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function deal() {
        return $this->belongsTo('CityPost\Calculator\Deal');
    }    
}
