<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Manager extends Model
{
    protected $table = 'managers';

    protected $fillable = ['name', 'email_address', 'primary_phone', 'secondary_phone', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('name', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function deals() {
        return $this->belongsToMany('CityPost\Calculator\Deal', 'deal_manager', 'manager_id', 'deal_id');
    }
}
