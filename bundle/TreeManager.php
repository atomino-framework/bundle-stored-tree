<?php namespace Atomino\Bundle\StoredTree;


use Atomino\Carbon\Store;
use function Atomino\debug;

class TreeManager {
	public function __construct(private readonly Store $store) { }

	public function move(int $id, int|null $parentId, int|null $sequence = null): bool {
		$tree = $this->getTree();
		if ($parentId !== null) {
			$parentPath = $this->getPath($parentId);
			if (in_array($id, $parentPath)) {
				return false;
			}
		}

		if (static::getParent($id) !== $parentId && $sequence !== null) $sequence++;
		if(static::getParent($id) === $parentId && $sequence < $this->getSequence($id)) $sequence++;

		$removed = $this->_remove($id, $tree, false);
		if (!is_null($removed)) {
			if ($parentId !== null) $subtree = &$this->_getSubTree($parentId, $tree);
			else $subtree = &$tree;
			$sequence = is_null($sequence) ? count($subtree) : min($sequence, count($subtree));
			array_splice($subtree, $sequence, 0, [$removed]);
		}
		$this->store->set($tree);
		return true;
	}

	public function add($id): void {
		$tree = $this->getTree();
		$tree[] = ["id" => $id, "items" => []];
		$this->store->set($tree);
	}

	public function drop(int $id): void {
		$tree = $this->getTree();
		$this->_remove($id, $tree, true);
		$this->store->set($tree);
	}

	public function getSequence($id): int|null {
		$parent = static::getParent($id);
		$items = $this->getChildren($parent);

		$index = array_search($id, $items);
		if ($index === false) return null;
		return $index;
	}

	public function getParent(int $id): int|null|false {
		$path = $this->getPath($id);
		return $path === false ? false : array_pop($path);
	}

	public function getPath(int|null $id): array|false { return $this->_getPath($id, $this->getTree(), null) ?? false; }

	public function getChildren(int|null $id, bool $recursive = false, &$subtree = null): array {
		$subtree = $this->getSubtree($id);
		if ($subtree === false) return [];
		$ids = [];
		$recursive
			? array_walk_recursive($subtree, function ($item) use (&$ids) { $ids[] = $item; })
			: array_walk($subtree, function ($item) use (&$ids) { $ids[] = $item["id"]; });
		return $ids;
	}

	public function getTree() { return $this->store->get(); }

	public function getSubTree(int|null $id): array {
		$tree = $this->getTree();
		if (is_null($id)) return $tree;
		return $this->_getSubTree($id, $tree);
	}

	private function _getPath(int $id, array $items, int|null $key = null): ?array {
		foreach ($items as $item) {
			if ($item["id"] === $id) return [$key];
			$result = $this->_getPath($id, $item["items"], $item["id"]);
			if (!is_null($result)) return [$key, ...$result];
		}
		return null;
	}

	private function &_getSubTree(int $id, array &$items): array|false {
		foreach ($items as &$item) {
			if ($item["id"] === $id) return $item["items"];
			else {
				$res = &$this->_getSubTree($id, $item["items"]);
				if ($res !== false) return $res;
			}
		}
		$res = false;
		return $res;
	}

	private function _remove(int $id, array &$items, bool $replaceWithChildren = true): null|array {
		foreach ($items as $index => &$item) {
			if ($item["id"] === $id) {
				array_splice($items, $index, 1, $replaceWithChildren ? $item["items"] : []);
				return $item;
			} elseif (count($item["items"])) {
				$result = $this->_remove($id, $item["items"], $replaceWithChildren);
				if (!is_null($result)) return $result;
			}
		}
		return null;
	}
}