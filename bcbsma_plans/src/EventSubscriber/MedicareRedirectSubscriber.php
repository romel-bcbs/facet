<?php

namespace Drupal\bcbsma_plans\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Subscribes to the Kernel Request event and redirects to the homepage.
 *
 * When the user has the "no connty setup".
 */
class MedicareRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  // phpcs:ignore
  protected $currentUser;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $request;

  /**
   * Route MatchInterface stack.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  // phpcs:ignore
  protected $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * The Pathmatcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  // phpcs:ignore
  protected $pathMatcher;

  /**
   * HelloWorldRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current User.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request params instance.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   Path Matcher Interface.
   */
  public function __construct(AccountProxyInterface $currentUser,
  RequestStack $request,
  RouteMatchInterface $routeMatch,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $logger,
    PathMatcherInterface $pathMatcher) {
    $this->currentUser = $currentUser;
    $this->request = $request;
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory->get('medicare_options.site_config_form');
    $this->logger = $logger;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'][] = ['onRequest', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCache() {
    return FALSE;
  }

  /**
   * On every Request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The current RequestEvent object.
   */
  public function onRequest(RequestEvent $event): void {
    // Adding every thing in to try catch if in case any thing fails.
    // Wont break apge.
    try {
      // Get the curent request from the event.
      $request = $event->getRequest();
      $userAgent = $request->headers->get('User-Agent');
      $queryStrings = $request->getQueryString();
      // Get route name by its path.
      $route_name = $this->routeMatch->getRouteName();
      // Path of the current request.
      $path = $request->getPathInfo();
      // Setting cachable dependencies.
      $cacheability = new CacheableMetadata();
      $cacheability->setCacheMaxAge(0);

      // Loading session and retreiving county from it.
      $session = $this->request->getSession();
      $county = !is_null($session->get("county")) ? $session->get("county") : '';

      // Getting values form config form.
      $urls = $this->configFactory->get('redirect_include');
      $includePaths = array_map('trim', explode(PHP_EOL, $urls));
      $alloweduserAgents = [];
      $redirectPath = $this->configFactory->get('redirect_to');
      $allowedAgents = $this->configFactory->get('zipcode_user_agent');
      $alloweduserAgents = array_map('trim', explode(PHP_EOL, $allowedAgents));
      $currentRole = $this->currentUser->getRoles();
      // Get the path matcher service.
      $pathStatus = FALSE;
      // Looping through included paths.
      foreach ($includePaths as $url) {
        if ($this->pathMatcher->matchPath($path, $url)) {
          $pathStatus = TRUE;
        }
      }
      // Used to check reediect OR not.
      $status = TRUE;
      // Checking Empty Condition if empty always redirect.
      if (count($alloweduserAgents) > 0 && $pathStatus) {
        // Looping throug agents.
        foreach ($alloweduserAgents as $agent) {
          // Condition is true we are setting false and allowing user to access.
          if (strpos($userAgent, $agent)) {
            $this->logger->get('redirect $userAgent')->notice($userAgent);
            $this->logger->get('redirect $agent')->notice($agent);
            $status = FALSE;
            break;
          }
        }
      }
      // Check to Redirct or Not.
      if ($status) {
        // Checking if no county in session.
        // Current route is not setsession controller.
        // CHecking if current path is one of the excluded urls.
        if (empty($county) && $route_name != "session_set.county" && $pathStatus && in_array('anonymous', $currentRole)) {
          // Set Url to session variable only.
          // If current path is one of the below.
          if ($route_name == "entity.node.canonical" || strpos($route_name, 'view.') === 0) {
            // Create a new RedirectResponse object with the destination URL.
            if (!empty($queryStrings)) {
              $params = '?' . $queryStrings;
            }
            else {
              $params = '';
            }
            $url = Url::fromUri($redirectPath, ['query' => ['redirectUrl' => $path . $params]]);
            $localRedirect = new LocalRedirectResponse($url->toString());
            $localRedirect->addCacheableDependency($cacheability);

            // Setting response to event.
            $event->setResponse($localRedirect);
          }
        }
      }
    }
    catch (\Exception $exception) {
      $this->logger->get('Medicare Redirect')->info($exception->getMessage());
    }
  }

}
