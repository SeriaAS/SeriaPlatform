<s:gui title="{'Moderate comments'|htmlspecialchars|_t}">
<?php
	$this->gui->activeMenuItem('controlpanel/outboard/comments/approve');
	$this->title = _t("Moderate comments");

?><h1 class='legend'>{{"Unmoderated comments"|_t}}</h1><?php

	$comments = SERIA_Comment::getAllUnmoderatedComments()->limit(1);

	$comment = $comments->current();
	if($comment)
	{
		$form = $comment->editAction();

		$approve = $comment->approveAction();
		if($approve->success)
		{
			header("Location: ".SERIA_Url::current());
			die();
		}

		$reject = $comment->rejectAction();
		if($reject->success)
		{
			header("Location: ".SERIA_Url::current());
			die();
		}

		echo $form->begin()."<table>
<tbody>
<tr>
	<th>".$form->label('title')."</th>
	<td>".$form->field('title')."</td>
</tr>
<tr>
	<th>".$form->label('displayName')."</th>
	<td>".$form->field('displayName')."</td>
</tr>
<tr>
	<th>".$form->label('message')."</th>
	<td>".$form->field('message')."</td>
</tr>
</tbody>
<tfoot>
	<td colspan=2>
		".$form->submit(_t("Save"))." <a href='".$approve."'>"._t("Approve")."</a> <a href='".$reject."'>"._t("Reject")."</a>
	</td>
</tfoot>
</table>".$form->end();
	}
	else
	{
		echo "<p>"._t("No unmoderated comments")."</p>";
	}
?>
</s:gui>