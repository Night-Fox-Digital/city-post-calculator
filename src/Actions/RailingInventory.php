<?php

namespace CityPost\Calculator\Actions;

class RailingInventory {

	/**
	 * @var Railing
	 */
	public $railing;

	/**
	 * @var Record<segmentId, SegmentInventory>
	 */
	public array $inventoryBySegment = [];

	public $totalPosts = 0;

	/**
	 * @var array (Record<sku, quantity>) - The calculated inventory usage for the quote
	 */
	public $inventoryBySku = [];

	/**
	 * @var array - Array of errors that occurred that would prevent an accurate quote
	 */
	public $errors = [];

	/**
	 * RailingInventory constructor.
	 * @param Railing $railing
	 */
	public function __construct($railing, $parts) {
		$this->railing = $railing;
		$this->calculate($parts);
	}

	protected function calculate($parts) {
		foreach ($parts as $part) {
			$this->inventoryBySku[$part->sku] = 0;
		}

		foreach ($this->railing->segment as $segment) {
			$segmentInventory = new SegmentInventory($this->railing, $segment, $parts);
			$this->totalPosts += $segmentInventory->totalPosts();
			$inventory = $segmentInventory->getInventory();
			foreach ($inventory as $sku => $count) {
				$this->inventoryBySku[$sku] += $count;
			}

			$this->errors = array_merge($this->errors, $segmentInventory->errors);

			$this->inventoryBySegment[$segment->id] = $segmentInventory;
		}

		$this->inventoryBySku['CBL-18'] = ceil($this->inventoryBySku['CBL-18']);

	}

	public function getInventory() {
		return collect($this->inventoryBySku)->filter(function($count) {
			return $count > 0;
		});
	}

}
