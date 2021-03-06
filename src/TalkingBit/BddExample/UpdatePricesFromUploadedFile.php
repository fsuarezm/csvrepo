<?php

declare(strict_types=1);

namespace TalkingBit\BddExample;

use RuntimeException;
use TalkingBit\BddExample\FileReader\FileReader;
use TalkingBit\BddExample\VO\FilePath;
use UnexpectedValueException;

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
        try {
            $data = $this->fileReader->readFrom($pathToFile);
            foreach ($data as $row) {
                $this->checkIsAValidDataStructure($row);

                $product = $this->productRepository->getById($row['product_id']);
                $product->setPrice((float)$row['new_price']);
            }
        } catch (UnexpectedValueException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw new RuntimeException('Something went wrong and it was not possible to update prices');
        }
    }

    private function checkIsAValidDataStructure($row): void
    {
        if (! isset($row['product_id']) || ! isset($row['new_price'])) {
            throw new \UnexpectedValueException('The file doesn\'t contain valid data to update prices');
        }
    }
}
