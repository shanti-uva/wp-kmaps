<?php
final class MandalaTranslate
{

    /**
     * Mandala version.
     *
     * @var string
     */
    public $version = '1.2.0';

    /**
     * The single instance of the class.
     *
     * @var MandalaTranslate
     * @since 1.0
     */
    protected static $_instance = null;
    protected static $wy_conv_url = 'https://texts.thdl.org/wylie/?wy='; // TODO: Fix/finalize this url
    public static $phrase_delims = '།༏༑༐༈༎༴༔\s'; // The various kinds of shad etc. plus a space
    public static $tib_delims = ['་ནི་', '་ཀྱང་', '་སྟེ་', '་ལ་', '་པོས་'];
    public static $syl_delims = '་༌';  // The two types of tseks: breaking and non-breaking
    public static $tsek = '་';
    public static $sa_jug = 'ས';
    public static $ai_jug = 'འི';
    public static $ra_jug = 'ར';
    public static $a_jug = 'འ';


    /**
     * Class Variables
     */
    protected string $wpsite;
    protected string $solrurl;

    /**
     * Main Mandala Instance.
     *
     * Ensures only one instance of Mandala is loaded or can be loaded.
     *
     * @return MandalaTranslate - Main instance.
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Mandala Constructor.
     */
    public function __construct() {
        $this->wpsite = get_site_url();
        $this->solrurl = $this->getSolrUrl();
    }

    private function getSolrUrl() {
        // TODO: Make these a setting so they can be changed site-by-site
        $devurl = 'https://mandala-index-dev.internal.lib.virginia.edu/solr/kmassets/select';
        $produrl = 'https://mandala-index.internal.lib.virginia.edu/solr/kmassets/select';
        $devstrs = ['local', 'dev', 'stage', 'staging'];
        $patt = '/(' . implode('|', $devstrs) . ')/';
        if(preg_match($patt, $this->wpsite)) {
            return $devurl;
        } else {
            return $produrl;
        }
    }

    public function parse($data, $isData=false) {
        // First call is just a string so isData is false, parse the string into phrases, do the first, then after isData is true
        if (!$isData) {

            $wyl = $data;
            $tib = $this->convert_wylie($wyl);

            // Look for conversion error messages and put into $errors variables
            $pts = preg_split('/\s+---\s*/', $tib);
            $tib = $pts[0];
            $errors = (count($pts) > 1) ? implode('<br/>', array_slice($pts, 1)) : '';
            if (!$tib) {
                return false;
            }

            if (empty($errors) && strlen($tib) == 0) {
                $errors = "Wylie conversion unsuccessful";
                $tib = $wyl;
            }

            $phrases = $this->break_phrases($tib);

            // Parse First phrase into words See $this->phrase_parse()
            /* original code did all phrases
            $words = [];
            foreach($phrases as $pn => $phrase) {
                $phrase_words = $this->phrase_parse($phrase);
                // error_log("phrase: " . $phrase . " (words: " . implode(', ', $phrase_words) . ')');
                array_push($words, ...$phrase_words);
            }
            */
            // New Code Do One Phrase at a time and load progressively (See React App).
            $phrase = array_shift($phrases);
            $words = $this->phrase_parse($phrase);
            $all_words = $words;
        } else {
        // Subsequent calls with data object are processed here
            $dobj = json_decode($data, true);
            $wyl = $dobj['wylie'];
            $tib = $dobj['tibetan'];
            $errors = $dobj['errors'];
            $phrases = $dobj['phrases'];
            $all_words = $dobj['words'];
            $phrase = array_shift($phrases);
            if (empty($phrase)) {
                $words = array();
            } else {
                $words = (array)$this->phrase_parse($phrase);
            }
            $words = array_diff($words, $all_words);
            array_push($all_words, ...$words);
        }

        // The returned object is always the same.
        $resp = array(
            'wylie' => $wyl,
            'tibetan' => $tib,
            'errors' => $errors,
            'phrases' => $phrases,
            'current_phrase' => $phrase,
            'word_count' => count($words),
            'all_words' => $all_words,
            'words' => $words,
        );
        return $resp;
    }

    public function convert_wylie($wyl) {
        $wyl = preg_replace('/\s+/', '%20', trim($wyl)); // Normalize spaces and remove leading and trailing ones
        $first_chr_code = mb_ord(mb_substr($wyl, 0, 1));
        // error_log("first code is: " . $first_chr_code);
        if ( $first_chr_code > 4095 ) { return false; }  // Outside (above) Tib range
        if ( $first_chr_code > 3839 ) { return $wyl; } // String is already Tibetan
        $req_url = $this::$wy_conv_url . $wyl;
        $data = @file_get_contents($req_url);
        if($data) {
            $data = json_decode($data, true);
            if ($data['bo']) {
                return $data['bo'];
            }
        }
        return false;
    }

    public function break_phrases($tib) {

        // Normalize spaces and Split Tibetan into phrases based on shads or spaces
        $tib = preg_replace('/%20/', ' ', $tib);
        $phrpat = '[' . $this::$phrase_delims . ']+';
        // mb_split does not take / pattern delimiters /
        $phrases = array_filter(mb_split($phrpat, $tib), function($it) {
            return strlen($it) > 0;
        });

        if (mb_strlen($phrases[0]) > 30) {
            $p1 = array_shift($phrases);
            foreach($this::$tib_delims as $tib_delim) {
                $p1pts = mb_split($tib_delim, $p1);
                foreach($p1pts as $n => &$pt) {
                    if ($n < count($p1pts) - 1) {
                        $pt .= $tib_delim;
                    }
                }
                if (count($p1pts) > 1) {
                    array_unshift($phrases, ...$p1pts);
                    break;
                }
            }
        }


        return $phrases;
    }

    /**
     * Takes a phrase and breaks it up into words
     * @param $phr
     * @return array|false|string[]
     */
    private function phrase_parse($phr) {
        // break phrase into syllables
        $patt = '[' . $this::$syl_delims . ']+';
        $syls = mb_split($patt, $phr);
        // Remove empty syllables
        $syls = array_filter($syls, function ($s) {
            return strlen($s) > 0;
        });
        $maxLoop = pow(count($syls), 2); // to prevent endless looping if something goes wrong
        $lct = 0;
        $words = [];
        while (count($syls) > 0 && $lct < $maxLoop) {
            $lct++;
            $unused = [];
            // Start with full phrase and knock one syllable off end each time not found.
            for($i = count($syls); $i > 0; $i--) {
                $test_word = implode($this::$tsek, array_slice($syls, 0, $i)); // build word by putting tseks between syllables
                $word_id = $this->find_word($test_word);
                if ($word_id) {
                    $words[] = $word_id; // $test_word . $this::$tsek;
                    $unused = array_slice($syls, $i);
                    break;
                } else if ($i == 1) {
                    $words[] = "$syls[0]:-1";
                    $unused = array_slice($syls, 1);
                }
            }
            $syls = $unused;
        }
        return array_unique($words);
    }

    private function find_word($wd) {
        $wds = $this->word_variants($wd);
        $sdoc = $this->querySolr($wds);
        if (!empty($sdoc['response']['docs'])) {
            if ($sdoc['response']['numFound'] * 1 > 1) {
                error_log("Multiple docs found for “{$wd}”: {$sdoc['response']['numFound']}");
            }
            $doc1 = $sdoc['response']['docs'][0];
            return "{$doc1['name_tibt_sort']}:{$doc1['id']}";
        }
        return false;
    }

    /**
     * Checks endings of words for instrumental, locative, genitive, etc and provides list of possible alternate options for the words
     * @param $wd
     * @return array : the returned array of word variants should be in order of importance. The first item will get boost of ^100, second ^99, etc.
     */
    private function word_variants($wd) {
        $wds = array($wd);
        $wlen = mb_strlen($wd);
        $last_char = mb_substr($wd, $wlen - 1, 1);
        $last_two_char = mb_substr($wd, $wlen - 2, 2);
        if ($last_char === $this::$sa_jug || $last_char === $this::$ra_jug) {
            $removed = mb_substr($wd, 0, $wlen - 1);
            $wds[] = $removed;
            $wds[] = $removed . $this::$a_jug;
        } else if ($last_two_char === $this::$ai_jug) {
            $removed =  mb_substr($wd, 0, $wlen - 2);
            $wds[] = $removed;
            $wds[] = $removed . $this::$a_jug;
        }
        return $wds;
    }

    private function querySolr($qwd, $opts=array()) {
        // sample query: ?q=names:ཁ&fl=*&wt=json&rows=30&fq=asset_type:terms&fq=related_uid_ss:subjects-9315 (means "Expression" not tree node)
        // q string for tibetan def: names:\"$enctib\"
        // fq for defs: &fq=asset_type:terms&fq=related_uid_ss:subjects-9315&rows=10&fl=*&wt=json
        $opts_list = array(
            'fq=asset_type:terms&fq=related_uid_ss:subjects-9315'  // Find only term definitions not other nodes in tree
        );
        $qwd = preg_replace('/\s+/', '%20', $qwd);
        foreach($opts as $oky => $oval) {
            $opts_list[] = "$oky=$oval";
        }
        if(!in_array('rows', array_keys($opts))) {
            $opts_list[] = 'rows=10';
        }
        if(!in_array('fl', array_keys($opts))) {
            $opts_list[] = 'fl=*';
        }
        $opts_list[] ='wt=json';
        $opts_str = implode('&', $opts_list);
        // This assumes we are only looking for kmaps
        if (is_array($qwd)) {
            $enc_qwds = array_map(function ($item) {
                return '"' . urlencode($item) . '"';
            }, $qwd);
            // $qwd list must be build in order of importance. First item most important.
            foreach($enc_qwds as $n => &$item) {
                $boost = 100 - $n;
                $item = "$item^$boost";
            }
            $wd = '(' . implode('%20', $enc_qwds) . ')';
        } else {
            $wd = '"' . urlencode($qwd) . '"';
        }
        // error_log("wds in mandala-translate querySolr: $wd");
        $surl = $this->solrurl . "?q=names:$wd&$opts_str";
        $sdoc_data = file_get_contents($surl);
        $sdoc = array(
            'status' => 'Nothing returned from solr'
        );
        if ($sdoc_data) {
            $sdoc = json_decode($sdoc_data, TRUE);
        }
        $sdoc['url'] = $surl;
        return $sdoc;
    }
}