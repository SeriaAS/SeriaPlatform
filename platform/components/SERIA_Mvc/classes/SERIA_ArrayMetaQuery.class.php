<?php

/**
 *
 * This file contains two classes one internal data backend and SERIA_ArrayMetaQuery
 * which can create a SERIA_MetaQuery from an array of SERIA_MetaObjects.
 * @author Jan-Espen Pettersen
 *
 */

/**
 * Data backend for the extended array-meta-query. This is not
 * intended for direct usage.
 */
class SERIA_ArrayMetaQueryDataObject implements Iterator
{
	protected $objects;
	protected $keys;
	protected $index = 0;
	protected $limitInEffect = null;

	public function __construct($objects)
	{
		$this->objects = $objects;
		$this->keys = array_keys($objects);
		if (!is_array($this->keys) && is_object($this->objects)) {
			$this->keys = array();
			$this->objects->rewind();
			while ($this->objects->valid()) {
				$this->keys[] = $this->objects->key();
				$this->objects->next();
			}
			$this->objects->rewind();
		}
	}

	protected function revertLimit()
	{
		if ($this->limitInEffect === null)
			return;
		$this->keys = $this->limitInEffect['keys'];
		$this->limitInEffect = null;
	}
	public function limit($a, $b=null)
	{
		if ($b === null) {
			$b = $a;
			$a = 0;
		}
		$this->revertLimit();
		$this->limitInEffect = array(
			'keys' => $this->keys
		);
		$jump = min(count($this->keys), $a);
		if ($jump > 0)
			$this->keys = array_slice($this->keys, $jump, $b);
		else
			$this->keys = array_slice($this->keys, 0, $b);
	}

	public function count()
	{
		return count($this->keys);
	}

	public function where($where, $args = NULL, $shardByValue = NULL)
	{
		throw new SERIA_Exception('WHERE is not yet supported by data backend.', SERIA_Exception::NOT_IMPLEMENTED);
	}

	// ITERATOR
	// returns the row that is being pointed to at this moment
	function current()
	{
		if (isset($this->objects[$this->keys[$this->index]]))
			return $this->objects[$this->keys[$this->index]];
		else
			return false;
	}

	// returns offset in recordset 0, 1, 2 etc
	function key()
	{
		return $this->index;
	}

	function next()
	{
		$this->index++;
		return $this->current();
	}

	function rewind()
	{
		$this->index = 0;
		return $this->current();
	}

	function valid()
	{
		return $this->current() ? true : false;
	}
}

/**
 *
 * SERIA_ArrayMetaQuery is an extended SERIA_MetaQuery which
 * can support an array as data backend. Pass the array to the
 * constructor. Returns objects in the same order as in the array
 * if not told to sort (not supportet yet).
 * @author Jan-Espen Pettersen
 *
 */
class SERIA_ArrayMetaQuery extends SERIA_MetaQuery
{
	/**
	 *
	 * Construct a SERIA_ArrayMetaQuery, a SERIA_MetaQuery with
	 * an array of objects.
	 * @param string $className The class of the objects. Must be descendant of SERIA_MetaObject
	 * @param array $objects Array of objects of the specified type.
	 */
	public function __construct($className, $objects)
	{
		if (!is_array($objects) && !is_object($objects) && !(($object instanceof ArrayAccess) && ($object instanceof Countable) && ($object instanceof Iterator))) {
			throw new SERIA_Exception('Argument $objects is not an array!');
		}
		parent::__construct($className);
		$this->_data = new SERIA_ArrayMetaQueryDataObject($objects);
	}
	public function current()
	{
		return $this->_data->current();
	}
}
