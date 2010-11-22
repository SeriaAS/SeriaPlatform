<?php
	/**
	*	This class gives you a DOM-like interface to work with hierarchial database structures where each row has a reference to
	*	a parent row or where the reference is NULL.
	*/
	abstract class SERIA_HierarchyAccess
	{
		protected $table, $idField, $parentIdField, $posField;
		protected $row;
		protected $id = false;
		protected $parent;
		protected $extraWhere = false;

		function __construct($table, $idField, $parentIdField, $posField=false, $id=false, $parent=false)
		{
			$this->table = $table;
			$this->idField = $idField;
			$this->parentIdField = $parentIdField;
			$this->posField = $posField;
			$this->parent = $parent;
			$this->db = SERIA_Base::db();
			
			// !== false || null
			if($id!==false && $id !== NULL)
			{
				if(is_array($id))
				{
					$this->row = $id;
				}
				else
				{
					$query = "SELECT * FROM ".$this->table." WHERE (".$this->idField."=".intval($id).")".$this->extraWhere;
					$this->row = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
					if(!$this->row)
						throw new SERIA_Exception("id ".$id." not found.");
				}

				$this->id = $this->row[$this->idField];
			}
		}

		function where($extraWhere="")
		{
			$this->extraWhere = " AND ".$extraWhere;
		}

		function isRoot()
		{
			return $this->id===false || is_null($this->id);
		}

		function notForRoot()
		{
			if(!$this->id)
				throw new SERIA_Exception("This method can not be called for the root node.");
		}

		function getId()
		{
			return $this->id;
		}

		function is($node)
		{
			if($this->id===false) // this is the root, and it can only be identical to root
			{
				if($node===false)
					return true;
				else if($node->getId()===false)
					return true;
				return false;
			}
			if($node===false) // comparing to root, and since this is an object - this is not root
				return false;
			return $this->id == $node->getId();
		}

		/**
		*	Move to a different location
		*	
		*	@param $parentId	If $parentId is false, move to root, else make child of $parentId
		*	@param $pos		If $pos = "first" then the node will be placed first under $parentId
		*				If $pos = "last" then the node will be placed last under $parentId
		*				If $pos = false then the node will be placed last under $parentId
		*/
		function relocate($parent=false,$pos=false)
		{
			if($this->id===false)
				throw new SERIA_Exception("Can't relocate the root.");

			if($parent->getId()===false) // moving to the root level
				$parent = false;

			if($parent && $parent->isChildOf($this))
				throw new SERIA_Exception("Can't move this node into a child node of this.");

			if($pos!==false && !$this->posField)
				throw new SERIA_Exception("Requires \$posField to set \$pos.");

			$currentParentDBWhere = ($this->getParent()->getId()) ? $this->parentIdField."=".$this->getParent()->getId() : "(".$this->parentIdField."=0 OR ".$this->parentIdField." IS NULL)";
			$parentDBWhere = ($parent && $parent->getId()) ? $this->parentIdField."=".$parent->getId() : "(".$this->parentIdField." = 0 OR " . $this->parentIdField." IS NULL)";

			if($this->posField)
			{ // algorithm that cares about $pos
				// temporary change current pos (to avoid problems with unique indexes)
				$this->db->query("UPDATE ".$this->table." SET ".$this->posField."=-1 WHERE (id=".$this->getId().")".$this->extraWhere);

				// decide new value for $pos
				if($pos===false || $pos=="last")
				{
					if($parent===false || !$parent->getId())
					{ // new location will be root node
						$newPos = 1 + $this->db->query("SELECT MAX(".$this->posField.") FROM ".$this->table." WHERE (".$this->parentIdField." IS NULL OR ".$this->parentIdField."=0)".$this->extraWhere)->fetch(PDO::FETCH_COLUMN, 0);
					}
					else
					{
						$newPos = 1 + $this->db->query("SELECT MAX(".$this->posField.") FROM ".$this->table." WHERE (".$this->parentIdField."=".$parent->getId().")".$this->extraWhere)->fetch(PDO::FETCH_COLUMN, 0);
					}
				}
				else if($pos=="first")
				{
					$newPos = 1;
				}
				else
					$newPos = $pos;

				// if target and source location is the same, there is a special case
				if($this->getParent() && $this->getParent()->is($parent))
				{ // same parent
					if($newPos == $this->getOffset())
					{ // not moving anything
						return true;
					}
					else if($newPos == $this->getOffset()+1 && ($pos=="last" || $pos===false))
					{ // this is the last node, and we want to move it to "last"
						return true;
					}
					else if($newPos == 1 && $this->getOffset() == 1 && $pos=="first")
					{ // this already is the first node at this location
						return true;
					}
					else if($newPos > $this->getOffset())
					{ // moving down
						$newPos--; // $newPos decreases because we do not change location
						$this->db->exec("UPDATE ".$this->table." SET ".$this->posField."=".$this->posField."-1 WHERE 
							(".$parentDBWhere." AND 
							".$this->posField.">".$this->getOffset()." AND 
							".$this->posField."<=".$newPos.")".$this->extraWhere);
					}
					else
					{ // move up
						$this->db->exec("UPDATE ".$this->table." SET ".$this->posField."=".$this->posField."+1 WHERE
							(".$parentDBWhere." AND 
							".$this->posField.">=".$newPos." AND
							".$this->posField."<".$this->getOffset().")".$this->extraWhere);
					}
				}
				else
				{ // moving into a new parent
					// make space at target location
					$query = "UPDATE ".$this->table." SET ".$this->posField."=".$this->posField."+1 WHERE (".$parentDBWhere." AND ".$this->posField.">".$newPos.")".$this->extraWhere;
					$this->db->exec($query);

					// move nodes at source location
					$this->db->exec("UPDATE ".$this->table." SET ".$this->posField."=".$this->posField."-1 WHERE (".$currentParentDBWhere." AND ".$this->posField.">".$this->row[$this->posField].")".$this->extraWhere);
				}


				// relocate this node
				if($parent && $parent->getId())
					$this->db->exec("UPDATE ".$this->table." SET ".$this->parentIdField."=".$parent->getId().", ".$this->posField."=".$newPos." WHERE (id=".$this->getId().")".$this->extraWhere);
				else
					$this->db->exec("UPDATE ".$this->table." SET ".$this->parentIdField."=NULL, ".$this->posField."=".$newPos." WHERE id=".$this->getId().$this->extraWhere);

				$this->row[$this->posField] = $newPos;
				if($parent)
				{
					$this->row[$this->parentIdField] = $parent->getId();
					$this->parent = $parent;
				}
				else
				{
					$this->row[$this->parentIdField] = false;
					$this->parent = false;
				}
			}
			else
			{ // algorithm that ignores $pos
				if($parent)
				{
					$sql = "UPDATE ".$this->table." SET ".$this->parentIdField."=".$parentId;
					$this->parent = $parent;
				}
				else
				{
					$sql = "UPDATE ".$this->table." SET ".$this->parentIdField."=NULL";
					$this->parent = false;
				}

				$sql .= " WHERE (".$this->idField."=".$this->row["id"].")".$this->extraWhere;

				if($parent)
					$this->row[$this->parentIdField] = $parent->get($this->parentIdField);
				else
					$this->row[$this->parentIdField] = false;

				$this->db->exec($sql);
			}
		}

		/**
		*	Check if this node is a child of $node
		*/
		function isChildOf($node)
		{
			if(!$this->id)
				throw new SERIA_Exception("The root can't be a child of any node.");

			$current = $this;

			$continue = true;
			while(!$current->is($node) && $continue) {
				if (!$current->isRoot()) {
					$current = $current->getParent();
				} else {
					$continue = false;
				}
			}

			if($current->getId())
				return true;
			else
				return false;
		}

		/**
		*	Make $node a child of $this
		*/
		function appendChild($node)
		{
			$node->relocate($this);
		}

		/**
		*	Place $this at the location where $node currently is
		*/
		function insertAt($node)
		{
			$this->relocate($node->getParent(), $node->getOffset());
		}

		abstract static function getRoot();

		function getParent()
		{
			if($this->parent)
				return $this->parent;

			if(!$this->id) // this is a special root node
				throw new SERIA_Exception("Root nodes does not have parents.");

			return $this->parent = $this->getNodeById($this->row[$this->parentIdField]);
		}

		function getNextSibling()
		{
			if($this->id === false)
				throw new SERIA_Exception("The root does not have siblings.");

			if($this->posField)
				throw new SERIA_Exception("Requires \$posField to get siblings.");

			if(!$this->id) // this is a special root node
				throw new SERIA_Exception("The root node does not have siblings.");

			if($this->getParent())
			{ // this is not a root node
				$sql = "SELECT * FROM ".$this->table." WHERE 
					(".$this->parentIdField."=".$this->row[$this->parentIdField]." AND
					".$this->posField.">".$this->row[$this->posField]." ORDER BY
					".$this->posField.")".$this->extraWhere." LIMIT 1";
				$row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

				return $this->getNodeByRow($row);
			}
			else
			{ // this is a root node
				$sql = "SELECT * FROM ".$this->table." WHERE 
					((".$this->parentIdField." IS NULL OR ".$this->parentIdField."=0) AND
					".$this->posField.">".$this->row[$this->posField]." ORDER BY
					".$this->posField.")".$this->extraWhere." LIMIT 1";
				$row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

				return $this->getNodeByRow($row);
			}
		}

		function getPreviousSibling()
		{
			if($this->id === false)
				throw new SERIA_Exception("The root does not have siblings.");

			if($this->posField)
				throw new SERIA_Exception("Requires \$posField to get siblings.");

			if(!$this->id) // this is a special root node
				throw new SERIA_Exception("The root node does not have siblings.");

			if($this->getParent())
			{ // this is not a root node
				$sql = "SELECT * FROM ".$this->table." WHERE 
					(".$this->parentIdField."=".$this->row[$this->parentIdField]." AND
					".$this->posField."<".$this->row[$this->posField].")".$this->extraWhere." ORDER BY
					".$this->posField." DESC LIMIT 1";
				$row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

				return $this->getNodeByRow($row);
			}
			else
			{ // this is a root node
				$sql = "SELECT * FROM ".$this->table." WHERE 
					((".$this->parentIdField." IS NULL OR ".$this->parentIdField."=0) AND
					".$this->posField."<".$this->row[$this->posField].")".$this->extraWhere." ORDER BY
					".$this->posField." DESC LIMIT 1";
				$row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

				return $this->getNodeByRow($row);
			}
		}

		function getChildren()
		{
			$res = array();
			if($this->id)
				$sql = "SELECT * FROM ".$this->table." WHERE (".$this->parentIdField."=".$this->db->quote($this->id).")".$this->extraWhere;
			else
				$sql = "SELECT * FROM ".$this->table." WHERE (".$this->parentIdField." IS NULL OR ".$this->parentIdField."=0)".$this->extraWhere;

			if($this->posField)
				$sql .= " ORDER BY ".$this->posField;

			$rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

			foreach($rows as $row)
				$res[] = $this->getNodeByRow($row, $this);

			return $res;
		}

		function getSiblings()
		{
			if($this->id === false)
				throw new SERIA_Exception("The root does not have siblings.");

			$res = array();
			if($parent = $this->getParent())
			{
				return $parent->getChildren();
			}
			else
			{ // this is a root node
				$res = array();
				$sql = "SELECT * FROM ".$this->table." WHERE
					(".$this->parentIdField." IS NULL OR ".$this->parentIdField."=0)".$this->extraWhere;

				if($this->posField)
					$sql .= " ORDER BY ".$this->posField;

				$rows = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);

				foreach($rows as $row)
					$res[] = $this->getNodeByRow($row);

				return $res;
			}
		}

		function getOffset()
		{
			if(!$this->id)
				throw new SERIA_Exception("The root does not have an offset.");

			return $this->row[$this->posField];
		}

		abstract static function getNodeById($id, $parent = false);
/*		{

			return new SERIA_HierarchyAccess($this->table, $this->idField, $this->parentIdField, $this->posField, $id, $parent);
		}
*/
		abstract static function getNodeByRow($row, $parent = false);
/*
		{
			return new SERIA_HierarchyAccess($this->table, $this->idField, $this->parentIdField, $this->posField, $row, $parent);
		}
*/
		function get($field)
		{
			if(!$this->id)
			{
//				throw new SERIA_Exception("The root does not have any values.");
			}

			if(!array_key_exists($field, $this->row))
				throw new SERIA_Exception("There is no field called '$field'.");

			return $this->row[$field];
		}

		function set($field, $value)
		{
			throw new SERIA_Exception("You can't update values on nodes from SERIA_HierarchyAccess. Please use a proper interface to the database.");
		}
	}
