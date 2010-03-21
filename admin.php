<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_autolink2 extends DokuWiki_Admin_Plugin {

  var $_auth = null;        // auth object
  function admin_plugin_autolink2(){
      global $auth;

      $this->setupLocale();

      if (!isset($auth)) {
        $this->disabled = $this->lang['noauth'];
      } else if (!$auth->canDo('getUsers')) {
        $this->disabled = $this->lang['nosupport'];
      } else {

        // we're good to go
        $this->_auth = & $auth;

      }
  }

  /**
   * return some info
   */
    function getInfo() {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

  /**
   * return sort order for position in admin menu
   */
  function getMenuSort() {
    return 999;
  }

  /**
   * handle user request
   */
  function handle() {
    if (isset($_REQUEST['delete'])) {
      $old=DOKU_PLUGIN.'autolink/data/links.php';
      @unlink($old);
      global $ID;
      header("Location: ".wl($ID,array('do'=>'admin','page'=>'autolink2'),true,'&'));
      exit();
    }
  }

  /**
   * output appropriate html
   */
  function html() {
    global $conf;
    global $ID;

    if(is_null($this->_auth)) {
      print $this->lang['badauth'];
      return false;
    }

    require_once (DOKU_INC.'inc/search.php');

    $sopts=array();
    $sopts['query']="autolink";
    search($replace, $conf['datadir'], array($this, 'search'), $sopts);

    if (!isset($replace)) {
      if (@file_exists(DOKU_PLUGIN.'autolink/data/links.php')) {
        $oldplugin=DOKU_PLUGIN.'autolink/syntax/add.php';
        if (@file_exists($oldplugin)) {
          ptln($this->lang["removeold"]);
        } else {
          ptln($this->lang["noneed"]);
        }
      } else {
        ptln($this->lang["noautolinks"]);
        ptln('<form action="'.wl($ID).'" method="post">');
        ptln('<input type="hidden" name="do" value="admin" />');
        ptln('<input type="hidden" name="page" value="autolink2" />');
        ptln("<p><input type=\"checkbox\" name=\"delete\"> ".$this->lang['deleteold']."<br />");
        ptln("<input type=\"submit\" name=\"deleteold\" class=\"button\" value=\"".$this->lang['delete']."\" /></p>");
        ptln("</form>");
      }
    } else {
      ptln("<table class=\"inline\">");
      ptln("  <thead>");
      ptln("    <tr>");
      ptln("      <th>".$this->lang["autolink2_oldpages"]."</th></thead>");
      foreach ($replace as $value){
        $page=substr($value,3,-2);
        $lnk="[[:" . $value['id'] . "|" . $value['id'] . "]]";
        ptln("<tr><td>");
        $x=p_render('xhtml',p_get_instructions($lnk),$info);
        $x=substr($x,4,-5);
        ptln($x);
        ptln("</td></tr>");
      }
      ptln("</table>");
    }
  }

  function search(&$data,$base,$file,$type,$lvl,$opts){
    if(!preg_match('#\.txt$#',$file)) return true;;
    require_once (DOKU_INC.'inc/search.php');
    $words[]=$opts['query'];
    $reg="autolink ";
    return search_regex(&$data,$base,$file,$reg,$words);
  }
}