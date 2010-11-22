<?php

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Template::disable();

SERIA_Base::pageRequires('login');

if (!SERIA_Base::isAdministrator())
	die();

SERIA_Base::viewMode('admin');

/*
 * Try to detect website hostname..
 */
$httproot = SERIA_HTTP_ROOT;
if (substr($httproot, 0, 7) == 'http://')
	$host = substr($httproot, 7);
else if (substr($httproot, 0, 8) == 'https://')
	$host = substr($httproot, 8);
else
	throw new Exception('Unable to understand SERIA_HTTP_ROOT');
$pos = strpos($host, '/');
if ($pos !== false)
	$host = substr($host, 0, $pos);
if (strpos($host, 'www.') === 0)
	$host = substr($host, 4);
if ($pos === 0 || strlen($host) == 0)
	throw new Exception('Unable to understand SERIA_HTTP_ROOT');

if (isset($_POST['widget_id'])) {
	try {
		$widget = SERIA_Widget::createObject($_POST['widget_id']);
		$article = $widget->getNamedObject();
		if (!isset($_POST['action']))
			throw new Exception('No action specified!');
		switch ($_POST['action']) {
			case 'delete':
				if (!isset($_POST['id']))
					throw new Exception('No id specified');
				$widget->getSubscriber($_POST['id'])->delete();
				break;
			case 'parlist':
				SERIA_Base::addFramework('bml');
				$subscribers = $widget->getAllSubscribers();
				$emails = array();
				$subList = array();
				foreach ($subscribers as $subscriber) {
					if ($subscriber->email && SERIA_IsInvalid::email($subscriber->email, true) === false)
						$emails[] = $subscriber->email;
					$subList[] = array(
						'name' => $subscriber->name,
						'company' => $subscriber->company
					);
				}
				$title = _t('%EVENT%: List of participants', array('EVENT' => $article->get('title')));
				$templateName = 'email_parlist.php';
				$rel_filename = 'widgets/'.$widget->getWidgetDirname().'/templates/'.$templateName;
				$filename = SERIA_ROOT.'/'.$rel_filename;
				if (!file_exists($filename))
					$filename = SERIA_ROOT.'/seria/platform/'.$rel_filename;
				$email_cont = SERIA_Template::parseToString($filename, array('participants' => $subList, 'title' => $title, 'host' => $host));
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
				$headers .= 'Content-Transfer-Encoding: base64'."\r\n";
				$headers .= 'From: donotreply@'.$host."\r\n";
				$headers .= 'To: donotreply@'.$host."\r\n";
				$to = implode(', ', $emails);
				mail($to, encode_header_value($title, "utf-8"), imap_binary($email_cont), $headers);
				break;
			case 'evalform':
				SERIA_Base::addFramework('bml');
				$subscribers = $widget->getAllSubscribers();
				$emails = array();
				$urlTokens = array(
					'widget_id' => $widget->getId(),
					'action' => 'eval'
				);
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
				$headers .= 'Content-Transfer-Encoding: base64'."\r\n";
				$headers .= 'From: donotreply@'.$host."\r\n";
				$headers .= 'To: donotreply@'.$host."\r\n";
				$title = _t('%EVENT%: Please evaluate the event', array('EVENT' => $article->get('title')));
				foreach ($subscribers as $subscriber) {
					if (!$subscriber->rating && $subscriber->email && SERIA_IsInvalid::email($subscriber->email, true) === false) {
						$urlTok = $urlTokens;
						$urlTok['id'] = $subscriber->id;
						if (!$subscriber->authsecret) {
							$subscriber->authsecret = substr(mt_rand().mt_rand().mt_rand().mt_rand(), 0, 20);
							$subscriber->save();
						}
						$urlTok['key'] = $subscriber->authsecret;
						foreach ($urlTok as $nam => &$val)
							$val = $nam.'='.$val;
						unset($val); /* drop the reference */
						$url = $widget->get('client_view_url');
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
						$url .= '?'.implode('&', $urlTok);
						$templateName = 'email_feedback.php';
						$rel_filename = 'widgets/'.$widget->getWidgetDirname().'/templates/'.$templateName;
						$filename = SERIA_ROOT.'/'.$rel_filename;
						if (!file_exists($filename))
							$filename = SERIA_ROOT.'/seria/platform/'.$rel_filename;
						$email_content = SERIA_Template::parseToString($filename, array('url' => $url, 'title' => $title, 'host' => $host));
						mail($subscriber->email, encode_header_value($title, "utf-8"), imap_binary($email_content), $headers);
					}
				}
				break;
			default:
				throw new Exception('Invalid action!');
		}
		SERIA_Lib::publishJSON(array('error' => false));
		die();
	} catch (Exception $e) {
		SERIA_Lib::publishJSON(array('error' => $e->getMessage()));
		die();
	}
}

SERIA_Base::addFramework('bml');

if (!isset($_GET['widget_id']))
	die();

$widget = SERIA_Widget::createObject($_GET['widget_id']);

$participants = $widget->getAllSubscribers();
$parList = array(
	new SERIA_BMLScript(
'

function sendParticipantListToParticipants()
{
	if (confirm("'.htmlspecialchars(_t('Are you sure that you want to send an email to every participant?')).'")) {
		$.post(
			\''.SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/ajax/participants.php\',
			{
				\'widget_id\': \''.$widget->getId().'\',
				\'action\': \'parlist\',
			},
			function (data) {
				if (data.error)
					alert(data.error);
			},
			\'json\'
		);
	}
}

function sendEvalFormToParticipants()
{
	if (confirm("'.htmlspecialchars(_t('Are you sure that you want to send an email to every participant?')).'")) {
		$.post(
			\''.SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/ajax/participants.php\',
			{
				\'widget_id\': \''.$widget->getId().'\',
				\'action\': \'evalform\',
			},
			function (data) {
				if (data.error)
					alert(data.error);
			},
			\'json\'
		);
	}
}

function deleteEventSubscriber(id)
{
	$.post(
		\''.SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/ajax/participants.php\',
		{
			\'widget_id\': \''.$widget->getId().'\',
			\'action\': \'delete\',
			\'id\': id
		},
		function (data) {
			if (data.error)
				alert(data.error);
			reloadEventParList();
		},
		\'json\'
	);
}

', 'text/javascript'
	)
);
$emails = array();
foreach ($participants as $key => $participant) {
	if ($participant->email && SERIA_IsInvalid::email($participant->email, true) === false)
		$emails[] = $participant->email;
	$parTableRows = array(
		'name' => _t('Name:'),
		'address' => _t('Address:'),
		'zip' => _t('Zip-code:'),
		'city' => _t('City:'),
		'phone' => _t('Phone:'),
		'orgNum' => _t('Organization number:'),
		'email' => _t('E-Mail:'),
		'company' => _t('Company'),
		'billingAddress' => _t('Billing address:'),
		'billingZip' => _t('Billing zip-code:'),
		'billingCity' => _t('Billing city:'),
		'otherInfo' => _t('Other information:')
	);
	foreach ($parTableRows as $fkey => &$row) {
		$row = seria_bml('tr')->addChildren(array(
			seria_bml('th')->setText($row),
			seria_bml('td')->setText($participant->$fkey)
		));
	}
	$parTable = seria_bml('table')->addChildren($parTableRows);
	$parList[] = seria_bml('li')->addChildren(array(
		seria_bml_ahref('#')->setAttr('onclick', 'var obj = document.getElementById(\'parListItem'.$key.'\'); if (obj.style.display == \'none\') obj.style.display = \'block\'; else obj.style.display = \'none\'; return false;')->setText($participant->name),
		seria_bml('div', array('id' => 'parListItem'.$key))->setStyle('display', 'none')->addChildren(array(
			$parTable,
			seria_bml('button', array('type' => 'button', 'onclick' => 'deleteEventSubscriber(\''.$participant->id.'\');'))->setText(_t('Delete'))
		))
	));
}

$tree = seria_bml()->addChildren(array(
	seria_bml('ul')->addChildren($parList),
	seria_bml('div')->setStyle('margin-top', '10px')->addChild(
		seria_bml_displaytable()->addChildren(array(
			seria_bml('button', array('type' => 'button', 'onclick' => 'sendParticipantListToParticipants();'))->setText(_t('Send participant lists')),
			seria_bml('button', array('type' => 'button', 'onclick' => 'sendEvalFormToParticipants();'))->setText(_t('Send evaluation-form to participants'))
		))
	),
	seria_bml('div')->setStyle('margin-top', '10px')->addChild(seria_bml_ahref('mailto:'.implode(',', $emails))->setText(_t('Send email to all participants')))
));

SERIA_Lib::publishJSON(array(
	'code' => $tree->output()
));

?>