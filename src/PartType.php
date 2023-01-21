<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PartType extends \App\FoxModel
{
    protected $table = 'part_types';

    protected $fillable = ['name', 'custom_ordering'];

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
        return $this->belongsToMany('CityPost\Calculator\Part', 'part_part_type', 'part_type_id', 'part_id');
    }    
}
