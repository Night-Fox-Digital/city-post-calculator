<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Price extends Model
{
    protected $table = 'pricing';

    protected $fillable = ['cost', 'commercial_only_warning', 'material', 'height', 'type', 'retail_cost', 'effective_date', 'end_date', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }    
}
