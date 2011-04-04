<?php
	require(dirname(__FILE__).'/../../../main.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>Test SERIA_JsonUserData</title>
		<script type='text/javascript'>
			<!--
				/* Global functions */
				var setUser;
				var setUserToSelf;
				var setPropertyList;
				var setValue;
				var deleteValue;

				/* Global variables */
				var user = false;
				var propertyList = false;

				(function () {
					/* Local functions */
					var updateUser;
					var updateTable;

					setUser = function (user_id)
					{
						user = SERIA.getUser(user_id);
						updateUser();
					}
					setUserToSelf = function ()
					{
						user = SERIA.User; /* Note this! */
						updateUser();
					}
					setPropertyList = function (namespace)
					{
						propertyList = user.getPropertyList(namespace);
						updateTable();
					}
					setValue = function ()
					{
						if (!propertyList) {
							alert('Please open a property-list first.');
							return;
						}
						propertyList.set(
							document.getElementById('setName').value,
							document.getElementById('setValue').value
						);
						updateTable();
					}
					setBatch = function ()
					{
						if (!propertyList) {
							alert('Please open a property-list first.');
							return;
						}
						var data = {
						}
						data[document.getElementById('setName1').value] = document.getElementById('setValue1').value;
						data[document.getElementById('setName2').value] = document.getElementById('setValue2').value;
						propertyList.setBatch(data);
						updateTable();
					}
					deleteValue = function (name)
					{
						propertyList.unset(name);
						updateTable();
					}

					var usertable = false;
					updateUser = function ()
					{
						if (!usertable)
							usertable = document.getElementById('usertable');
						else {
							var new_usertable = document.createElement('table');
							$(usertable).replaceWith(new_usertable);
							usertable = new_usertable;
						}
						var params = [
							'id',
							'firstName',
							'lastName',
							'displayName',
							'email'
						];
						for (n in params) {
							var key = params[n];
							var tr = document.createElement('tr');
							var th = document.createElement('th');
							var td = document.createElement('td');
							usertable.appendChild(tr);
							tr.appendChild(th);
							tr.appendChild(td);
							th.innerHTML = key;
							var input = document.createElement('input');
							td.appendChild(input);
							input.setAttribute('type', 'text');
							input.setAttribute('disabled', 'disabled');
							input.value = user.get(key);
						}
					}
					var tbody = false;
					updateTable = function ()
					{
						var values = propertyList.getAll();
						var html;

						if (!tbody)
							tbody = document.getElementById('valuetable');
						else {
							var new_tbody = document.createElement('tbody');
							$(tbody).replaceWith(new_tbody);
							tbody = new_tbody;
						}
						for (key in values) {
							var tr = (function (key) {
								var tr = document.createElement('tr');
								var td = [
									document.createElement('td'),
									document.createElement('td'),
									document.createElement('td')
								];
								tr.appendChild(td[0]);
								tr.appendChild(td[1]);
								tr.appendChild(td[2]);
								var elements = [
									document.createElement('input'),
									document.createElement('input'),
									document.createElement('button')
								];
								for (var i = 0; i < 3; i++)
									td[i].appendChild(elements[i]);
								elements[0].setAttribute('type', 'text');
								elements[0].setAttribute('disabled', 'disabled');
								elements[1].setAttribute('type', 'text');
								elements[1].setAttribute('disabled', 'disabled');
								elements[2].setAttribute('type', 'button');
								elements[0].value = key;
								elements[1].value = values[key];
								elements[2].innerHTML = 'Delete';
								elements[2].onclick = function () {
									alert('Delete: ' + key);
									deleteValue(key);
								}
								return tr;
							})(key);
							tbody.appendChild(tr);
						}
					}
				})();
			-->
		</script>
	</head>
	<body>
		<h1>Test SERIA_JsonUserData</h1>
		<div>
			<h2>User</h2>
			<input type='text' id='user_id' value='0' /><button type='button' onclick='setUser(document.getElementById("user_id").value);'>Open user</button><button type='button' onclick='setUserToSelf();'>Open logged in user</button>
			<table id='usertable'>
			</table>
		</div>
		<div>
			<h2>Property-list</h2>
			<input type='text' id='namespace' value='default' /><button type='button' onclick='setPropertyList(document.getElementById("namespace").value);'>Open namespace</button>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Value</th>
						<th>Edit</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td><input type='text' id='setName' value='' /></td>
						<td><input type='text' id='setValue' value='' /></td>
						<td><button type='button' onclick='setValue();'>Set</button></td>
					</tr>
				</tfoot>
				<tbody id='valuetable'>
				</tbody>
			</table>
			<h2>Batch set</h2>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Value</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspans='2'><button type='button' onclick='setBatch();'>Set</button></td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td><input type='text' id='setName1' value='' /></td>
						<td><input type='text' id='setValue1' value='' /></td>
					</tr>
					<tr>
						<td><input type='text' id='setName2' value='' /></td>
						<td><input type='text' id='setValue2' value='' /></td>
					</tr>
				</tbody>
			</table>
		</div>
	</body>
</html>