<?php
/**
 * Hooks for CategoryWatch extension
 *
 * Copyright (C) 2017  Mark A. Hershberger
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace CategoryWatch;

use Content;
use Status;
use Title;
use User;
use WikiPage;

class Hook {
	// Instance
	protected static $watcher;

	const CATWATCH = 'catwatch';

	/**
	 * Instantiate CategoryWatch object and get categories
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSave
	 * @param WikiPage $wikiPage the page
	 * @param User $user who is modifying
	 * @param Content $content the new article content
	 * @param string $summary the article summary (comment)
	 * @param bool $isMinor minor flag
	 * @param bool $isWatch watch flag (not used, aka always null)
	 * @param int $section section number (not used, aka always null)
	 * @param int $flags see WikiPage::doEditContent documentation for flags' definition
	 * @param Status $status Status (object)
	 */
	public static function onPageContentSave(
		WikiPage $wikiPage, User $user, Content $content, $summary, $isMinor,
		$isWatch, $section, $flags, Status $status
	) {
		self::$watcher = new CategoryWatch(
			$wikiPage, $user, $content, $summary, $isMinor, $flags
		);
	}

	/**
	 * The proper hook for save page request.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 * @param WikiPage $article Article edited
	 * @param User $user who edited
	 * @param Content $content New article text
	 * @param string $summary Edit summary
	 * @param bool $isMinor Minor edit or not
	 * @param bool $isWatch Watch this article?
	 * @param string $section Section that was edited
	 * @param int $flags Edit flags
	 * @param Revision $revision that was created
	 * @param Status $status of activities
	 * @param int $baseRevId base revision
	 */
	public static function onPageContentSaveComplete(
		WikiPage $article, User $user, Content $content, $summary, $isMinor, $isWatch,
		$section, $flags, Revision $revision, Status $status, $baseRevId
	) {
		self::$watcher->notifyCategoryWatchers( $revision, $baseRevId );
	}

	/**
	 * Send notifications of categorization changes
	 * Doing it here because core is hard-coded not to send notifications
	 * when rc_type == RC_CATEGORIZE
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RecentChanges_save
	 * @param RecentChange $rc the RC object
	 */
	public static function onRecentChangeSave( RecentChange $rc ) {
		$attr = $rc->getAttributes();
		if ( $attr['rc_type'] !== RC_CATEGORIZE ) {
			return;
		}

		$editor = $rc->getPerformer();
		$category = $rc->getTitle();
		$title = Title::newFromID( $attr['rc_cur_id'] );
		$summary = $attr['rc_comment'];
		$params = unserialize( $attr['rc_params'] );
		$ts = $attr['rc_timestamp'];
		$oldId = $attr['rc_last_oldid'];
		$added = $rc->getParam( 'added' );
		$watchers = MediaWikiServices::getInstance()->getWatchedItemStore()
				  ->updateNotificationTimestamp( $editor, $category, $timestamp );

		$enotif = new EmailNotification();
		$enotif->actuallyNotifyOnPageChange(
			$editor, $category, $ts, $summary, false, $oldId, $watchers, self::CATWATCH
		);
	}

	/**
	 * Add our pagestatus to the list of valid status
	 * @param array &$fps formattedPageSatus variable
	 */
	public static function onUpdateUserMailerFormattedPageStatus( array &$fps ) {
		$fps[] = self::CATWATCH;
	}

	/**
	 * EVIL, PURE EVIL
	 */
	protected static function accessProtected( $obj, $prop ) {
		$reflection = new \ReflectionClass( $obj );
		$property = $reflection->getProperty( $prop );
		$property->setAccessible( true );
		return $property->getValue( $obj );
	}

	/**
	 * Override actually sending email notifications
	 * @param User $watchingUser who is getting the notice
	 * @param Title $category that they are watching
	 * @param EmailNotification $enotif has useful info
	 * @return bool false when we dealt with our status
	 */
	public static function onSendWatchlistEmailNotification(
		User $watchingUser, Title $category, EmailNotification $enotif
	) {
		if ( self::accessProtected( $enotif, 'pageStatus' ) !== self::CATWATCH ) {
			return true;
		}

		wfDebugLog( __METHOD__, "Sending an email now!" );
		return false;
	}
}
