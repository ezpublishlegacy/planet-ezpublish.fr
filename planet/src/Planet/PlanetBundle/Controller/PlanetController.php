<?php

namespace Planet\PlanetBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion,
    eZ\Publish\API\Repository\Values\Content\Query\SortClause,
    DateTime;


class PlanetController extends Controller
{
    /**
     * Build the response so that depending on settings it's cacheable
     *
     * @param string $etag
     * @param DateTime $lastModified
     * @return \Symfony\Component\HttpFoundation\Response
     * @todo taken for ViewController, should be defined in one of the base;
     * controller.
     */
    protected function buildResponse( $etag, DateTime $lastModified )
    {
        $request = $this->getRequest();
        $response = new Response();
        if ( $this->getParameter( 'content.view_cache' ) === true )
        {
            $response->setPublic();
            $response->setEtag( $etag );

            // If-None-Match is the request counterpart of Etag response header
            // Making the response to vary against it ensures that an HTTP
            // reverse proxy caches the different possible variations of the
            // response as it can depend on user role for instance.
            if ( $request->headers->has( 'If-None-Match' )
                && $this->getParameter( 'content.ttl_cache' ) === true )
            {
                $response->setVary( 'If-None-Match' );
                $response->setMaxAge(
                    $this->getParameter( 'content.default_ttl' )
                );
            }

            $response->setLastModified( $lastModified );
        }
        return $response;
    }

    /**
     * Builds the top menu ie first level items of classes folder, page or
     * contact.
     *
     * @todo do NOT hard code the types id
     * @todo correctly get the UrlAlias
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function topMenu()
    {
        $locationService = $this->getRepository()->getLocationService();
        $rootLocationId = $this->container->getParameter(
            'planet.root_location_id'
        );
        $root = $locationService->loadLocation( $rootLocationId );

        $response = $this->buildResponse(
            __METHOD__ . $rootLocationId,
            $root->contentInfo->modificationDate
        );
        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        //$urlAliasService = $this->getRepository()->getUrlAliasService();
        //$urlAliasRoot = $urlAliasService->reverseLookup( $root, 'fre-FR' );

        $searchService = $this->getRepository()->getSearchService();
        $query = new Query();
        $query->criterion = new Criterion\LogicalAND(
            array(
                new Criterion\ParentLocationId( $rootLocationId ),
                new Criterion\ContentTypeId( array( 1, 20, 21 ) ),
            )
        );
        $query->sortClauses = array(
            new SortClause\LocationPriority()
        );
        $results = $searchService->findContent( $query );
        $items = array( $root );
        foreach ( $results->searchHits as $hit )
        {
            $location = $locationService->loadLocation(
                $hit->valueObject->contentInfo->mainLocationId
            );
            $items[] = $location;
        }

        return $this->render(
            'PlanetBundle:parts:top_menu.html.twig',
            array(
                'rootLocationId' => $rootLocationId,
                'items' => $items,
            ),
            $response
        );
    }

    /**
     * Builds the site list block
     *
     * @todo do NOT hard code the site type id
     * @todo correctly get the UrlAlias
     * @todo sort by modified_subnode instead of last modified
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function siteList()
    {
        $locationService = $this->getRepository()->getLocationService();
        $blogsLocationId = $this->container->getParameter(
            'planet.blogs_location_id'
        );
        $blogs = $locationService->loadLocation( $blogsLocationId );

        $response = $this->buildResponse(
            __METHOD__ . $blogsLocationId,
            $blogs->contentInfo->modificationDate
        );
        if ( $response->isNotModified( $this->getRequest() ) )
        {
            return $response;
        }

        $searchService = $this->getRepository()->getSearchService();
        $query = new Query();
        $query->criterion = new Criterion\LogicalAND(
            array(
                new Criterion\ParentLocationId( $blogsLocationId ),
                new Criterion\ContentTypeId( array( 17 ) ),
            )
        );
        $query->sortClauses = array(
            new SortClause\DateModified( Query::SORT_DESC )
        );
        $results = $searchService->findContent( $query );
        $sites = array();
        foreach ( $results->searchHits as $hit )
        {
            $location = $locationService->loadLocation(
                $hit->valueObject->contentInfo->mainLocationId
            );
            $sites[] = $location;
        }

        return $this->render(
            'PlanetBundle:parts:site_list.html.twig',
            array(
                'sites' => $sites,
                'blogs' => $blogs,
            ),
            $response
        );
    }

    /**
     * Returns the version of eZ Publish (taken from legacy eZPublishSDK)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function poweredBy()
    {
        $response = new Response();
        $response->setPublic();
        $response->setContent( \eZPublishSDK::version() );
        return $response;
    }


}
