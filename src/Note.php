<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Note extends Model
{
    protected $table = 'notes';

    protected $fillable = ['deal_id', 'date', 'note', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('date', 'DESC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function deal() {
        return $this->belongsTo('CityPost\Calculator\Deal');
    }    
}
