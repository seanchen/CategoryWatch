<?php

/**
 * Category watch events
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

use RawMessage;
use Title;
use WikiPage;

class EchoEventPresentationModel extends \EchoEventPresentationModel {
	/**
	 * Tell the caller if this event can be rendered.
	 *
	 * @return bool
	 */
	public function canRender() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		return (bool)$this->event->getTitle();
	}

	/**
	 * Which of the registered icons to use.
	 *
	 * @return string
	 */
	public function getIconType() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		return 'categorywatch';
	}

	/**
	 * The header of this event's display
	 *
	 * @return Message
	 */
	public function getHeaderMessage() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		if ( $this->isBundled() ) {
			$msg = $this->msg( 'categorywatch-notification-bundle' );
			$msg->params( $this->getBundleCount() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			$msg->params( $this->getViewingUserForGender() );
		} else {
			$msg = $this->msg( 'categorywatch-notification-' . $this->event->getType() . '-header' );
			$msg->params( $this->getPageTitle() );
			$msg->params( $this->getTruncatedTitleText( $this->getPageTitle(), true ) );
			$msg->params( $this->event->getTitle() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		}
		return $msg;
	}

	/**
	 * Shorter display
	 *
	 * @return Message
	 */
	public function getCompactHeaderMessage() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$msg = parent::getCompactHeaderMessage();
		$msg->params( $this->getViewingUserForGender() );
		return $msg;
	}

	/**
	 * Summary of edit
	 *
	 * @return string
	 */
	public function getRevisionEditSummary() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$msg = $this->getMessageWithAgent( 'categorywatch-notification-' . $this->event->getType() . '-summary' );
		$msg->params( $this->getPageTitle() );
		$msg->params( $this->getTruncatedTitleText( $this->getPageTitle(), true ) );
		$msg->params( $this->event->getTitle() );
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		return $msg;
	}

	/**
	 * Body to display
	 *
	 * @return Message
	 */
	public function getBodyMessage() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$msg = $this->getMessageWithAgent( 'categorywatch-notification-' .
										   $this->event->getType() . '-body' );
		$msg->params( $this->getPageTitle() );
		$msg->params( $this->event->getTitle() );
		return $msg;
	}

	/**
	 * Title of page
	 *
	 * @return Title|string
	 */
	public function getPageTitle() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$page = WikiPage::newFromId( $this->event->getExtraParam( "pageid" ) );
		return $page ? $page->getTitle() : new Title();
	}

	/**
	 * Provide the main link
	 *
	 * @return string
	 */
	public function getPrimaryLink() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$title = $this->event->getTitle();
		$msg = $this->msg( 'categorywatch-notification-link' );
		$msg->params( $title );
		return [
			'url' => $title->getFullURL(),
			'label' => $title->getPrefixedText()
		];
	}

	/**
	 * Aux links
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		if ( $this->isBundled() ) {
			// For the bundle, we don't need secondary actions
			return [];
		} else {
			return [
				$this->getAgentLink(),
				[
					'url' => $this->getPageTitle()->getFullURL(),
					'label' => $this->getPageTitle()->getPrefixedText()
				]
			];
		}
	}

	/**
	 * override parent
	 * @return array
	 * @throws TimestampException
	 */
	public function jsonSerialize() {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$body = $this->getBodyMessage();

		return [
			'header' => $this->getHeaderMessage()->parse(),
			'compactHeader' => $this->getCompactHeaderMessage()->parse(),
			'body' => $body ? $body->toString() : '',
			'icon' => $this->getIconType(),
			'links' => [
				'primary' => $this->getPrimaryLinkWithMarkAsRead() ?: [],
				'secondary' => array_values( array_filter( $this->getSecondaryLinks() ) ),
			],
		];
	}
}