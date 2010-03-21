<?php
/**
 * Autolink2  Plugin
 *
 * @author Otto Vainio <otto@valjakko.net>
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_autolink2 extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
    function getInfo() {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }
 
  /**
   * Register its handlers with the DokuWiki's event controller
   */
  function register(&$controller) {
    $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE',  $this, '_hookautolink');
    $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE',  $this, '_hookautolinkwrite');
  }
 
  /**
   */
  function _hookautolink(&$event, $param) {
    if (!$this->getConf('autoautolink')) return;
    if ($my =& plugin_load('helper', 'autolink2')) $anchors = $my->getAnchors();
    $x=$event->data;
    if (substr($x,0,14)=='~~noautolink~~') {
      $event->data = substr($x,14);
    } else {
      if (is_array($anchors)){
        $pattern=$anchors[0];
        $replace=$anchors[1];
        if ($pattern<>'' and $replace<>'') {
          $replaced = preg_replace($pattern,$replace,$x);
          $x=$replaced;
          $event->data = $x;
        }
      }
    }
  }
  function _hookautolinkwrite(&$event, $param) {
    if ($event->data[3]) return;
    $x=$event->data[0][1];
    $id="";
    if ($event->data[1]) {
      $id=$event->data[1].":";
    }
    $id=$id.$event->data[2];
    if (empty($x)) {
      if ($my =& plugin_load('helper', 'autolink2')) {
        $my->_removeAutolinkIndex($id, '');
      }
    }
  }
}

