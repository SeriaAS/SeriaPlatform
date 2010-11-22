<?php

$article = $this->getNamedObject();

function eventWidgetRatingIsInvalid($value)
{
	if ($value != intval($value))
		return _t('The value must be an integer.');
	if ($value < 1 || $value > 6)
		return _t('Any value ranging from 1 (inclusive) to 6 (inclusive)');
	return false;
}

class SERIA_EventSubscriptionEvaluationForm extends SERIA_Form {
	public $dataStorage;

	public function __construct(&$object)
	{
		parent::__construct();
		$this->dataStorage =& $object;
		$this->spec = self::getFormSpec();
	}

	public static function caption()
	{
		return _t('Evaluation form');
	}

	public static function getFormSpec()
	{
		return array(
			'rating' => array(
				'caption' => _t('Rating:'),
				'fieldtype' => 'select',
				'options' => array(
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
					5 => 5,
					6 => 6
				),
				'validator' => new SERIA_Validator(
					array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::CALLBACK, 'eventWidgetRatingIsInvalid')
					),
					true
				)
			),
			'positiveComment' => array(
				'caption' => _t('What did we do right?:'),
				'fieldtype' => 'textarea',
				'validator' => new SERIA_Validator(
					array(
						array(SERIA_Validator::REQUIRED)
					),
					true
				)
			),
			'negativeComment' => array(
				'caption' => _t('What could we have done better?:'),
				'fieldtype' => 'textarea',
				'validator' => new SERIA_Validator(
					array(
						array(SERIA_Validator::REQUIRED)
					)
				)
			)
		);
	}
	public function get($name)
	{
		return $this->dataStorage->$name;
	}
	public function _handle($data)
	{
		$spec = array_keys(self::getFormSpec());
		foreach ($spec as $nam)
			$this->dataStorage->$nam = $data[$nam];
		$this->dataStorage->save();
		return true;
	}
}

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'eval':
			if (isset($_GET['id']) && isset($_GET['key'])) {
				$participant = $this->getSubscriber($_GET['id']);
				if ($participant->authsecret == $_GET['key']) {
					$templateName = 'evaluation.php';
					$rel_filename = 'widgets/'.$this->getWidgetDirname().'/templates/'.$templateName;
					$template_filename = SERIA_ROOT.'/'.$rel_filename;
					if (!file_exists($template_filename))
						$template_filename = SERIA_ROOT.'/seria/platform/'.$rel_filename;
					if (!file_exists($template_filename))
						throw new Exception('Template not found: '.$template_filename);
					if (!$participant->rating) {
						$form = new SERIA_EventSubscriptionEvaluationForm($participant);
						if (sizeof($_POST)) {
							if ($form->receive($_POST) !== false) {
								$urlTok = array();
								foreach ($_GET as $nam => $val)
									$urlTok[] = $nam.'='.urlencode($val);
								$url = $this->get('client_view_url');
								if ($url === null)
									$url = SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/pages/client.php';
								else {
									$urlparsed = parse_url($url);
									$fileUrl = '';
									if (isset($urlparsed['scheme']) && $urlparsed['scheme'])
										$fileUrl .= $urlparsed['scheme'].'://';
									if (isset($urlparsed['user']) && $urlparsed['user']) {
										$fileUrl .= $urlparsed['user'];
										if (isset($urlparsed['pass']) && $urlparsed['pass'])
											$fileUrl .= ':'.$urlparsed['pass'];
										$fileUrl .= '@';
									}
									$fileUrl .= $urlparsed['host'];
									if (isset($urlparsed['path']) && $urlparsed['path'])
										$fileUrl .= $urlparsed['path'];
									else
										$fileUrl .= '/';
									if (isset($urlparsed['query']) && $urlparsed['query']) {
										$q = array();
										parse_str($urlparsed['query'], $q);
										foreach ($q as $nam => $val)
											$urlTok[$nam] = $nam.'='.urlencode($val);
									}
									$url = $fileUrl;
								}
								if (count($urlTok))
									$url .= '?'.implode('&', $urlTok);
								header('Location: '.$url);
								die();
							}
						}
						SERIA_Template::parse($template_filename, array('article' => $article, 'form' => $form));
					} else
						SERIA_Template::parse($template_filename, array('article' => $article));
				}
			}
			break;
	}
}

?>