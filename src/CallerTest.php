<?php

namespace App;
use PHPUnit\Framework\TestCase;
require_once('./vendor/autoload.php');

class CallerTest extends TestCase
{
  private $caller = null;

  public function setUp()
  {
    $this->caller = new Caller();
    $this->caller->make('https://api.github.com/users', 'get');
    $this->caller->root();
  }

  public function testCallerShouldBeCreated(): void
  {
    // Response should not be empty
    $this->assertNotEmpty($this->caller->response, "Response is empty!");
    // Data array should not be empty.
    $this->assertNotEmpty($this->caller->dataArray, "Data array is empty!");
  }

  public function testWhereShouldFilterDataArray(): void
  {
    $this->caller->where('login', '=', 'mojombo');

    // Data array should only contains data that login equals to 'mojombo'
    foreach($this->caller->dataArray as $data) {
      $this->assertEquals('mojombo', $data['login'], "Data array contains the data that login is not equals to mojombo!");
    }
  }

  public function testSortShouldSortDataArray(): void
  {
    $this->caller->sort('id', 'DESC');

    // Data array should be sorted by ID in DESC order.
    $past_id = PHP_INT_MAX;
    foreach($this->caller->dataArray as $data) {
      $this->assertGreaterThan($data['id'], $past_id, "Data array is not sorted by ID in DESC order!");
    }
  }

  public function testGetShouldReturnDataArray(): void
  {
    // Data array should be returned by get method.
    $this->assertEquals($this->caller->dataArray, $this->caller->get(), "Get method didn't return correct Data Array!");
  }

  public function testOnlyShouldReturnSelectedFields(): void
  {
    $fields = ['login', 'node_id'];
    $result = $this->caller->only($fields);

    // 'only' method should return only selected fields.
    foreach($result as $data) {
      $keys = array_keys($data);
      $this->assertEquals($fields, $keys, "Only result contains unexpected field!");
    }
  }
}