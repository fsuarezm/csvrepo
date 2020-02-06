<?php

declare(strict_types=1);

namespace TalkingBit\BddExample;

use TalkingBit\BddExample\FileReader\FileReader;
use TalkingBit\BddExample\VO\FilePath;

class UpdatePricesFromUploadedFile
{
    /** @var ProductRepository */
    private $productRepository;
    /**
     * @var FileReader
     */
    private $fileReader;

    public function __construct(ProductRepository $productRepository, FileReader $fileReader)
    {
        $this->productRepository = $productRepository;
        $this->fileReader = $fileReader;
    }

    public function usingFile(FilePath $pathToFile)
    {
        $data = $this->fileReader->readFrom($pathToFile);
        foreach ($data as $row) {
            $product = $this->productRepository->getById($row['product_id']);
            $product->setPrice((float)$row['new_price']);
        }
    }
}
