<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Source extends Model
{
    protected $table = 'sources';

    protected $fillable = ['name', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('name', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function customer() {
        return $this->belongsToMany('CityPost\Calculator\Customer', 'customer_source', 'source_id', 'customer_id');
    }    
}
