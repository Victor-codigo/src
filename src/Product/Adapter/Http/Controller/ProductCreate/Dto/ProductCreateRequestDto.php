<?php

declare(strict_types=1);

namespace Product\Adapter\Http\Controller\ProductCreate\Dto;

use Common\Adapter\Http\Dto\RequestDtoInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ProductCreateRequestDto implements RequestDtoInterface
{
    public readonly ?string $groupId;
    public readonly ?string $name;
    public readonly ?string $description;
    public readonly ?UploadedFile $image;

    public function __construct(Request $request)
    {
        $this->groupId = $request->request->get('group_id');
        $this->name = $request->request->get('name');
        $this->description = $request->request->get('description');
        $this->image = $request->files->get('image');
    }
}
