<?php

namespace CityPost\Calculator\Actions;

use Carbon\Carbon;
use CityPost\Calculator\Adapters\VersionedPartAdapter;
use CityPost\Calculator\LegacyPart;
use CityPost\Calculator\Price;
use Illuminate\Support\Facades\Cache;

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

	public $parts = [];

	/**
	 * @var Record<sku, LegacyPart>
	 */
	public $partsByCalculatorId;
	/**
	 * @var Record<partVerionId, LegacyPart>
	 */
	public $partsByPartVersionId;

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
	 * @var array (Record<sku, quantity>) - The calculated inventory usage for the quote
	 */
	public $customItemsByPartVersionId = [];

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

		$this->parts = collect($this->getParts($deal))->sortBy('name');
		$this->partsByCalculatorId = $this->parts->keyBy('sku');
		$this->partsByPartVersionId = $this->parts->keyBy('part_version_id');
		foreach ($this->parts as $part) {
			$this->inventoryByCalculatorId[$part->sku] = 0;
			$this->customItemsByPartVersionId[$part->part_version_id] = 0;
		}

		foreach ($deal->railings as $railing) {
			$railingInventory = new RailingInventory($railing, $this->parts);
			foreach ($railingInventory->inventoryByCalculatorId as $sku => $count) {
				$this->addInventory($sku, $count);
			}
			foreach ($railingInventory->errors as $error) {
				$this->errors[] = $error;
			}

			$this->railingInventories[$railing->id] = $railingInventory;
		}

		$customItemInventory = new CustomItemsInventory($deal, $this->partsByPartVersionId);
		foreach ($customItemInventory->getInventory() as $partVersionId => $count) {
			if (!isset($this->customItemsByPartVersionId[$partVersionId])) {
				$this->customItemsByPartVersionId[$partVersionId] = 0;
			}
			$this->customItemsByPartVersionId[$partVersionId] += $count;
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
		})->sortBy(function($count, $calculatorId) {
			return $this->partsByCalculatorId[$calculatorId]->custom_ordering;
		});
	}

	public function getCustomItemsInventory() {
		return collect($this->customItemsByPartVersionId)->filter(function($count) {
			return $count > 0;
		})->sortBy(function($count, $partVersionId) {
			return $this->partsByPartVersionId[$partVersionId]->custom_ordering;
		});
	}

	public function calculateRailingCost($railing, $deal) {
		$reseller = IsReseller::check($deal);
		$this->linealFeetPerRailing[$railing->id] = 0;
		$subtotal = 0;
		foreach ($railing->segment as $segment) {
			$linealFeet = (float) $segment->width;
			$this->linealFeetPerRailing[$railing->id] += $linealFeet;

			$segmentPrice = $this->getSegmentPrice($deal, $segment, $railing->steel, $reseller);

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

	public function getSegmentPrice($deal, $segment, $material, $isReseller) {
		$price = Price::where('type', $segment->type)
			->where('height', $segment->height)
			->where('material', $material);

		if ($deal->execution_date) {
			$price->whereDate('effective_date', '<=', $deal->execution_date)
				->where(function($query) use ($deal) {
					$query->whereDate('end_date', '>', $deal->execution_date)
						->orWhereNull('end_date');
				});
		} else {
			$price->whereNull('end_date');
		}


		$price = $price->first();
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

//		return Cache::remember('parts-' . $date->toString(), 60, function() use ($date) {
//			$partsAdapter = new VersionedPartAdapter();
//			return $partsAdapter->loadPartsByDate($date);
//		});
	}

	protected function addInventory($sku, $count) {
		if (!isset($this->inventoryByCalculatorId[$sku])) {
			$this->inventoryByCalculatorId[$sku] = 0;
		}

		$this->inventoryByCalculatorId[$sku] += $count;
	}
}
