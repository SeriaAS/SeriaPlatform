<?php

	require(dirname(__FILE__).'/../../main.php');
	SERIA_Base::pageRequires('login');
	$gui = new SERIA_Gui();
	$gui->activeMenuItem('serialive');


	/**
	* Stealing JEP's building interface tools and usin'em in all the edit.php's
	*/

	function label($for, $text)
	{
		if (!$for)
			return '<label>'.$text.'</label>';
		return '<label for="'.htmlspecialchars($for).'">'.$text.'</label>';
	}

	function generateRowsForTwoColumnsForm($f, $show)
	{
		$r = array();
		foreach ($show as $field) {
			ob_start();
?>
			<tr>
				<th><?php echo $f[$field][0]; ?></th>
				<td><?php echo $f[$field][1]; ?></td>
			</tr>
<?php
			$r[$field] = ob_get_clean();
		}
		return $r;
	}

	function showTableRowsForm($f, $show)
	{
		ob_start();
		?>
		<table>
			<tbody>
		<?php
			foreach ($show as $field)
				echo $f[$field];
		?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	function showTwoColumnForm($f, $show)
	{
		return showTableRowsForm(generateRowsForTwoColumnsForm($f, $show), $show);
	}

