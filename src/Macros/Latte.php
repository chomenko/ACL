<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Macros;

use Chomenko\ACL\ACL;
use Latte\Compiler;
use Latte\Engine;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;

class Latte extends MacroSet
{

	/**
	 * @var ACL
	 */
	private $acl;

	/**
	 * @param Compiler $compiler
	 * @param ACL $acl
	 */
	public function __construct(Compiler $compiler, ACL $acl)
	{
		parent::__construct($compiler);
		$this->acl = $acl;
	}

	/**
	 * @param Engine $engine
	 * @param ACL $acl
	 * @return static
	 */
	public static function install(Engine $engine, ACL $acl)
	{
		$engine->addProvider("acl", $acl);
		$me = new static($engine->getCompiler(), $acl);
		$me->addMacro('isLinkAccessed', NULL, [$me, 'isLinkAccessed']);
		$me->addMacro('isClassAccessed', NULL, [$me, 'isClassAccessed']);
		return $me;
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return MacroNode
	 * @throws \Latte\CompileException
	 */
	public function isLinkAccessed(MacroNode $node, PhpWriter $writer)
	{
		$dest = trim((string)$node->args, '\'"');
		$content = '<?php if ($this->getEngine()->getProviders()["acl"]->isLinkAccessed("' . $dest . '")) { ?>';
		$content .= $node->content;
		$content .= '<?php } ?>';
		$node->content = $content;
		return $node;
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return MacroNode
	 * @throws \Latte\CompileException
	 */
	public function isClassAccessed(MacroNode $node, PhpWriter $writer)
	{
		$class = trim((string)$node->args, '\'"');
		$content = '<?php if ($this->getEngine()->getProviders()["acl"]->isClassAccessed("' . $class . '")) { ?>';
		$content .= $node->content;
		$content .= '<?php } ?>';
		$node->content = $content;
		return $node;
	}

}
