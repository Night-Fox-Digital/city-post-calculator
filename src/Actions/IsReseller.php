<?php

namespace CityPost\Calculator\Actions;

class IsReseller {

	public static function check($deal) {
		$reseller = false;
		if (isset($deal->customer[0])) {
			$reseller = $deal->customer[0]->reseller === 'Yes' || $deal->customer[0]->reseller == 1;
		}

		return $reseller;
	}
}
