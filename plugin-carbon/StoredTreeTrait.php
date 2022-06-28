<?php namespace Atomino\Carbon\Plugins\StoredTree;

use Atomino\Bundle\StoredTree\TreeManager;
use Atomino\Carbon\Attributes\EventHandler;
use Atomino\Carbon\Database\Finder\Comparison;
use Atomino\Carbon\Database\Finder\Filter;
use Atomino\Carbon\Entity;
use Atomino\Carbon\Store;
use function Atomino\debug;

trait StoredTreeTrait {

	static private TreeManager|null $tree = null;

	public static function treeManager(): TreeManager {
		if (is_null(static::$tree)) static::$tree = new TreeManager(new Store(static::model()->getConnection(), StoredTree::get(static::class)->store));
		return static::$tree;
	}

	#[EventHandler(self::EVENT_ON_DELETE)]
	public function StoredTreeOnDelete(): bool {
		static::treeManager()->drop($this->id);
		return true;
	}

	#[EventHandler(self::EVENT_ON_INSERT)]
	public function StoredTreeOnInsert(): bool {
		static::treeManager()->add($this->id);
		return true;
	}

	public static function treePath(int|Entity $id): array {
		$id = is_object($id) ? $id->id : $id;
		return static::treeManager()->getPath($id);
	}

	public static function treeMove(int|Entity $id, int|Entity|null $parentId, int|null $sequence): bool {
		$id = is_object($id) ? $id->id : $id;
		if ($parentId !== null) {
			$parentId = is_object($parentId) ? $parentId->id : $parentId;
		}
		return static::treeManager()->move($id, $parentId, $sequence);
	}

	/**
	 * @param int|Entity $id
	 * @return static|null
	 */
	public static function treeParent(int|Entity $id): Entity|null {
		$id = is_object($id) ? $id->id : $id;
		static::treeManager()->getParent($id);
		return is_null($id) ? null : static::pick($id);
	}

	/**
	 * @param int|Entity $id
	 * @return static[]
	 */
	public static function treeChildren(int|null|Entity $id, Filter|null $filter = null): array {
		$id = is_object($id) ? $id->id : $id;
		$ids = static::treeManager()->getChildren($id);
		$objects = is_null($filter)
			? static::collect($ids)
			: static::search(Filter::where(Comparison::field("id", $ids))->and($filter))->collect();
		$result = [];
		foreach ($objects as $object) {
			$index = array_search($object->id, $ids);
			$result[$index] = $object;
		}
		return $result;
	}

	public static function treeFetch(int|null|Entity $id, Filter|null $filter = null): void {
		$id = is_object($id) ? $id->id : $id;
		$ids = static::treeManager()->getChildren($id, true);
		is_null($filter)
			? static::collect($ids)
			: static::search(Filter::where(Comparison::field("id", $ids))->and($filter))->collect();
	}

	public static function tree(int|null|Entity $id = null, Filter|null $filter = null, callable|null $converter = null) {
		$id = is_object($id) ? $id->id : $id;
		static::treeFetch($id, $filter);
		$subtree = self::treeManager()->getSubTree($id);
		self::_tree($subtree, $converter);
		return $subtree;
	}

	private static function _tree(&$items, callable|null $converter) {
		foreach ($items as &$item) {
			$object = static::pick($item["id"]);
			$item["object"] = is_null($converter) ? $object : $converter($object);
			self::_tree($item["items"], $converter);
		}
	}
}