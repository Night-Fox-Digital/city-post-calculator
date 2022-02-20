<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\Deal;
use CityPost\Calculator\Part;
use CityPost\Calculator\Price;

class QuoteCalculator {

	/**
	 * @var RailingInventory[]
	 */
	public $railingInventories = [];

	public $customItems = [];

	/**
	 * @var Price[]
	 */
	public $prices = [];

	/**
	 * @var Record<sku, Part>
	 */
	public $partsBySku;

	public $subtotal = 0;
	public $customItemSubtotal = 0;
	public $total = 0;
	public $discount = 0;
	public $tools = 0;
	public $taxes = 0;
	public $shipping = 0;
	public bool $includeDiscount;

	public $type;

	/**
	 * @var Record<railingId, linealFeet>
	 */
	public $linealFeetPerRailing = [];

	/**
	 * @var array - Array of errors that occurred that would prevent an accurate quote
	 */
	public $errors = [];

	/**
	 * @var array (Record<sku, quantity>) - The calculated inventory usage for the quote
	 */
	public $inventoryBySku = [];

	/**
	 * @param Deal $deal
	 * @param $type
	 */

	public function __construct(Deal $deal, $type = null, $prices = null) {
		$this->prices = $prices ?: Price::get();

		if (!$type) {
			if (isset($deal->customer[0])) {
				$type = ($deal->customer[0]->reseller === 'Yes' || $deal->customer[0]->reseller === '1') ? 'wholesale' : 'direct';
			} else {
				$type = 'direct';
			}
		}

		$this->includeDiscount = $type !== 'direct';
		$this->type = $type;

		$parts = Part::get();
		$this->partsBySku = $parts->keyBy('sku');
		foreach ($parts as $part) {
			$this->inventoryBySku[$part->sku] = 0;
		}

		foreach ($deal->railings as $railing) {
			$railingInventory = new RailingInventory($railing, $parts);
			foreach ($railingInventory->inventoryBySku as $sku => $count) {
				$this->inventoryBySku[$sku] += $count;
			}
			foreach ($railingInventory->errors as $error) {
				$this->errors[] = $error;
			}

			$this->railingInventories[$railing->id] = $railingInventory;
		}

		$this->calculateCosts($deal);
	}

	public function totalLinealFeet() {
		$total = 0;
		foreach ($this->linealFeetPerRailing as $linealFeet) {
			$total += $linealFeet;
		}

		return $total;
	}

	public function getInventory() {
		return collect($this->inventoryBySku)->filter(function($count) {
			return $count > 0;
		});
	}

	/**
	 * For each segment, look up pricing details
	 * it's width, no less than 4 feet, multiplied by price
	 * multiply entire total by customer's discount if it exists
	 */

	protected function calculateCosts(Deal $deal) {

		$reseller = IsReseller::check($deal);

		foreach ($deal->railings as $railing) {
			$this->linealFeetPerRailing[$railing->id] = 0;
			foreach ($railing->segment as $segment) {
				$linealFeet = (float) $segment->width;
				$this->linealFeetPerRailing[$railing->id] += $linealFeet;

				$segmentPrice = $this->getSegmentPrice($segment, $railing->steel, $reseller && $this->type !== 'wholesale');

				$this->subtotal += $linealFeet * $segmentPrice;
			}
		}

		$this->customItems = $deal->custom_items;

		foreach ($this->customItems as $customItem) {
			$itemPrice = (float) $customItem['retail_price'];
			$quantity = (int) $customItem['quantity'];
			$this->customItemSubtotal += $itemPrice * $quantity;
		}

		$this->subtotal += $this->customItemSubtotal;

		if (isset($deal->customer[0]) && is_numeric($deal->customer[0]->discount_percentage)) {
			$percentageDiscount = $deal->customer[0]->discount_percentage / 100;
			$this->discount = $this->subtotal * $percentageDiscount;
		}

		if ($this->includeDiscount && !empty($deal->special_discount)) {
			$specialDiscount = (float) $deal->special_discount;
			$this->discount += $specialDiscount;
		}

		$this->tools = (float) $deal->tools;
		$this->taxes = (float) $deal->taxes;
		$this->shipping = (float) $deal->shipping;

		$this->total = $this->subtotal - $this->discount + $this->tools + $this->taxes + $this->shipping;
	}

	protected function getSegmentPrice($segment, $material, $useRetailPrice) {
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

		$cost = $useRetailPrice ? $price->retail_cost : $price->cost;

		$cost = (float) $cost;

		if ($segment->top_rail === 'No Toprail') {
			$cost -= $useRetailPrice ? 2.50 : 5;
		}

		return $cost;
	}
}
