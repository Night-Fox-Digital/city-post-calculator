<?php

namespace CityPost\Calculator\Actions;

use Carbon\Carbon;
use CityPost\Calculator\Adapters\VersionedPartAdapter;
use CityPost\Calculator\LegacyPart;
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
	 * @var Record<sku, LegacyPart>
	 */
	public $partsByCalculatorId;

	public $subtotal = 0;
	public $customItemSubtotal = 0;
	public $total = 0;
	public $discount = 0;
	public $tools = 0;
	public $taxes = 0;
	public $shipping = 0;
	public bool $includeDiscount;
	public $percentageDiscount;
	public $segmentPrices = [];

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
	public $inventoryByCalculatorId = [];

	/**
	 * @param Deal $deal
	 * @param $type
	 */

	public function __construct($deal, $type = null, $prices = null) {
		$this->prices = $prices ?: Price::get();

		if (!$type) {
			if (isset($deal->customer[0])) {
				$type = ($deal->customer[0]->reseller === 'Yes' || $deal->customer[0]->reseller === '1') ? 'wholesale' : 'direct';
			} else {
				$type = 'direct';
			}
		}

		$this->includeDiscount = $type !== 'retail';
		$this->type = $type;

		$parts = collect($this->getParts($deal))->sortBy('name');
		$this->partsByCalculatorId = $parts->keyBy('sku');
		foreach ($parts as $part) {
			$this->inventoryByCalculatorId[$part->sku] = 0;
		}

		foreach ($deal->railings as $railing) {
			$railingInventory = new RailingInventory($railing, $parts);
			foreach ($railingInventory->inventoryByCalculatorId as $sku => $count) {
				$this->inventoryByCalculatorId[$sku] += $count;
			}
			foreach ($railingInventory->errors as $error) {
				$this->errors[] = $error;
			}

			$this->railingInventories[$railing->id] = $railingInventory;
		}

		$customItemInventory = new CustomItemsInventory($deal, $parts);
		foreach ($customItemInventory->inventoryByCalculatorId as $sku => $count) {
			$this->inventoryByCalculatorId[$sku] += $count;
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
		return collect($this->inventoryByCalculatorId)->filter(function($count) {
			return $count > 0;
		});
	}

	public function calculateRailingCost($railing, $deal) {
		$reseller = IsReseller::check($deal);
		$this->linealFeetPerRailing[$railing->id] = 0;
		$subtotal = 0;
		foreach ($railing->segment as $segment) {
			$linealFeet = (float) $segment->width;
			$this->linealFeetPerRailing[$railing->id] += $linealFeet;

			$segmentPrice = $this->getSegmentPrice($segment, $railing->steel, $reseller);

			$this->segmentPrices[$segment->id] = $linealFeet * $segmentPrice;

			$subtotal += $this->segmentPrices[$segment->id];
		}

		return $subtotal;
	}

	/**
	 * For each segment, look up pricing details
	 * it's width, no less than 4 feet, multiplied by price
	 * multiply entire total by customer's discount if it exists
	 */

	protected function calculateCosts($deal) {

		$reseller = IsReseller::check($deal);

		foreach ($deal->railings as $railing) {
			$this->subtotal += $this->calculateRailingCost($railing, $deal);
		}

		$this->customItems = $deal->custom_items;

		foreach ($this->customItems as $customItem) {
			$itemPrice = (float) $customItem['retail_price'];
			$quantity = (int) $customItem['quantity'];
			$this->customItemSubtotal += $itemPrice * $quantity;
		}

		$this->subtotal += $this->customItemSubtotal;

		if ($this->includeDiscount && isset($deal->customer[0]) && is_numeric($deal->customer[0]->discount_percentage)) {
			$this->percentageDiscount = $deal->customer[0]->discount_percentage / 100;
			$this->discount = $this->subtotal * $this->percentageDiscount;
		}

		if (!empty($deal->special_discount)) {
			$specialDiscount = (float) $deal->special_discount;
			$this->discount += $specialDiscount;
		}

		$this->tools = (float) $deal->tools;
		$this->taxes = (float) $deal->taxes;
		$this->shipping = (float) $deal->shipping;

		$this->total = $this->subtotal - $this->discount + $this->tools + $this->taxes + $this->shipping;
	}

	public function getSegmentPrice($segment, $material, $isReseller) {
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

		$cost = $price->retail_cost;
		if ($this->type === 'direct' || !$isReseller) {
			$cost = $price->cost;
		}

		$cost = (float) $cost;

		if ($segment->top_rail === 'No Toprail') {
			// Retail is $5 off, Wholesale is $2.50 off, Direct is $5 off
			$discount = 5;
			if ($this->type === 'wholesale') $discount = 2.5;
			$cost -= $discount;
		}

		return $cost;
	}

	protected function getEffectiveDate($deal) {
		$date = Carbon::now();
		if (!empty($deal->execution_date)) {
			$date = Carbon::parse($deal->execution_date);
		}

		return $date;
	}

	protected function getParts($deal) {
		$partsAdapter = new VersionedPartAdapter();
		return $partsAdapter->loadPartsByDate($this->getEffectiveDate($deal));
	}
}
