<?php

namespace Cssr\MainBundle\Twig;

use Cssr\MainBundle\Model\Group;

class CssrExtension extends \Twig_Extension {

    public function getFunctions () {
        return array(
            new \Twig_SimpleFunction('user_is_granted', array($this, 'isGranted')),
        );
    }

    public function isGranted ( $user, $permission ) {
        return Group::isGranted($user,$permission);
    }

    public function getName () {
        return 'cssr_extension';
    }
}