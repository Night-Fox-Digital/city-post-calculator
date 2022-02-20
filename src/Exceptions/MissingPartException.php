<?php

namespace CityPost\Calculator\Exceptions;

use Exception;

class MissingPartException extends Exception
{
    public $parameters = [];
}
