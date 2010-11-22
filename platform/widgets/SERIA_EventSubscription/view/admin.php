<?php

$form = $this->getForm();

if ($form === false)
	return;

if (count($_POST))
	$form->receive($_POST);

SERIA_Base::addFramework('bml');

?>
<h2><?php echo htmlspecialchars(_t("Event subscription")); ?></h2>
<div class='tabs'>
	<ul>
		<li>
			<a href='#event_subscription_1'><span><?php echo htmlspecialchars(_t('Information')); ?></span></a>
		</li>
		<li>
			<a href='#event_subscription_2'><span><?php echo htmlspecialchars(_t('Participants')); ?></span></a>
		</li>
		<li>
			<a href='#event_subscription_3'><span><?php echo htmlspecialchars(_t('Evaluation')); ?></span></a>
		</li>
	</ul>
</div>

<div id='event_subscription_1'>
	<?php echo $form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/genericInlineForm.php'); ?>
</div>

<div id='event_subscription_2'>
	<h2><?php echo htmlspecialchars(_t('Participant list')); ?></h2>
	<div id='parListContainer'><?php echo htmlspecialchars(_t('Loading...')); ?></div>
	<script type='text/javascript'>
		function reloadEventParList()
		{
			$.getJSON(
				'<?php echo SERIA_HTTP_ROOT; ?>/seria/platform/widgets/SERIA_EventSubscription/ajax/participants.php',
				{
					'widget_id': '<?php echo $this->getId(); ?>'
				},
				function (data) {
					$('#parListContainer').html(data.code);
				}
			);
		}
		reloadEventParList();
	</script>
</div>

<div id='event_subscription_3'>
<?php

/* Evaluation */
$count = 0;
$rating = 0;
$participants = $this->getAllSubscribers();

$table = new SERIA_Table(array(
	'fields' => array(
		array(
			'title' => _t('Rating')
		),
		array(
			'title' => _t('What did we do right?')
		),
		array(
			'title' => _t('What could we have done better?')
		)
	)
));
foreach ($participants as $participant) {
	if ($participant->rating) {
		$rating += $participant->rating;
		$count++;
		$table->addRow(array($participant->rating, $participant->positiveComment, $participant->negativeComment));
	}
}

$rating /= $count;

?>
	<h2><?php echo htmlspecialchars(_t('Evaluation')); ?></h2>
	<div><?php echo htmlspecialchars(_t('Average rating: %RATING%', array('RATING' => $rating))); ?></div>
	<h3><?php echo htmlspecialchars(_t('Feedback')); ?></h3>
	<?php echo $table->output(); ?>
</div>
