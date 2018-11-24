<?php

namespace Cam5\RidPhp\Service;

use Cam5\RidPhp\Service\Dictionary;

class Analyzer
{
    public $tree;

    public function __construct()
    {
		$this->tree = new \stdClass();
        $this->tree->dictionary = new Dictionary();
    }

    private function analyze_all_parents(&$domElement)
    {
		$parent = $domElement->parentNode;
        if ($parent) {
            //As long as the top element isn't the document
            if ($parent->nodeName != "#document")
                $this->log_count($parent);
            return $this->analyze_all_parents($parent );
        }
    }
	
    public function log_count(&$node)
    {
        $count = $node->getAttribute('count') ? $node->getAttribute('count') : 0;
        $node->setAttribute('count', $count + 1);
    }


    public function get_category($word)
    {
        $first_letter = substr($word, 0, 1);
        $first_letter = ctype_upper($first_letter) ? $first_letter : strtoupper($first_letter) ;

		$tags = $this->tree->dictionary->getTermsByLetter($first_letter);
		
        foreach ($tags as $term) {
			
            if (preg_match('/^(' . $term->nodeValue . ')$/i', $word)) {
                return $term;
            }
        }

    }

    public function analyze($input)
    {
        $tokens = preg_split("/[^A-z]/", $input);

        for ($i=0, $tl = count($tokens); $tl > $i; $i++)
            if ($tokens[$i] == '')
                unset($tokens[$i]);

        $this->words_input = count($tokens);
        $this->words_analyzed = 0;
        $this->input_text = $input;
		
        foreach ($tokens as $token){
			$node = $this->get_category($token);
			if ($node) {
				$this->words_analyzed++;
                $this->analyze_all_parents($node);
            }
		}
        if ($this->words_analyzed > 0) return true;
    }

    public function retrieve_data( $nodepath = array() )
    {
        $xpath = new \DOMXpath($this->tree->dictionary->records);
        $path = is_array($nodepath) ? "/" . implode($nodepath, "/") : "/*";
        $query = '/'.$path . "/*[@count]";
		
		$nodes = $xpath->query($query);
		
        $data['columns'] = array(
            array(
                'name' => 'Category',
                'type' => 'string'),
            array(
                'name' => 'Count',
                'type' => 'number')
            );
		
        foreach ($nodes as $node) {
            $data['rows'][] = array($node->nodeName, $node->getAttribute('count'));
        }
        return $data;
    }	
}
