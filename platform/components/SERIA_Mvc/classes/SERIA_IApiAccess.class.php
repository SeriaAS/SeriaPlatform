<?php
	/**
	*	Interface that declares if a class can be made available trough various APIs, such as a REST api.
	*
	*	EXTREME CAUTION!
	*	************************************************************************************************************************
	*	It is very important that you perform access checks on every implemented feature. Also you should check the current
	*	viewMode, so that you do not make unpublished elements available.
	*	Implementing this API means that your data is available to the public trough APIs that you may not have control over.
	*	Future updates to the Seria Platform may make your data available in unspecified ways.
	*	Normal access check routines apply: if no user is authenticated, you must ensure that all data you return is publicly
	*	available.
	*	************************************************************************************************************************
	*
	*	$options are key-value pairs that you may support to provide faster or better views of the data, for example
	*	the getCollectionApi may support an option "givenName=frode" and return a subset of the entire collection where
	*	the field givenName equals frode. It is up to the developer to support options, but we recommend supporting the
	*	following options for every indexed field:
	*
	*	"order=<fieldname>", results are sorted by the specified fieldname
	*	"descending=true", results are ordered descending
	*
	*	To support range queries, the following convention have been defined (still it is up to the developer to support
	*	them):
	*
	*	"givenNameLow=first_match", (where givenName >= 'first_match')
	*	"givenNameHigh=last_match", (where givenName <= 'last_match')
	*
	*	Queries not matching convention:
	*
	*	"givenNameNot=frode", (where givenName <> 'frode')
	*
	*	All options are combined with AND. Supporting OR may have scalability issues, and it is generally recommended that
	*	OR is performed using multiple queries in a clever manner. Google App engine supports OR, but behind the scenes it
	*	converts OR into multiple queries and union the results.
	*/
	interface SERIA_IApiAccess {

		/**
		*	Methods related to collections of data
		*/
		// public static function getCollectionApi($view='default',$start=0, $length=1000, $options=NULL); // returns an array with $length items representing table rows, starting at offset $start. $options is an associative array, possibly sent trough the request query string
		// public static function putCollectionApi($values, $options=NULL); // overwrite entire collection, return true or false
		// public static functino postCollectionApi($values, $options); // insert a new element to the collection, return the new primary key or throw an exception
		// public static function deleteCollectionApi($options=NULL); // delete the entire collection, return true or throw an exception

		/**
		*	Methods related to a specified element belonging to a collection
		*/
		// public static function getElementApi($key, $options=NULL) // returns an array of key=>value pairs
		// public static function putElementApi($key, $values, $options=NULL) // overwrite or create element, return true or false
		// public static function deleteElementApi($key, $options=NULL) // delete an element, return true or throw an exception
	}

