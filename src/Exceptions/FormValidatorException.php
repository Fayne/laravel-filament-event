<?php

namespace Fayne\Event\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FormValidatorException extends ValidationException
{
    private int $customCode;

    /**
     * CustomValidationException constructor.
     *
     * @param  int  $customCode
     * @param  null  $response
     * @param string $errorBag
     */
    public function __construct($validator, $customCode = 422000, $response = null, string $errorBag = 'default')
    {
        parent::__construct($validator, $response, $errorBag);

        $this->customCode = $customCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => $this->errors(),
            'code' => $this->customCode,
            'result' => 0,
        ], $this->status);
    }
}
