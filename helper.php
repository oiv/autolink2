<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Otto Vainio <oiv-plugins@valjakko.net>
 *             Ideas "borrowed" from Esther Brunners tag plugin.
 * Version 8.3.2007 Fixed replace patter to use back reference to keep case of replaced text
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_autolink2 extends DokuWiki_Plugin {

  var $idx_dir = '';      // directory for index files
  var $page_idx = array(); // array of existing pages
  var $autolink_idx = array(); // array of anchors and index in which pages they are found
        
  /**
   * Constructor gets default preferences and language strings
   */
  function helper_plugin_autolink2(){
    global $ID, $conf;

    // determine where index files are saved
    if (@file_exists($conf['indexdir'].'/page.idx')){ // new word length based index
      $this->idx_dir = $conf['indexdir'];
      $this->page_idx = @file($this->idx_dir.'/page.idx');
      if (!@file_exists($this->idx_dir.'/autolink.idx')) $this->_importOldAutolinkIndex('index');
    } else {                                          // old index
      $this->idx_dir = $conf['cachedir'];
      $this->page_idx = @file($this->idx_dir.'/page.idx');
      if (!@file_exists($this->idx_dir.'/autolink.idx')) $this->_importOldAutolinkIndex('cache');
    }
  
    // load page and tag index
    $autolink_index      = @file($this->idx_dir.'/autolink.idx');
    if (is_array($autolink_index)){
      foreach ($autolink_index as $idx_line){
        list($key, $value) = explode("\t", $idx_line, 2);
        if ($value) {
          $this->autolink_idx[$key]=trim($value);
        }
      }
    }
  }

    function getInfo() {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }
  
  function getMethods(){
    $result = array();
    $result[] = array(
      'name'   => 'getAnchors',
      'desc'   => 'returns pattern and replace arrays for replace',
      'return' => array('{pattern,replace}' => 'array'),
    );
    return $result;
  }
  

  function getAnchors() {
    $result = array(); // array of line numbers in the page index
//    $result = array_merge($result, $this->autolink_idx);
    $result = $this->autolink_idx;
    $res=$this->_numToID($result);
    if (is_array($res)){
    $l=0;
    $sr="-anchorlink-";
    $er="-knilrohcna-";
    $sp="-anchorlink-";
    $ep="-knilrohcna-";
    $hasCustom=false;
    $customStart="";
    $customEnd="";
    if ($this->getConf('customfilter_start') && $this->getConf('customfilter_end')) {
      $customStart=$this->getConf('customfilter_start');
      $customEnd=$this->getConf('customfilter_end');
      $hasCustom=true;
    }
    foreach ($res as $anchor => $page){
      // Mark everything close to possible as a potential autolink anchor
      $pattern[$l]="/(?<= |\\n|\\t|\|)(".$anchor.")(?=( |,|\.|:|\\n|\\t|\|))/msi";
      //    $replace[$l++]=$sr.$anchor.$er;
      $replace[$l++]=$sr."\\1".$er;
      // Remove anchor from headings
      $pattern[$l]="/(={1,6}.*?)".$sp."(".$anchor.")".$ep."(.*?={1,6})/i";
      //    $replace[$l++]="$1".$anchor."$3";
      $replace[$l++]="$1$2$3";
      // Remove anchor from media (and some plugin) refs = {{something}}
      $pattern[$l]="/(\{\{.*?)".$sp."(".$anchor.")".$ep."(.*?\}\})/i";
      $replace[$l++]="$1$2$3";
      // Remove anchor from links refs = [[something]]
      $pattern[$l]="/(\[\[.*)".$sp."(".$anchor.")".$ep."(.*\]\])/Ui";
      $replace[$l++]="$1$2$3";
      // Remove anchor from links refs = <something>
      $pattern[$l]="/(\<.*)".$sp."(".$anchor.")".$ep."(.*\>)/Ui";
      $replace[$l++]="$1$2$3";
      // Remove custom pattern links.
      if ($hasCustom==true) {
        $pattern[$l]=$customStart.$sp."(".$anchor.")".$ep.$customEnd;
        $replace[$l++]="$1$2$3";
      }

      // Finally change all that's left to links
      $pattern[$l]="/".$sp."(".$anchor.")".$ep."/i";
      //    $replace[$l++]="[[:".$page."|".$anchor."]]";
      $replace[$l++]="[[:".$page."|$1]]";

    }
    if (is_array($pattern)) ksort($pattern);
    if (is_array($replace)) ksort($replace);
  }

    // now convert to page IDs and return
    return array($pattern,$replace);
  }
      
  function lensort($a,$b){
    return strlen($b)-strlen($a);
  }

  /**
   * Update Autolink index
   */
  function _updateAutolinkIndex($id, $autolinks){
    global $ID, $INFO;
    if (!is_array($autolinks) || empty($autolinks)) return false;
    usort($autolinks,array("helper_plugin_autolink2","lensort"));
    $changed = false;
    // get page id (this is the linenumber in page.idx)
    $pid = array_search("$id\n", $this->page_idx);
    if (!is_int($pid)){
      $this->page_idx[] = "$id\n";
      $pid = count($this->page_idx) - 1;
      // page was new - write back
      $this->_saveIndex('page');
    }

    // clean array first
    $c = count($autolinks);
    for ($i = 0; $i <= $c; $i++){
      $autolinks[$i] = utf8_strtolower($autolinks[$i]);
    }

    // clear no longer used autolinks
    if ($ID == $id){
      $oldautolinks = $INFO['meta']['anchors'];
      if (!is_array($oldautolinks)) $oldautolinks = explode(' ', $oldautolinks);
      foreach ($oldautolinks as $oldtag){
        if (!$oldtag) continue;                 // skip empty autolinks
        $oldtag = utf8_strtolower($oldtag);
        if (in_array($oldtag, $autolinks)) continue; // tag is still there
        $this->autolink_idx[$oldtag]="";
        $changed = true;
      }
    }
        
    // fill tag in
    foreach ($autolinks as $autolink){
      if (!$autolink) continue; // skip empty autolinks
      if ($this->autolink_idx[$autolink]!=$pid){
        $this->autolink_idx[$autolink] = $pid;
        $changed = true;
      }
    }
        
    // save tag index
    if ($changed) return $this->_saveIndex('autolink');
    else return true;
  }

    /**
   * Remove Autolink index
   */
  function _removeAutolinkIndex($id){
    global $ID, $INFO;
 
    // get page id (this is the linenumber in page.idx)
    $pid = array_search("$id\n", $this->page_idx);
    if (!is_int($pid)){
      return;
    }
    
    
    // clear no longer used autolinks
    if ($ID == $id){
      $oldautolinks = $INFO['meta']['anchors'];

      if (!is_array($oldautolinks)) $oldautolinks = explode(' ', $oldautolinks);
      foreach ($oldautolinks as $oldtag){
        if (!$oldtag) continue;                 // skip empty autolinks
        $oldtag = utf8_strtolower($oldtag);
//        if (in_array($oldtag, $autolinks)) continue; // tag is still there
        $this->autolink_idx[$oldtag]="";
        $changed = true;
      }

    }
/*        
    // fill tag in
    foreach ($autolinks as $autolink){
      if (!$autolink) continue; // skip empty autolinks
      if ($this->autolink_idx[$autolink]!=$pid){
        $this->autolink_idx[$autolink] = $pid;
        $changed = true;
      }
    }
  */      
    // save tag index
    if ($changed) return $this->_saveIndex('autolink');
    else return true;
  }

  /**
   * Save tag or page index
   */
  function _saveIndex($idx = 'autolink'){
    $fh = fopen($this->idx_dir.'/'.$idx.'.idx', 'w');
    if (!$fh) return false;
    if ($idx == 'page'){
      fwrite($fh, join('', $this->page_idx));
    } else {
      $autolink_index = array();
      foreach ($this->autolink_idx as $key => $value){
        if ($value=="") continue;                 // skip empty autolinks
        $autolink_index[] = $key."\t".$value."\n";
      }
      fwrite($fh, join('', $autolink_index));
    }
    fclose($fh);
    return true;
  }
  
  /**
   * Generates the autolink index
   */
  function _generateAutolinkIndex(){
    global $conf;
    require_once (DOKU_INC.'inc/search.php');
    $pages = array();
    search($pages, $conf['datadir'], 'search_allpages', array());
    foreach ($pages as $page){
      $anchors = p_get_metadata($page['id'], 'anchors');
      if (!is_array($anchors)) $anchors = explode('|', $anchors);
      $this->_updateAutolinkIndex($page['id'], $anchors);
    }
    return true;
  }
  
  
  /**
   * Converts an array of pages numbers to IDs
   */
  function _numToID($nums){
    if (is_array($nums)){
      $docs = array();
      foreach ($nums as $page=>$num){
        $docs[$page] = trim($this->page_idx[$num]);
      }
      return $docs;
    } else {
      return trim($this->page_idx[$nums]);
    }
  }
  
  /**
   * Import old Autolink index
   */
  function _importOldAutolinkIndex($to){
    global $conf;
    $old=DOKU_PLUGIN.'autolink/data/links.php';
    $cache = $conf['cachedir'].'/autolink.idx';
    $index = $conf['indexdir'].'/autolink.idx';

    if ($to=='index') {
      if (@file_exists($cache)) {
        if (@copy($cache, $index)){
          @unlink($cache);
          return true;
        }
      } else if (@file_exists($old)) {
        return $this->_buildindexFromOld($index);
      } else {
        return $this->_generateAutolinkIndex();
      }
    } else {
      if (@file_exists($old)) {
        return $this->_buildindexFromOld($cache);
      } else {
        $this->_generateAutolinkIndex();
      }
    }
    return false;
  }


  function _buildindexFromOld($to) {
    global $conf;
    $old=DOKU_PLUGIN.'autolink/data/links.php';
    require_once($old);
    $changed = false; 
    foreach ($replace as $value){
      $page=substr($value,3,-2);
      list ($pageid,$anchor) = explode('|',$page);
      $pid = array_search("$pageid\n", $this->page_idx);
      $this->autolink_idx[$anchor] = $pid;
      $changed = true;
    }
    // save autolink index
    if ($changed) return $this->_saveIndex('autolink');
    else return true;
  }



}
