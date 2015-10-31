<?php

namespace Cam5\RidPhp\Service;

class Dictionary implements DictionaryInterface
{

    const DEFAULT_RID = 'RID.CAT';

    const PRIMARY = 0;
    const SECONDARY = 1;
    const TERTIARY = 2;
    const WORD = 3;

    protected static $enum = [
        self::PRIMARY => 'Primary',
        self::SECONDARY => 'Secondary',
        self::TERTIARY => 'Tertiary',
        self::WORD => 'Word',
    ];

    protected static $parentEnum = [
        'Primary'  => 'None',
        'Secondary' => 'Primary',
        'Tertiary' => 'Secondary',
        'Word' => 'Tertiary',
    ];

    public $records;
    public $alphadex = [];

    /** @codeCoverageIgnore **/
    public function __construct($input = null)
    {
        $this->initTemporaryValues();

        $this->parseDictionary(
            $input ?: $this->getDefaultSource()
        );
    }

    public function initTemporaryValues()
    {
        $this->temporaryValues = new \stdClass;
    }

    public function parseDictionary($input)
    {
        $lines = explode("\n", $input);

        $this->records = new \DomDocument;
        $this->records->formatOutput = true;

        array_map([$this, 'processLine'], $lines);

        $this->clearTemporaryValues();
    }

    public function readTabs($input, $maxTabs = 3)
    {
        // Detect the number of tabs in the line
        for ($t = $maxTabs; $t > -1; $t--) {
            if (preg_match("/^(\t{".$t.'}\w)/', $input)) {
                return $t;
            }
        }
    }

    public function normalizeWord($word)
    {
        //EXAMPLE* (1) --> EXAMPLE.*
        $word = preg_replace(
            array('/(\(1\))|\s/', '/\*/'),
            array('', '.*'),
            $word
        );

        return $word;
    }

    public function processLine($line)
    {
        $tabs     = $this->readTabs($line);
        $word     = $this->normalizeWord($line);
        $category = $this->fixTabRead(self::$enum[$tabs], $line);

        switch ($category) {
            case 'Primary' :
            case 'Secondary' :
            case 'Tertiary' :
                $node = $this->records->createElement($word);
                $this->handleCategoryNode($node, $category);
                break;

            case 'Word' :
                $node = $this->records->createElement('term', $word);
                $originalCategory = self::$enum[$tabs];
                $this->handleTermNode($node, $word, $category, $originalCategory);
                break;
        }
    }

    public function handleCategoryNode(\DOMNode $node, $category)
    {
        $parentCategory = self::$parentEnum[$category];

        // Append to root if primary, else last parent-level node.
        if ('None' === $parentCategory) {
            $this->temporaryValues->$category = $this->records->appendChild($node);
        } else {
            $this->temporaryValues->$category = $this->temporaryValues->$parentCategory->appendChild($node);
        }

        // Label for querying by level, later.
        $this->temporaryValues->$category->setAttribute('level', $category);
    }

    public function handleTermNode(\DOMNode $node, $word, $category, $originalCategory)
    {
        $targetCategory = $this->getTargetCategory($word, $category, $originalCategory);

        $this->temporaryValues->$targetCategory->appendChild($node);

        $firstLetter = substr($word, 0, 1);

        $this->alphadex[$firstLetter][] = $word;
    }

    public function getTargetCategory($word, $category, $originalCategory)
    {
        $parentCategory = self::$parentEnum[$category];

        if ($category !== $originalCategory) {
            $targetCategory = ('Tertiary' === $originalCategory)
                ? 'Primary'
                : 'Secondary';
        } else {
            $targetCategory = $parentCategory;
        }

        return $targetCategory;
    }

    public function fixTabRead($category, $line)
    {
      if ('Tertiary' !== $category) {
          return $category;
      }

      $wordPattern = '/\*|(\(1\))/';

      if (preg_match($wordPattern, $line)) {
          return 'Word';
      } else {
          return $category;
      }
    }

    public function clearTemporaryValues() {
        unset($this->temporaryValues);
    }

    public function validateRidString($fileString)
    {
        return true;
    }

    public function getDefaultSource()
    {
        $filePath = dirname(__FILE__) . '/../Resource/';

        return file_get_contents(
            $filePath . static::DEFAULT_RID
        );
    }
}
