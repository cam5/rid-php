<?php

namespace Cam5\RidPhp\Service;

interface DictionaryInterface
{
    public function parseDictionary($fileString);

    public function validateRidString($fileString);
}
