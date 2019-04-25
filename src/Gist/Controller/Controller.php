<?php

namespace Gist\Controller;

use Silex\Application;
use Gist\Model\Gist;
use Symfony\Component\HttpFoundation\Request;
use Gist\Model\GistQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Controller.
 *
 * @author Simon Vieille <simon@deblan.fr>
 */
abstract class Controller
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $types = [
        'html',
        'css',
        'javascript',
        'php',
        'sql',
        'xml',
        'yaml',
        'markdown',
        'perl',
        'c',
        'asp',
        'python',
        'bash',
        'actionscript3',
        'text',
    ];

    /**
     * __construct.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the application.
     *
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Returns a 404 response.
     *
     * @return Response
     */
    protected function notFoundResponse()
    {
        $app = $this->getApp();

        return new Response(
            $app['twig']->render(
                'View/notFound.html.twig',
                []
            ),
            404
        );
    }

    /**
     * Returns the default options of a gist view.
     *
     * @param Request $request
     * @param string  $gist    Gist's ID
     * @param string  $commit  The commit ID
     *
     * @return array
     */
    protected function getViewOptions(Request $request, $gist, $commit)
    {
        $app = $this->getApp();

        $gist = GistQuery::create()->findOneByFile($gist);

        if (null === $gist) {
            return null;
        }

        $history = $app['gist']->getHistory($gist);

        if (empty($history)) {
            return null;
        }

        $content = $this->getContentByCommit($gist, $commit, $history);

        return array(
            'gist' => $gist,
            'type' => $gist->getType(),
            'types' => $this->types,
            'history' => $history,
            'commit' => $commit,
            'raw_content' => $content,
        );
    }

    /**
     * Returns the content of the gist depending of the commit and its history.
     *
     * @param Gist  $gist
     * @param mixed $commit
     * @param mixed $history
     */
    protected function getContentByCommit(Gist $gist, &$commit, $history)
    {
        $app = $this->getApp();

        if ($commit === 0) {
            $commit = $history[0]['commit'];
        } else {
            $commitExists = false;

            foreach ($history as $ci) {
                if ($commit === $ci['commit']) {
                    $commitExists = true;
                }
            }

            if (!$commitExists) {
                return null;
            }
        }

        return $app['gist']->getContent($gist, $commit);
    }

    /**
     * Returns the connected user.
     *
     * @param Request $request An API request
     *
     * @return mixed
     */
    public function getUser(Request $request = null)
    {
        $app = $this->getApp();

        if (!empty($request)) {
        }

        $securityContext = $app['security.token_storage'];
        $securityToken = $securityContext->getToken();

        if (!$securityToken) {
            return null;
        }

        $user = $securityToken->getUser();

        if (!is_object($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Renders a view.
     *
     * @param string $template
     * @param array  $params
     *
     * @return string
     */
    public function render($template, array $params = null)
    {
        $app = $this->getApp();

        if (null === $params) {
            $params = [];
        }

        if (!isset($params['user'])) {
            $params['user'] = $this->getUser();
        }

        return $app['twig']->render(
            $template,
            $params
        );
    }

    /**
     * Creates a Response.
     *
     * @param string $template
     * @param array  $params
     *
     * @return Response
     */
    public function createResponse($template, array $params = null)
    {
        $body = $this->render($template, $params);

        $response = new Response($body);

        if (empty($params['no_cache'])) {
            $ttl = 3600 * 24 * 7;
            $etag = sha1($response->getContent());

            $response->setTtl($ttl);
            $response->setClientTtl($ttl);
            $response->setExpires(new \DateTime('now +7 days'));
            $response->setLastModified(new \DateTime('now'));
            $response->setEtag($etag, true);
        }

        return $response;
    }
}
