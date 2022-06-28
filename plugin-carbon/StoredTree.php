<?php namespace Atomino\Carbon\Plugins\StoredTree;

use Atomino\Carbon\Generator\CodeWriter;
use Atomino\Carbon\Plugin\Plugin;

#[\Attribute(\Attribute::TARGET_CLASS)]
class StoredTree extends Plugin {
	public function __construct(public string $store) { }
	public function getTrait(): string|null { return StoredTreeTrait::class; }
	public function generate(\ReflectionClass $ENTITY, CodeWriter $codeWriter) {
		$codeWriter->addInterface(StoredTreeInterface::class);
	}
}