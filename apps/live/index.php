<?php
	require_once("../../main.php");
	require_once("common.php");
	$gui->activeMenuItem('live/front');

	$gui->contents("Deprecated");
	echo $gui->output();	
	

/*

	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/apps/live/index.css');
	$contents = '<h1 class="legend">'._t('Welcome to Seria Live').'</h1>';

	$aq = new SERIA_ArticleQuery();

	$aq->addSQL('event_date>'.SERIA_Base::db()->quote(date("Y-m-d 00:00:00")));
	$aq->addSQL('event_date<'.SERIA_Base::db()->quote(date("Y-m-d 23:59:00")));
	$articles = $aq->page(0,4);

	
	$todaysEvents = '<table class="todayTable"><tr><th colspan="3">Todays events: </th></tr>';
	foreach($articles as $article) {
		switch($article->get("status")) {
			case 'not_initiated' :
			case '' :
				$action = 'start';
				break;
			case 'playing' :
			case 'paused' :
				$action = 'take over/resume';
				break;
			case 'finished' :
			case 'published' :
				$action = 'edit';
				break;
			default :
				break;	
		}
		$todaysEvents.='<tr>
			<td class="title">'.$article->get("title").' '.($article->get("status")=='' ? '' : '<span class='.$article->get("status").'>('.$article->get("status").')</span>').'</td>
			<td class="view">'.($article->get("status")=='' ? '' : '<a href="'.SERIA_HTTP_ROOT.'/?id='.$article->get("id").'" target="_blank">View</a>').'</td>
			<td class="action"><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php?id='.$article->get("id").'">'.$action.'</a></td>
		</tr>';
	}
	if(sizeof($articles) == 4) {
		$todaysEvents.='<tr><td colspan="3" style="height:10px;"></td></tr>';
		$todaysEvents.='<tr><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php">Create new presentation</a></td><td></td><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/upcoming.php">+more</a></td></tr>';
	} else if(sizeof($articles) == 0) {
		$todaysEvents.='<tr><td colspan="3"></td></tr>';
		$todaysEvents.='<tr><td colspan="3">No events lined up for today..</td></tr>';
		$todaysEvents.='<tr><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php">Create new presentation</a></td><td></td><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/upcoming.php">+more</a></td></tr>';
	} else {
		$todaysEvents.='<tr><td colspan="3" style="height:10px;"></td></tr>';
		$todaysEvents.='<tr><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php">Create new presentation</a></td><td></td><td><a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/upcoming.php">+more</a></td></tr>';
	}

	$todaysEvents .= '</table>';




	$startNewPresentation = '<a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php">Create new presentation</a>';




	$aq2 = new SERIA_ArticleQuery();

	$aq2->addSQL('event_date<'.SERIA_Base::db()->quote(date("Y-m-d 00:00:00")));
	$aq2->order("altered_date DESC");
	$archivedEvents = $aq2->page(0,20);

	$archive = '<table><tr><th colspan="2">Past presentations:</th></tr>';
	foreach($archivedEvents as $event) {
		$archive.='<tr><td>'.mb_substr($event->get("title"),0,20).'.. </td><td>
<a href="'.SERIA_HTTP_ROOT.'/seria/apps/live/edit.php?id='.$event->get("id").'">edit</a>/<a href="'.SERIA_HTTP_ROOT.'/?id='.$event->get("id").'" target="_blank">view</a>
</td></tr>';
	}
	$archive.='</table>';


	$contents.= '<table class="mainTableFront">
			<tr>
				<td class="todaysEvents">'.$todaysEvents.'</td>
				<td class="startNew">'.$startNewPresentation.'</td>
			</tr>
			<tr>
				<td class="statistics">'.$statisticsFrame.'</td>
				<td class="archive">'.$archive.'</td>
			</tr>
		</table>';

	$gui->contents($contents);
	echo $gui->output();

	die();
*/
?>
