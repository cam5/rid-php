<?php

namespace Cam5\RidPhp\Service;

class Analyzer
{
    public $tree;

    public function __construct($sauce)
    {
        $this->load_dictionary($sauce);
    }

    public function alphabetize(&$tree, &$node, $letter)
    {
        $tree[$letter][] = $node;
    }

    public function load_dictionary($file)
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

    public function all_parents($domElement, $function)
    {
        if ($parent = $domElement->parentNode) {

            //As long as the top element isn't the document
            if ($parent->nodeName != "#document")
                $this->$function($parent);

            return $this->all_parents($parent, $function);

        }

    }

    public function log_count($node)
    {
        $count = $node->getAttribute('count') ? $node->getAttribute('count') : 0;
        $node->setAttribute('count', $count + 1);
    }


    public function get_category($word)
    {
        $first_letter = substr($word, 0, 1);
        $first_letter = ctype_upper($first_letter) ? $first_letter : strtoupper($first_letter) ;

        $tags = $this->tree->alpha[$first_letter];

        foreach ($tags as $term)
            if (preg_match('/^(' . $term->nodeValue . ')$/i', $word))
                return $term;

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

        foreach ($tokens as $token)

            if ($this->get_category($token)) {
                $node = $this->get_category($token);
                $this->words_analyzed++;
                $this->all_parents($node, 'log_count');
            }

        if ($this->words_analyzed > 0) return true;
    }

    public function retrieve_data( $nodepath = array() )
    {
        $xpath = new \DOMXpath($this->tree->dictionary);
        $path = is_array($nodepath) ? "/" . implode($nodepath, "/") : "/*";
        $query = $path . "/*[@count]";

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

    public function make_data($object, $array)
    {
        extract($array);
        $columncount = count($columns) - 1;

        foreach ($columns as $column)
            echo $object . ".addColumn('" . $column['type'] . "', '" . $column['type'] . "');";

        echo $object . ".addRows([";

        foreach ($rows as $row) {
            echo "[";
                for ($i = 0; $i <= $columncount; $i++) {

                    if ($i < $columncount)
                        $cell = $columns[$i]['type'] == 'number' ? $row[$i]."," : "'" . $row[$i] . "'," ;

                    else $cell = $columns[$i]['type'] == 'number' ? $row[$i] : "'" . $row[$i] . "'" ;

                    echo $cell;
                }
            echo "],";
        }

        echo "]);";
    }

}
