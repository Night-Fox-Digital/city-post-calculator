<?php

namespace CityPost\Calculator\Actions;

use CityPost\Calculator\Exceptions\MissingPartException;
use CityPost\Calculator\LegacyPart;

class PostParts {

	/**
	 * @param Segment $segment
	 * @return [ 'standard' => Part, 'finish' => Part ];
	 */
	public static function get($railing, $segment, $purpose = 'Horizontal', $corner = 'Standard') {
		$mount = $segment->mount;
		$height = $segment->height . '"';
		$material = $railing->steel;
		$color = $railing->color;

		if ($material === 'Zinc' && !$color) {
			$color = 'Black';
		}

		$part = LegacyPart::where('mount', $mount)
			->where('purpose', $purpose)
			->where('height', $height)
			->where('material', $material)
			->where('corner', $corner)
			->where('color', $color)
			->first();
		if (!$part) {
			$exception = new MissingPartException("Note: You're seeing this message because the segment(s) referenced below require a part type that doesn't exist in the CityPost Parts list. You do not have a part that matches the mount, purpose, height, material and/or corner given for the segment(s). " . 'One of the segments in your railing ' . $railing->name . ' is not configured properly. Please correct it. Segment ID: ' . $segment->id);
			$exception->parameters = [
				'mount' => $mount,
				'purpose' => $purpose,
				'height' => $height,
				'material' => $material,
				'corner' => $corner,
				'color' => $railing->color,
			];
			throw $exception;
		}
		return $part;
	}


}
