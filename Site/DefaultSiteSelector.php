<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Site;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Sonata\PageBundle\Model\SiteManagerInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Request\PageRequest as Request;

class DefaultSiteSelector implements SiteSelectorInterface
{
    protected $container;

    protected $siteManager;

    protected $site;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Sonata\PageBundle\Model\SiteManagerInterface $siteManager
     */
    public function __construct(ContainerInterface $container, SiteManagerInterface $siteManager)
    {
        $this->container = $container;
        $this->siteManager = $siteManager;
    }

    /**
     * @return \Sonata\PageBundle\Model\SiteInterface
     */
    public function retrieve()
    {
        return $this->site;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

//        $siteId = $this->getRequest()->getSession()->get('sonata/page/site/current');

        $sites = array();
//        if ($siteId) {
//            $site = $this->siteManager->findOneBy(array('id' => $siteId));
//
//            if ($site) {
//                $sites = array($site);
//            }
//        }

        if (count($sites) == 0) {
            $sites = $this->siteManager->findBy(array(
                'domain'  => $this->getRequest()->getHost(),
                'enabled' => true,
            ));
        }

        $now = new \DateTime;
        foreach ($sites as $site) {
            if ($site->getEnabledFrom()->format('U') > $now->format('U')) {
                continue;
            }

            if ($now->format('U') > $site->getEnabledTo()->format('U') ) {
                continue;
            }

            $results = array();

            if (!preg_match(sprintf('@^(%s)(.*|)@', $site->getRelativePath()), $request->getPathInfo(), $results)) {
                continue;
            }

            if ($request instanceof Request) {
                $request->setPathInfo($results[2] ?: '/');
            }

            $this->site = $site;

            break;
        }


        if (!$this->site) {
            throw new \RuntimeException('Unable to retrieve the current website');
        }
    }

    /**
     * This method hijack the path generated by the Generator cache file to use
     * the relative path from the current active site.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @return void
     */
    public function onKernelRequestRedirect(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->site) {
            return;
        }

        if ('Symfony\\Bundle\\FrameworkBundle\\Controller\\RedirectController::urlRedirectAction' == $request->get('_controller')) {
            $request->attributes->set('path', $this->site->getRelativePath().$request->attributes->get('path'));
        }
    }

    /**
     * @param \Sonata\PageBundle\Model\SiteInterface $site
     * @return void
     */
    public function set(SiteInterface $site)
    {
        $this->getRequest()->getSession()->set('sonata/page/site/current', $site->getId());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * @return \Symfony\Component\Routing\RequestContext
     */
    public function getRequestContext()
    {
        return new SiteRequestContext($this);
    }
}