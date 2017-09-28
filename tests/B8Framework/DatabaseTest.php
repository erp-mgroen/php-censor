<?php

namespace Tests\b8;

use b8\Config;
use b8\Database;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $config = new Config([
            'b8' => [
                'database' => [
                    'servers' => [
                        'read'  => [
                            ['host' => 'localhost'],
                        ],
                        'write' => [
                            ['host' => 'localhost'],
                        ],
                    ],
                    'type'     => DB_TYPE,
                    'name'     => DB_NAME,
                    'username' => DB_USER,
                    'password' => DB_PASS,
                ],
            ],
        ]);
    }

    protected function checkDatabaseConnection()
    {
        try {
            $connection = Database::getConnection('read');
        } catch (\Exception $e) {
            if ('Could not connect to any read servers.' === $e->getMessage()) {
                $this->markTestSkipped('Test skipped because test database doesn`t exist.');
            } else {
                throw $e;
            }
        }
    }

    public function testGetWriteConnection()
    {
        $this->checkDatabaseConnection();

        $connection = Database::getConnection('write');
        $this->assertInstanceOf('\b8\Database', $connection);
    }

    public function testGetDetails()
    {
        $this->checkDatabaseConnection();

        $details = Database::getConnection('read')->getDetails();
        $this->assertTrue(is_array($details));
        $this->assertTrue(($details['db'] == DB_NAME));
        $this->assertTrue(($details['user'] == DB_USER));
        $this->assertTrue(($details['pass'] == DB_PASS));
    }

    /**
     * @expectedException \Exception
     */
    public function testConnectionFailure()
    {
        $this->checkDatabaseConnection();

        Database::reset();

        $config = new Config([
            'b8' => [
                'database' => [
                    'servers' => [
                        'read'  => [
                            ['host' => 'localhost'],
                        ],
                        'write' => [
                            ['host' => 'localhost'],
                        ],
                    ],
                    'type'     => 'mysql',
                    'name'     => 'b8_test_2',
                    'username' => '',
                    'password' => '',
                ],
            ],
        ]);

        Database::getConnection('read');
    }
}
