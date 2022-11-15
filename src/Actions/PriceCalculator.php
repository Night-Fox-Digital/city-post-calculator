<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\Price;

class PriceCalculator {

	/**
	 * @var Price[]
	 */
	public $prices = [];

	public bool $isWholesaler;
	/**
	 * @var Deal
	 */
	public $deal;
	/**
	 * @var Record<railingId, linealFeet>
	 */
	public $linealFeetPerRailing = [];
	/**
	 * @var array - Array of errors that occurred that would prevent an accurate quote
	 */
	public $errors = [];

	public $subtotal = 0;

	public $customItemSubtotal = 0;

	public $total = 0;

	public $discount = 0;

	public $tools = 0;

	public $taxes = 0;

	public $shipping = 0;

	public function __construct($deal, bool $isWholesaler) {
		$this->deal = $deal;
		$this->isWholesaler = $isWholesaler;
		$this->prices = Price::get();

		$this->calculateCosts();
	}

	/**
	 * For each segment, look up pricing details
	 * it's width, no less than 4 feet, multiplied by price
	 * multiply entire total by customer's discount if it exists
	 */

	protected function calculateCosts() {
		foreach ($this->deal->railings as $railing) {
			$this->linealFeetPerRailing[$railing->id] = 0;
			foreach ($railing->segment as $segment) {
				$linealFeet = (float) $segment->width;
				$this->linealFeetPerRailing[$railing->id] += $linealFeet;

				$segmentPrice = $this->getSegmentPrice($segment, $railing->steel);

				$this->subtotal += $linealFeet * $segmentPrice;
			}
		}

		$this->customItems = $this->deal->custom_items;

		foreach ($this->customItems as $customItem) {
			$itemPrice = (float) $customItem['retail_price'];
			$quantity = (int) $customItem['quantity'];
			$this->customItemSubtotal += $itemPrice * $quantity;
		}

		$this->subtotal += $this->customItemSubtotal;

		$this->discount = $this->subtotal * $this->getDiscountPercentage();

		if (!empty($this->deal->special_discount)) {
			$specialDiscount = (float) $this->deal->special_discount;
			$this->discount += $specialDiscount;
		}

		$this->tools = (float) $this->deal->tools;
		$this->taxes = (float) $this->deal->taxes;
		$this->shipping = (float) $this->deal->shipping;

		$this->total = $this->subtotal - $this->discount + $this->tools + $this->taxes + $this->shipping;
	}

	protected function getSegmentPrice($segment, $material) {
		$price = $this->prices->where('type', $segment->type)->where('height', $segment->height)->where('material', $material)->first();
		if (!$price) {
			$this->errors[] = [
				'Could not find pricing information for the following segment. Please contact support@nightfox.digital',
				$segment->toArray(),
				$material,
			];
			$price = new Price([
				'cost' => 0,
				'retail_cost' => 0,
			]);
		}

		$cost = $this->isWholesaler ? $price->cost : $price->retail_cost;

		$cost = (float) $cost;

		if ($segment->top_rail === 'No Toprail') {
			$cost -= $this->isWholesaler ? 5 : 2.50;
		}

		return $cost;
	}

	protected function getDiscountPercentage() {
		if ($this->isWholesaler && isset($this->deal->customer[0]) && is_numeric($this->deal->customer[0]->discount_percentage)) {
			return $this->deal->customer[0]->discount_percentage / 100;
		}

		return 0;
	}

}
