<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\Railing;
use CityPost\Calculator\Segment;

class PostCalculator {

	/**
	 * @var Railing
	 */
	public Railing $railing;

	/**
	 * @var Segment
	 */
	public Segment $segment;

	/**
	 * @var Record<segmentId, Record<partName, numPosts>>
	 */
	public $segmentPostTypes = [];

	/**
	 * @var Record<segmentId, numPosts> $segmentPosts
	 */
	public $segmentPosts = [];

	public function __construct(Railing $railing, Segment $segment) {
		$this->segment = $segment;
		$this->railing = $railing;
	}

	public function getStandardPost() {

	}
}
