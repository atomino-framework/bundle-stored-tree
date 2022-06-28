<?php

namespace Atomino\Carbon\Plugins\StoredTree;

use Atomino\Bundle\StoredTree\TreeManager;
use Atomino\Carbon\Database\Finder\Filter;
use Atomino\Carbon\Entity;

interface StoredTreeInterface {
	public static function treeManager(): TreeManager;
	public static function treePath(int|Entity $id): array;
	public static function treeMove(int|Entity $id, int|Entity|null $parentId, int|null $sequence): bool;

	/**
	 * @param int|Entity $id
	 * @return static|null
	 */
	public static function treeParent(int|Entity $id): Entity|null;

	/**
	 * @param int|Entity $id
	 * @return static[]
	 */
	public static function treeChildren(int|null|Entity $id, Filter|null $filter = null): array;
	public static function treeFetch(int|null|Entity $id, Filter|null $filter = null): void;
	public static function tree(int|null|Entity $id = null, Filter|null $filter = null, callable|null $converter = null);

}