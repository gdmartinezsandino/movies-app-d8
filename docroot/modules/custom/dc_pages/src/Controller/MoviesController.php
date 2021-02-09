<?php

namespace Drupal\dc_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Movies routes.
 */
class MoviesController extends ControllerBase {

  /**
   * Renderer Interface.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer = NULL;


  /**
   * MoviesController constructor.
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
  public function moviesRender(Request $request) {
    $build = [
      'movies_page_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'movies-page-wrapper',
        ],
        'content' => $this->getMoviespageContent(),
      ],
    ];
    return $build;
  }


  /**
   * Returns moviespage content using Purl Modifier
   */
  private function getMoviespageContent() {
    $build = [
      '#theme' => 'dc_movies_blocks'
    ];

    $view_movies_blocks = [
      'block_upcoming_movies',
      'block_popular_movies'
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

    return $build;
  }
}
