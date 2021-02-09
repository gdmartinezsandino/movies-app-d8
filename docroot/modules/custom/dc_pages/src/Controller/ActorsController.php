<?php

namespace Drupal\dc_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Actors routes.
 */
class ActorsController extends ControllerBase {

  /**
   * Renderer Interface.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer = NULL;


  /**
   * ActorsController constructor.
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
  public function actorsRender(Request $request) {
    $build = [
      'actors_page_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'actors-page-wrapper',
        ],
        'content' => $this->getActorspageContent(),
      ],
    ];
    return $build;
  }


  /**
   * Returns actorspage content using Purl Modifier
   */
  private function getActorspageContent() {
    $build = [
      '#theme' => 'dc_actors_blocks'
    ];

    $view_actors_blocks = [
      'block_popular_actors'
    ];

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
