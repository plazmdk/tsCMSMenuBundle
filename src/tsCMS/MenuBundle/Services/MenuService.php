<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 4/16/14
 * Time: 5:04 PM
 */

namespace tsCMS\MenuBundle\Services;


use Doctrine\ORM\EntityManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Component\Routing\RouterInterface;
use tsCMS\MenuBundle\Entity\Menu;
use tsCMS\MenuBundle\Entity\MenuItem;
use tsCMS\SystemBundle\Entity\Route;
use tsCMS\SystemBundle\Event\BuildSiteStructureEvent;
use tsCMS\SystemBundle\Event\UpdateRouteEvent;
use tsCMS\SystemBundle\Model\SiteStructureAction;
use tsCMS\SystemBundle\Model\SiteStructureGroup;
use tsCMS\SystemBundle\Model\SiteStructureTree;

class MenuService {
    /** @var \Doctrine\ORM\EntityManager  */
    private $entityManager;
    /** @var RouterInterface */
    private $router;

    function __construct(EntityManager $entityManager, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
    }


    public function onBuildSiteStructure(BuildSiteStructureEvent $event) {
        $menuGroup = new SiteStructureGroup("Menu","fa-list");

        $menuGroup->addElement(new SiteStructureAction("Opret menu",$this->router->generate("tscms_menu_menu_create")));

        $treeRoot = new SiteStructureTree(null,null);
        /** @var Menu[] $menus */
        $menus = $this->entityManager->getRepository("tsCMS\MenuBundle\Entity\Menu")->findAll();
        foreach ($menus as $menu) {
            $menuRoot = new SiteStructureTree($menu->getId(),$menu->getTitle());
            $menuRoot->setAction(new SiteStructureAction("Redigér",$this->router->generate("tscms_menu_menu_edit",array("id" => $menu->getId()))));
            $menuRoot->addContextmenuAction(new SiteStructureAction("Opret underpunkt",$this->router->generate("tscms_menu_menu_createitem",array("id" => $menu->getId()))));
            $menuRoot->setSortCallback($this->router->generate("tscms_menu_menu_sortcallback"));

            /** @var NestedTreeRepository $repo */
            $repo = $this->entityManager->getRepository('tsCMS\MenuBundle\Entity\MenuItem');

            $menuRootNode = $repo->findOneBy(array("menu" => $menu, "parent" => null));
            $arrayTree = $repo->childrenHierarchy($menuRootNode);
            $this->visitNode($menuRoot, $arrayTree, $menu->getId());

            $treeRoot->addSubtreeElement($menuRoot);
        }

        $menuGroup->addElement($treeRoot);

        $event->addElement($menuGroup);
    }

    public function getMenu($name) {
        /** @var Menu $menu */
        $menu = $this->entityManager->getRepository("tsCMS\MenuBundle\Entity\Menu")->findOneBy(array("title" => $name));

        /** @var NestedTreeRepository $repo */
        $repo = $this->entityManager->getRepository('tsCMS\MenuBundle\Entity\MenuItem');

        $menuRootNode = $repo->findOneBy(array("menu" => $menu, "parent" => null));

        return $repo->childrenHierarchy($menuRootNode);
    }

    private function visitNode(SiteStructureTree $node, $roots, $menuId) {
        foreach ($roots as $root) {
            $newNode = new SiteStructureTree($root['id'],$root['title']);
            $newNode->setAction(new SiteStructureAction("Redigér",$this->router->generate("tscms_menu_menu_edititem",array("id" => $root['id']))));

            $newNode->addContextmenuAction(new SiteStructureAction("Opret underpunkt",$this->router->generate("tscms_menu_menu_createitem",array("id" => $menuId))));

            $this->visitNode($newNode,$root['__children'],$menuId);
            $node->addSubtreeElement($newNode);
        }
    }

    public function onRouteUpdate(UpdateRouteEvent $event) {
        /** @var MenuItem $menuItem */
        $menuItem = $this->entityManager->getRepository('tsCMS\MenuBundle\Entity\MenuItem')->findOneBy(array("route" => $event->getRoute()));
        if ($menuItem) {
            if ($event->getPath()) {
                $menuItem->setPath($event->getPath());
                if ($menuItem->getStickyTitle()) {
                    $menuItem->setTitle($event->getTitle());
                }
            } else {
                $this->entityManager->remove($menuItem);
            }
            $this->entityManager->flush();
        }
    }
} 