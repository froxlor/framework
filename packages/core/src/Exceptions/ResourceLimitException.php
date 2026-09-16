<?php

namespace Froxlor\Core\Exceptions;

use Exception;

class ResourceLimitException extends Exception
{
    public function render($request)
    {
        return response()->json(['message' => $this->getMessage(), 'errors' => ['resources' => [$this->getMessage()]]], 422);
    }
}
