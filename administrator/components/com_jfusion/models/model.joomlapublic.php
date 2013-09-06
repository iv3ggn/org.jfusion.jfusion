<?php

/**
 * Model for joomla actions
 *
 * PHP version 5
 *
 * @category  JFusion
 * @package   Models
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the JFusion framework
 */

/**
 * Common Class for Joomla JFusion plugins
 *
 * @category  JFusion
 * @package   Models
 * @author    JFusion Team <webmaster@jfusion.org>
 * @copyright 2008 JFusion. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link      http://www.jfusion.org
 */
class JFusionJoomlaPublic extends JFusionPublic
{
	/**
	 * Returns the registration URL for the integrated software
	 *
	 * @return string registration URL
	 */
	public function getRegistrationURL()
	{
		$url = 'index.php?option=com_users&view=registration';
		return $url;
	}

	/**
	 * Returns the lost password URL for the integrated software
	 *
	 * @return string lost password URL
	 */
	public function getLostPasswordURL()
	{
		$url = 'index.php?option=com_users&view=reset';
		return $url;
	}

	/**
	 * Returns the lost username URL for the integrated software
	 *
	 * @return string lost username URL
	 */
	public function getLostUsernameURL()
	{
		$url = 'index.php?option=com_users&view=remind';
		return $url;
	}

	/**
	 * Returns a query to find online users
	 * Make sure columns are named as userid, username, username_clean (if applicable), name (of user), and email
	 *
	 * @param int $limit integer to use as a limiter for the number of results returned
	 *
	 * @return string online user query
	 */
	public function getOnlineUserQuery($limit)
	{
		$db = JFusionFactory::getDatabase($this->getJname());

		$limiter = (!empty($limit)) ? ' LIMIT 0,'.$limit : '';

		$query = $db->getQuery(true)
			->select('DISTINCT u.id AS userid, u.username, u.name, u.email')
			->from('#__users AS u')
			->innerJoin('#__session AS s ON u.id = s.userid')
			->where('s.client_id = 0')
			->where('s.guest = 0');

		$query = (string)$query;
		return $query.$limiter;
	}

	/**
	 * Returns number of guests
	 *
	 * @return int
	 */
	public function getNumberOnlineGuests()
	{
		$db = JFusionFactory::getDatabase($this->getJname());

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__session')
			->where('guest = 1')
			->where('client_id = 0')
			->where('usertype = ' . $db->Quote(''));

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Returns number of logged in users
	 *
	 * @return int
	 */
	public function getNumberOnlineMembers()
	{
		$db = JFusionFactory::getDatabase($this->getJname());

		$query = $db->getQuery(true)
			->select('COUNT(DISTINCT userid) AS c')
			->from('#__session')
			->where('guest = 0')
			->where('client_id = 0');

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Update the language front end param in the account of the user if this one changes it
	 * NORMALLY THE LANGUAGE SELECTION AND CHANGEMENT FOR JOOMLA IS PROVIDED BY THIRD PARTY LIKE JOOMFISH
	 *
	 * @param object $userinfo userinfo
	 *
	 * @return array status
	 */
	public function setLanguageFrontEnd($userinfo)
	{
		$status = array('error' => array(),'debug' => array());
		$user = JFusionFactory::getUser($this->getJname());
		$existinguser = (isset($userinfo)) ? $user->getUser($userinfo) : null;
		// If the user is connected we change his account parameter in function of the language front end
		if ($existinguser) {
			$userinfo->language = JFactory::getLanguage()->getTag();
			$user->updateUserLanguage($userinfo, $existinguser, $status, $this->getJname());
		} else {
			$status['debug'] = JText::_('NO_USER_DATA_FOUND');
		}
		return $status;
	}
}