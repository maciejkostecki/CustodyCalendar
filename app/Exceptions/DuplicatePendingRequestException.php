<?php

namespace App\Exceptions;

class DuplicatePendingRequestException extends SwapProposalException
{
    protected $message = 'A pending swap request already exists for this day.';

    public function status(): int
    {
        return 409;
    }
}
