<?php

namespace Drupal\dc_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Home routes.
 */
class HomeController extends ControllerBase {

  /**
   * Renderer Interface.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer = NULL;


  /**
   * HomeController constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct( RendererInterface $renderer) {
    $this->renderer = $renderer;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function homeRender(Request $request) {
    $build = [
      'home_page_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'home-page-wrapper',
        ],
        'content' => $this->getHomepageContent(),
      ],
    ];
    return $build;
  }


  /**
   * Returns homepage content using Purl Modifier
   */
  private function getHomepageContent() {
    $build = [
      '#theme' => 'dc_home_blocks'
    ];

    $view_movies_blocks = [
      'block_upcoming_movies',
      'block_popular_movies'
    ];
    $view_actors_blocks = [
      'block_popular_actors'
    ];

    foreach($view_movies_blocks as $view_display) {
      $view = Views::getView('movies');
      $view->setDisplay($view_display);
      $view->preExecute();
      $view->execute();

      if (count($view->result)) {
        $build['#' . $view_display] = $view->buildRenderable($view_display);
      }
    }

    foreach($view_actors_blocks as $view_display) {
      $view = Views::getView('actors');
      $view->setDisplay($view_display);
      $view->preExecute();
      $view->execute();

      if (count($view->result)) {
        $build['#' . $view_display] = $view->buildRenderable($view_display);
      }
    }

    return $build;
  }
}
