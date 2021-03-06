<?php

namespace ArsThanea\PageActionsBundle\EventListener;

use ArsThanea\PageActionsBundle\Entity\PageActionsInterface;
use ArsThanea\PageActionsBundle\Entity\PageRouteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kunstmaan\NodeBundle\Event\NodeEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\RouterInterface;

class PageRouteListener
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     */
    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    public function onNodeSaved(NodeEvent $event)
    {
        /** @var PageActionsInterface $page */
        $page = $event->getPage();
        $nodeTranslation = $event->getNodeTranslation();

        $pageRouteRepository = $this->getPageRouteRepository();

        if (false === $page instanceof PageActionsInterface || 0 === sizeof($page->getPageActions())) {
            $pageRouteRepository->clearRoutesForNodeTranslation($nodeTranslation);

            return;
        }

        $pageRouteRepository->saveNodeTranslationActions($nodeTranslation, $page->getPageActions(), $this->router);
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (false === $request->attributes->get('page_action', false)) {
            return ;
        }

        $nt = $request->attributes->get("_nodeTranslation");

        // prevent SlugListener:
        $request->attributes->remove("_nodeTranslation");

        $nodeTranslation = $this->em->getRepository('KunstmaanNodeBundle:NodeTranslation')->find($nt);
        /** @noinspection PhpParamsInspection */
        $page = $nodeTranslation->getRef($this->em);

        $request->attributes->add([
            'nodeTranslation' => $nodeTranslation,
            'page' => $page,
        ]);

    }

    /**
     * @return PageRouteRepository
     */
    protected function getPageRouteRepository()
    {
        return $this->em->getRepository('PageActionsBundle:PageRoute');
    }
}
