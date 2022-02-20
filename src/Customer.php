<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = ['company_name', 'phone_number', 'email_address', 'address_line_1', 'address_line_2', 'city', 'state_province', 'zip_postal_code', 'country', 'tax_exemption', 'tax_id', 'discount_percentage', 'first_name', 'last_name', 'reseller', 'alternate_phone_number', 'custom_ordering', 'created_at', 'updated_at'];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('custom_ordering', 'ASC');
        });
    }

    public function tags() {
        return $this->morphToMany('CityPost\Calculator\Tag', 'taggable');
    }

    public function source() {
        return $this->belongsToMany('CityPost\Calculator\Source', 'customer_source', 'customer_id', 'source_id');
    }

    public function account_manager() {
        return $this->belongsToMany('CityPost\Calculator\Manager', 'customer_manager', 'customer_id', 'manager_id');
    }

    public function salespeople() {
        return $this->hasMany('CityPost\Calculator\Salesperson');
    }

    public function deal() {
        return $this->belongsToMany('CityPost\Calculator\Deal', 'customer_deal', 'customer_id', 'deal_id');
    }    
}
