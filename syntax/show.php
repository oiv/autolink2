<?php
/**
 * Allows definition of autolink which is then shown using wikilink tag throughout the pages:
 * Example:
 * On the page wanted to be autolinked. {{autolink>anchors|separated by|}}
 * On the pages where autolink is wanted to insert the whole page around <autolink> and </autolink>
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
class syntax_plugin_autolink2_show extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo() {
        return confToHash(dirname(__FILE__).'/../plugin.info.txt');
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){return 'substition';}
    function getPType() {return 'normal';}
    function getSort() {return 999;}

	function connectTo($mode) { 
      $this->Lexer->addEntryPattern('<autolink>(?=.*?\x3C/autolink\x3E)',$mode,'plugin_autolink2_show'); 
    }
    function postConnect() { 
      $this->Lexer->addExitPattern('</autolink>','plugin_autolink2_show'); 
    }
    

    function handle($match, $state, $pos, Doku_Handler $handler){
       return array($match, $state);
    }
 
    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){
          $renderer->doc .= $this->_renderautolink($renderer, $data[0],$data[1]);
          return true;
        }
        return false;
    }
    
    function _renderautolink(&$renderer, $match, $state) {
//        if (!$this->getConf('autoautolink')) return false;

        switch ($state) {
        case DOKU_LEXER_ENTER :      
          return "";
          break;
        case DOKU_LEXER_UNMATCHED :
          if ($my =& plugin_load('helper', 'autolink2')) $anchors = $my->getAnchors();
          $x=$match;
          if (is_array($anchors)){
          $pattern=$anchors[0];
          $replace=$anchors[1];
              $replaced = preg_replace($pattern,$replace,$match);
              $x=p_render('xhtml',p_get_instructions($replaced),$info);
            return $x;
          }
          break;
        case DOKU_LEXER_EXIT :
          return "";
          break;
        }
    }
}
?>