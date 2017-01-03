<?php
/**
 * @package     CedFacebookComments
 * @subpackage  com_cedfacebookcomments
 * http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL 3.0</license>
 * @copyright   Copyright (C) 2013-2016 galaxiis.com All rights reserved.
 * @license     The author and holder of the copyright of the software is Cédric Walter. The licensor and as such issuer of the license and bearer of the
 *              worldwide exclusive usage rights including the rights to reproduce, distribute and make the software available to the public
 *              in any form is Galaxiis.com
 *              see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class plgContentCedFacebookComments extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		//Do not run in admin area and non HTML  (rss, json, error)
		$app = JFactory::getApplication();
		if ($app->isAdmin() || JFactory::getDocument()->getType() !== 'html')
		{
			return true;
		}
		// Return if we don't have a valid article id
		if (!isset($row->id) || !(int) $row->id)
		{
			return true;
		}

		$print    = JFactory::getApplication()->input->get('print') == 1;
		$isActive = JFactory::getApplication()->input->getWord('view') == 'article' && $context == 'com_content.article' && !$print;
		if ($isActive)
		{
			$this->addWidget($row, true);
		}
	}

	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{
		//Do not run in admin area
		$app = JFactory::getApplication();
		if ($app->isAdmin())
		{
			return true;
		}

		if ($this->params->get('counter', 0))
		{
			$isActive = JFactory::getApplication()->input->getWord('view') != 'article' && ($context == 'com_content.featured' || $context == 'com_content.category');
			if ($isActive)
			{
				return $this->addWidget($row, false);
			}
		}
	}

	private function addWidget(&$row, $view)
	{
//        $categories = $this->getCategories($row);
//        $categories = $this->getChildCategories($categories);
//        $menus = $this->getMenuIds();

		if ($this->isActiveInCategory($row->catid) == false)
		{
			return;
		}

		$facebookId = $this->params->get('appId');
		$document   = JFactory::getDocument();

		$document->setMetaData("fb:app_id", $facebookId);


		$moderatorIds = $this->params->get('moderatorId');
		if ($moderatorIds != '')
		{
			$document->setMetaData("fb:admins", trim($moderatorIds));
		}

		//Adjust Language
		// You can adjust the language of the Comments plugin by loading a localized version of the Facebook
		// SDK for JavaScript. When you load the SDK adjust the value js.src to use your locale:
		// Just replaced en_US by your locale, e.g. fr_FR for French (France):
		$lang = JFactory::getLanguage();
		$lang = $lang->getTag();
		$lang = str_replace("-", "_", $lang);

		$output = "<div id=\"fb-root\"></div>
                    <script>(function(d, s, id) {
                      var js, fjs = d.getElementsByTagName(s)[0];
                      if (d.getElementById(id)) return;
                      js = d.createElement(s); js.id = id;
                      js.src = \"//connect.facebook.net/" . $lang . "/sdk.js#xfbml=1&appId=" . $facebookId . "&version=v2.0\";
                      fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));";

		$uri = JUri::getInstance();
		if ($view == 'article')
		{
			$output .= '<!-- Copyright (C) 2013-2016 galaxiis.com All rights reserved. -->';
			$output .= '<div class="fb-comments"
             data-version="v2.3"
             data-href="' . $uri->toString() . '"
             data-width="' . $this->params->get('width', '550') . '"
             data-numposts="' . $this->params->get('numposts', '10') . '"
             order_by="' . $this->params->get('order', 'time') . '"
             data-colorscheme="' . $this->params->get('color', 'light') . '"></div>';

			$row->text .= $output;
		}
		else
		{
			require_once(JPATH_ROOT . '/components/com_content/helpers/route.php');
			$link = $uri->toString(array('scheme', 'host')) . JRoute::_(ContentHelperRoute::getArticleRoute($row->id,
					$row->catid));

			$icon = "";
			if ($this->params->get('showIcon', 1))
			{
				$document = JFactory::getDocument();
				$document->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');

				$icon = '<span class="fa ' . $this->params->get('icon', 'fa-comment') . ' pull-left fa-border"></span>';
			}

			$output = '<a href="' . $link . '#g-comments">' . $icon . '<fb:comments-count href="' . $link . '"></fb:comments-count></a>';

			return $output;
		}

	}

	public function isActiveInCategory($categoryId)
	{
		$categoryMode       = intval($this->params->get('categoryMode', 0));
		$selectedCategories = $this->params->get('includedCatIds');

		if ($categoryMode == 0)
		{
			return true;
		}

		if ($categoryMode == 1)
		{
			if ($selectedCategories == null)
			{
				return false;
			}

			return $this->isSelectedInCategory($selectedCategories, $categoryId);
		}

		return !$this->isSelectedInCategory($selectedCategories, $categoryId);
	}

	private function isSelectedInCategory($selectedCategories, $categoryId) {
		$match = false;
		if (is_array($selectedCategories))
		{
			foreach ($selectedCategories as $category)
			{
				if ($category === "")
				{ // all category is in the list
					return true;
				}
				if (strcmp(trim($category), $categoryId) == 0)
				{
					$match = true;
				}
			}
		}
		return $match;
	}

}
