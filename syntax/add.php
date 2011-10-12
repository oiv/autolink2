<?php
/**
 * Allows definition of autolink which is then shown using wikilink tag throughout the pages:
 * Example:
 * On the page wanted to be autolinked. {{autolink>anchors|separated by|}}
 * On the pages where autolink is wanted to insert the whole page around <autolink> and </autolink>
 * or by setting option 'autoautolink' to 1 links are set in avery page. You can prevent page from 
 * autoimatically  setting links by setting ~~noautolink~~ in the start of the page
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
 
if (!defined('DOKU_INC')) {
  define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
}
if (!defined('DOKU_PLUGIN')) {
  define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}
require_once(DOKU_PLUGIN.'syntax.php');
 
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_autolink2_add extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo() {
        return confToHash(dirname(__FILE__).'/../plugin.info.txt');
    }
 
    function getType(){ return 'substition'; }
    function getSort(){ return 304; }
    function getPType(){ return 'block';}
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
    // {{autolink>Anchor text}}
    // '\{\{tag>.*?\}\}'
      $this->Lexer->addSpecialPattern('\{\{autolink>.*?\}\}',$mode,'plugin_autolink2_add');
    }
 
    /**
     * Handle the match
     */

  function handle($match, $state, $pos, &$handler){
    global $ID;
    global $conf;
    global $ACT;
    if ($ACT<>"show") return "";
    $anchors = explode('|', substr($match, 11, -2)); // strip markup and split tags
    if (!$my = plugin_load('helper', 'autolink2')) return false;
    $my->_updateAutolinkIndex($ID, $anchors);
    return $anchors;
  }      

 
    /**
     * Create output
     */
  function render($mode, &$renderer, $data) {
    if ($data === false) return false;
    if (!$my = plugin_load('helper', 'autolink2')) return false;
    // XHTML output
    if ($mode == 'xhtml'){
      return true;
    // for metadata renderer
    } elseif ($mode == 'metadata'){
//      if ($renderer->capture) $renderer->doc .= DOKU_LF.strip_tags($tags).DOKU_LF;
//      foreach ($my->references as $ref => $exists){
//        $renderer->meta['relation']['references'][$ref] = $exists;
//      }
      $renderer->meta['anchors'] = $data;
      return true;
    }
    return false;
  }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
