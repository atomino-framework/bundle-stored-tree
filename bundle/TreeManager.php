<?php namespace Atomino\Bundle\StoredTree;


use Atomino\Carbon\Store;

class TreeManager {
	public function __construct(private readonly Store $store) { }

	public function move(int $id, int|null $parentId, int|null $sequence = null): bool {
		$tree = $this->getTree();
		$parentPath = $this->getPath($parentId);
		if(in_array($id, $parentPath)){ return false; }

		$removed = $this->_remove($id, $tree, false);
		if (!is_null($removed)) {
			$subtree = &$this->_getSubTree($parentId, $tree);
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

	public function getParent(int $id): int|null|false {
		$path = $this->getPath($id);
		return $path === false ? false : array_pop($path);
	}

	public function getPath(int $id): array|false { return $this->_getPath($id, $this->getTree(), null) ?? false; }

	public function getChildren(int $id, bool $recursive = false, &$subtree = null): array {
		$subtree = $this->getSubtree($id);
		if ($subtree === false) return [];
		$ids = [];
		$recursive
			? array_walk_recursive($subtree, function ($item) use (&$ids) { $ids[] = $item; })
			: array_walk($subtree, function ($item) use (&$ids) { $ids[] = $item["id"]; });
		return $ids;
	}

	public function getTree() { return $this->store->get(); }

	public function getSubTree(int $id): array {
		$tree = $this->getTree();
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
				if(!is_null($result)) return $result;
			}
		}
		return null;
	}
}