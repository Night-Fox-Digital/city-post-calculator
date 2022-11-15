<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PreDealFile extends Model
{
    protected $table = 'pre_deal_files';

    protected $fillable = ['pre_deal_id', 'path', 'filename', 'original_filename', 'caption', 'order'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('order', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function pre_deal() {
        return $this->belongsTo('CityPost\Calculator\PreDeal');
    }    
}
