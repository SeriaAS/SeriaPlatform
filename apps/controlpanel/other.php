<?php
	/**
	*	Seria TV Welcome page
	*/
	require_once(dirname(__FILE__)."/common.php");
	$gui->activeMenuItem('controlpanel/other');
        SERIA_Base::pageRequires("admin");
        SERIA_Base::viewMode("admin");

	$contents = 
"<h1 class='legend'>"._t("Other")."</h1>
<p>"._t("Site wide configuration.")."</p>";

$topCategories = $gui->getMenuItemsLevel(2);

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

	$gui->contents($contents);
	
	echo $gui->output();
	
	
