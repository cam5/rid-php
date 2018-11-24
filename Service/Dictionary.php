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

	public function getRecords(){
		return $this->records;
	}
	
    public function initTemporaryValues()
    {
        $this->temporaryValues = new \stdClass;
    }

    private function initRecords()
    {
        $this->records = new \DomDocument;
		$this->records->appendChild($this->records->createElement('Dictionary'));
		
        $this->records->formatOutput = true;

        return $this->records;
    }

    public function parseDictionary($input)
    {
        $lines = explode("\n", $input);

        $this->initRecords();

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
		$word = trim($word);
        $word = preg_replace(
            array('/(\s\(1\))/', '/\*/', '/\s/'),
            array('', '.*', '-'),
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

    private function handleCategoryNode(\DOMNode $node, $category)
    {
        $parentCategory = self::$parentEnum[$category];
		
        // Append to root if primary, else last parent-level node.
        if ('None' === $parentCategory) {
            $this->temporaryValues->$category = $this->records->getElementsByTagName('Dictionary')->item(0)->appendChild(
                $this->records->importNode($node)
            );
        } else {
            $this->temporaryValues->$category = $this->temporaryValues->$parentCategory->appendChild($node);
        }

        // Label for querying by level, later.
        $this->temporaryValues->$category->setAttribute('level', $category);

        return $this->temporaryValues->$category;
    }

    private function handleTermNode(\DOMNode $node, $word, $category, $originalCategory)
    {
        $targetCategory = $this->getTargetCategory($word, $category, $originalCategory);
        $this->temporaryValues->$targetCategory->appendChild($node);

        $firstLetter = substr($word, 0, 1);

        $this->alphadex[$firstLetter][] = $node;
    }

	public function getTermsByLetter($letter){
		return $this->alphadex[$letter];
	}
	
    public function getTargetCategory($word, $category, $originalCategory)
    {
        if ($category !== $originalCategory) {
            $targetCategory = ('Tertiary' === $originalCategory)
                ? 'Secondary'
                : 'Primary';
        } else {
			$targetCategory = self::$parentEnum[$category];
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
