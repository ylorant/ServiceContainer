<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\ServiceContainer\Tests;

use Berlioz\ServiceContainer\Exception\ContainerException;
use Berlioz\ServiceContainer\Instantiator;
use Berlioz\ServiceContainer\ServiceContainer;
use Berlioz\ServiceContainer\Tests\files\Service1;
use Berlioz\ServiceContainer\Tests\files\Service2;
use Berlioz\ServiceContainer\Tests\files\Service3;
use Berlioz\ServiceContainer\Tests\files\Service4;
use PHPUnit\Framework\TestCase;

class InstantiatorTest extends TestCase
{
    private function getConfig()
    {
        $json = <<<'EOD'
{
  "aliasService1": {
    "class": "\\Berlioz\\ServiceContainer\\Tests\\files\\Service1",
    "arguments": {
      "param1": "test",
      "param2": "test",
      "param3": 1 
    },
    "calls": [
      {
        "method": "increaseParam3",
        "arguments": {
          "nb": 5
        }
      }
    ]
  },
  "aliasService1X": {
    "class": "\\Berlioz\\ServiceContainer\\Tests\\files\\Service1",
    "arguments": {
      "param1": "another",
      "param2": "test",
      "param3": 1 
    }
  },
  "aliasService2": {
    "class": "\\Berlioz\\ServiceContainer\\Tests\\files\\Service2",
    "arguments": {
      "param3": false,
      "param1": "test"
    }
  },
  "aliasServiceX": {
    "class": "\\Berlioz\\ServiceContainer\\Tests\\files\\Service2",
    "arguments": {
      "param3": false,
      "param1": "test",
      "param2": "@aliasService1X"
    }
  }
}
EOD;

        return json_decode($json, true);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testNewInstanceOf()
    {
        $instantiator = new Instantiator;
        $service = $instantiator->newInstanceOf(Service3::class,
                                                ['param1' => 'test',
                                                 'param2' => 'test',
                                                 'param3' => 3,
                                                 'param4' => 'test']);
        $this->assertInstanceOf(Service3::class, $service);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testNewInstanceOfWithNotNamedParameters()
    {
        $service1 = new Service1('param1', 'param2', 1);
        $instantiator = new Instantiator;
        $service = $instantiator->newInstanceOf(Service2::class,
                                                ['param1'   => 'test',
                                                 'aService' => $service1]);
        $this->assertInstanceOf(Service2::class, $service);
        $this->assertEquals($service1, $service->getParam2());
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testNewInstanceOf_optionalParameters()
    {
        $instantiator = new Instantiator();
        $service = $instantiator->newInstanceOf(Service3::class,
                                                ['param1' => 'test',
                                                 'param2' => 'test']);
        $this->assertInstanceOf(Service3::class, $service);
        $this->assertNull($service->param3);
        $this->assertEquals('test', $service->param4);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testNewInstanceOf_missingParameter()
    {
        $this->expectException(ContainerException::class);
        $instantiator = new Instantiator;
        $instantiator->newInstanceOf(Service3::class,
                                     ['param1' => 'test',
                                      'param4' => 'test2']);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testNewInstanceOf_withoutConstructor()
    {
        $instantiator = new Instantiator;
        $service = $instantiator->newInstanceOf(Service4::class,
                                                ['param1' => 'test',
                                                 'param4' => 'test2']);
        $this->assertInstanceOf(Service4::class, $service);

        $service = $instantiator->newInstanceOf(Service4::class);
        $this->assertInstanceOf(Service4::class, $service);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function testInvokeMethod()
    {
        $serviceContainer = new ServiceContainer($this->getConfig());
        $instantiator = new Instantiator(null, $serviceContainer);
        $service = $serviceContainer->get(Service2::class);
        $result = $instantiator->invokeMethod($service,
                                              'test',
                                              ['param' => ($str = 'toto')]);
        $this->assertEquals(sprintf('It\'s a test "%s"', $str), $result);
    }
}
