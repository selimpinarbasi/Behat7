<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var Wrapper[]
     */
    private $wrappers = [];
    private $response;
    private $mySampleJson;
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($hosts)
    {
        $hosts = json_decode($hosts, true);
        if (is_array($hosts) || is_object($hosts))
        {
            foreach ($hosts as $key => $host) {
                $wrapper = new Wrapper(
                    new \PDO('mysql:host=' . $host['host'] . ';port=' . $host['port'] . ';dbname=' . $host['dbname'] . ';charset=utf8',
                        'root', ''), null);
                $wrapper->dbName = $host['dbname'];
                $this->wrappers[$key] = $wrapper;
            }
        }

    }

    /**
     * @Given I am a developer
     */
    public function iAmADeveloper()
    {
        $client = new \GuzzleHttp\Client(['base_uri'=>"http://localhost/api"]);
        $this->response = $client->get('/');
        $responseCode = $this->response->getStatusCode();

        if ($responseCode != 200)
        {
            throw new Exception("API is not working");
        }
        return true;
    }

    /**
     * @When I :arg2 request news from :arg1
     */
    public function iRequestNewsFrom($arg1, $arg2)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', 'http://localhost/api/news');
        $responseCode = $res->getStatusCode();
        if ($responseCode != 200)
        {
            throw new Exception("data bulunamadı");
        }
    }

    /**
     * @When Id is :arg1
     */
    public function idIs($arg1)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', 'http://localhost/api/news/' . $arg1);
        $responseCode = $res->getStatusCode();
        if ($responseCode != 200)
        {
            throw new Exception("data bulunamadı");
        }
    }

    /**
     * @Then Response equal to
     */
    public function responseEqualTo(PyStringNode $string)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', 'http://localhost/api/news/2');

        $data = json_decode($res->getBody(), true);
        $string = json_decode($string,true);
        $statusCode = $res->getStatusCode();
        echo $string["id"];
        echo $data["id"];

        if ($data['id'] != $string['id'] || $statusCode != 200)
        {
            throw new Exception("Data are not equal or request is wrong !");
        }
    }
    /**
     * @When I write this query :arg1
     */
    public function iWriteThisQuery($arg1)
    {
        return $arg1;
    }

    /**
     * @When Following records should be seen at table :arg1
     * @throws Exception
     */
    public function followingRecordsShouldBeSeenAtTable(string $tableName, TableNode $table)
    {

        $tableName = explode('.', $tableName);
        $dbIndex = $tableName[1];
        $tableName = $tableName[0];
            $rows = $this->wrappers[$dbIndex]->query('SELECT * FROM `' . $tableName . '`');
            $expectedRows = $table->getHash();
            static::assertCount(count($rows), $expectedRows, $tableName . ' tablosundaki satır sayısı ile uyuşmuyor.');
            foreach ($rows as $index => $row) {
                $expectedRow = $expectedRows[$index];
                foreach ($expectedRow as $expectedKey => $expectedValue) {
                    $this->assertFormattedValue(
                        $row[$expectedKey],
                        $expectedKey,
                        $expectedValue,
                        ", key: $expectedKey\nexpectedRow:\n | " . implode(' | ', $expectedRow) . ' |'
                    );
                }
            }
        /*$arg1 = explode('.', $arg1);
        $dbIndex = $arg1[1];
        $arg1 = $arg1[1];

        $rows = $this->wrappers[$dbIndex]->fetchAll('SELECT * FROM `' . $arg1 . '`');
        $expectedRows = $table->getHash();
        static::assertCount(count($rows), $expectedRows, $arg1 . ' tablosundaki satır sayısı ile uyuşmuyor.');
        foreach ($rows as $index => $row) {
            $expectedRow = $expectedRows[$index];
            foreach ($expectedRow as $expectedKey => $expectedValue) {
                $this->assertFormattedValue(
                    $row[$expectedKey],
                    $expectedKey,
                    $expectedValue,
                    ", key: $expectedKey\nexpectedRow:\n | " . implode(' | ', $expectedRow) . ' |'
                );
            }
        }*/
    }
}