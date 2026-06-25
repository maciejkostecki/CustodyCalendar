<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base for swap-proposal domain errors. `status` is the HTTP status the
 * controller should return for this kind of failure.
 */
abstract class SwapProposalException extends RuntimeException
{
    abstract public function status(): int;
}
