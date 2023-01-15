<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Part extends \App\FoxModel
{
    protected $table = 'parts';

    protected $fillable = ['type', 'name', 'quantity_on_hand', 'quantity_note', 'color', 'purpose', 'mount', 'corner', 'height', 'material', 'sku', 'track_inventory', 'sku_id', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('type', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('App\Tag', 'taggable');
    }

    public function part_type() {
        return $this->belongsToMany('App\PartType', 'part_part_type', 'part_id', 'part_type_id');
    }

    public function ledger() {
        return $this->belongsToMany('App\Ledger', 'ledger_part', 'part_id', 'ledger_id');
    }

    public function custom_items() {
        return $this->belongsToMany('App\CustomItem', 'custom_item_part', 'part_id', 'custom_item_id');
    }

    public function part_versions() {
        return $this->hasMany('App\PartVersion');
    }
}
