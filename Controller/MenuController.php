<?php

namespace tsCMS\MenuBundle\Controller;

use Doctrine\ORM\EntityManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use tsCMS\MenuBundle\Entity\Menu;
use tsCMS\MenuBundle\Entity\MenuItem;
use tsCMS\MenuBundle\Form\MenuItemType;
use tsCMS\MenuBundle\Form\MenuType;
use tsCMS\SystemBundle\Services\RouteService;

/**
 * @Route("/menu")
 */
class MenuController extends Controller
{
    /**
     * @Route("/create")
     * @Template("tsCMSMenuBundle:Menu:menu.html.twig")
     */
    public function createAction(Request $request)
    {
        $menu = new Menu();
        $menuForm = $this->createForm(new MenuType(), $menu);
        $menuForm->handleRequest($request);
        if ($menuForm->isValid()) {
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $em->persist($menu);

            $menuItem = new MenuItem();
            $menuItem->setMenu($menu);
            $menuItem->setTitle($menu->getTitle());
            $menuItem->setStickyTitle(true);

            $em->persist($menuItem);

            $em->flush();

            return $this->redirect($this->generateUrl("tscms_menu_menu_edit",array("id" => $menu->getId())));
        }

        return array(
            "menu" => null,
            "form" => $menuForm->createView()
        );
    }
    /**
     * @Route("/edit/{id}")
     * @Template("tsCMSMenuBundle:Menu:menu.html.twig")
     */
    public function editAction(Request $request, Menu $menu) {
        $menuForm = $this->createForm(new MenuType(), $menu);
        $menuForm->handleRequest($request);
        if ($menuForm->isValid()) {
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirect($this->generateUrl("tscms_menu_menu_edit",array("id" => $menu->getId())));
        }
        return array(
            "menu" => $menu,
            "form" => $menuForm->createView()
        );
    }
    /**
     * @Route("/delete/{id}")
     */
    public function deleteAction(Menu $menu) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $em->remove($menu);
        $em->flush();

        return $this->redirect($this->generateUrl("tscms_system_default_index"));
    }
    /**
     * @Route("/sortCallback")
     */
    public function sortCallbackAction(Request $request) {
        $sourceId = $request->request->get("source");
        $action = $request->request->get("action");
        $targetId = $request->request->get("target");

        /** @var NestedTreeRepository $repo */
        $repo = $this->getDoctrine()->getManager()->getRepository('tsCMS\MenuBundle\Entity\MenuItem');
        /** @var Menu $source */
        $source = $repo->find($sourceId);
        /** @var Menu $target */
        $target = $repo->find($targetId);

        $nextSiblings = $repo->getNextSiblings($source);
        $targetPosition = array_search($target,$nextSiblings);
        if ($targetPosition !== false) {
            if ($action == "after") {
                $targetPosition++;
            }
            $repo->moveDown($source, $targetPosition);
        } else {
            $previousSiblings = $repo->getPrevSiblings($source);
            $previousSiblings = array_reverse($previousSiblings);
            $targetPosition = array_search($target,$previousSiblings);
            if ($targetPosition !== false) {
                if ($action == "before") {
                    $targetPosition++;
                }
                $repo->moveUp($source,$targetPosition);
            }
        }

        return new Response();
    }



    /**
     * @Route("/{id}/createItem")
     * @Template("tsCMSMenuBundle:Menu:menuItem.html.twig")
     */
    public function createItemAction(Request $request, Menu $menu)
    {
        $menuItem = new MenuItem();
        $menuItem->setMenu($menu);
        $menuItemForm = $this->createForm(new MenuItemType($menu, null, $this->get('router'), $this->getDoctrine()->getManager()), $menuItem);
        $menuItemForm->handleRequest($request);

        if ($menuItemForm->isValid()) {
            $menuItem->setPath($menuItem->getRoute()->getPath());
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $em->persist($menuItem);
            $em->flush();

            return $this->redirect($this->generateUrl("tscms_menu_menu_edititem",array("id" => $menuItem->getId())));
        }

        return array(
            "menuItem" => null,
            "form" => $menuItemForm->createView()
        );
    }
    /**
     * @Route("/editItem/{id}")
     * @Template("tsCMSMenuBundle:Menu:menuItem.html.twig")
     */
    public function editItemAction(Request $request, MenuItem $menuItem) {
        $menuItemForm = $this->createForm(new MenuItemType($menuItem->getMenu(),$menuItem,$this->get('router'),$this->getDoctrine()->getManager()), $menuItem);
        $menuItemForm->handleRequest($request);
        if ($menuItemForm->isValid()) {
            $menuItem->setPath($menuItem->getRoute()->getPath());
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirect($this->generateUrl("tscms_menu_menu_edititem",array("id" => $menuItem->getId())));
        }
        return array(
            "menuItem" => $menuItem,
            "form" => $menuItemForm->createView()
        );
    }
    /**
     * @Route("/deleteItem/{id}")
     */
    public function deleteItemAction(MenuItem $menuItem) {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $em->remove($menuItem);
        $em->flush();

        return $this->redirect($this->generateUrl("tscms_system_default_index"));
    }
}
