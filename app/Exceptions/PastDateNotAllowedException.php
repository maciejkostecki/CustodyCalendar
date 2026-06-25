<?php

namespace App\Exceptions;

class PastDateNotAllowedException extends SwapProposalException
{
    protected $message = 'A swap can only be proposed for a future date.';

    public function status(): int
    {
        return 422;
    }
}
