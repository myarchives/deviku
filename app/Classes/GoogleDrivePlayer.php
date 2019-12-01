<?php

//Create folder if it doesn't already exist
if (!file_exists('cache')) {
    mkdir('cache', 0777, true);
}

function GoogleDrive($gid)
{
    $gdurl = 'https://drive.google.com/file/d/' . $gid . '/preview';
    $iframeid = my_simple_crypt($gid);
    //$title = gdTitle($gid);
    //$img = gdImg($gdurl);
    $streaming_vid = Drive($gid);
    $keys = array('AIzaSyCNxXAnWvUkdi0m7XTkC-EFHb2z2MQMtRo', 'AIzaSyCSqEAuMN_6svup7oZc_v9JRq1PHOQ_2dE', 'AIzaSyD7jsVh3vlw-xhJcklRTugVDSwdnfxMma4', 'AIzaSyDVP1vHDb9fP2fNAhd4GSRRspLMFyVt_X0', 'AIzaSyAFin5-mcY0LhVmjZ56jnVkuUyomb8qf6E', 'AIzaSyACZPjRqcxAS4q_J-MP-dAfMzZVUKqh-2Y');
    if (empty($streaming_vid) || is_null($streaming_vid) || $streaming_vid == "Error") {
        $output = ['label' => 'auto', 'file' => 'https://www.googleapis.com/drive/v3/files/' . $gid . '?alt=media&key=' . $keys[array_rand($keys)], 'type' => 'video/mp4'];
        $output = json_encode($output, JSON_PRETTY_PRINT);
        return $output;
    }
    $output = ['label' => 'auto', 'file' => $streaming_vid, 'type' => 'video/mp4'];
    $output = json_encode($output, JSON_PRETTY_PRINT);
    return $output;
}

//Check cache
function Drive($gid)
{
    $timeout = 900;
    $file_name = md5('GD' . $gid . 'player');
    if (file_exists('cache/' . $file_name . '.cache')) {
        $fopen = file_get_contents('cache/' . $file_name . '.cache');
        $data = explode('@@', $fopen);
        $now = gmdate('Y-m-d H:i:s', time() + 3600 * (+7 + date('I')));
        $times = strtotime($now) - $data[0];
        if ($times >= $timeout) {
            $linkdown = trim(getlink($gid));
            $create_cache = gd_cache($gid, $linkdown);
            $arrays = explode('|', $create_cache);
            $cache = $arrays[0];
        } else {
            $cache = $data[1];
        }
    } else {
        $linkdown = trim(getlink($gid));
        $create_cache = gd_cache($gid, $linkdown);
        $arrays = explode('|', $create_cache);
        $cache = $arrays[0];
    }
    return $cache;
}

//New cache
function gd_cache($gid, $source)
{
    if ($source == '404') {
        return 'Error|a';
    }
    $time = gmdate('Y-m-d H:i:s', time() + 3600 * (+7 + date('I')));
    $file_name = md5('GD' . $gid . 'player');
    $string = strtotime($time) . '@@' . $source;
    $file = fopen("cache/" . $file_name . ".cache", 'w');
    fwrite($file, $string);
    fclose($file);
    if (file_exists('cache/' . $file_name . '.cache')) {
        $msn = $source;
    } else {
        $msn = $source;
    }
    return $msn;
}

function getlink($id)
{
    $link = "https://drive.google.com/uc?export=download&id=$id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/cookies/cookies" . rand(1, 5) . ".txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/cookies/cookies" . rand(1, 5) . ".txt");
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    //curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/google.mp3");
    //curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/google.mp3");
    $page = curl_exec($ch);
    $get = locheader($page);
    if (strpos($page, "Can&#39;t")) {
        //'Sorry, the owner hasn\'t given you permission to download this file.';
        $get = '404';
    } elseif (strpos($page, "Error 404")) {
        //Error 404. We\'re sorry. You can\'t access this item because it is in violation of our Terms of Service.
        $get = '404';
    } else {
        if ($get != "") {
            $get = '404';
        } else {
            $html = str_get_html($page);
            $link = urldecode(trim($html->find('a[id=uc-download-link]', 0)->href));
            $tmp = explode("confirm=", $link);
            $tmp2 = explode("&", $tmp[1]);
            $confirm = $tmp2[0];
            $linkdowngoc = "https://drive.google.com/uc?export=download&id=$id&confirm=$confirm";
            curl_setopt($ch, CURLOPT_URL, $linkdowngoc);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/cookies/cookies" . rand(1, 5) . ".txt");
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/cookies/cookies" . rand(1, 5) . ".txt");

            // Getting binary data
            $page = curl_exec($ch);
            $get = locheader($page);
        }
        curl_close($ch);
    }
    return $get;
}

function gdTitle($gid)
{
    $title = fetch_value(file_get_contents('https://drive.google.com/get_video_info?docid=' . $gid), "title=", "&");
    return $title;
}

function gdImg($url)
{
    $html = new simple_html_dom();
    $html->load_file($url);
    return $html->find('meta[property=og:image]', 0)->attr['content'];
}

function get_drive_id($string)
{
    if (strpos($string, "/edit")) {
        $string = str_replace("/edit", "/view", $string);
    } else if (strpos($string, "?id=")) {
        $parts = parse_url($string);
        parse_str($parts['query'], $query);
        return $query['id'];
    } else if (!strpos($string, "/view")) {
        $string = $string . "/view";
    }
    $start = "file/d/";

    if (strpos($string, "/preview")) {
        $end = "/preview";
    } elseif (strpos($string, "/view")) {
        $end = "/view";
    }
    $string = " " . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) {
        return null;
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function my_simple_crypt($string, $action = 'e')
{
    $secret_key = 'GReg7rNx2z[2';
    $secret_iv = 'C0?s9rh4';
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'e') {
        $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    } else if ($action == 'd') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

function fetch_value($str, $find_start = '', $find_end = '')
{
    if ($find_start == '') {
        return '';
    }
    $start = strpos($str, $find_start);
    if ($start === false) {
        return '';
    }
    $length = strlen($find_start);
    $substr = substr($str, $start + $length);
    if ($find_end == '') {
        return $substr;
    }
    $end = strpos($substr, $find_end);
    if ($end === false) {
        return $substr;
    }

    return substr($substr, 0, $end);
}

// helper functions
// -----------------------------------------------------------------------------
// get html dom form file

define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT', 3);
define('HDOM_TYPE_ENDTAG', 4);
define('HDOM_TYPE_ROOT', 5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO', 3);
define('HDOM_INFO_BEGIN', 0);
define('HDOM_INFO_END', 1);
define('HDOM_INFO_QUOTE', 2);
define('HDOM_INFO_SPACE', 3);
define('HDOM_INFO_TEXT', 4);
define('HDOM_INFO_INNER', 5);
define('HDOM_INFO_OUTER', 6);
define('HDOM_INFO_ENDSPACE', 7);

function locheader($page)
{
    $temp = explode("\r\n", $page);
    foreach ($temp as $item) {
        $temp2 = explode(": ", $item);
        $infoheader[$temp2[0]] = $temp2[1];
    }
    $location = $infoheader['Location'];
    return $location;
}

function file_get_html()
{
    $dom = new simple_html_dom;
    $args = func_get_args();
    $dom->load(call_user_func_array('file_get_contents', $args), true);
    return $dom;
}

// get html dom form string
function str_get_html($str, $lowercase = true)
{
    $dom = new simple_html_dom;
    $dom->load($str, $lowercase);
    return $dom;
}

// dump html dom tree
function dump_html_tree($node, $show_attr = true, $deep = 0)
{
    $lead = str_repeat('    ', $deep);
    echo $lead . $node->tag;
    if ($show_attr && count($node->attr) > 0) {
        echo '(';
        foreach ($node->attr as $k => $v) {
            echo "[$k]=>\"" . $node->$k . '", ';
        }

        echo ')';
    }
    echo "\n";

    foreach ($node->nodes as $c) {
        dump_html_tree($c, $show_attr, $deep + 1);
    }

}

// simple html dom node
// -----------------------------------------------------------------------------
class simple_html_dom_node
{
    public $nodetype = HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    public $_ = array();
    private $dom = null;

    public function __construct($dom)
    {
        $this->dom = $dom;
        $dom->nodes[] = $this;
    }

    public function __destruct()
    {
        $this->clear();
    }

    public function __toString()
    {
        return $this->outertext();
    }

    // clean up memory due to php5 circular references memory leak...
    public function clear()
    {
        $this->dom = null;
        $this->nodes = null;
        $this->parent = null;
        $this->children = null;
    }

    // dump node's tree
    public function dump($show_attr = true)
    {
        dump_html_tree($this, $show_attr);
    }

    // returns the parent of node
    public function parent()
    {
        return $this->parent;
    }

    // returns children of node
    public function children($idx = -1)
    {
        if ($idx === -1) {
            return $this->children;
        }

        if (isset($this->children[$idx])) {
            return $this->children[$idx];
        }

        return null;
    }

    // returns the first child of node
    public function first_child()
    {
        if (count($this->children) > 0) {
            return $this->children[0];
        }

        return null;
    }

    // returns the last child of node
    public function last_child()
    {
        if (($count = count($this->children)) > 0) {
            return $this->children[$count - 1];
        }

        return null;
    }

    // returns the next sibling of node
    public function next_sibling()
    {
        if ($this->parent === null) {
            return null;
        }

        $idx = 0;
        $count = count($this->parent->children);
        while ($idx < $count && $this !== $this->parent->children[$idx]) {
            ++$idx;
        }

        if (++$idx >= $count) {
            return null;
        }

        return $this->parent->children[$idx];
    }

    // returns the previous sibling of node
    public function prev_sibling()
    {
        if ($this->parent === null) {
            return null;
        }

        $idx = 0;
        $count = count($this->parent->children);
        while ($idx < $count && $this !== $this->parent->children[$idx]) {
            ++$idx;
        }

        if (--$idx < 0) {
            return null;
        }

        return $this->parent->children[$idx];
    }

    // get dom node's inner html
    public function innertext()
    {
        if (isset($this->_[HDOM_INFO_INNER])) {
            return $this->_[HDOM_INFO_INNER];
        }

        if (isset($this->_[HDOM_INFO_TEXT])) {
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        $ret = '';
        foreach ($this->nodes as $n) {
            $ret .= $n->outertext();
        }

        return $ret;
    }

    // get dom node's outer text (with tag)
    public function outertext()
    {
        if ($this->tag === 'root') {
            return $this->innertext();
        }

        // trigger callback
        if ($this->dom->callback !== null) {
            call_user_func_array($this->dom->callback, array($this));
        }

        if (isset($this->_[HDOM_INFO_OUTER])) {
            return $this->_[HDOM_INFO_OUTER];
        }

        if (isset($this->_[HDOM_INFO_TEXT])) {
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        // render begin tag
        $ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();

        // render inner text
        if (isset($this->_[HDOM_INFO_INNER])) {
            $ret .= $this->_[HDOM_INFO_INNER];
        } else {
            foreach ($this->nodes as $n) {
                $ret .= $n->outertext();
            }

        }

        // render end tag
        if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END] != 0) {
            $ret .= '</' . $this->tag . '>';
        }

        return $ret;
    }

    // get dom node's plain text
    public function text()
    {
        if (isset($this->_[HDOM_INFO_INNER])) {
            return $this->_[HDOM_INFO_INNER];
        }

        switch ($this->nodetype) {
            case HDOM_TYPE_TEXT:return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
            case HDOM_TYPE_COMMENT:return '';
            case HDOM_TYPE_UNKNOWN:return '';
        }
        if (strcasecmp($this->tag, 'script') === 0) {
            return '';
        }

        if (strcasecmp($this->tag, 'style') === 0) {
            return '';
        }

        $ret = '';
        foreach ($this->nodes as $n) {
            $ret .= $n->text();
        }

        return $ret;
    }

    public function xmltext()
    {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    // build node's text with tag
    public function makeup()
    {
        // text, comment, unknown
        if (isset($this->_[HDOM_INFO_TEXT])) {
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        }

        $ret = '<' . $this->tag;
        $i = -1;

        foreach ($this->attr as $key => $val) {
            ++$i;

            // skip removed attribute
            if ($val === null || $val === false) {
                continue;
            }

            $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
            //no value attr: nowrap, checked selected...
            if ($val === true) {
                $ret .= $key;
            } else {
                switch ($this->_[HDOM_INFO_QUOTE][$i]) {
                    case HDOM_QUOTE_DOUBLE:$quote = '"';
                        break;
                    case HDOM_QUOTE_SINGLE:$quote = '\'';
                        break;
                    default:$quote = '';
                }
                $ret .= $key . $this->_[HDOM_INFO_SPACE][$i][1] . '=' . $this->_[HDOM_INFO_SPACE][$i][2] . $quote . $val . $quote;
            }
        }
        $ret = $this->dom->restore_noise($ret);
        return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
    }

    // find elements by css selector
    public function find($selector, $idx = null)
    {
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) {
            return array();
        }

        $found_keys = array();

        // find each selector
        for ($c = 0; $c < $count; ++$c) {
            if (($levle = count($selectors[0])) === 0) {
                return array();
            }

            if (!isset($this->_[HDOM_INFO_BEGIN])) {
                return array();
            }

            $head = array($this->_[HDOM_INFO_BEGIN] => 1);

            // handle descendant selectors, no recursive!
            for ($l = 0; $l < $levle; ++$l) {
                $ret = array();
                foreach ($head as $k => $v) {
                    $n = ($k === -1) ? $this->dom->root : $this->dom->nodes[$k];
                    $n->seek($selectors[$c][$l], $ret);
                }
                $head = $ret;
            }

            foreach ($head as $k => $v) {
                if (!isset($found_keys[$k])) {
                    $found_keys[$k] = 1;
                }

            }
        }

        // sort keys
        ksort($found_keys);

        $found = array();
        foreach ($found_keys as $k => $v) {
            $found[] = $this->dom->nodes[$k];
        }

        // return nth-element or array
        if (is_null($idx)) {
            return $found;
        } else if ($idx < 0) {
            $idx = count($found) + $idx;
        }

        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    // seek for given conditions
    protected function seek($selector, &$ret)
    {
        list($tag, $key, $val, $exp, $no_key) = $selector;

        // xpath index
        if ($tag && $key && is_numeric($key)) {
            $count = 0;
            foreach ($this->children as $c) {
                if ($tag === '*' || $tag === $c->tag) {
                    if (++$count == $key) {
                        $ret[$c->_[HDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }

        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end == 0) {
            $parent = $this->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent !== null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }

        for ($i = $this->_[HDOM_INFO_BEGIN] + 1; $i < $end; ++$i) {
            $node = $this->dom->nodes[$i];
            $pass = true;

            if ($tag === '*' && !$key) {
                if (in_array($node, $this->children, true)) {
                    $ret[$i] = 1;
                }

                continue;
            }

            // compare tag
            if ($tag && $tag != $node->tag && $tag !== '*') {$pass = false;}
            // compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) {
                        $pass = false;
                    }

                } else if (!isset($node->attr[$key])) {
                    $pass = false;
                }

            }
            // compare value
            if ($pass && $key && $val && $val !== '*') {
                $check = $this->match($exp, $val, $node->attr[$key]);
                // handle multiple class
                if (!$check && strcasecmp($key, 'class') === 0) {
                    foreach (explode(' ', $node->attr[$key]) as $k) {
                        $check = $this->match($exp, $val, $k);
                        if ($check) {
                            break;
                        }

                    }
                }
                if (!$check) {
                    $pass = false;
                }

            }
            if ($pass) {
                $ret[$i] = 1;
            }

            unset($node);
        }
    }

    protected function match($exp, $pattern, $value)
    {
        switch ($exp) {
            case '=':
                return ($value === $pattern);
            case '!=':
                return ($value !== $pattern);
            case '^=':
                return preg_match("/^" . preg_quote($pattern, '/') . "/", $value);
            case '$=':
                return preg_match("/" . preg_quote($pattern, '/') . "$/", $value);
            case '*=':
                if ($pattern[0] == '/') {
                    return preg_match($pattern, $value);
                }

                return preg_match("/" . $pattern . "/i", $value);
        }
        return false;
    }

    protected function parse_selector($selector_string)
    {
        // pattern of CSS selectors, modified from mootools
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string) . ' ', $matches, PREG_SET_ORDER);
        $selectors = array();
        $result = array();
        //print_r($matches);

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0] === '' || $m[0] === '/' || $m[0] === '//') {
                continue;
            }

            // for borwser grnreated xpath
            if ($m[1] === 'tbody') {
                continue;
            }

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if (!empty($m[2])) {$key = 'id';
                $val = $m[2];}
            if (!empty($m[3])) {$key = 'class';
                $val = $m[3];}
            if (!empty($m[4])) {$key = $m[4];}
            if (!empty($m[5])) {$exp = $m[5];}
            if (!empty($m[6])) {$val = $m[6];}

            // convert to lowercase
            if ($this->dom->lowercase) {$tag = strtolower($tag);
                $key = strtolower($key);}
            //elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0] === '!') {$key = substr($key, 1);
                $no_key = true;}

            $result[] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m[7]) === ',') {
                $selectors[] = $result;
                $result = array();
            }
        }
        if (count($result) > 0) {
            $selectors[] = $result;
        }

        return $selectors;
    }

    public function __get($name)
    {
        if (isset($this->attr[$name])) {
            return $this->attr[$name];
        }

        switch ($name) {
            case 'outertext':return $this->outertext();
            case 'innertext':return $this->innertext();
            case 'plaintext':return $this->text();
            case 'xmltext':return $this->xmltext();
            default:return array_key_exists($name, $this->attr);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'outertext':return $this->_[HDOM_INFO_OUTER] = $value;
            case 'innertext':
                if (isset($this->_[HDOM_INFO_TEXT])) {
                    return $this->_[HDOM_INFO_TEXT] = $value;
                }

                return $this->_[HDOM_INFO_INNER] = $value;
        }
        if (!isset($this->attr[$name])) {
            $this->_[HDOM_INFO_SPACE][] = array(' ', '', '');
            $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        }
        $this->attr[$name] = $value;
    }

    public function __isset($name)
    {
        switch ($name) {
            case 'outertext':return true;
            case 'innertext':return true;
            case 'plaintext':return true;
        }
        //no value attr: nowrap, checked selected...
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->attr[$name])) {
            unset($this->attr[$name]);
        }

    }

    // camel naming conventions
    public function getAllAttributes()
    {return $this->attr;}
    public function getAttribute($name)
    {return $this->__get($name);}
    public function setAttribute($name, $value)
    {$this->__set($name, $value);}
    public function hasAttribute($name)
    {return $this->__isset($name);}
    public function removeAttribute($name)
    {$this->__set($name, null);}
    public function getElementById($id)
    {return $this->find("#$id", 0);}
    public function getElementsById($id, $idx = null)
    {return $this->find("#$id", $idx);}
    public function getElementByTagName($name)
    {return $this->find($name, 0);}
    public function getElementsByTagName($name, $idx = null)
    {return $this->find($name, $idx);}
    public function parentNode()
    {return $this->parent();}
    public function childNodes($idx = -1)
    {return $this->children($idx);}
    public function firstChild()
    {return $this->first_child();}
    public function lastChild()
    {return $this->last_child();}
    public function nextSibling()
    {return $this->next_sibling();}
    public function previousSibling()
    {return $this->prev_sibling();}
}

// simple html dom parser
// -----------------------------------------------------------------------------
class simple_html_dom
{
    public $root = null;
    public $nodes = array();
    public $callback = null;
    public $lowercase = false;
    protected $pos;
    protected $doc;
    protected $char;
    protected $size;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    // use isset instead of in_array, performance boost about 30%...
    protected $self_closing_tags = array('img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1);
    protected $block_tags = array('root' => 1, 'body' => 1, 'form' => 1, 'div' => 1, 'span' => 1, 'table' => 1);
    protected $optional_closing_tags = array(
        'tr' => array('tr' => 1, 'td' => 1, 'th' => 1),
        'th' => array('th' => 1),
        'td' => array('td' => 1),
        'li' => array('li' => 1),
        'dt' => array('dt' => 1, 'dd' => 1),
        'dd' => array('dd' => 1, 'dt' => 1),
        'dl' => array('dd' => 1, 'dt' => 1),
        'p' => array('p' => 1),
        'nobr' => array('nobr' => 1),
    );

    public function __construct($str = null)
    {
        if ($str) {
            if (preg_match("/^http:\/\//i", $str) || is_file($str)) {
                $this->load_file($str);
            } else {
                $this->load($str);
            }

        }
    }

    public function __destruct()
    {
        $this->clear();
    }

    // load html from string
    public function load($str, $lowercase = true)
    {
        // prepare
        $this->prepare($str, $lowercase);
        // strip out comments
        $this->remove_noise("'<!--(.*?)-->'is");
        // strip out cdata
        $this->remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        // strip out <style> tags
        $this->remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        // strip out <script> tags
        $this->remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        // strip out preformatted tags
        $this->remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        // strip out server side scripts
        $this->remove_noise("'(<\?)(.*?)(\?>)'s", true);
        // strip smarty scripts
        $this->remove_noise("'(\{\w)(.*?)(\})'s", true);

        // parsing
        while ($this->parse());
        // end
        $this->root->_[HDOM_INFO_END] = $this->cursor;
    }

    // load html from file
    public function load_file()
    {
        $args = func_get_args();
        $this->load(call_user_func_array('file_get_contents', $args), true);
    }

    // set callback function
    public function set_callback($function_name)
    {
        $this->callback = $function_name;
    }

    // remove callback function
    public function remove_callback()
    {
        $this->callback = null;
    }

    // save dom as string
    public function save($filepath = '')
    {
        $ret = $this->root->innertext();
        if ($filepath !== '') {
            file_put_contents($filepath, $ret);
        }

        return $ret;
    }

    // find dom node by css selector
    public function find($selector, $idx = null)
    {
        return $this->root->find($selector, $idx);
    }

    // clean up memory due to php5 circular references memory leak...
    public function clear()
    {
        foreach ($this->nodes as $n) {$n->clear();
            $n = null;}
        if (isset($this->parent)) {$this->parent->clear();unset($this->parent);}
        if (isset($this->root)) {$this->root->clear();unset($this->root);}
        unset($this->doc);
        unset($this->noise);
    }

    public function dump($show_attr = true)
    {
        $this->root->dump($show_attr);
    }

    // prepare HTML data and init everything
    protected function prepare($str, $lowercase = true)
    {
        $this->clear();
        $this->doc = $str;
        $this->pos = 0;
        $this->cursor = 1;
        $this->noise = array();
        $this->nodes = array();
        $this->lowercase = $lowercase;
        $this->root = new simple_html_dom_node($this);
        $this->root->tag = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = -1;
        $this->root->nodetype = HDOM_TYPE_ROOT;
        $this->parent = $this->root;
        // set the length of content
        $this->size = strlen($str);
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }

    }

    // parse html content
    protected function parse()
    {
        if (($s = $this->copy_until_char('<')) === '') {
            return $this->read_tag();
        }

        // text
        $node = new simple_html_dom_node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = $s;
        $this->link_nodes($node, false);
        return true;
    }

    // read tag info
    protected function read_tag()
    {
        if ($this->char !== '<') {
            $this->root->_[HDOM_INFO_END] = $this->cursor;
            return false;
        }
        $begin_tag_pos = $this->pos;
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

        // end tag
        if ($this->char === '/') {
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            $this->skip($this->token_blank_t);
            $tag = $this->copy_until_char('>');

            // skip attributes in end tag
            if (($pos = strpos($tag, ' ')) !== false) {
                $tag = substr($tag, 0, $pos);
            }

            $parent_lower = strtolower($this->parent->tag);
            $tag_lower = strtolower($tag);

            if ($parent_lower !== $tag_lower) {
                if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        if ($this->parent->parent) {
                            $this->parent = $this->parent->parent;
                        }

                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } else if (($this->parent->parent) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $this->parent->parent;
                    }

                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } else if (($this->parent->parent) && strtolower($this->parent->parent->tag) === $tag_lower) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $this->parent = $this->parent->parent;
                } else {
                    return $this->as_text_node($tag);
                }

            }

            $this->parent->_[HDOM_INFO_END] = $this->cursor;
            if ($this->parent->parent) {
                $this->parent = $this->parent->parent;
            }

            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        $node = new simple_html_dom_node($this);
        $node->_[HDOM_INFO_BEGIN] = $this->cursor;
        ++$this->cursor;
        $tag = $this->copy_until($this->token_slash);

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0] === '!') {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until_char('>');

            if (isset($tag[2]) && $tag[1] === '-' && $tag[2] === '-') {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag = 'comment';
            } else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag = 'unknown';
            }

            if ($this->char === '>') {
                $node->_[HDOM_INFO_TEXT] .= '>';
            }

            $this->link_nodes($node, true);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // text
        if ($pos = strpos($tag, '<') !== false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[HDOM_INFO_TEXT] = $tag;
            $this->link_nodes($node, false);
            $this->char = $this->doc[--$this->pos]; // prev
            return true;
        }

        if (!preg_match("/^[\w-:]+$/", $tag)) {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until('<>');
            if ($this->char === '<') {
                $this->link_nodes($node, false);
                return true;
            }

            if ($this->char === '>') {
                $node->_[HDOM_INFO_TEXT] .= '>';
            }

            $this->link_nodes($node, false);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // begin tag
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $tag_lower = strtolower($tag);
        $node->tag = ($this->lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        if (isset($this->optional_closing_tags[$tag_lower])) {
            while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[HDOM_INFO_END] = 0;
                $this->parent = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }

        $guard = 0; // prevent infinity loop
        $space = array($this->copy_skip($this->token_blank), '', '');

        // attributes
        do {
            if ($this->char !== null && $space[0] === '') {
                break;
            }

            $name = $this->copy_until($this->token_equal);
            if ($guard === $this->pos) {
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                continue;
            }
            $guard = $this->pos;

            // handle endless '<'
            if ($this->pos >= $this->size - 1 && $this->char !== '>') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = '<' . $tag . $space[0] . $name;
                $node->tag = 'text';
                $this->link_nodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if ($this->doc[$this->pos - 1] == '<') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->tag = 'text';
                $node->attr = array();
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = substr($this->doc, $begin_tag_pos, $this->pos - $begin_tag_pos - 1);
                $this->pos -= 2;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $this->link_nodes($node, false);
                return true;
            }

            if ($name !== '/' && $name !== '') {
                $space[1] = $this->copy_skip($this->token_blank);
                $name = $this->restore_noise($name);
                if ($this->lowercase) {
                    $name = strtolower($name);
                }

                if ($this->char === '=') {
                    $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                    $this->parse_attr($node, $name, $space);
                } else {
                    //no value attr: nowrap, checked selected...
                    $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                    $node->attr[$name] = true;
                    if ($this->char != '>') {
                        $this->char = $this->doc[--$this->pos];
                    }
                    // prev
                }
                $node->_[HDOM_INFO_SPACE][] = $space;
                $space = array($this->copy_skip($this->token_blank), '', '');
            } else {
                break;
            }

        } while ($this->char !== '>' && $this->char !== '/');

        $this->link_nodes($node, true);
        $node->_[HDOM_INFO_ENDSPACE] = $space[0];

        // check self closing
        if ($this->copy_until_char_escape('>') === '/') {
            $node->_[HDOM_INFO_ENDSPACE] .= '/';
            $node->_[HDOM_INFO_END] = 0;
        } else {
            // reset parent
            if (!isset($this->self_closing_tags[strtolower($node->tag)])) {
                $this->parent = $node;
            }

        }
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    // parse attributes
    protected function parse_attr($node, $name, &$space)
    {
        $space[2] = $this->copy_skip($this->token_blank);
        switch ($this->char) {
            case '"':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape('"'));
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                break;
            case '\'':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape('\''));
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
                break;
            default:
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                $node->attr[$name] = $this->restore_noise($this->copy_until($this->token_attr));
        }
    }

    // link node's parent
    protected function link_nodes(&$node, $is_child)
    {
        $node->parent = $this->parent;
        $this->parent->nodes[] = $node;
        if ($is_child) {
            $this->parent->children[] = $node;
        }

    }

    // as a text node
    protected function as_text_node($tag)
    {
        $node = new simple_html_dom_node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = '</' . $tag . '>';
        $this->link_nodes($node, false);
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    protected function skip($chars)
    {
        $this->pos += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
    }

    protected function copy_skip($chars)
    {
        $pos = $this->pos;
        $len = strspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        if ($len === 0) {
            return '';
        }

        return substr($this->doc, $pos, $len);
    }

    protected function copy_until($chars)
    {
        $pos = $this->pos;
        $len = strcspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
        return substr($this->doc, $pos, $len);
    }

    protected function copy_until_char($char)
    {
        if ($this->char === null) {
            return '';
        }

        if (($pos = strpos($this->doc, $char, $this->pos)) === false) {
            $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
            $this->char = null;
            $this->pos = $this->size;
            return $ret;
        }

        if ($pos === $this->pos) {
            return '';
        }

        $pos_old = $this->pos;
        $this->char = $this->doc[$pos];
        $this->pos = $pos;
        return substr($this->doc, $pos_old, $pos - $pos_old);
    }

    protected function copy_until_char_escape($char)
    {
        if ($this->char === null) {
            return '';
        }

        $start = $this->pos;
        while (1) {
            if (($pos = strpos($this->doc, $char, $start)) === false) {
                $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
                $this->char = null;
                $this->pos = $this->size;
                return $ret;
            }

            if ($pos === $this->pos) {
                return '';
            }

            if ($this->doc[$pos - 1] === '\\') {
                $start = $pos + 1;
                continue;
            }

            $pos_old = $this->pos;
            $this->char = $this->doc[$pos];
            $this->pos = $pos;
            return substr($this->doc, $pos_old, $pos - $pos_old);
        }
    }

    // remove noise from html content
    protected function remove_noise($pattern, $remove_tag = false)
    {
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        for ($i = $count - 1; $i > -1; --$i) {
            $key = '___noise___' . sprintf('% 3d', count($this->noise) + 100);
            $idx = ($remove_tag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // reset the length of content
        $this->size = strlen($this->doc);
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }

    }

    // restore noise to html content
    public function restore_noise($text)
    {
        while (($pos = strpos($text, '___noise___')) !== false) {
            $key = '___noise___' . $text[$pos + 11] . $text[$pos + 12] . $text[$pos + 13];
            if (isset($this->noise[$key])) {
                $text = substr($text, 0, $pos) . $this->noise[$key] . substr($text, $pos + 14);
            }

        }
        return $text;
    }

    public function __toString()
    {
        return $this->root->innertext();
    }

    public function __get($name)
    {
        switch ($name) {
            case 'outertext':return $this->root->innertext();
            case 'innertext':return $this->root->innertext();
            case 'plaintext':return $this->root->text();
        }
    }

    // camel naming conventions
    public function childNodes($idx = -1)
    {return $this->root->childNodes($idx);}
    public function firstChild()
    {return $this->root->first_child();}
    public function lastChild()
    {return $this->root->last_child();}
    public function getElementById($id)
    {return $this->find("#$id", 0);}
    public function getElementsById($id, $idx = null)
    {return $this->find("#$id", $idx);}
    public function getElementByTagName($name)
    {return $this->find($name, 0);}
    public function getElementsByTagName($name, $idx = -1)
    {return $this->find($name, $idx);}
    public function loadFile()
    {$args = func_get_args();
        $this->load(call_user_func_array('file_get_contents', $args), true);}
}