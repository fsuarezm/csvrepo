<?php

namespace Acceptance\Product\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;
use TalkingBit\BddExample\FileReader\CSVFileReader;
use TalkingBit\BddExample\Persistence\InMemoryProductRepository;
use TalkingBit\BddExample\Product;
use TalkingBit\BddExample\UpdatePricesFromUploadedFile;
use TalkingBit\BddExample\VO\FilePath;
use Throwable;

/**
 * Defines application features from the specific context.
 */
class MassiveUpdateContext implements Context
{
    /** @var FilePath */
    private $pathToFile;
    /** @var UpdatePricesFromUploadedFile */
    private $updatePricesFromUploadedFile;
    /**
     * @var InMemoryProductRepository
     */
    private $productRepository;
    /**
     * @var \Exception|Throwable
     */
    private $lastException;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->productRepository = new InMemoryProductRepository();
        $this->updatePricesFromUploadedFile = new UpdatePricesFromUploadedFile(
            $this->productRepository,
            new CSVFileReader()
        );
    }

    /**
     * @Given There are current prices in the system
     */
    public function thereAreCurrentPricesInTheSystem(TableNode $productTable)
    {
        foreach ($productTable as $productRow) {
            $product = new Product(
                $productRow['id'],
                $productRow['name'],
                $productRow['price']
            );
            $this->productRepository->store($product);
        }

        $this->assertTheseProductsAreInTheRepository($productTable);
    }

    /**
     * @Given /I have a file named "([^"]+)" with (.*)/
     */
    public function iHaveAFileNamedWithData(FilePath $pathToFile, TableNode $table)
    {
        $this->pathToFile = $pathToFile;
        $this->createCsvFileWithDataFromTable($this->pathToFile->path(), $table);

        Assert::assertFileExists($pathToFile->path());
    }

    /**
     * @Given There is an error in the system
     */
    public function thereIsAnErrorInTheSystem()
    {
        $path = $this->pathToFile->path();
        unlink($this->pathToFile->path());

        Assert::assertFileNotExists($path);
    }

    /**
     * @When I upload the file
     */
    public function iUploadTheFile()
    {
        try {
            $this->updatePricesFromUploadedFile->usingFile($this->pathToFile);
        }
        catch (Throwable $exception) {
            $this->lastException = $exception;
        }
    }

    /**
     * @Then A message is shown explaining the problem
     */
    public function aMessageIsShownExplainingTheProblem(PyStringNode $expectedMessage)
    {
        $message = $this->lastException->getMessage();
        Assert::assertEquals($expectedMessage->getRaw(), $message);
    }

    /**
     * @Then /Changes are (not )?applied to the current prices/
     */
    public function changesAreOrNotAppliedToTheCurrentPrices(TableNode $productTable)
    {
        $this->assertTheseProductsAreInTheRepository($productTable);
    }

    /** @Transform /([^"]+)/ */
    public function getFilePath(string $pathToFile): FilePath
    {
        $fullPathToFile = './' . $pathToFile;
        touch($fullPathToFile);
        return new FilePath($fullPathToFile);
    }

    private function createCsvFileWithDataFromTable(string $path, TableNode $table)
    {
        $file = fopen($path, 'w');

        $header = true;
        foreach ($table as $row) {
            if ($header) {
                fputcsv($file, array_keys($row));
                $header = false;
            }
            fputcsv($file, $row);
        }
        fclose($file);
    }

    /**
     * @param TableNode $productTable
     */
    private function assertTheseProductsAreInTheRepository(TableNode $productTable): void
    {
        foreach ($productTable as $productRow) {
            $product = $this->productRepository->getById($productRow['id']);
            Assert::assertEquals($productRow['price'], $product->price());
        }
    }
}
