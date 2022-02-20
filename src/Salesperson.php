<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Salesperson extends Model
{
    protected $table = 'salespeople';

    protected $fillable = ['customer_id', 'name', 'phone_number', 'email_address', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function customer() {
        return $this->belongsTo('CityPost\Calculator\Customer');
    }

    public function deal() {
        return $this->belongsToMany('CityPost\Calculator\Deal', 'deal_salesperson', 'salesperson_id', 'deal_id');
    }    
}
