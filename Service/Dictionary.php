<?php

namespace Cam5\RidPhp\Service;

class Dictionary implements DictionaryInterface
{

    const DEFAULT_RID = 'RID.CAT';

    public $tree;

    public function __construct($input = null)
    {
        $dictionarySource = $input ?: $this->getDefaultSource();
        error_log($dictionarySource);
        $this->loadDictionary($dictionarySource);
    }

    public function alphabetize(&$tree, &$node, $letter)
    {
        $tree[$letter][] = $node;
    }

    public function loadDictionary($file)
    {
        $linebyline = explode("\n", $file);

        $dictionary = new \DOMDocument;
        $dictionary->formatOutput = true;

        foreach ($linebyline as $line) {

            for ($t = 3; $t > -1; $t--) {
                if (preg_match("/^(\t{".$t."}\w)/", $line))
                    $tabs = $t;
            }

            //EXAMPLE* (1) --> EXAMPLE.*
            $word = preg_replace(
                array( "/(\(1\))|\s/", "/\*/" ),
                array( "", ".*" ),
                $line);

            switch ($tabs) {
                case 0 :
                    $node = $dictionary->createElement($word);
                    $primary = $dictionary->appendChild($node);
                    $primary->setAttribute('level', 'primary');

                    break;

                case 1 :
                    $node = $dictionary->createElement($word);
                    $secondary = $primary->appendChild($node);
                    $secondary->setAttribute('level', 'secondary');

                    break;

                case 2 :
                    if ( preg_match('/\*|(\(1\))/', $line ) ) {
                        $node = $dictionary->createElement('term', $word);
                        $term = $secondary->appendChild($node);
                        $this->alphabetize($this->tree->alpha, $term, substr($word, 0, 1));
                    } else {
                        $node = $dictionary->createElement($word);
                        $tertiary = $secondary->appendChild($node);
                        $tertiary->setAttribute('level', 'tertiary');
                    }

                    break;

                case 3 :
                    $node = $dictionary->createElement('term', $word);
                    $term = $tertiary->appendChild($node);
                    $this->alphabetize($this->tree->alpha, $term, substr($word, 0, 1));

                    break;
            }
        }

        return $this->tree->dictionary = $dictionary;

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
