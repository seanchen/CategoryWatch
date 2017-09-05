<?php
/**
 * CategoryWatch extension
 * - Extends watchlist functionality to include notification about membership
 *   changes of watched categories
 *
 * Copyright (C) 2008  Aran Dunkley
 * Copyright (C) 2017  Sean Chen
 * Copyright (C) 2017  Mark A. Hershberger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 *
 * See https://www.mediawiki.org/Extension:CategoryWatch
 *     for installation and usage details
 * See http://www.organicdesign.co.nz/Extension_talk:CategoryWatch
 *     for development notes and disucssion
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2008 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

namespace CategoryWatch;

use SpecialPage;
use Title;
use User;
use UserMailer;

class Hook {
	// Instance
	protected static $watcher;

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
		$wikiPage, $user, $content, $summary, $isMinor,
		$isWatch, $section, $flags, $status
	) {
        self::$watcher = new CategoryWatch(
            $wikiPage, $user, $content, $summary, $isMinor, $flags
        );
	}

	/**
	 * the proper hook for save page request.
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
		$article, $user, $content, $summary, $isMinor, $isWatch, $section,
		$flags, $revision, $status, $baseRevId
	) {
        self::$watcher->notifyCategoryWatchers( $revision, $baseRevId );
	}
}
