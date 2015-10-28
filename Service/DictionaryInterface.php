<?php

namespace Cam5\RidPhp\Service;

interface DictionaryInterface
{
    public function loadDictionary($fileString);

    public function validateRidString($fileString);
}
