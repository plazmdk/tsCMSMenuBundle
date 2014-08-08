<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 4/16/14
 * Time: 7:23 PM
 */

namespace tsCMS\MenuBundle\Twig;


use Symfony\Component\HttpFoundation\RequestStack;
use tsCMS\MenuBundle\Services\MenuService;
use tsCMS\SystemBundle\Services\SiteStructureService;

class MenuExtension extends \Twig_Extension {
    /** @var MenuService */
    private $menuService;

    function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('menu', array($this, 'getMenu')),
        );
    }

    public function getMenu($name) {

        return $this->menuService->getMenu($name);
    }

    public function getName()
    {
        return 'menu_extension';
    }
} 