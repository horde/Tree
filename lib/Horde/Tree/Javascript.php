<?php
/**
 * The Horde_Tree_Javascript:: class extends the Horde_Tree class to provide
 * javascript specific rendering functions.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @package  Horde_Tree
 */
class Horde_Tree_Javascript extends Horde_Tree
{
    /**
     * Constructor.
     *
     * @param string $name   @see Horde_Tree::__construct().
     * @param array $params  @see Horde_Tree::__construct().
     */
    public function __construct($tree_name, $params = array())
    {
        parent::__construct($tree_name, $params);

        /* Check for a javascript session state. */
        if ($this->_usesession &&
            isset($_COOKIE[$this->_instance . '_expanded'])) {
            /* Remove "exp" prefix from cookie value. */
            $nodes = explode(',', substr($_COOKIE[$this->_instance . '_expanded'], 3));

            /* Make sure there are no previous nodes stored in the
             * session. */
            $_SESSION['horde_tree'][$this->_instance]['expanded'] = array();

            /* Save nodes to the session. */
            foreach ($nodes as $id) {
                $_SESSION['horde_tree'][$this->_instance]['expanded'][$id] = true;
            }
        }

        Horde::addScriptFile('prototype.js', 'horde');
        Horde::addScriptFile('hordetree.js', 'horde');
        if (!empty($this->_options['alternate'])) {
            Horde::addScriptFile('stripe.js', 'horde');
        }
    }

    /**
     * Returns the tree.
     *
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $this->_static = $static;

        $opts = array(
            'extraColsLeft' => $this->_extra_cols_left,
            'extraColsRight' => $this->_extra_cols_right,
            'header' => $this->_header,
            'options' => $this->_options,
            'target' => $this->_instance,

            'cookieDomain' => $GLOBALS['conf']['cookie']['domain'],
            'cookiePath' => $GLOBALS['conf']['cookie']['path'],

            'scrollbar_in_way' => $GLOBALS['browser']->hasQuirk('scrollbar_in_way'),

            'imgDir' => $this->_img_dir,
            'imgBlank' => $this->_images['blank'],
            'imgFolder' => $this->_images['folder'],
            'imgFolderOpen' => $this->_images['folderopen'],
            'imgLine' => $this->_images['line'],
            'imgJoin' => $this->_images['join'],
            'imgJoinBottom' => $this->_images['join_bottom'],
            'imgPlus' => $this->_images['plus'],
            'imgPlusBottom' => $this->_images['plus_bottom'],
            'imgPlusOnly' => $this->_images['plus_only'],
            'imgMinus' => $this->_images['minus'],
            'imgMinusBottom' => $this->_images['minus_bottom'],
            'imgMinusOnly' => $this->_images['minus_only'],
            'imgNullOnly' => $this->_images['null_only'],
            'imgLeaf' => $this->_images['leaf'],

            'floatDir' => empty($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'float:left;' : 'float:right'
        );

        Horde::addInlineScript(array(
            $this->_instance . ' = new Horde_Tree(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ')',
            $this->renderNodeDefinitions()
        ), 'dom');

        return '<div id="' . $this->_instance . '"></div>';
    }

    /**
     * Check the current environment to see if we can render the HTML tree.
     * We check for DOM support in the browser.
     *
     * @return boolean  Whether or not this backend will function.
     */
    public function isSupported()
    {
        $browser = Horde_Browser::singleton();
        return $browser->hasFeature('dom');
    }

    /**
     * Returns just the JS node definitions as a string.
     *
     * @return string  The Javascript node array definitions.
     */
    public function renderNodeDefinitions()
    {
        $this->_buildIndents($this->_root_nodes);

        return $this->_instance . '.renderTree(' . Horde_Serialize::serialize($this->_nodes, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ',' . Horde_Serialize::serialize($this->_root_nodes, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ',' . ($this->_static ? 'true' : 'false') . ')';
    }

}
