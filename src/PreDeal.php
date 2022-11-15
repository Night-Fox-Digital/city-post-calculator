<?php

namespace CityPost\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PreDeal extends Model
{
    protected $table = 'pre_deals';

    protected $fillable = ['urgency_rating', 'company_name', 'name', 'shipping', 'tools', 'taxes', 'special_discount', 'creation_date', 'quote_status', 'order_status', 'shipped_on_date', 'billing_status', 'execution_date', 'invoice_date', 'payment_date', 'purchase_order', 'address_line_1', 'address_line_2', 'city', 'state_province', 'zip_postal_code', 'country', 'phone_number', 'file_gallery', 'stripe_charge_id', 'stripe_charge_amount', 'order_subtotal', 'order_discount', 'order_shipping', 'order_tools', 'order_taxes', 'custom_ordering'];

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
        return $this->hasMany('CityPost\Calculator\PreDealFile');
    }

    public function customer() {
        return $this->belongsToMany('CityPost\Calculator\Customer', 'customer_pre_deal', 'pre_deal_id', 'customer_id');
    }

    public function salesperson() {
        return $this->belongsToMany('CityPost\Calculator\Salesperson', 'pre_deal_salesperson', 'pre_deal_id', 'salesperson_id');
    }

    public function manager() {
        return $this->belongsToMany('CityPost\Calculator\Manager', 'manager_pre_deal', 'pre_deal_id', 'manager_id');
    }

    public function pre_deal_railings() {
        return $this->hasMany('CityPost\Calculator\PreDealRailing');
    }

    public function pre_deal_segments() {
        return $this->hasMany('CityPost\Calculator\PreDealSegment');
    }

    public function pre_deal_custom_items() {
        return $this->hasMany('CityPost\Calculator\PreDealCustomItem');
    }    
}
