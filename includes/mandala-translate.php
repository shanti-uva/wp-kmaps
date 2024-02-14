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
    protected static $wy_conv_url = 'https://texts.thdl.org/wylie/?wy='; // TODO: Fix/finalize this url. Not necessary. Conversion happens in react
    public static $phrase_delims = '།༏༑༐༈༎༴༔\s'; // The various kinds of shad etc. plus a space
    public static $tib_delims = ['་ནི་', '་ཀྱང་', '་སྟེ་', '་རྣམས་', '་པོས་', '་ཅིང་', '་ཞིང་', '་ཤིང་', '་ཞེས་', '་ལ་', '་ན་', '་ཡི་', '་གྱིས་', '་ཀྱིས་', '་གིས་']; // for extra long phrases
    public static $syl_delims = '་༌';  // The two types of tseks: breaking and non-breaking
    public static $tsek = '་';
    public static $sa_jug = 'ས';
    public static $ai_jug = 'འི';
    public static $ra_jug = 'ར';
    public static $a_jug = 'འ';
    public static $ao = 'འོ';


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

    public function parse($data, $done) {
        //error_log("parse data in: " . json_encode($data));
        $wyl = str_replace('%20', ' ', $data);

        $tib = $this->convert_wylie($wyl); // Returns same value if already Unicode Tibetan

        // Look for conversion error messages and put into $errors variables
        $pts = preg_split('/\s+---\s*/', $tib);
        $tib = $pts[0];
        //error_log("Tib after split: $tib");
        $errors = (count($pts) > 1) ? implode('<br/>', array_slice($pts, 1)) : '';
        if (!$tib) {
            return false;
        }

        $tib = mb_ereg_replace('^།', '', $tib); // Strip of leading shad

        $first_phrase = $tib; // if split doesn't work defaults to all of $tib;
        $phrdelims = $this::$phrase_delims;
        $pattern = "[{$phrdelims}]";
        // error_log("Pattern: $pattern");
        $phrase_split = mb_split($pattern, $tib, 2);
        $undone = '';
        if (!empty($phrase_split) && is_array($phrase_split) && count($phrase_split) == 2) {
            $first_phrase = $phrase_split[0];
            $undone = $phrase_split[1];
        }
        //error_log("First Phrase: $first_phrase :: Undone: $undone");
        [$words, $remaining, $done] = $this->phrase_parse($first_phrase, $done);
        $undone = implode($this::$tsek, $remaining) . ' ' . $undone;

        // The returned object is always the same. "undone" is the portion of the initial phrase untranslated. It is an array of syllables so is rejoined with at tsek
        $resp = array(
            'words' => $words,
            'undone' => $undone,
            'done' => $done
        );
        return $resp;
    }

    public function convert_wylie($wyl) {
        $wyl = preg_replace('/\s+/', '%20', trim($wyl)); // Normalize spaces and remove leading and trailing ones
        $first_chr = mb_substr($wyl, 0, 1);
        if(empty($first_chr)) { return ''; }
        $first_chr_code = mb_ord($first_chr);
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

    public function split($strin) {
        $wyl = $strin;
        $tib = $this->convert_wylie($wyl);

        // Look for conversion error messages and put into $errors variables
        $pts = preg_split('/\s+---\s*/', $tib);
        $tib = $pts[0];

        // $errors = (count($pts) > 1) ? implode('<br/>', array_slice($pts, 1)) : '';
        if (!$tib || mb_strlen($tib) === 0) {
            return array();
        }

        // Normalize spaces and Split Tibetan into phrases based on shads or spaces
        $tib = preg_replace('/%20/', ' ', $tib);
        $phrpat = '[' . $this::$phrase_delims . ']+';
        // mb_split does not take / pattern delimiters /
        $phrases = array_filter(mb_split($phrpat, $tib), function($it) {
            return strlen($it) > 0;
        });
        // return $phrases;

        $newphrases = [];

        foreach($phrases as $n => $phr) {
            if (mb_strlen($phr) > 30) {
                foreach($this::$tib_delims as $tib_delim) {
                    $p1pts = mb_split($tib_delim, $phr);
                    foreach($p1pts as $n => &$pt) {
                        if ($n < count($p1pts) - 1) {
                            $pt .= $tib_delim;
                        }
                    }
                    if (count($p1pts) > 1) {
                        array_push($newphrases, ...$p1pts);
                        break;
                    }
                }
            } else {
                $newphrases[] = $phr;
            }
        }
        return $newphrases;
    }

    /**
     * Takes a phrase and breaks it up into words
     * @param $phr
     * @param $done : the JSON string of the array of word:ids that have already be processed
     * @return array|false|string[]
     */
    private function phrase_parse($phr, $done) {
        // break phrase into syllables
        $patt = '[' . $this::$syl_delims . ']+';
        $syls = mb_split($patt, urldecode($phr));
        // Remove empty syllables
        $syls = array_filter($syls, function ($s) {
            return strlen($s) > 0;
        });
        //error_log("Syllables in parse phrase: " . implode('$', $syls));

        // Remove any extraneous shads at end of syllables (couldn't get to work with pattern above)
        $delimfilter = function ($s)  {
            return str_replace("།", '', $s); // remove any extraneous delimiters
        };

        $syls = array_map($delimfilter, $syls);
        $maxLoop = pow(count($syls), 2); // to prevent endless looping if something goes wrong
        $lct = 0;

        // Do n words at a time and returns remaining phrase for resending.
        $words = [];
        $word_limit = 1;
        $syl_bank = array_splice($syls, $word_limit * 6);
        while (count($words) < $word_limit && $lct < $maxLoop) {
            $lct++;
            $unused = [];
            // Start with full phrase and knock one syllable off end each time not found.
            for($i = count($syls); $i > 0; $i--) {
                $test_word = trim(implode($this::$tsek, array_slice($syls, 0, $i))); // build word by putting tseks between syllables
                if (str_contains($done, "|$test_word:")) {
                    // Word has already been translated
                    $unused = array_slice($syls, $i);
                    break;
                } else {
                    $word_id = $this->find_word($test_word);
                    if ($word_id) {
                        if (!in_array($word_id, $words)) {
                            $words[] = $word_id;
                        }
                        $unused = array_slice($syls, $i);
                        break;
                    } else if ($i == 1) {
                        $words[] = "$syls[0]:-1";
                        $unused = array_slice($syls, 1);
                    }
                }
            }
            $syls = $unused;
        }
        array_push($syls, ...$syl_bank);
        $done .= '|' . implode('|', $words);
        $done = str_replace('||',  '|', $done);
        return [$words, $syls, $done]; // return n number of words plus unprocessed syllable list
    }

    private function find_word($wd) {
        $wds = $this->word_variants($wd);
        //error_log("Word variants in find word: " . implode('$', $wds));
        $sdoc = $this->querySolr($wds);
        if (!empty($sdoc['response']['docs'])) {
            /*  For debugging, queries for full syllable with secondary hits on syllable without instrumental or la-don */
            /*
            if ($sdoc['response']['numFound'] * 1 > 1) {
                error_log("Multiple docs found for “{$wd}”: {$sdoc['response']['numFound']}");
                error_log(json_encode($sdoc['response']));
            }
            */
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
        $removed =  mb_substr($wd, 0, $wlen - 2);
        //error_log("word variants: $wd, $last_char, $last_two_char, $removed");
        if ($last_char === $this::$sa_jug || $last_char === $this::$ra_jug) {
            $wds[] = $removed . $this::$a_jug;
        } else if ($last_two_char === $this::$ai_jug) {
            $wds[] = $removed . $this::$a_jug;
        } else if ($last_two_char === $this::$ao) {
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
        //error_log("solr query: $surl");
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