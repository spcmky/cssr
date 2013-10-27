<?php

namespace Cssr\MainBundle\Model;

class Group {

    public static function isGranted ( $user, $permission ) {
        if ( in_array($permission,self::getPermissions($user)) ) {
            return true;
        } else {
            return false;
        }
    }

    public static function getPermissions ( $user ) {
        $userGroupIds = [];
        foreach ( $user->getGroups() as $group ) {
            $userGroupIds[] = $group->getId();
        }

        $groupPrecedent = min($userGroupIds);

        if ( $groupPrecedent == 1 ) {
            return array(
                'center create',
                'center update',
                'center delete',
                'user update',
                'corp admin update',
                'center admin update',
                'center act update',
                'center mgr update',
                'staff update',
                'student update',
                'score admin',
                'score update',
                'reports',
                'report admin',
                'report stats',
                'messages'
            );
        } else if ( $groupPrecedent == 2 ) {
            return array(
                'center update',
                'user update',
                'center act update',
                'center mgr update',
                'staff update',
                'student update',
                'score admin',
                'score update',
                'reports',
                'report admin',
                'report stats',
                'messages'
            );
        } else if ( $groupPrecedent == 3 ) {
            return array(
                'center update',
                'user update',
                'center act update',
                'center mgr update',
                'staff update',
                'student update',
                'reports',
                'report admin'
            );
        } else if ( $groupPrecedent == 4 ) {
            return array(
                'user update',
                'score admin',
                'score update',
                'reports',
                'report admin'
            );
        } else if ( $groupPrecedent == 5 ) {
            return array(
                'user update',
                'score update',
                'reports',
                'report admin'
            );

        } else if ( $groupPrecedent == 6 ) {
            return array(
                'user update',
                'reports'
            );
        }

        return array();
    }
}