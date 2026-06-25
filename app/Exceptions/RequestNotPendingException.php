<?php

namespace App\Exceptions;

class RequestNotPendingException extends SwapProposalException
{
    protected $message = 'This swap request has already been decided.';

    public function status(): int
    {
        return 409;
    }
}
