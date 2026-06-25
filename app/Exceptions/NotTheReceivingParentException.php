<?php

namespace App\Exceptions;

class NotTheReceivingParentException extends SwapProposalException
{
    protected $message = 'Only the other parent can decide on this request.';

    public function status(): int
    {
        return 403;
    }
}
