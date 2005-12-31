<?php
/**
* @version $Id$
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/**
 * DocumentHTML class, provides an easy interface to parse and display an html document
 *
 * @author Johan Janssens <johan@joomla.be>
 * @package Joomla
 * @subpackage JFramework
 * @since 1.1
 */

class JDocumentHTML extends JDocument
{
	/**
     * Array of published modules
     *
     * @var       array
     * @access    private
     */
	var $_jdoc_modules      = array();

	/**
     * Contains the base url
     *
     * @var     string
     * @access  private
     */
    var $_base = '';

	/**
     * Array of meta tags
     *
     * @var     array
     * @access  private
     */
    var $_metaTags = array( 'standard' => array ( 'Generator' => 'Joomla! 1.1' ) );

	 /**
     * Array of Header <link> tags
     *
     * @var     array
     * @access  private
     */
    var $_links = array();

	/**
     * Array of custom tags
     *
     * @var     string
     * @access  private
     */
    var $_custom = array();
	
	/**
	 * Class constructore
	 *
	 * @access protected
	 * @param	string	$type 		(either html or tex)
	 * @param	array	$attributes Associative array of attributes
	 */
	function __construct($attributes = array())
	{
		parent::__construct($attributes);

		if (isset($attributes['base'])) {
            $this->setBase($attributes['base']);
        }

		//set mime type
		$this->_mime = 'text/html';
		
		//define renderer sequence
		$this->_renderers = array('component' => array(), 
		                          'modules'   => array(), 
		                          'module'    => array(), 
		                          'head'      => array()
							);

		$this->_jdoc_modules =& $this->_loadModules();
	}

	 /**
     * Adds <link> tags to the head of the document
     *
     * <p>$relType defaults to 'rel' as it is the most common relation type used.
     * ('rev' refers to reverse relation, 'rel' indicates normal, forward relation.)
     * Typical tag: <link href="index.php" rel="Start"></p>
     *
     * @access   public
     * @param    string  $href       The link that is being related.
     * @param    string  $relation   Relation of link.
     * @param    string  $relType    Relation type attribute.  Either rel or rev (default: 'rel').
     * @param    array   $attributes Associative array of remaining attributes.
     * @return   void
     */
    function addHeadLink($href, $relation, $relType = 'rel', $attributes = array())
	{
        $attribs = mosHTML::_implode_assoc('=', ' ', $attribs);
        $generatedTag = "<link href=\"$href\" $relType=\"$relation\"" . $attribs;
        $this->_links[] = $generatedTag;
    }

	 /**
     * Adds a shortcut icon (favicon)
     *
     * <p>This adds a link to the icon shown in the favorites list or on
     * the left of the url in the address bar. Some browsers display
     * it on the tab, as well.</p>
     *
     * @param     string  $href        The link that is being related.
     * @param     string  $type        File type
     * @param     string  $relation    Relation of link
     * @access    public
     */
    function addFavicon($href, $type = 'image/x-icon', $relation = 'shortcut icon')
	{
        $this->_links[] = "<link href=\"$href\" rel=\"$relation\" type=\"$type\"";
    }

	/**
	 * Adds a custom html string to the head block
	 *
	 * @param string The html to add to the head
	 * @access   public
	 * @return   void
	 */

	function addCustomTag( $html )
	{
		$this->_custom[] = trim( $html );
	}

	 /**
     * Sets the document base tag
     *
     * @param   string   $url  The url used in the base tag
     * @access  public
     * @return  void
     */
    function setBase($url)
	{
        $this->_base = $url;
    }

	/**
     * Returns the document base url
     *
     * @access public
     * @return string
     */
    function getBase()
	{
        return $this->_base;
    }

	 /**
     * Sets or alters a meta tag.
     *
     * @param string  $name           Value of name or http-equiv tag
     * @param string  $content        Value of the content tag
     * @param bool    $http_equiv     META type "http-equiv" defaults to null
     * @return void
     * @access public
     */
    function setMetaData($name, $content, $http_equiv = false)
    {
        if ($content == '') {
            $this->unsetMetaData($name, $http_equiv);
        } else {
            if ($http_equiv == true) {
                $this->_metaTags['http-equiv'][$name] = $content;
            } else {
                $this->_metaTags['standard'][$name] = $content;
            }
        }
    }

	 /**
     * Unsets a meta tag.
     *
     * @param string  $name           Value of name or http-equiv tag
     * @param bool    $http_equiv     META type "http-equiv" defaults to null
     * @return void
     * @access public
     */
    function unsetMetaData($name, $http_equiv = false)
    {
        if ($http_equiv == true) {
            unset($this->_metaTags['http-equiv'][$name]);
        } else {
            unset($this->_metaTags['standard'][$name]);
        }
    }

	 /**
     * Sets an http-equiv Content-Type meta tag
     *
     * @access   public
     * @return   void
     */
    function setMetaContentType()
    {
        $this->setMetaData('Content-Type', $this->_mime . '; charset=' . $this->_charset , true );
    }
	
	/**
	 * Get module by name
	 *
	 * @access public
	 * @param string 	$name	The name of the module
	 * @return object	The Module object
	 */
	function &getModule($name) 
	{
		$result = null;

		$total = count($this->_jdoc_modules);
		for($i = 0; $i < $total; $i++) {
			if($this->_jdoc_modules[$i]->name == $name) {
				$result =& $this->_jdoc_modules[$i];
				break;
			}
		}

		return $result;
	}

	/**
	 * Get modules by position
	 *
	 * @access public
	 * @param string 	$position	The position of the module
	 * @return array	An array of module objects
	 */
	function &getModules($position)
	{
		$result = array();

		$total = count($this->_jdoc_modules);
		for($i = 0; $i < $total; $i++) {
			if($this->_jdoc_modules[$i]->position == $position) {
				$result[] =& $this->_jdoc_modules[$i];
			}
		}

		return $result;
	}

	/**
	 * Executes a component script and returns the results as a string
	 *
	 * @access public
	 * @param string 	$name		The name of the component to render
	 * @param string 	$message	A message to prepend
	 * @return string	The output of the script
	 */
	function fetchComponent($name)
	{
		global $mainframe, $my, $acl, $database;
		global $Itemid, $task, $option;
		global $mosConfig_offset;

		$gid = $my->gid;
		
		$name = !isset($name) ? $option : $name;
			
		$file = substr( $name, 4 );
		$path = JPATH_BASE.DS.'components'.DS.$name;
		
		if(JFile::exists($path.DS.$file.'.php')) {
			$path = $path.DS.$file.'.php';
		} else {
			$path = $path.DS.'admin.'.$file.'.php';
		}
		
		$task 	= mosGetParam( $_REQUEST, 'task', '' );
		$ret 	= mosMenuCheck( $Itemid, $name, $task, $my->gid );

		$content = '';
		ob_start();

		$msg = mosGetParam( $_REQUEST, 'mosmsg', '' );
		if (!empty($msg)) {
			echo "\n<div class=\"message\">$msg</div>";
		}
		
		if ($ret) {
			//load common language files
			$lang =& $mainframe->getLanguage();
			$lang->load($name);
			require_once $path;
		} else {
			mosNotAuth();
		}
		
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Executes multiple modules scripts and returns the results as a string
	 *
	 * @access public
	 * @param string 	$name	The position of the modules to render
	 * @param  string 	$style	The style to be used for the module, if not present the default style is used
	 * @return string	The output of the scripts
	 */
	function fetchModules($name, $style = null)
	{
		$contents = '';
		foreach ($this->getModules($name) as $module)  {
			$contents .= $this->fetchModule($module, $style);
		}
		return $contents;
	}

	/**
	 * Executes a single module script and returns the results as a string
	 *
	 * @access public
	 * @param  mixed 	$name	The name of the module to render or a module object
	 * @param  string 	$style	The style to be used for the module, if not present the default style is used
	 * @return string	The output of the script
	 */
	function fetchModule($module, $style = null)
	{
		global $mosConfig_live_site, $mosConfig_sitename, $mosConfig_lang, $mosConfig_absolute_path;
		global $mainframe, $database, $my, $Itemid, $acl, $task;

		//echo $module;
		
		$contents = '';

		if(!is_object($module)) {
			$module = $this->getModule($module);
		}
		
		//get module parameters
		$params = new JParameters( $module->params );
		$style  = isset($style) ? $style : $module->style;
		

		//get module path
		$path = JPATH_BASE . '/modules/'.$module->module.'.php';

		//load the module
		if (!$module->user && file_exists( $path ))
		{
			$lang =& $mainframe->getLanguage();
			$lang->load($module->module);

			ob_start();
			require $path;
			$module->content = ob_get_contents();
			ob_end_clean();
		}

		ob_start();
			if ($params->get('cache') == 1 && $mainframe->getCfg('caching') == 1) {
				$cache =& JFactory::getCache( 'com_content' );
				$cache->call('modules_html::module', $module, $params, $style );
			} else {
				modules_html::module( $module, $params, $style);
			}
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	 /**
     * Generates the head html and return the results as a string
     * 
     * @access public
     * @return string
     */
    function fetchHead()
    {
        // get line endings
        $lnEnd = $this->_getLineEnd();
        $tab = $this->_getTab();

		$tagEnd = ' />';

		$strHtml  = $tab . '<title>' . $this->getTitle() . '</title>' . $lnEnd;
		$strHtml .= $tab . '<base href=' . $this->getBase() . ' />' . $lnEnd;

        // Generate META tags
        foreach ($this->_metaTags as $type => $tag) {
            foreach ($tag as $name => $content) {
                if ($type == 'http-equiv') {
                    $strHtml .= $tab . "<meta http-equiv=\"$name\" content=\"$content\"" . $tagEnd . $lnEnd;
                } elseif ($type == 'standard') {
                    $strHtml .= $tab . "<meta name=\"$name\" content=\"$content\"" . $tagEnd . $lnEnd;
                }
            }
        }

        // Generate link declarations
        foreach ($this->_links as $link) {
            $strHtml .= $tab . $link . $tagEnd . $lnEnd;
        }

        // Generate stylesheet links
        foreach ($this->_styleSheets as $strSrc => $strAttr ) {
            $strHtml .= $tab . "<link rel=\"stylesheet\" href=\"$strSrc\" type=\"".$strAttr['mime'].'"';
            if (!is_null($strAttr['media'])){
                $strHtml .= ' media="'.$strAttr['media'].'"';
            }
            $strHtml .= $tagEnd . $lnEnd;
        }

        // Generate stylesheet declarations
        foreach ($this->_style as $styledecl) {
            foreach ($styledecl as $type => $content) {
                $strHtml .= $tab . '<style type="' . $type . '">' . $lnEnd;

                // This is for full XHTML support.
                if ($this->_mime == 'text/html' ) {
                    $strHtml .= $tab . $tab . '<!--' . $lnEnd;
                } else {
                    $strHtml .= $tab . $tab . '<![CDATA[' . $lnEnd;
                }

				$strHtml .= $content . $lnEnd;

                // See above note
                if ($this->_mime == 'text/html' ) {
                    $strHtml .= $tab . $tab . '-->' . $lnEnd;
                } else {
                    $strHtml .= $tab . $tab . ']]>' . $lnEnd;
                }
                $strHtml .= $tab . '</style>' . $lnEnd;
            }
        }

        // Generate script file links
        foreach ($this->_scripts as $strSrc => $strType) {
            $strHtml .= $tab . "<script type=\"$strType\" src=\"$strSrc\"></script>" . $lnEnd;
        }

        // Generate script declarations
        foreach ($this->_script as $script) {
            foreach ($script as $type => $content) {
                $strHtml .= $tab . '<script type="' . $type . '">' . $lnEnd;

                // This is for full XHTML support.
                if ($this->_mime == 'text/html' ) {
                    $strHtml .= $tab . $tab . '// <!--' . $lnEnd;
                } else {
                    $strHtml .= $tab . $tab . '<![CDATA[' . $lnEnd;
                }

				$strHtml .= $content . $lnEnd;

                // See above note
                if ($this->_mime == 'text/html' ) {
                    $strHtml .= $tab . $tab . '// -->' . $lnEnd;
                } else {
                    $strHtml .= $tab . $tab . '// ]]>' . $lnEnd;
                }
                $strHtml .= $tab . '</script>' . $lnEnd;
            }
        }

		foreach($this->_custom as $custom) {
			$strHtml .= $tab . $custom .$lnEnd;
		}

		ob_start();

		//load editor
		initEditor();

		$contents = ob_get_contents();
		ob_end_clean();

        return $contents.$strHtml;
    }

	/**
	 * Parse a file and create an internal patTemplate object
	 *
	 * @access public
	 * @param string 	$template	The template to look for the file
	 * @param string 	$filename	The actual filename
	 */
	function parse($template, $filename = 'index.php')
	{
		if ( !file_exists( 'templates'.DS.$template.DS.$filename) ) {
			$template = '_system';
		}
		
		parent::parse($template, $filename);
	}

	/**
	 * Execute and display a layout script.
	 *
	 * @access public
	 * @param string 	$template	The name of the template
	 * @param boolean 	$compress	If true, compress the output using Zlib compression
	 */
	function display($template, $compress = true)
	{
		foreach($this->_renderers as $type => $names) 
		{
			foreach($names as $name) 
			{
				$function = 'fetch'.$type;
				$html = $this->$function($name);
				$this->addGlobalVar($type.'_'.$name, $html);
			}
		}

		parent::display( $template, $compress );
	}

	/**
	 * Load published modules
	 *
	 * @access private
	 * @return array
	 */
	function &_loadModules() 
	{
		global $mainframe, $Itemid;
				
		$user =& $mainframe->getUser();
		$db   =& $mainframe->getDBO();
		
		$modules = array();
		
		$wheremenu = isset($Itemid)? "\n AND ( mm.menuid = '". $Itemid ."' OR mm.menuid = 0 )" : "";

		$query = "SELECT id, title, module, position, content, showtitle, params"
			. "\n FROM #__modules AS m"
			. "\n LEFT JOIN #__modules_menu AS mm ON mm.moduleid = m.id"
			. "\n WHERE m.published = 1"
			. "\n AND m.access <= '". $user->gid ."'"
			. "\n AND m.client_id = '". $mainframe->getClient() ."'"
			. $wheremenu
			. "\n ORDER BY position, ordering";

		$db->setQuery( $query );
		$modules = $db->loadObjectList();
			
		$total = count($modules);
		for($i = 0; $i < $total; $i++) {
			//determine if this is a user module
			$file = $modules[$i]->module;
			$modules[$i]->user = substr( $file, 0, 4 )  == 'mod_' ?  0 : 1;
			$modules[$i]->name = substr( $file, 4 );
		}
		
		return $modules;
	}
	
	/**
	 * Module callback function
	 * 
	 * @access protected
	 * @param string $module 	The name of the module 
	 * @param array	 $params	Array of module parameters
	 * @return string	The result to be inserted in the template
	 */
	function _moduleCallback($module, $params = array())
	{
		if($module != 'placeholder' && !isset($$params['type'])) {
			return false;
		}
		
		$type = isset($params['type']) ? strtolower( $params['type'] ) : null;
		unset($params['type']);
		
		$name = isset($params['name']) ? strtolower( $params['name'] ) : null;
		unset($params['name']);
		
		switch($type) 
		{
			case 'modules'  		:
			{
				$modules =& $this->getModules($name);
		
				$total = count($modules);
				for($i = 0; $i < $total; $i++) {
					foreach($params as $param => $value) {
						$modules[$i]->$param = $value;
					}
				}
				
				$this->_addRenderer($type, $name);
				
			} break;
			case 'module' 		:
			{
				$module =& $this->getModule($name);

				foreach($params as $param => $value) {
					$module->$param = $value;
				}
				
				$this->_addRenderer($type, $name);
			} break;
		
			default : $this->_addRenderer($type, $name);
		}
		
		return '{'.strtoupper($type).'_'.strtoupper($name).'}';
	}
}
?>