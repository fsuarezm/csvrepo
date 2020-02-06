<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;
use TalkingBit\BddExample\Product;
use TalkingBit\BddExample\UpdatePricesFromUploadedFile;
use TalkingBit\BddExample\FileReader\CSVFileReader;
use TalkingBit\BddExample\Persistence\InMemoryProductRepository;
use TalkingBit\BddExample\VO\FilePath;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
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
     * @var Exception|Throwable
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
    }

    /**
     * @Given I have a file named :pathToFile with the new prices
     */
    public function iHaveAFileNamedWithTheNewPrices(FilePath $pathToFile, TableNode $table)
    {
        $this->pathToFile = $pathToFile;
        $this->createCsvFileWithDataFromTable($this->pathToFile->path(), $table);
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
     * @Then Changes are applied to the current prices
     */
    public function changesAreAppliedToTheCurrentPrices(TableNode $productTable)
    {
        $this->checkProductPrice($productTable);
    }

    /**
     * @Given I have a file named :pathToFile with invalid data
     */
    public function iHaveAFileNamedWithInvalidData(FilePath $pathToFile, TableNode $table)
    {
        $this->pathToFile = $pathToFile;
        $this->createCsvFileWithDataFromTable($this->pathToFile->path(), $table);
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
     * @Then Changes are not applied to the current prices
     */
    public function changesAreNotAppliedToTheCurrentPrices(TableNode $productTable)
    {
        $this->checkProductPrice($productTable);
    }

    /**
     * @When There is an error in the system
     */
    public function thereIsAnErrorInTheSystem()
    {
        throw new PendingException();
    }

    /**
     * @Transform :pathToFile
     */
    public function getFilePath(string $pathToFile): FilePath
    {
        return new FilePath('./'.$pathToFile);
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
    private function checkProductPrice(TableNode $productTable): void
    {
        foreach ($productTable as $productRow) {
            $product = $this->productRepository->getById($productRow['id']);
            Assert::assertEquals($productRow['price'], $product->price());
        }
    }
}
