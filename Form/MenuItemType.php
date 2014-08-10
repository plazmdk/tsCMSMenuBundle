<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 5/1/14
 * Time: 5:20 PM
 */

namespace tsCMS\MenuBundle\Form;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Orx;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use tsCMS\MenuBundle\Entity\Menu;
use tsCMS\MenuBundle\Entity\MenuItem;
use tsCMS\SystemBundle\Form\DataTransformer\RouteToStringTransformer;

class MenuItemType extends AbstractType {

    private $router;
    private $em;

    private $menu;
    private $menuItem;

    function __construct(Menu $menu, MenuItem $menuItem = null, RouterInterface $router, EntityManager $em)
    {
        $this->menu = $menu;
        $this->menuItem = $menuItem;
        $this->router = $router;
        $this->em = $em;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "tsCMS_menu_menuitemtype";
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $menu = $this->menu;
        $menuItem = $this->menuItem;

        $routeTransformer = new RouteToStringTransformer($this->em);

        $builder
            ->add(
                $builder->create("route", "addon", array(
                    "label" => "menuitem.route",
                    "data_class" => 'tsCMS\SystemBundle\Entity\Route',
                    "postfixIcon" => "fa fa-search",
                    "postfixAction" => $this->router->generate("tscms_system_route_selectroute"),
                    "hiddenInput" => true,
                    "previewValue" => "e.title (e.path)",
                    "entityValue" => "name",
                    "compound" => false,
                    "attr" => array(
                        "class" => "routeSelect"
                    )
                ))->addViewTransformer($routeTransformer)
            )
            ->add("title", "text", array(
                "label" => "menuitem.title",
                "required" => false,
                "attr" => array(
                    "class" => "menuitemTitle"
                )
            ))
            ->add("stickyTitle", "checkbox", array(
                "label" => "menuitem.stickytitle",
                "required" => false,
                "attr" => array(
                    "class" => "menuitemStickyTitle"
                )
            ))
            ->add("parent", "entity", array(
                "label" => "menuitem.parent",
                'class'    => 'tsCMSMenuBundle:MenuItem',
                'required' => true,
                'query_builder' => function(EntityRepository $er) use($menu,$menuItem)
                    {
                        $qb = $er->createQueryBuilder('m')
                            ->where("m.menu = :menu")
                            ->setParameter("menu", $menu)
                            ->orderBy('m.lft');

                        if ($menuItem) {
                            $qb
                            ->andWhere(new Orx(array('m.lft < :lft','m.rgt > :rgt')))
                            ->setParameter('lft', $menuItem->getLft())
                            ->setParameter("rgt", $menuItem->getRgt());
                        }
                        return $qb;
                    },
            ))
            ->add('save', 'submit',array('label'  => 'page.save'))
        ;

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'tsCMS\MenuBundle\Entity\MenuItem'
        ));
    }
}