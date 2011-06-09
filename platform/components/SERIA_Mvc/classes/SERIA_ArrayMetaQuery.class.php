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

	public function __construct(array $objects)
	{
		$this->objects = array_values($objects);
		$this->keys = array_keys($objects);
	}

	protected function revertLimit()
	{
		if ($this->limitInEffect === null)
			return;
		$this->objects = $this->limitInEffect['objects'];
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
			'objects' => $this->objects,
			'keys' => $this->keys
		);
		if (count($this->objects) != count($this->keys))
			throw new SERIA_Exception('Should be equal amount of keys and objects (bad array)');
		while (count($this->objects) > 0 && $a > 0) {
			array_shift($this->objects);
			array_shift($this->keys);
			$a--;
		}
		if (count($this->objects) != count($this->keys))
			throw new SERIA_Exception('Should be equal amount of keys and objects (corrupted array)');
		if (count($this->objects) > $b) {
			$objects = $this->objects;
			$keys = $this->keys;
			$this->objects = array();
			$this->keys = array();
			while ($b > 0) {
				$this->objects[] = array_shift($objects);
				$this->keys[] = array_shift($keys);
				$b--;
			}
		}
		if (count($this->objects) != count($this->keys))
			throw new SERIA_Exception('Should be equal amount of keys and objects (corrupted array)');
	}

	public function count()
	{
		return count($this->objects);
	}

	public function where($where, $args = NULL, $shardByValue = NULL)
	{
		throw new SERIA_Exception('WHERE is not yet supported by data backend.', SERIA_Exception::NOT_IMPLEMENTED);
	}

	// ITERATOR
	// returns the row that is being pointed to at this moment
	function current()
	{
		if (isset($this->objects[$this->index]))
			return $this->objects[$this->index];
		else
			return false;
	}

	// returns offset in recordset 0, 1, 2 etc
	function key()
	{
		if (isset($this->keys[$this->index]))
			return $this->keys[$this->index];
		else
			return false;
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
	public function __construct($className, array $objects)
	{
		parent::__construct($className);
		$this->_data = new SERIA_ArrayMetaQueryDataObject($objects);
	}
	public function current()
	{
		return $this->_data->current();
	}
}
