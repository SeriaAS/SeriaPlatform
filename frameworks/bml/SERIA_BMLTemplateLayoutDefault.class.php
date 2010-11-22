<?php

class SERIA_BMLTemplateLayoutDefault extends SERIA_BMLTemplateLayout
{
	protected $topHeight = 100;
	protected $leftWidth = 100;
	protected $rightWidth = 100;
	protected $bottomHeight = 100;
	protected $contentTopMargin = 0;
	protected $contentMargin = 20;
	protected $expandHeight = false;
	protected $contentAlign = 'center';
	protected $solidEdges = false; /* Do not shrink edges if window is to narrow to display them fully. */
	protected $printLayout = false; /* Set to plain to print just "#contents".. */
	protected $printLogo = false;
	protected $printLogoWidth = 0;
	protected $backgroundPattern = false;
	protected $transparencyMode = false; /* Use fixed large height images, instead of repeat-y because of overlay issues */
	protected $maxPageHeight = 10000; /* Pixels: Used by transparency mode */
	protected $transparentLeftSide = false; /* For ovverride scripts */
	protected $transparentRightSide = false; /* For override scripts */
	protected $autoMinHeight = true;
	protected $topCenterHref = false; /* Top center area should be an a:link */
	protected $topCenterHrefTitle = '';

	/* override parents */
	protected $contentAreas = array('TOPLEFT', 'TOP', 'CONTENTS', 'LEFT', 'RIGHT', 'BOTTOM', 'CONTENT_BOTTOM');

	public function serializeParams()
	{
		$settings = array(
			'img' => $this->layoutImg,
			'topHeight' => $this->topHeight,
			'leftWidth' => $this->leftWidth,
			'rightWidth' => $this->rightWidth,
			'bottomHeight' => $this->bottomHeight,
			'contentTopMargin' => $this->contentTopMargin,
			'contentMargin' => $this->contentMargin,
			'expandHeight' => $this->expandHeight,
			'contentAlign' => $this->contentAlign,
			'generatorType' => $this->generatorType,
			'solidEdges' => $this->solidEdges,
			'printLayout' => $this->printLayout,
			'printLogo' => $this->printLogo,
			'printLogoWidth' => $this->printLogoWidth,
			'backgroundPattern' => $this->backgroundPattern,
			'transparencyMode' => $this->transparencyMode,
			'maxPageHeight' => $this->maxPageHeight,
			'transparentLeftSide' => $this->transparentLeftSide,
			'transparentRightSide' => $this->transparentRightSide,
			'autoMinHeight' => $this->autoMinHeight,
			'topCenterHref' => $this->topCenterHref,
			'topCenterHrefTitle' => $this->topCenterHrefTitle
		);
		return serialize($settings);
	}
	public function setParams($settings)
	{
		if (isset($settings['img']))
			$this->layoutImg = $settings['img'];
		if (isset($settings['topHeight']))
			$this->topHeight = $settings['topHeight'];
		if (isset($settings['leftWidth']))
			$this->leftWidth = $settings['leftWidth'];
		if (isset($settings['rightWidth']))
			$this->rightWidth = $settings['rightWidth'];
		if (isset($settings['bottomHeight']))
			$this->bottomHeight = $settings['bottomHeight'];
		if (isset($settings['contentTopMargin']))
			$this->contentTopMargin = $settings['contentTopMargin'];
		if (isset($settings['contentMargin']))
			$this->contentMargin = $settings['contentMargin'];
		if (isset($settings['expandHeight']))
			$this->expandHeight = $settings['expandHeight'];
		if (isset($settings['contentAlign']))
			$this->contentAlign = $settings['contentAlign'];
		if (isset($settings['solidEdges']))
			$this->solidEdges = $settings['solidEdges'];
		if (isset($settings['printLayout']))
			$this->printLayout = $settings['printLayout'];
		if (isset($settings['printLogo']))
			$this->printLogo = $settings['printLogo'];
		if (isset($settings['printLogoWidth']))
			$this->printLogoWidth = $settings['printLogoWidth'];
		if (isset($settings['backgroundPattern']))
			$this->backgroundPattern = $settings['backgroundPattern'];
		if (isset($settings['transparencyMode']))
			$this->transparencyMode = $settings['transparencyMode'];
		if (isset($settings['maxPageHeight']))
			$this->maxPageHeight = $settings['maxPageHeight'];
		if (isset($settings['transparentLeftSide']))
			$this->transparentLeftSide = $settings['transparentLeftSide'];
		if (isset($settings['transparentRightSide']))
			$this->transparentRightSide = $settings['transparentRightSide'];
		if (isset($settings['autoMinHeight']))
			$this->autoMinHeight = $settings['autoMinHeight'];
		if (isset($settings['topCenterHref']))
			$this->topCenterHref = $settings['topCenterHref'];
		if (isset($settings['topCenterHrefTitle']))
			$this->topCenterHrefTitle = $settings['topCenterHrefTitle'];
		parent::setParams($settings);
	}
	protected function imagecreatealpha($width, $height)
	{
		$img = imagecreatetruecolor($width, $height);
		/*$black = imagecolorallocate($img, 0, 0, 0);
		imagecolortransparent($img, $black);*/
		imagealphablending($img, false);
		imagesavealpha($img, true);
		return $img;
	}
	protected function loadpng($pngfile)
	{
		$img = imagecreatefrompng($pngfile);
		imagealphablending($img, false);
		imagesavealpha($img, true);
		return $img;
	}
	protected function generate()
	{
		$cacheHttpDir = $this->getCacheHTTPDir();

		$pinfo = pathinfo($this->layoutImg);
		switch ($pinfo['extension']) {
			case 'png':
				$imgobj = $this->loadpng($this->layoutImg);
				break;
			case 'jpg':
			case 'jpeg':
				$imgobj = imagecreatefromjpeg($this->layoutImg);
				break;
			case 'gif':
				$imgobj = imagecreatefromgif($this->layoutImg);
				break;
			default:
				throw new Exception('Unknown image type in template generation');
		}
		$width = imagesx($imgobj);
		$height = imagesy($imgobj);
		$cw = $width - $this->leftWidth - $this->rightWidth;
		$ch = $height - $this->topHeight - $this->bottomHeight;

		/*
		 * Get the background color..
		 */
		if ($this->contentAlign == 'center' || $this->contentAlign == 'right')
			$rgb = imagecolorat($imgobj, 0, $height-1);
		else
			$rgb = imagecolorat($imgobj, $width-1, $height-1);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		$backgroundColor = '#'.$this->outputByteInHex($r).$this->outputByteInHex($g).$this->outputByteInHex($b);
		/*
		 * Get frame background clips...
		 */
		$backgroundGradient = $this->imagecreatealpha(2048, $height);
		if ($this->contentAlign == 'center' || $this->contentAlign == 'right')
			imagecopy($backgroundGradient, $imgobj, 0, 0, 0, 0, 1, $height);
		else
			imagecopy($backgroundGradient, $imgobj, 0, 0, $width-1, 0, 1, $height);
		$expand = 1;
		while ($expand < 2048) {
			imagecopy($backgroundGradient, $backgroundGradient, $expand, 0, 0, 0, $expand, $height);
			$expand = $expand << 1;
		}
		$frameTopLeft = $this->imagecreatealpha($this->leftWidth, $this->topHeight);
		imagecopy($frameTopLeft, $imgobj, 0, 0, 0, 0, $this->leftWidth, $this->topHeight);
		$frameTopCenter = $this->imagecreatealpha($cw, $this->topHeight);
		imagecopy($frameTopCenter, $imgobj, 0, 0, $this->leftWidth, 0, $cw, $this->topHeight);
		$frameTopRight = $this->imagecreatealpha($this->rightWidth, $this->topHeight);
		imagecopy($frameTopRight, $imgobj, 0, 0, $this->leftWidth + $cw, 0, $this->rightWidth, $this->topHeight);
		/* left/right edges */
		if (!$this->transparencyMode) {
			$frameLeft = $this->imagecreatealpha($this->leftWidth, $height - $this->topHeight - $this->bottomHeight);
			$frameRight = $this->imagecreatealpha($this->rightWidth, $height - $this->topHeight - $this->bottomHeight);
		} else {
			$frameLeft = $this->imagecreatealpha($this->leftWidth, $this->maxPageHeight + $height - $this->topHeight - $this->bottomHeight);
			$frameRight = $this->imagecreatealpha($this->rightWidth, $this->maxPageHeight + $height - $this->topHeight - $this->bottomHeight);
			$startFrom = $height - $this->topHeight - $this->bottomHeight;
			imagecopy($frameLeft, $imgobj, 0, $startFrom, 0, $height-$this->bottomHeight-1, $this->leftWidth, 1);
			imagecopy($frameRight, $imgobj, 0, $startFrom, $this->leftWidth + $cw, $height-$this->bottomHeight-1, $this->rightWidth, 1);
			for ($i = 1; $i < $this->maxPageHeight; $i *= 2) {
				$cpheight = min($i, $this->maxPageHeight-$i);
				imagecopy($frameLeft, $frameLeft, 0, $startFrom+$i, 0, $startFrom, $this->leftWidth, $cpheight);
				imagecopy($frameRight, $frameRight, 0, $startFrom+$i, 0, $startFrom, $this->rightWidth, $cpheight);
			}
		}
		imagecopy($frameLeft, $imgobj, 0, 0, 0, $this->topHeight, $this->leftWidth, $height - $this->topHeight - $this->bottomHeight);
		imagecopy($frameRight, $imgobj, 0, 0, $this->leftWidth + $cw, $this->topHeight, $this->rightWidth, $height - $this->topHeight - $this->bottomHeight);
		$frameLeftRepeating = $this->imagecreatealpha($this->leftWidth, 1);
		imagecopy($frameLeftRepeating, $imgobj, 0, 0, 0, $height-$this->bottomHeight-1, $this->leftWidth, 1);
		$frameRightRepeating = $this->imagecreatealpha($this->rightWidth, 1);
		imagecopy($frameRightRepeating, $imgobj, 0, 0, $this->leftWidth + $cw, $height-$this->bottomHeight-1, $this->rightWidth, 1);
		if ($this->bottomHeight) {
			$frameBottomLeft = $this->imagecreatealpha($this->leftWidth, $this->bottomHeight);
			imagecopy($frameBottomLeft, $imgobj, 0, 0, 0, $height - $this->bottomHeight, $this->leftWidth, $this->bottomHeight);
			$frameBottomCenter = $this->imagecreatealpha($cw, $this->bottomHeight);
			imagecopy($frameBottomCenter, $imgobj, 0, 0, $this->leftWidth, $height - $this->bottomHeight, $cw, $this->bottomHeight);
			$frameBottomRight = $this->imagecreatealpha($this->rightWidth, $this->bottomHeight);
			imagecopy($frameBottomRight, $imgobj, 0, 0, $width - $this->rightWidth, $height - $this->bottomHeight, $this->rightWidth, $this->bottomHeight);
		} else {
			$frameBottomLeft = false;
			$frameBottomCenter = false;
			$frameBottomRight = false;
		}

		/*
		 * Get content background clip ...
		 */
		if ($this->contentTopMargin) {
			$contentAreaTopMarg = $this->imagecreatealpha($cw, $this->contentTopMargin);
			imagecopy($contentAreaTopMarg, $imgobj, 0, 0, $this->leftWidth, $this->topHeight, $cw, $this->contentTopMargin);
		} else
			$contentAreaTopMarg = false;
		$contentAreaTop = $this->imagecreatealpha($cw, $ch - $this->contentMargin - $this->contentTopMargin);
		imagecopy($contentAreaTop, $imgobj, 0, 0, $this->leftWidth, $this->topHeight + $this->contentTopMargin, $cw, $ch - $this->contentMargin - $this->contentTopMargin);
		/* repeating image */
		$contentAreaRepeating = $this->imagecreatealpha($cw, 1);
		imagecopy($contentAreaRepeating, $imgobj, 0, 0, $this->leftWidth, $this->topHeight + $ch - $this->contentMargin - 1, $cw, 1);
		if ($this->contentMargin) {
			$contentAreaBottom = $this->imagecreatealpha($cw, $this->contentMargin);
			imagecopy($contentAreaBottom, $imgobj, 0, 0, $this->leftWidth, $this->topHeight + $ch - $this->contentMargin, $cw, $this->contentMargin);
		} else
			$contentAreaBottom = false;

		/*
		 * Print logo: scaled..
		 */
		if ($this->printLogo) {
			$pinfo = pathinfo($this->printLogo);
			switch ($pinfo['extension']) {
				case 'png':
					$logoobj = $this->loadpng($this->printLogo);
					break;
				case 'jpg':
				case 'jpeg':
					$logoobj = imagecreatefromjpeg($this->printLogo);
					break;
				case 'gif':
					$logoobj = imagecreatefromgif($this->printLogo);
					break;
				default:
					throw new Exception('Unknown image type in template generation');
			}
			if (!$this->printlogo_width)
				$this->printlogo_width = $cw;
			$printLogoWidth = $this->printLogoWidth;
			$printLogoHeight = ceil(imagesy($logoobj) * $printLogoWidth / imagesx($logoobj));
			$printLogoFilename = 'printlogo.'.$pinfo['extension'];
		} else {
			$printLogoImage = false;
			$printLogoFilename = false;
		}

		/*
		 * Background pattern..
		 */
		if ($this->backgroundPattern !== false) {
			set_time_limit(60);
			$pinfo = pathinfo($this->backgroundPattern);
			switch ($pinfo['extension']) {
				case 'png':
					$backpattern = $this->loadpng($this->backgroundPattern);
					break;
				case 'jpg':
				case 'jpeg':
					$backpattern = imagecreatefromjpeg($this->backgroundPattern);
					break;
				case 'gif':
					$backpattern = imagecreatefromgif($this->backgroundPattern);
					break;
				default:
					throw new Exception('Unknown image type in template generation');
			}
			$bpw = imagesx($backpattern);
			$bph = imagesy($backpattern);
			if ($this->contentAlign == 'left')
				$referencePoint = 'topleft';
			else if ($this->contentAlign == 'right')
				$referencePoint = 'topright';
			else
				throw new Exception('Content align must be either left or right with background pattern');
			$bpobjnoref = new SERIA_BMLBackgroundPattern($backpattern);
			if ($bpw < $width) {
				$repeater = $bpobjnoref->left;
				$backpattern = $repeater->appendToCopy($backpattern, $width - $bpw);
				$bpw = $width;
				$bpobjnoref = new SERIA_BMLBackgroundPattern($backpattern);
			}
			if ($bph < $height) {
				$repeater = $bpobjnoref->bottom;
				$backpattern = $repeater->appendToCopy($backpattern, $height - $bph);
				$bph = $height;
				$bpobjnoref = new SERIA_BMLBackgroundPattern($backpattern);
			}
			$bpobj = new SERIA_BMLBackgroundPattern($backpattern, $referencePoint);
			if ($this->contentAlign == 'left') {
				$patternSide = $bpobj->right->image;
				$patternDefault = $bpobj->bottomRight->image;
				$patternBottom = $bpobj->bottom->image;
			} else {
				$patternSide = $bpobj->left->image;
				$patternDefault = $bpobj->bottomLeft->image;
				$patternBottom = $bpobj->bottom->image;
			}
			$patternBackground = $bpobj->image;
			/*
			 * $bpobj and $backpattern are now at least of same size as the master image.
			 */
		} else {
			$patternBackground = false;
			$patternSide = false;
			$patternDefault = false;
			$patternBottom = false;
		}

		/*
		 * HTML...
		 */

		/* IE switch display script */
		$ie6script = '
<script type=\'text/javascript\'>
'.(!$this->transparencyMode ? '	(function () {
		var leftobj = document.getElementById(\'AutoTemplate_content_idleft\');
		var leftchild = leftobj.firstChild;
		var rightobj = document.getElementById(\'AutoTemplate_content_idright\');
		var rightchild = rightobj.firstChild;
		var expandToContentHeight = '.$ch.';

		leftchild.style.width = leftobj.offsetWidth + \'px\';
		leftchild.style.height = leftobj.offsetHeight + \'px\';
		leftchild.style.background = \'url('.$cacheHttpDir.'/frame_left.png) no-repeat top right\';
		rightchild.style.width = rightobj.offsetWidth + \'px\';
		rightchild.style.height = rightobj.offsetHeight + \'px\';
		rightchild.style.background = \'url('.$cacheHttpDir.'/frame_right.png) no-repeat top left\';
	})();' :
'	$(document).ready(function () {
		var replaceBackgroundF = function (objName, backgroundUrl) {
			var obj;
			obj = document.getElementById(objName);
			obj.style.background = \'\';
			if (backgroundUrl) {
				var filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'"+backgroundUrl+"\', sizingMethod=\'scale\')";
				obj.style.filter = filter;
			}
		}
		var replaceBackgroundCrop = function (objName, backgroundUrl) {
			var obj;
			obj = document.getElementById(objName);
			obj.style.background = \'\';
			if (backgroundUrl) {
				var filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'"+backgroundUrl+"\', sizingMethod=\'crop\')";
				obj.style.filter = filter;
			}
		}
		var replaceBackgroundSplat = function (objName, backgroundUrl) {
			var obj;
			obj = document.getElementById(objName);
			obj.style.background = \'cyan\';
		}

		replaceBackgroundCrop(\'AutoTemplate_content_idleft\', \''.$cacheHttpDir.'/frame_left.png\');
		replaceBackgroundCrop(\'AutoTemplate_topleft_id\', \''.$cacheHttpDir.'/frame_topleft.png\');
		replaceBackgroundCrop(\'AutoTemplate_topcenter_id\', \''.$cacheHttpDir.'/frame_topcenter.png\');
		replaceBackgroundCrop(\'AutoTemplate_topright_id\', \''.$cacheHttpDir.'/frame_topright.png\');
		replaceBackgroundCrop(\'AutoTemplate_contenttop\', \''.$cacheHttpDir.'/content_top.png\');
		replaceBackgroundF(\'AutoTemplate_contentsubrow\', \''.$cacheHttpDir.'/content_rep.png\');
		replaceBackgroundCrop(\'AutoTemplate_contentbottom\', \''.$cacheHttpDir.'/content_bottom.png\');
		replaceBackgroundCrop(\'AutoTemplate_content_idright\', \''.$cacheHttpDir.'/frame_right.png\');
		replaceBackgroundCrop(\'AutoTemplate_bottomleft_id\', \''.$cacheHttpDir.'/frame_bottomleft.png\');
		replaceBackgroundCrop(\'AutoTemplate_bottomcenter_id\', \''.$cacheHttpDir.'/frame_bottomcenter.png\');
		replaceBackgroundCrop(\'AutoTemplate_bottomright_id\', \''.$cacheHttpDir.'/frame_bottomright.png\');
		replaceBackgroundCrop(\'AutoTemplate_expanding_layer\', \''.$cacheHttpDir.'/background_repeat.png\');
	});').'
	</script>
';

		/* Opera expand to height script */
		$operascript = '
<script type=\'text/javascript\'>
	(function () {
		if (/Opera[\/\s](\d+\.\d+)/.test(navigator.userAgent)) {
		}
	})();
</script>
';
		/* IE8 expand to height script */
		$ie8script = '
<script type=\'text/javascript\'>
	var seria_ieexpand = function () {
		var contentrow = document.getElementById(\'AutoTemplate_contentbg\');

		var expectedMinimumHeight = document.body.offsetHeight - '.($this->topHeight + $this->bottomHeight).';
		if (contentrow.offsetHeight < expectedMinimumHeight)
			contentrow.style.height = expectedMinimumHeight + \'px\';
	};
	$(document).ready(function () {
		/*seria_ieexpand();*/
	});
	
</script>
';
		$ie7expand = '
<script type=\'text/javascript\'>
	var seria_autotemplate_expand_elements = function () {
		var contentrow_left = document.getElementById(\'AutoTemplate_content_idleft\');
		var contentrow_middle = document.getElementById(\'AutoTemplate_contentbg\');
		var contentrow_right = document.getElementById(\'AutoTemplate_content_idright\');
		var contentrow_toprow = document.getElementById(\'AutoTemplate_contenttoprow\');
		var contentrow_top = document.getElementById(\'AutoTemplate_contenttop\');
		var contentrow_iet = document.getElementById(\'AutoTemplate_content\');
		var contentrow_bottomrow = document.getElementById(\'AutoTemplate_contentbottomrow\');
		var contentrow_bottom = document.getElementById(\'AutoTemplate_contentbottom\');

		/*alert(\'body=\' +document.body.offsetHeight+ \' left=\' +contentrow_left.offsetHeight+ \' middle=\' + contentrow_middle.offsetHeight);*/
		var finished = false;
		var giveUp = 5;
		var expectedMinimumHeight = '.($this->expandHeight ? 'document.body.offsetHeight - '.($this->topHeight + $this->bottomHeight) : 0).';
		while (!finished) {
			finished = true;

			/*alert(\'body=\' +document.body.offsetHeight+ \' left=\' +contentrow_left.offsetHeight+ \' middle=\' + contentrow_middle.offsetHeight + \' minimums=\' + expectedMinimumHeight);*/
			/*
			 * Don\'t loop, just give up..
			 */
			if (--giveUp == 0)
				break;
			if (contentrow_left.offsetHeight < (expectedMinimumHeight-5)) {
				contentrow_left.style.height = expectedMinimumHeight + \'px\';
			} else if (expectedMinimumHeight < contentrow_left.offsetHeight) {
				expectedMinimumHeight = contentrow_left.offsetHeight;
				/*alert(\'body=\' +document.body.offsetHeight+ \' left=\' +contentrow_left.offsetHeight+ \' middle=\' + contentrow_middle.offsetHeight + \' minimums=\' + expectedMinimumHeight);*/
			}
			if (contentrow_right.offsetHeight < (expectedMinimumHeight-5)) {
				contentrow_right.style.height = expectedMinimumHeight + \'px\';
			} else if (expectedMinimumHeight < contentrow_left.offsetHeight) {
				expectedMinimumHeight = contentrow_left.offsetHeight;
				finished = false; /* Redo the stretch */
			}
			force_iet = false;'.'
			if (contentrow_middle.offsetHeight < expectedMinimumHeight) {
				contentrow_middle.style.height = expectedMinimumHeight + \'px\';
				force_iet = true;
			} else if (expectedMinimumHeight < contentrow_middle.offsetHeight) {
				expectedMinimumHeight = contentrow_middle.offsetHeight;
				finished = false; /* Redo the stretch */
			}'.'
			expectedMinimumHeight -= '.($this->contentMargin + $this->contentTopMargin).';
			if (force_iet || contentrow_iet.offsetHeight < expectedMinimumHeight) {
				contentrow_iet.style.minHeight = expectedMinimumHeight + \'px\';
				contentrow_iet.style.height = expectedMinimumHeight + \'px\';
			} else if (expectedMinimumHeight < contentrow_iet.offsetHeight) {
				expectedMinimumHeight = contentrow_iet.offsetHeight;
				finished = false; /* Redo the stretch */
			}
			expectedMinimumHeight += '.($this->contentMargin + $this->contentTopMargin).';
		}
	};
	$(document).ready(function () {
		seria_autotemplate_expand_elements();
	});
</script>
';
		if ($this->printLogo) {
			$printLogo = new SERIA_BMLImage($cacheHttpDir.'/'.$printLogoFilename, 'Top logo');
			$printLogo->setAttr('id', 'AutoTemplate_printlogo');
		} else
			$printLogo = false;
		if ($this->topCenterHref !== false)
			$topCenterContent = seria_bml('a', array('id' => 'AutoTemplate_topcenter_content', 'href' => $this->topCenterHref, 'title' => $this->topCenterHrefTitle))->setText('%%TOP%%');
		else
			$topCenterContent = seria_bml('div', array('id' => 'AutoTemplate_topcenter_content'))->setText('%%TOP%%');
		$ml = seria_bml('div', array('id' => 'AutoTemplate_expanding_layer'))->addChildren(array(
				seria_bml('div', array('id' => 'AutoTemplate_toplevel'))->addChildren(array(
				seria_bml_iecond('lt IE 8')->addChild('<table id=\'AutoTemplate_toplevel_ietable\' border=\'0\' cellspacing=\'0\' cellpadding=\'0\'><tr id=\'AutoTemplate_toplevel_ietable_head\'><td>'),
				seria_bml('div', array('id' => 'AutoTemplate_toprow'))->addChildren(array(
					seria_bml_iecond('lt IE 8')->addChild('<table class=\'AutoTemplate_ietable\' border=\'0\' cellspacing=\'0\' cellpadding=\'0\'><tr><td class=\'AutoTemplate_ietable_cell_left\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_topleft AutoTemplate_tcell', 'id' => 'AutoTemplate_topleft_id'))->addChild(
						seria_bml('div', array('id' => 'AutoTemplate_topleft_content'))->setText('%%TOPLEFT%%')
					),
					seria_bml_iecond('lt IE 8')->addChild('</td><td align=\'center\' class=\'AutoTemplate_ietable_center\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_topcenter AutoTemplate_tcell', 'id' => 'AutoTemplate_topcenter_id'))->addChildren(array(
						$topCenterContent,
						$printLogo
					)),
					seria_bml_iecond('lt IE 8')->addChild('</td><td class=\'AutoTemplate_ietable_cell_right\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_topright AutoTemplate_tcell', 'id' => 'AutoTemplate_topright_id')),
					seria_bml_iecond('lt IE 8')->addChild('</td></tr></table>')
				)),
				seria_bml_iecond('lt IE 8')->addChild('</td></tr><tr id=\'AutoTemplate_toplevel_ietable_contentrow\'><td>'),
				seria_bml('div', array('id' => 'AutoTemplate_contentrow'))->addChildren(array(
					seria_bml_iecond('lt IE 8')->addChild('<table class=\'AutoTemplate_ietable\' border=\'0\' cellspacing=\'0\' cellpadding=\'0\'><tr><td style=\'vertical-align: top; overflow: hidden;\' class=\'AutoTemplate_ietable_cell_left\' id=\'AutoTemplate_ietable_content_left\'>'),
					seria_bml('div', array('id' => 'AutoTemplate_content_idleft', 'class' => 'AutoTemplate_contentleft AutoTemplate_tcell'))->addChild(
						seria_bml('div', array('id' => 'AutoTemplate_content_idleftcontent'))->addChild('%%LEFT%%')
					),
					seria_bml_iecond('lt IE 8')->addChild('</td><td align=\'center\' class=\'AutoTemplate_ietable_center\' style=\'vertical-align: top; height: 100%; overflow: hidden;\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_contentcenter AutoTemplate_tcell'))->addChildren(array(
						seria_bml('div', array('id' => 'AutoTemplate_contentbg'))->addChildren(array(
							seria_bml('div', array('id' => 'AutoTemplate_contenttoprow'))->addChild(seria_bml('div', array('id' => 'AutoTemplate_contenttop'))),
							seria_bml('div', array('id' => 'AutoTemplate_contentsubrow'))->addChild(seria_bml('div', array('id' => 'AutoTemplate_content'))->setText('%%CONTENTS%%')),
							seria_bml('div', array('id' => 'AutoTemplate_contentbottomrow'))->addChild(seria_bml('div', array('id' => 'AutoTemplate_contentbottom'))->setText('%%CONTENT_BOTTOM%%'))
						))
					)),
					seria_bml_iecond('lt IE 8')->addChild('</td><td class=\'AutoTemplate_ietable_cell_right\' style=\'vertical-align: top; overflow: hidden;\' id=\'AutoTemplate_ietable_content_right\'>'),
					seria_bml('div', array('id' => 'AutoTemplate_content_idright', 'class' => 'AutoTemplate_contentright AutoTemplate_tcell'))->addChild(
						seria_bml('div')->addChild('%%RIGHT%%')
					),
					seria_bml_iecond('lt IE 8')->addChild('</td></tr></table>')
				)),
				seria_bml_iecond('lt IE 8')->addChild('</td></tr><tr id=\'AutoTemplate_toplevel_ietable_bottom\'><td>'),
				seria_bml('div', array('id' => 'AutoTemplate_bottomrow'))->addChildren(array(
					seria_bml_iecond('lt IE 8')->addChild('<table class=\'AutoTemplate_ietable\' border=\'0\' cellspacing=\'0\' cellpadding=\'0\'><tr><td class=\'AutoTemplate_ietable_cell_left\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_bottomleft AutoTemplate_tcell', 'id' => 'AutoTemplate_bottomleft_id')),
					seria_bml_iecond('lt IE 8')->addChild('</td><td align=\'center\' class=\'AutoTemplate_ietable_center\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_bottomcenter AutoTemplate_tcell', 'id' => 'AutoTemplate_bottomcenter_id'))->addChild('%%BOTTOM%%'),
					seria_bml_iecond('lt IE 8')->addChild('</td><td class=\'AutoTemplate_ietable_cell_right\'>'),
					seria_bml('div', array('class' => 'AutoTemplate_bottomright AutoTemplate_tcell', 'id' => 'AutoTemplate_bottomright_id')),
					seria_bml_iecond('lt IE 8')->addChild('</td></tr></table>')
				)),
				seria_bml_iecond('lt IE 8')->addChild('</td></tr></table>')
			)),
			/*$operascript,*/ /* javascript detects */
			/*seria_bml_iecond('gte IE 8')->addChild($ie8script),*/
			seria_bml_iecond('IE 7')->addChild($ie7expand),
			seria_bml_iecond('IE 6')->addChild($ie6script)
		));

		/*
		 * IE8 CSS
		 */
		$ie8css = '
#AutoTemplate_contentsubrow {'.($this->expandHeight ? '
	height: 100%;' : '').'
	vertical-align: top;
}
';
		/*
		 * CSS..
		 */
		$css = '';
		$css .= "
* {
	margin: 0px;
	padding: 0px;
}

html, body {".($this->expandHeight ? "
	height: 100%;" : '')."
	padding: 0px;
	margin: 0px;".($patternDefault === false ? "
	background: ".$backgroundColor." url(".$cacheHttpDir."/background_repeat.png) repeat-x top left;" : "
	background: ".$backgroundColor." url(".$cacheHttpDir."/pattern.png) repeat top ".($this->contentAlign == 'left' ? 'left' : 'right').";")."
	line-height: 0px;
	font-size: 0px;
}

body > * {
	font-size: 12px;
	line-height: 1.2em;
}

#AutoTemplate_expanding_layer {
	width: 100%;".($this->expandHeight ? "
	height: 100%;" : '')."
	padding: 0px;".($this->expandHeight ? "
	margin: 0px;" : "
	margin-top: 0px;
	margin-bottom: auto;
	margin-left: 0px;
	margin-right: 0px;").($patternDefault !== false ? "
	background: url(".$cacheHttpDir."/background_repeat.png) repeat-x top left;" : '')."
	line-height: 1.2;
	font-size: 12px;
}

#AutoTemplate_toplevel {
	display: table;".($this->expandHeight ? "
	height: 100%;
	margin-bottom: 0px;" : "
	margin-bottom: auto;")."
	margin-top: 0px;".($this->contentAlign == 'center' || $this->contentAlign == 'right' ? "
	margin-left: auto;" : "
	margin-left: 0px;").($this->contentAlign == 'center' || $this->contentAlign == 'left' ? "
	margin-right: auto;" : "
	margin-right: 0px;").($this->solidEdges ? "
	width: ".$width."px;" : "
	width: 100%;")."
	border-collapse: collapse;
}

#AutoTemplate_toprow {
	display: table-row;
}
#AutoTemplate_contentrow {
	display: table-row;"./*($this->expandHeight ? "
	height: 100%;" : '').*/"
}
#AutoTemplate_contentrow > div {".($this->expandHeight ? "
	height: 100%;" : '')."
}
#AutoTemplate_bottomrow {
	display: table-row;
}

div.AutoTemplate_tcell {
	display: table-cell;
}

.AutoTemplate_topleft {
	background: url(".$cacheHttpDir."/frame_topleft.png) no-repeat top right;".($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : ($this->contentAlign == 'left' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	height: ".$this->topHeight."px;
	overflow: hidden;
}
#AutoTemplate_topleft_content {".($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : ($this->contentAlign == 'left' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	height: ".$this->topHeight."px;
	text-align: left;
	overflow: hidden;
}
.AutoTemplate_topcenter {
	background: url(".$cacheHttpDir."/frame_topcenter.png) no-repeat top left;
	width: ".$cw."px;
	height: ".$this->topHeight."px;
	overflow: hidden;
}
a#AutoTemplate_topcenter_content {
	display: block;
}
#AutoTemplate_topcenter_content {
	width: ".$cw."px;
	height: ".$this->topHeight."px;
	text-align: left;
	overflow: hidden;
}
.AutoTemplate_topright {
	background: url(".$cacheHttpDir."/frame_topright.png) no-repeat top left;".($this->solidEdges ? "
	width: ".$this->rightWidth."px;" : ($this->contentAlign == 'right' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	height: ".$this->topHeight."px;
	overflow: hidden;
}

#AutoTemplate_content_idleft {
}
#AutoTemplate_content_idright {
}

.AutoTemplate_contentleft {".(!$this->transparentLeftSide ? (!$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_left_rep.png) repeat-y top right;" : "
	background: url(".$cacheHttpDir."/frame_left.png) no-repeat top right;") : '').($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : ($this->contentAlign == 'left' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	overflow: visible;
	vertical-align: top;
	margin: 0px;
}
#AutoTemplate_content_idleftcontent {".(!$this->transparentLeftSide && !$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_left.png) no-repeat top right;" : '').($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : '
	width: 100%;').($this->autoMinHeight ? "
	height: ".$ch."px;
	min-height: ".$ch."px;
	height: auto !important;" : '')."
	margin: 0px;
	text-align: left;
}
.AutoTemplate_contentcenter {
	overflow: hidden;
}
.AutoTemplate_contentright {".(!$this->transparentRightSide ? (!$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_right_rep.png) repeat-y top left;" : "
	background: url(".$cacheHttpDir."/frame_right.png) no-repeat top left;") : '').($this->solidEdges ? "
	width: ".$this->rightWidth."px;" : ($this->contentAlign == 'right' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	overflow: hidden;
	vertical-align: top;
	margin: 0px;
}
.AutoTemplate_contentright > div {".(!$this->transparentRightSide && !$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_right.png) no-repeat top left;" : '').($this->solidEdges ? "
	width: ".$this->rightWidth."px;" : '
	width: 100%;').($this->autoMinHeight ? "
	height: ".$ch."px;
	min-height: ".$ch."px;
	height: auto !important;" : '')."
	overflow: hidden;
	margin: 0px;
}

#AutoTemplate_contentbg {
	display: table;
	width: ".$cw."px;".($this->expandHeight ? "
	height: 100%;" : ($this->autoMinHeight ? "
	height: ".$ch."px;" : ''))."
	margin: 0px;
	padding: 0px;
	border-collapse: collapse;
}
#AutoTemplate_contentbg > div {
	display: table-row;
}
#AutoTemplate_contentsubrow {
	background: url(".$cacheHttpDir."/content_rep.png) repeat-y top left;
	width: ".$cw."px;".($this->expandHeight ? '
	/*height: 100%;*/' : '')."
}
#AutoTemplate_contenttoprow {
	height: ".$this->contentTopMargin."px;
}
#AutoTemplate_contenttop {
	display: table-cell;".($contentAreaTopMarg ? '
	background: url('.$cacheHttpDir.'/content_top.png) no-repeat top left;' : '')."
	width: ".$cw."px;
	height: ".$this->contentTopMargin."px;
}
#AutoTemplate_content {
	display: table-cell;".($this->autoMinHeight ? "
	background: url(".$cacheHttpDir."/content.png) no-repeat top left;" : '')."
	width: ".$cw."px;
	height: ".($ch-$this->contentMargin-$this->contentTopMargin)."px;
	min-height: ".($ch-$this->contentMargin-$this->contentTopMargin)."px;
	height: auto !important;
	overflow: hidden;
	text-align: left;
}
#AutoTemplate_contentbottomrow {
	height: ".$this->contentMargin."px;
}
#AutoTemplate_contentbottom {
	display: table-cell;".($contentAreaBottom ? "
	background: url(".$cacheHttpDir."/content_bottom.png) no-repeat top left;" : '')."
	width: ".$cw."px;
	height: ".$this->contentMargin."px;
}

.AutoTemplate_bottomleft {".($frameBottomLeft !== false ? "
	background: url(".$cacheHttpDir."/frame_bottomleft.png) no-repeat top right;" : '').($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : ($this->contentAlign == 'left' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	height: ".$this->bottomHeight."px;
}
.AutoTemplate_bottomcenter {".($frameBottomCenter !== false ? "
	background: url(".$cacheHttpDir."/frame_bottomcenter.png) no-repeat top left;" : '')."
	width: ".$cw."px;
	height: ".$this->bottomHeight."px;
	vertical-align: top;
}
.AutoTemplate_bottomright {".($frameBottomRight !== false ? "
	background: url(".$cacheHttpDir."/frame_bottomright.png) no-repeat top left;" : '').($this->solidEdges ? "
	width: ".$this->rightWidth."px;" : ($this->contentAlign == 'right' ? "
	width: 0px;" : ($this->contentAlign == 'center' ? "
	width: 50%;" : "
	width: 100%;")))."
	height: ".$this->bottomHeight."px;
}

/*
 * For Internet Explorer I've used a table instead of display table..
 */

/*
 * Without javascript..
 */
#AutoTemplate_toplevel_ietable {
	margin: 0px;
	padding: 0px;
	/* Commented out because this is a javascript not enabled fallback, and we cn't adjust the height */
	".($this->expandHeight ? "
	height: 100%;" : '')."

	margin-top: 0px;
	margin-bottom: 0px;".($this->contentAlign == 'center' || $this->contentAlign == 'left' ? "
	margin-left: auto;" : "
	margin-left: 0px;").($this->contentAlign == 'center' || $this->contentAlign == 'right' ? "
	margin-right: auto;" : "
	margin-right: 0px;")."
	width: 100%;
}
table.AutoTemplate_ietable {
	margin-top: 0px;
	margin-bottom: 0px;".($this->contentAlign == 'center' || $this->contentAlign == 'left' ? "
	margin-left: auto;" : "
	margin-left: 0px;").($this->contentAlign == 'center' || $this->contentAlign == 'right' ? "
	margin-right: auto;" : "
	margin-right: 0px;")."
	width: 100%;
}
#AutoTemplate_ietable_content_left {".(!$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_left_rep.png) repeat-y top right;" : '').($this->solidEdges ? "
	width: ".$this->leftWidth."px;" : '')."
	overflow: hidden;
}
#AutoTemplate_ietable_content_right {".(!$this->transparencyMode ? "
	background: url(".$cacheHttpDir."/frame_right_rep.png) repeat-y top left;" : '').($this->solidEdges ? "
	width: ".$this->rightWidth."px;" : '')."
	overflow: hidden;
}
td.AutoTemplate_ietable_cell_left {".($this->contentAlign == 'center' ? "
	width: 50%;" : ($this->contentAlign == 'left' ? "
	width: 0px;" : "
	width: 100%;"))."
	text-align: right;
}
td.AutoTemplate_ietable_cell_right {".($this->contentAlign == 'center' ? "
	width: 50%;" : ($this->contentAlign == 'right' ? "
	width: 0px;" : "
	width: 100%;"))."
}
td.AutoTemplate_ietable_center {
	overflow: hidden;
	width: ".$cw."px;
}
#AutoTemplate_toplevel_ietable_head {
	height: ".$this->topHeight."px;
}
#AutoTemplate_toplevel_ietable_head > td {
	height: ".$this->topHeight."px;
}
#AutoTemplate_toplevel_ietable_bottom {
	height: ".$this->bottomHeight."px;
}
#AutoTemplate_toplevel_ietable_bottom > td {
	height: ".$this->bottomHeight."px;
}

/*
 * With javascript..
 */
#AutoTemplate_toplevel_ie {
	margin: 0px;
	padding: 0px;".($this->expandHeight ? "
	height: 100%;" : '')."
}

#AutoTemplate_toplevel_ie > thead > tr > td {
	height: ".$this->topHeight."px;
}
#AutoTemplate_toplevel_ie > tfoot > tr > td {
	height: ".$this->bottomHeight."px;
}

#AutoTemplate_content_innertable {
	width: ".$cw."px;
	height: 100%;
}
#AutoTemplate_content_area_ie > td {
	background: url(".$cacheHttpDir."/content.png) no-repeat top left;
	width: ".$cw."px;
	height: ".($ch-$this->contentMargin)."px;
}
#AutoTemplate_content_bottom_ie > td {
	background: url(".$cacheHttpDir."/content_bottom.png) no-repeat top left;
	width: ".$cw."px;
	height: ".$this->contentMargin."px;
}

#AutoTemplate_printlogo {
	display: none;
	width: ".($this->printLogo !== false ? $printLogoWidth : 0)."px;
	height: ".($this->printLogo !== false ? $printLogoHeight : 0)."px;
}

";

		$print_css = '';

		$print_css .=
"

.AutoTemplate_topleft {
	height: 0px !important;
}
.AutoTemplate_topcenter {
	height: ".($printLogo !== false ? $printLogoHeight : 0)."px !important;
}
.AutoTemplate_topright {
	height: 0px !important;
}

#AutoTemplate_toplevel_ietable_head {
	height: ".($printLogo !== false ? $printLogoHeight : 0)."px !important;
}
#AutoTemplate_toplevel_ietable_head > td {
	height: 0px !important;
}
#AutoTemplate_toplevel_ietable_head > td.AutoTemplate_ietable_center {
	height: ".($printLogo !== false ? $printLogoHeight : 0)."px !important;
}

#AutoTemplate_printlogo {
	display: inline !important;
}

/*
 * Remove all backgrounds for printing..
 */
html, body {
	background: none !important;
}
.AutoTemplate_topleft {
	background: none !important;
}
.AutoTemplate_topcenter {
	background: none !important;
}
.AutoTemplate_topright {
	background: none !important;
}
.AutoTemplate_contentleft {
	background: none !important;
}
.AutoTemplate_contentleft > div {
	background: none !important;
}
.AutoTemplate_contentright {
	background: none !important;
}
.AutoTemplate_contentright > div {
	background: none !important;
}
#AutoTemplate_contentbg {
	background: none !important;
}
#AutoTemplate_content {
	background: none !important;
}
#AutoTemplate_contentbottom {
	background: none !important;
}
.AutoTemplate_bottomleft {
	background: none !important;
}
.AutoTemplate_bottomcenter {
	background: none !important;
}
.AutoTemplate_bottomright {
	background: none !important;
}

";

		/*
		 * Return the data..
		 */
		return array(
			'name' => $pinfo['filename'],
			'css' => $css,
			'ie8css' => $ie8css,
			'print_css' => $print_css,
			'html' => $ml->output(),
			'images' => array(
				'content_top.png' => $contentAreaTopMarg,
				'content.png' => $contentAreaTop,
				'content_rep.png' => $contentAreaRepeating,
				'content_bottom.png' => $contentAreaBottom,
				'frame_topleft.png' => $frameTopLeft,
				'frame_topcenter.png' => $frameTopCenter,
				'frame_topright.png' => $frameTopRight,
				'frame_left.png' => $frameLeft,
				'frame_right.png' => $frameRight,
				'frame_left_rep.png' => $frameLeftRepeating,
				'frame_right_rep.png' => $frameRightRepeating,
				'frame_bottomleft.png' => $frameBottomLeft,
				'frame_bottomcenter.png' => $frameBottomCenter,
				'frame_bottomright.png' => $frameBottomRight,
				'background_repeat.png' => $backgroundGradient,
				'pattern_side.png' => $patternSide,
				'pattern.png' => $patternDefault,
				'pattern_background.png' => $patternBackground,
				'pattern_bottom.png' => $patternBottom
			),
			'copy' => array(
				$this->printLogo => $printLogoFilename
			)
		);
	}
}

?>