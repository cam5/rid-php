<?

class RID {

    public $tree;
    public $words_analyzed;
    
    function __construct($sauce) {
        $this->load_dictionary($sauce);
    }

    public function load_dictionary($file) {
    
        $linebyline = explode("\n", $file);
        
        $dictionary = new DOMDocument;
        $dictionary->formatOutput = true;
        
        function alphabetize(&$tree, &$node, $letter) {
            $tree[$letter][] = $node;
        }
        
        foreach ($linebyline as $line) {
            
            for($t = 3; $t > -1; $t--) {
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
                        alphabetize($this->tree->alpha, $term, substr($word, 0, 1));
                    }
                    
                    else {
                        $node = $dictionary->createElement($word);
                        $tertiary = $secondary->appendChild($node);
                        $tertiary->setAttribute('level', 'tertiary');  
                    }
                    
                    break;
                
                case 3 :
                    $node = $dictionary->createElement('term', $word);
                    $term = $tertiary->appendChild($node);
                    alphabetize($this->tree->alpha, $term, substr($word, 0, 1));
                    
                    break;
            }
        }
        
        return $this->tree->dictionary = $dictionary;
    
    }
    
    
    public function count_category($domElement) {                                    
        if ($parent = $domElement->parentNode) {
            
            //As long as the top element isn't the document
            if ($parent->nodeName != "#document") {
                
                $count = $parent->getAttribute('count') ? $parent->getAttribute('count') : 0;
                $parent->setAttribute('count', $count + 1);
                                
            }
            return $this->count_category($parent);
        }
    }
    
    public function get_category($word) {
    
        $first_letter = substr($word, 0, 1);
        $first_letter = ctype_upper($first_letter) ? $first_letter : strtoupper($first_letter) ;
    
        $tags = $this->tree->alpha[$first_letter];

            foreach ($tags as $term)  {
            if (preg_match('/^(' . $term->nodeValue . ')$/i', $word))
                return $term;
            }
        
    }
        
    public function analyze($input) {
        
        $this->words_analyzed = 0;
        $this->input_text = $input;
        
        $tokens = preg_split("/[^A-z]/", $input);
        
        for ($i=0, $tl = count($tokens); $tl > $i; $i++)
            if ($tokens[$i] == '')
                unset($tokens[$i]);

        foreach ($tokens as $token)
            
            if ($this->get_category($token)) {
                $node = $this->get_category($token);
                $this->words_analyzed++;
                $this->count_category($node);
            }
            
        if ($this->words_analyzed > 0) return true;
    }
    
    public function spew() {
        $xpath = new DOMXpath($this->tree->dictionary);
        $query = "//*[@count]";
        $counts = $xpath->query($query);
        foreach($counts as $count) {
            echo $count->nodeName . " - " . $count->getAttribute('count') . "<br />";
        }
    }
    

}

$rid = new RID(file_get_contents('RID.CAT'));
if ($rid->analyze(file_get_contents('obama-speech.txt'))) {
    $rid->spew();
}