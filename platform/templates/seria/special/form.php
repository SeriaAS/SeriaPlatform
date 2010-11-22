<?php
	var_dump($form);

	echo $form->begin();

	foreach($form->spec as $fieldName => $info)
	{
		$type = $info['type'];
		echo '<div id="$prefix'.$fieldName.'">';
		echo $form->label($fieldName).':<br>';
		echo $form->$type($fieldName);
		echo '</div>';
	}

	echo $form->end();
