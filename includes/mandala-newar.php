<?php
final class MandalaNewar
{

    /**
     * Mandala version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class.
     *
     * @var MandalaNewar
     * @since 1.0
     */
    protected static $_instance = null;


    /**
     * Class Variables
     */
    protected string $wpsite;
    protected string $solrurl;

    /**
     * Main Mandala Newar Instance.
     *
     * Ensures only one instance of Mandala is loaded or can be loaded.
     *
     * @return MandalaNewar - Main instance.
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
        $newar = str_replace('%20', ' ', $data);

        // Convert transliteration??

        $newwords = explode(' ', $newar);
        $words = [];
        foreach($newwords as $wrd) {
            $wid = $this->find_word($wrd);
            if ($wid) {
                $words[] = $wid;
            }
        }

        // The returned object is always the same. "undone" is the portion of the initial phrase untranslated. It is an array of syllables so is rejoined with at tsek
        $resp = array(
            'words' => $words,
        );
        return $resp;
    }

    private function find_word($wd) {
        //$wds = $this->word_variants($wd);
        // error_log("Word variants in find word: " . implode(', ', $wds));
        $sdoc = $this->querySolr($wd);
        if (!empty($sdoc['response']['docs'])) {
            /*  For debugging, queries for full syllable with secondary hits on syllable without instrumental or la-don */
            /*
            if ($sdoc['response']['numFound'] * 1 > 1) {
                error_log("Multiple docs found for “{$wd}”: {$sdoc['response']['numFound']}");
                error_log(json_encode($sdoc['response']));
            }
            */
            $doc1 = $sdoc['response']['docs'][0];
            return "{$doc1['name_deva'][0]}:{$doc1['id']}";
        } /*else if (!empty($sdoc['url'])) {
            return $sdoc['url'];
        }*/
        return "$wd:-1";
    }

    /**
     * Checks endings of words for instrumental, locative, genitive, etc and provides list of possible alternate options for the words
     * @param $wd
     * @return array : the returned array of word variants should be in order of importance. The first item will get boost of ^100, second ^99, etc.
     */
    private function word_variants($wd) {
        /*$wds = array($wd);
        $last_char = mb_substr($wd, -1);
        $last_two_char = mb_substr($wd, -2);
        $truncated = mb_substr($wd, 0, -1);
        $extra_truncated = mb_substr($wd, 0, -2);
        $with_a_jug =  $extra_truncated . $this::$a_jug;
        //error_log("word variants: $wd, $last_char, $last_two_char, $removed");
        if ($last_char === $this::$sa_jug || $last_char === $this::$ra_jug) {
            $wds[] = $with_a_jug;
            $wds[] = $truncated;
        } else if ($last_two_char === $this::$ai_jug) {
            $wds[] = $with_a_jug;
            $wds[] = $extra_truncated;
        } else if ($last_two_char === $this::$ao) {
            $wds[] = $with_a_jug;
            $wds[] = $extra_truncated;
        }
        return $wds;*/
        return [];
    }

    private function querySolr($qwd, $opts=array()) {
        // sample query: ?q=names:ཁ&fl=*&wt=json&rows=30&fq=asset_type:terms&fq=related_uid_ss:subjects-9315 (means "Expression" not tree node)
        // q string for tibetan def: names:\"$enctib\"
        // fq for defs: &fq=asset_type:terms&fq=related_uid_ss:subjects-9315&rows=10&fl=*&wt=json
        $opts_list = array(
            'fq=asset_type:terms'  // Find only term definitions not other nodes in tree // For tibetan had &fq=related_uid_ss:subjects-9315 doesn't work for Newar
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
        $opts_list[] = 'sort=score%20DESC';
        $opts_list[] = 'wt=json';
        $opts_str = implode('&', $opts_list);
        // This assumes we are only looking for kmaps
        if (is_array($qwd)) {
            $enc_qwds = array_map(function ($item) {
                return '"' . urlencode($item) . '"';
            }, $qwd);
            // $qwd list must be build in order of importance. First item most important.
            foreach($enc_qwds as $n => &$item) {
                $boost = 100 - ($n * 3);
                $item = "$item^$boost";
            }
            $wd = '(' . implode('%20', $enc_qwds) . ')';
        } else {
            $wd = '"' . urlencode($qwd) . '"';
        }
        // error_log("wds in mandala-translate querySolr: $wd");
        $surl = $this->solrurl . "?q=names:$wd&$opts_str";
        // error_log("solr query: $surl");
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