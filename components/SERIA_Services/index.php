<?php
	/**
	*	The purpose of this page is to provide a common place for service APIs to be available for the user.
	*/
	require('common.php');
	$gui->activeMenuItem('controlpanel/settings/services');

	$contents = '<h1 class="legend">'._t("Seria Platform Services").'</h1>';

	$topCategories = $gui->getMenuItemsLevel(3);

	if(!$topCategories)
	{
		$contents .= "<p><em>"._t("No Service Components installed.")."</em></p>";
	}
	else
	{
		$contents .= '<p>'._t("From here you configure various services to integrate with your website.").'</p>';
		$contents .= "<div>";
		$columns = array('','');
		$numColumns = 2;
		$count = 0;
		foreach($topCategories as $id => $info)
		{
			$columns[$count % $numColumns] .= '<table style="width:100%;border-collapse:collapse;">
	<tr>
		<td style="width:64px;height:64px;vertical-align:center;text-align:center;">
			<img src="'.$info['icon'].'">
		</td><td>
			<h2><a href="'.$info['url'].'">'.$info['title'].'</a></h2>';

			$buttons = $gui->getMenuItems($id);

			if($buttons && sizeof($buttons)>0)
			{
				$columns[$count % $numColumns] .= "<ul>";

				foreach($buttons as $button)
				{
					$columns[$count % $numColumns] .= '<li><a href="'.$button['url'].'">'.htmlspecialchars($button['title']).'</a></li>';
				}

				$columns[$count % $numColumns] .= "</ul>";
			}
			else
				$columns[$count % $numColumns] .= "<ul><li><em>"._t("No alternatives here...")."</em></li></ul>";

			$columns[$count % $numColumns] .= '
		</td>
	</tr>
</table>';
			$count++;
		}

		$contents .= '<table style="border-collapse:collapse;width:100%"><tr><td>'.implode('</td><td>', $columns).'</td></tr></table>';
		$contents .= "</div>";
	}

	$gui->contents($contents);
	$gui->output();
