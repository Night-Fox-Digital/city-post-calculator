<?php

namespace CityPost\Calculator\Actions;

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

	public function __construct($railing, $segment) {
		$this->segment = $segment;
		$this->railing = $railing;
	}

	public function getStandardPost() {

	}
}
