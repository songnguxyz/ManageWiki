<?php

class ManageWikiHooks {
	public static function onRegistration() {
		global $wgLogTypes;

		if ( !in_array( 'farmer', $wgLogTypes ) ) {
			$wgLogTypes[] = 'farmer';
		}
	}

	public static function onSetupAfterCache() {
		global $wgManageWikiPermissionsManagement, $wgGroupPermissions, $wgAddGroups, $wgRemoveGroups, $wgCreateWikiDatabase, $wgDBname;

		// Safe guard if - should not remove all existing settigs if we're not managing permissions with in.
		if ( $wgManageWikiPermissionsManagement ) {
			$wgGroupPermissions = [];
			$wgAddGroups = [];
			$wgRemoveGroups = [];

			$dbr = wfGetDB( DB_REPLICA, [], $wgCreateWikiDatabase );

			$res = $dbr->select(
				'mw_permissions',
				[ 'perm_group', 'perm_permissions', 'perm_addgroups', 'perm_removegroups' ],
				[ 'perm_dbname' => $wgDBname ],
				__METHOD__
			);

			foreach ( $res as $row ) {
				$permsjson = json_decode( $row->perm_permissions );

				foreach ( (array)$permsjson as $perm ) {
					$wgGroupPermissions[$row->perm_group][$perm] = true;
				}

				$wgAddGroups[$row->perm_group] = json_decode( $row->perm_addgroups );
				$wgRemoveGroups[$row->perm_group] = json_decode( $row->perm_removegroups );
			}
		}
	}

	public static function fnNewSidebarItem( $skin, &$bar ) {
		global $wgManageWikiSidebarLinks;
		if (
			$skin->getUser()->isAllowed( 'managewiki' ) &&
			$wgManageWikiSidebarLinks
		) {
			$bar['Administration'][] = [
				'text' => wfMessage( 'managewiki-settings-link' )->plain(),
				'id' => 'managewikilink',
				'href' => htmlspecialchars( SpecialPage::getTitleFor( 'ManageWiki' )->getFullURL() )
			];
			$bar['Administration'][] = [
				'text' => wfMessage( 'managewiki-extensions-link' )->plain(),
				'id' => 'managewikiextensionslink',
				'href' => htmlspecialchars( SpecialPage::getTitleFor( 'ManageWikiExtensions' )->getFullURL() )
			];
		}
	}
}