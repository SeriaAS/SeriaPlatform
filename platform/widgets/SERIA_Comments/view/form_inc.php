<?php

require_once(dirname(__FILE__).'/recaptcha-php-1.10/recaptchalib.php');
$publickey = "6LeaGAYAAAAAAAM9X6rKvtc7Hui1MtYocs0ntCSh";
$privatekey = "6LeaGAYAAAAAAJYryymZ7K0Zb6Apvf9Y7oUJuh_v";

SERIA_Base::addFramework('bml');
SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_Comments/view/style.css');

function is_empty($val)
{
	return empty($val);
}

$avatar = null;
$showForm = true;
$errors = array();
$data = array();
foreach (array('name', 'title', 'comment', 'rating') as $copyf) {
	if (isset($_POST[$copyf]))
		$data[$copyf] = $_POST[$copyf];
	else {
		$data[$copyf] = '';
	}
}
if (count($_POST) > 0) {
	/*print_r($_FILES);
	print_r($_POST);*/
	
	/*
	 * SECURITY!!
	 */
	if (!SERIA_Base::isAdministrator()) {
		if (isset($_POST['edit'])) {
			$errors['edit'] = _t('Administrator privileges required for edit.');
			$script = new SERIA_BMLScript('alert('.$errors['edit'].');');
			$_GET['edit'] = $_POST['edit']; /* For form */
			echo $script->output();
			/* Not admin: clear fields.. */
			unset($_POST['edit']);
		}
	}

	$resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
	if (!$resp->is_valid)
		$errors['captcha'] = _t('Incorrect answer. Please type the two words below.');

	$avatar = null;
	if (isset($_FILES['avatar']) && isset($_FILES['avatar']['error'])) {
		$error = false;
		switch ($_FILES['avatar']['error']) {
			case UPLOAD_ERR_OK:
				$avatar = new SERIA_File($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name']);
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$error = _t('The file exceeds maximum file size.');
				break;
			case UPLOAD_ERR_PARTIAL:
				$error = _t('The file was truncated.');
				break;
			case UPLOAD_ERR_NO_FILE:
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$error = _t('No tmp directory was found.');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$error = _t('Write error while uploading.');
				break;
			case UPLOAD_ERR_EXTENSION:
				$error = _t('A security feature stopped the upload.');
				break;
		}
		if ($error !== false)
			$errors['avatar'] = $error;
	}
	if (!isset($_POST['name']) || is_empty(trim($_POST['name'])))
		$errors['name'] = _t('Please type your name below.');
	$data['rating'] = intval($data['rating']);
	if (!isset($_POST['edit']) && ($data['rating'] < 1 || $data['rating'] > 6))
		$errors['rating'] = _t('Please rate the article below. (1-6)');
	if (!isset($_POST['title']) || is_empty(trim($_POST['title'])))
		$errors['title'] = _t('Please type a title below.');
	if (!isset($_POST['comment']) || is_empty(trim($_POST['comment'])))
		$errors['comment'] = _t('Please type your comment below');
	if (count($errors) == 0) {
		if (!isset($_POST['edit'])) {
			$showForm = false;
			$this->addComment($data['name'], $avatar, $data['title'], $data['comment'], $data['rating']);
		} else {
			$comment = $this->getComment($_POST['edit']);
			if (isset($_FILES['avatar']) && isset($_FILES['avatar']['error']) && $_FILES['avatar']['error'] != UPLOAD_ERR_NO_FILE) {
				$avatar = new SERIA_File($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name']);
				if ($comment->avatar_id !== null) {
					$old_avatar = SERIA_File::createObject($comment->avatar_id);
					$old_avatar->decreaseReferrers();
				}
				$avatar->increaseReferrers();
				$comment->avatar_id = $avatar->get('id');
			}
			$comment->author = $data['name'];
			$comment->title = $data['title'];
			$comment->text = $data['comment'];
			$comment->save();
		}
	}
}
if (isset($_GET['edit']) && count($_POST) == 0) {
	/*
	 * Fetch fields...
	 */
	$comment = $this->getComment($_GET['edit']);
	$data['name'] = $comment->author;
	$data['rating'] = $comment->rating;
	$data['title'] = $comment->title;
	$data['comment'] = $comment->text;
	$avatar = SERIA_File::createObject($comment->avatar_id);
}
if ($showForm) {
	foreach (array_keys($data) as $errf)
		if (isset($errors[$errf]))
			$errors[$errf] = seria_bml('p', array('class' => 'error'))->setText($errors[$errf]);
		else
			$errors[$errf] = false;
	if (isset($errors['avatar']))
		$errors['avatar'] = seria_bml('p', array('class' => 'error'))->setText($errors['avatar']);
	else
		$errors['avatar'] = false;
	if (isset($errors['captcha']))
		$errors['captcha'] = seria_bml('p', array('class' => 'error'))->setText($errors['captcha']);
	else
		$errors['captcha'] = false; 
	$ratingItems = array();
	if (!$data['rating'])
		$ratingItems[] = seria_bml('option', array('value' => '', 'selected' => 'selected'))->setText(_t('Please select rating'));
	for ($i = 1; $i <= 6; $i++) {
		$attrs = array('value' => $i);
		if ($data['rating'] == $i)
			$attrs['selected'] = 'selected';
		$ratingItems[] = seria_bml('option', $attrs)->setText($i);
	}
	$data['rating'] = array('type' => 'text', 'name' => 'rating');
	if (isset($_GET['edit']))
		$data['rating']['disabled'] = false;
	if ($avatar !== null)
		$avatar = new SERIA_BMLImage($avatar->getThumbnailUrl(90, 90), 'Avatar image');
	else
		$avatar = false;
	$capt_err = null;
	$uri = $_SERVER['REQUEST_URI'];
	if (substr($uri, 0, 1) == '/')
		$uri = substr($uri, 1);
	$submitArea = seria_bml('div', array('class' => 'submitcomment'))->addChild(
		seria_bml('form', array('method' => 'POST', 'action' => SERIA_HTTP_ROOT.$uri,  'enctype' => 'multipart/form-data'))->addChild(seria_bml('div')->addChildren(array(
			seria_bml('h1')->setText((isset($_GET['edit']) ? _t('Edit a comment') : _t('Write a comment'))),
			seria_bml('p')->setText(_t('Please fill in the information below including your name and a small image representing you (avatar).')),
			$avatar,
			(isset($_GET['edit']) ? seria_bml('input', array('type' => 'hidden', 'name' => 'edit', 'value' => $_GET['edit'])) : false),
			seria_bml('input', array('type' => 'hidden', 'name' => 'showAddcom', 'value' => 'yes')),
			$errors['captcha'],
			recaptcha_get_html($publickey, $error),
			$errors['name'],
			seria_bml('div', array('class' => 'comline'))->addChildren(array(
				seria_bml('label', array('for' => 'name'))->setText(_t('Your name:')),
				seria_bml('input', array('type' => 'text', 'name' => 'name', 'value' => $data['name'])),
			)),
			$errors['avatar'],
			seria_bml('div', array('class' => 'comline'))->addChildren(array(
				seria_bml('label', array('for' => 'avatar'))->setText(_t('Avatar:')),
				seria_bml('input', array('type' => 'file', 'name' => 'avatar')),
			)),
			$errors['rating'],
			seria_bml('div', array('class' => 'comline'))->addChildren(array(
				seria_bml('label', array('for' => 'rating'))->setText(_t('Rating:')),
				seria_bml('select', $data['rating'])->addChildren($ratingItems)
			)),
			$errors['title'],
			seria_bml('div', array('class' => 'comline'))->addChildren(array(
				seria_bml('label', array('for' => 'title'))->setText(_t('Title:')),
				seria_bml('input', array('type' => 'text', 'name' => 'title', 'value' => $data['title'])),
			)),
			$errors['comment'],
			seria_bml('div', array('class' => 'comline'))->addChildren(array(
				seria_bml('label', array('for' => 'comment'))->setText(_t('Comment:')),
				seria_bml('textarea', array('cols' => 80, 'rows' => 25, 'name' => 'comment'))->setText($data['comment']),
			)),
			seria_bml('button', array('type' => 'submit'))->setText(_t('Submit'))
		)))
	);
	echo $submitArea->output();
}

?>