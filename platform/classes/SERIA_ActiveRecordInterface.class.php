<?php
  class SERIA_ActiveRecordInterface {
  	protected static function getInstanceOf($model) {
  		return new $model();
  	}
  }
?>