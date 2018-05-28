<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */
require_once "Snep/Locale.php";

/**
 * Snep main menu system
 *
 * @category  Snep
 * @package   Snep_Bootstrap
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class Snep_Menu {

    protected static $master;

    /**
     * Master instance of Snep_Menu
     * @return Snep_Menu
     */
    public static function getMasterInstance() {
        if (self::$master === null) {
            self::$master = new self("master");
        }

        return self::$master;
    }

    /**
     * Menu or resource id.
     * @var <string> id
     */
    private $id;

    /**
     * Sub-menus of this menu.
     * @var Snep_Menu[]
     */
    private $children = array();

    /**
     * @var string Label to show.
     */
    private $label;

    /**
     * @var string uri for the resource
     */
    private $uri;

    /**
     * @var string font for the resource
     */
    private $font;

    /**
     * Base path for menu links
     * @var <string>
     */
    protected $baseUrl = "";

    /**
     * __construct
     * @param <string> $id
     */
    public function __construct($id) {
        $this->id = $id;
    }

    /**
     * __toString
     * @return <string>
     */
    public function __toString() {
        return $this->render();
    }

    /**
     * getBaseUrl
     * @return type
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * setBaseUrl
     * @param <string> $baseUrl
     */
    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    /**
     * getUri
     * @return type
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * setUri
     * @param <string> $uri
     */
    public function setUri($uri) {
        $this->uri = $uri;
    }

    /**
     * getFont
     * @return type
     */
    public function getFont() {
        return $this->font;
    }

    /**
     * setFont
     * @param <string> $font
     */
    public function setFont($font) {
        $this->font = $font;
    }

    /**
     * addChild - Add a child
     * @param Snep_Menu $child
     */
    public function addChild(Snep_Menu $child) {

        $item = $this->getChildById($child->getId());
        if ($item) {
            $item->setSubmenu(array_merge($item->getSubmenu(), $child->getSubmenu()));
        } else {
            $this->children[] = $child;
        }
    }

    /**
     * getChildById - Finds a child with the desidered id
     * @param <string> $id
     * @return Snep_Menu|null
     */
    public function getChildById($id) {
        foreach ($this->getChildren() as $child) {
            if ($child->getId() === $id) {
                return $child;
            }
        }
        return null;
    }

    /**
     * getChildren - Returns all the children of this menu
     * @return Snep_Menu[]
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * setChildren - Defines the children of this menu
     * @param Snep_Menu_Item[] $children
     */
    public function setChildren($children) {
        $this->children = $children;
    }

    /**
     * getId
     * @return string menu id
     */
    public function getId() {

        return $this->id;
    }

    /**
     * setId
     * @param string $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * renderChildren - Render all the children of this menu
     * @return <string> HTML rendered children
     */
    public function renderChildren() {

        $html = "";
        foreach ($this->getChildren() as $child) {
            if (substr($child->id, 0, 7) == 'default') {
                $html .= $child->render();
            }
        }

        return $html;
    }

    /**
     * renderChildren - Render all the children of this menu
     * @return <string> HTML rendered children
     */
    public function renderChildrenModule() {
        $html = "";

        foreach ($this->getChildren() as $child) {

            if (substr($child->id, 0, 7) != 'default') {
                $html .= $child->renderModule();
            }
        }
        return $html;
    }

    /**
     * getLabel
     * @return type
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * setLabel
     * @param <string> $label
     */
    public function setLabel($label) {

        $this->label = Snep_Locale::getInstance()->getZendTranslate()->translate($label);
        ;
    }

    public function getPermission($uri){
        
        $link = explode("/", $uri); // /snep/index.php/default/ip-status
        $count = count($link); // [0] = "", [1] = snep, [2] = index.php, [3] = default, [4] = ip-status
        if($count > 4){ // 5

            $group = Snep_Profiles_Manager::getIdProfile($_SESSION['id_user']);
            $result = Snep_Permission_Manager::get($group, "$link[3]_". $link[4].'_read');
            $user = Snep_Permission_Manager::getUser($_SESSION['id_user'], "$link[3]_". $link[4]. '_read');

            // Verifica se usuario possui permissao individuais
            if ($user != false) {
                $result = $user;
            }
                    
            if (!$result) {
                return false;
            } elseif (!$result['allow']) {
                return false;
            }
            return true;
        }
        return true;

    }

    /**
     * render - Render the menu and its children
     * @return <string> HTML rendered menu
     */
    public function render() {

        $html = "";
        // premission
        if ($_SESSION['id_user'] != "1"){
            $permission = $this->getPermission($this->getUri());
        }else{
            $permission = true;
        }
        
        if($permission){
            $html = "<li id=\"{$this->getId()}\">";
            $font = $this->getFont();

            if ($font == 'sn-dashboard') {
                $html .= "<a href='/snep/index.php/default/index'><i class='sn-dashboard fa-fw'></i><span class='side-menu-title'>Dashboard</span></a>";
            } else {

                if (count(explode("_", $html)) == 2) {
                    $html .= "<a href=\"{$this->getUri()}\" class='dropdown-collapse'><i class='$font fa-fw'></i><span class='side-menu-title'> " . $this->getLabel() . "</span><span class='fa arrow'></span></a>";
                } else {
                    $html .= "<a href=\"{$this->getUri()}\"><i class='$font fa-fw'></i>" . " " . $this->getLabel() . "</a>";
                }

                if (count($this->getChildren()) > 0) {
                    $html .= "<ul id=\"sub-{$this->getId()}\" class=\"nav nav-second-level\">";
                    $html .= $this->renderChildren();
                    $html .= "</ul>";
                }
            }

            $html .= "</li>";

        }
                
        return $html;

    }

    /**
     * render - Render the menu and its children
     * @return <string> HTML rendered menu
     */
    public function renderModule() {

        $html = "";
        // premission
        if ($_SESSION['id_user'] != "1"){
            $permission = $this->getPermission($this->getUri());
        }else{
            $permission = true;
        }

        if($permission){

            $html = "<li id=\"{$this->getId()}\">";
            $font = $this->getFont();

            if(count(explode("_", $html)) == 2){
                $html .= "<a  class='dropdown-collapse' href=\"{$this->getUri()}\">" . "<i class='$font fa-fw'></i> " . $this->getLabel() . "<span class='fa arrow'></span></a>";
            }else{
                $html .= "<a href=\"{$this->getUri()}\">" . $this->getLabel() . "</a>";
            }
            if (count($this->getChildren()) > 0) {
                $html .= "<ul class='nav nav-third-level'>";
                $html .= $this->renderChildrenModule();
                $html .= "</ul>";
            }

            $html .= "</li>";

        }

        return $html;
    }

}
