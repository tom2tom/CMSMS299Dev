<?php
/*
Simple tree-manager interface class
Copyright (C) 2010-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\HierarchyManager;

/**
 * A class for interacting with the singleton pages-tree
 * hierarchy manager.
 *
 * @package CMS
 *
 * @since 3.0
 * @since 1.9 as global-namespace cms_tree
 */
class Tree
{
	/**
	 * @var object HierarchyManager singleton
	 * @ignore
	 */
	private $manager;

	/**
	 * @var int identifier of this node
	 * @ignore
	 */
	public $id;

	/**
	 * @param int $id identifier of this node
	 * @param HierarchyManager $mgr the HierarchyManager-class singleton
	 */
	#[\ReturnTypeWillChange]
	public function __construct(int $id, HierarchyManager $mgr)
	{
		$this->manager = $mgr;
		$this->id = $id;
	}

	/**
	 * Use the corresponding HierarchyManager-class method if not defined here
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function __call($name, $args)
	{
		$args[] = $this->id;
		return $this->manager->$name(...$args);
//		return $this->manager->$name(...$args, $this->id); PHP 7.4+
	}

	/**
	 * Return the identifier of this node.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return the parent node (if any) of this one.
	 * @deprecated since 2.0 use Tree::get_parent()
	 *
	 * @return mixed parent-node Tree | null
	 */
	public function getParent()
	{
		return $this->manager->get_parent($this->id);
	}

	//the following cannot be auto-re-routed as they might have any number of unprovided arguments

	/**
	 * Remove this node from the tree, optionally along with all its descendants
	 * @see also HierarchyManager::remove_node()
	 */
	public function remove_node(bool $with_descends = false)
	{
		$this->manager->remove_node($with_descends, $this->id);
	}

	/**
	 * Return the content object for the page associated with this node
	 * @see also HierarchyManager::getContent()
	 */
	public function getContent(bool $deep = false, bool $loadsiblings = true, bool $loadall = false)
	{
		return $this->manager->getContent($deep, $loadsiblings, $loadall, $this->id);
	}

	/**
	 * Get the children of this node, without loading content
	 * @see also HierarchyManager::get_children()
	 */
	public function &get_children(bool $as_node = true)
	{
		return $this->manager->get_children($as_node, $this->id);
	}

	/**
	 * Get the children of this node, loading their respective
	 * content objects as part of the process
	 * @see also HierarchyManager::()
	 */
	public function &getChildren(bool $deep = false, bool $all = false, bool $loadcontent = true, bool $as_node = true) : array
	{
		return $this->manager->getChildren($deep, $all, $loadcontent, $as_node, $this->id);
	}
}
//if (!\class_exists('cms_tree', false)) \class_alias(Tree::class, 'cms_tree', false);
//if (!\class_exists('cms_content_tree', false)) \class_alias(Tree::class, 'cms_content_tree', false);
