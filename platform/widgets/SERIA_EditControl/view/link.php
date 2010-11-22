<?php
$article = SERIA_NamedObjects::getInstanceOf($this->getGUID());
?><a href='<?php echo SERIA_HTTP_ROOT; ?>seria/articles.php?id=<?php echo $article->get('id'); ?>' title='<?php echo _t('Edit'); ?>'><?php echo _t('Edit');?></a>