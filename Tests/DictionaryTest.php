<?php

namespace Cam5\RidPhp\Tests;

use Cam5\RidPhp\Service\Dictionary;

class DictionaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::initTemporaryValues
     * @covers \Cam5\RidPhp\Service\Dictionary::clearTemporaryValues
     */
    public function testClearTemporaryValues()
    {
        $dictionary = new Dictionary();
        $dictionary->initTemporaryValues();

        $this->assertInstanceOf('stdClass', $dictionary->temporaryValues);

        $dictionary->temporaryValues->foo = 'bar';
        $dictionary->clearTemporaryValues();

        try {
          $dictionary->temporaryValues->foo;
        } catch (\PHPUnit_Framework_Error_Notice $e) {
          return;
        }

        $this->fail('clearTemporaryValues did not clear the values it said it would.');
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::getDefaultSource
     */
    public function testGetDefaultSource()
    {
        $dictionary       = new Dictionary();
        $dictionarySource = $dictionary->getDefaultSource();
        $ridFile          = file_get_contents(dirname(__FILE__) . '/../Resource/RID.CAT');

        $this->assertEquals($ridFile, $dictionarySource);
    }
}

