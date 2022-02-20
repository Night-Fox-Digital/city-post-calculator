<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = ['module_field_id', 'module_field_id', 'tag_type', 'name', 'slug'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('name', 'ASC');
        });
    }

    public function fields() {
        return $this->belongsTo('NightFox\Builder\Models\ModuleField', 'module_field_id');
    }    
}
