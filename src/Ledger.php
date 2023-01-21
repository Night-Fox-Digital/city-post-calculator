<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Ledger extends Model
{
    protected $table = 'ledger';

    protected $fillable = ['notes', 'quantity', 'date_received', 'adjustment_type', 'cost', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('date_received', 'DESC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function part() {
        return $this->belongsToMany('CityPost\Calculator\LegacyPart', 'ledger_part', 'ledger_id', 'part_id');
    }

    public function part_version() {
        return $this->belongsToMany('CityPost\Calculator\PartVersion', 'ledger_part_version', 'ledger_id', 'part_version_id');
    }
}
