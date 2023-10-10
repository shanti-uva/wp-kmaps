<?php
final class MandalaTranslate
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
     * @var MandalaTranslate
     * @since 2.1
     */
    protected static $_instance = null;

    protected static $wy_conv_url = 'http://texts.thdl.org/wylie/?wy='; // TODO: Fix/finalize this url

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

    public function parse($wyl) {
        $tib = $this->convert_wylie($wyl);
        error_log("Tib in parse is: " . $tib);
        if (!$tib) { return false; }
        /*

        $opts = array(
            'fq' => 'asset_type:terms&fq=related_uid_ss:subjects-9315',
            // 'fl' => 'uid',
        );
        $doc = $this->querySolr($tib, $opts);
        return $doc;
        */
        $resp = array(
            'wylie' => $wyl,
            'tibetan' => $tib
        );
        return $resp;
    }

    public function convert_wylie($wyl) {
        $wyl = preg_replace('/\s+/', '%20', trim($wyl)); // Normalize spaces and remove leading and trailing ones
        $first_chr_code = mb_ord(substr($wyl, 0, 1));
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

    private function querySolr($q, $opts=array()) {
        // sample query: ?q=names:ཁ&fl=*&wt=json&rows=30&fq=asset_type:terms&fq=related_uid_ss:subjects-9315 (means "Expression" not tree node)
        // q string for tibetan def: names:\"$enctib\"
        // fq for defs: &fq=asset_type:terms&fq=related_uid_ss:subjects-9315&rows=10&fl=*&wt=json
        $opts_list = array();
        $q = preg_replace('/\s+/', '%20', $q) . '/';
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
        $surl = $this->solrurl . "?q=names:\"$q\"&$opts_str";
        // return $surl;
        $sdoc_data = @file_get_contents($surl);
        $sdoc = array('warning' => "{$q}: Not found (URL: $surl)");
        if ($sdoc_data) {
            $sdoc = json_decode($sdoc_data, TRUE);
            if (!empty($sdoc['response']['docs'])) {
                $sdoc = $sdoc['response']['docs'][0];
            }
        }
        $sdoc['url'] = $surl;
        return $sdoc;
    }
}