<?php

declare(strict_types=1);

namespace Adapter\Framework\Http\Dto;

use Symfony\Component\HttpFoundation\Request;

interface IRequestDto
{
    public function __construct(Request $request);
}
