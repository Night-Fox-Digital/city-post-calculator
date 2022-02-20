<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Deal extends Model
{
    protected $table = 'deals';

    protected $fillable = ['address_line_1', 'address_line_2', 'city', 'state_province', 'zip_postal_code', 'country', 'purchase_order', 'creation_date', 'execution_date', 'quote_status', 'order_status', 'billing_status', 'file_gallery', 'shipping', 'tools', 'taxes', 'company_name', 'name', 'special_discount', 'payment_date', 'urgency_rating', 'custom_ordering'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('id', 'DESC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function files() {
        return $this->hasMany('CityPost\Calculator\DealFile');
    }

    public function customer() {
        return $this->belongsToMany('CityPost\Calculator\Customer', 'customer_deal', 'deal_id', 'customer_id');
    }

    public function salesperson() {
        return $this->belongsToMany('CityPost\Calculator\Salesperson', 'deal_salesperson', 'deal_id', 'salesperson_id');
    }

    public function manager() {
        return $this->belongsToMany('CityPost\Calculator\Manager', 'deal_manager', 'deal_id', 'manager_id');
    }

    public function railings() {
        return $this->hasMany('CityPost\Calculator\Railing');
    }

    public function segments() {
        return $this->hasMany('CityPost\Calculator\Segment');
    }

    public function custom_items() {
        return $this->hasMany('CityPost\Calculator\CustomItem');
    }

    public function notes() {
        return $this->hasMany('CityPost\Calculator\Note');
    }    
}
