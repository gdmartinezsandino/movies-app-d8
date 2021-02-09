<?php

namespace Drupal\dc_content_import\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;
use Drupal\dc_content_import\Service\ApiImportTools;

/**
 * Class DCImportCommands
 *
 * @package Drupal\dc_content_import\Commands
 */
class DCImportCommands extends DrushCommands {

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * @var \Drupal\dc_content_import\Service\ApiImportTools
   */
  private $DCMovieTools;

  /**
   * DCImportCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, ApiImportTools $DCMovieTools) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->DCMovieTools = $DCMovieTools;
  }

  /**
   * Import Genres
   *
   * @command import:genres
   * @aliases import-genres
   *
   * @usage drush import:genres
   */
  public function importGenres() {
    $this->logger()->notice('Import Genres operations start');

    $genres = $this->DCMovieTools->getGenreMovieList();
    $genres = json_decode($genres, TRUE);
    $genres = $genres['genres'];

    if (!empty($genres)) {
      foreach ($genres as $genre) {
        $message = t('Creating Genre @original_id: @title', [
          '@original_id' => $genre['id'],
          '@title' => $genre['name'],
        ]);
        $this->logger()->notice($message);

        $query = \Drupal::service('entity.query')
          ->get('taxonomy_term')
          ->condition('vid', 'movie_genre')
          ->condition('field_original_id', $genre['id']);

        $result = $query->execute();
        if (empty($result)) {
          $entity_storage = $this->entityTypeManager
            ->getStorage('taxonomy_term');

          $entity_storage->create([
            'uid' => 1,
            'name' => $genre['name'],
            'field_original_id' => $genre['id'],
            'vid' => 'movie_genre',
          ])->save();
        }
      }
    }
    else {
      $this->logger()->warning('No Genres to import!');
    }

    $this->logger()->notice("Import Genres operations end.");
  }

  /**
   * @param $api_request_count
   *
   * @return int
   */
  public function manageAPIRequestCount(&$api_request_count) {
    $api_request_count++;
    if ($api_request_count === 40) {
      //Like the development API key just allow 40 request each 10 seconds,
      // it is necessary to wait for a while.
      $this->logger()
        ->notice('Waiting 10 seconds before we can continue using themoviesdb API...');

      sleep(10);
      $api_request_count = 0;
    }
  }


  /**
   * Import upcoming movies
   *
   * @param string $movie_type
   *   Argument provided to the drush command.
   * @param int $pages_to_import
   *   cuantity of pages to import.
   *
   * @command import:movies
   * @aliases import-movies
   *
   * @usage drush import:movies upcoming 15
   */
  public function importUpcomingMovies($movie_type, $pages_to_import = 15) {
    if (!in_array($movie_type, ['upcoming', 'popular'])) {
      $this->logger()
        ->error('Incorrect parameter. only \'upcoming\' and \'popular\' are allowed');

      return;
    }

    $this->logger()->notice('Import Movies operations start');

    $api_request_count = 0;
    for ($i = 1; $i <= $pages_to_import; $i++) {
      $movies = $this->DCMovieTools->getAPIEntities('movie', $movie_type, $i);
      $this->manageAPIRequestCount($api_request_count);
      $decoded_movies = json_decode($movies, TRUE);
      if (!empty($decoded_movies['results'])) {
        foreach ($decoded_movies['results'] as $movie) {
          $query = \Drupal::service('entity.query')
            ->get('node')
            ->condition('type', 'movie')
            ->condition('field_original_id', $movie['id']);

          $result = $query->execute();
          if (empty($result)) {
            $full_movie_data = $this->DCMovieTools->getEntityFullData('movie', $movie['id']);
            $this->manageAPIRequestCount($api_request_count);
            if ($full_movie_data) {
              $full_movie_data = json_decode($full_movie_data, TRUE);

              $message = t('Creating movie @original_id: @title', [
                '@original_id' => $full_movie_data['id'],
                '@title' => $full_movie_data['title'],
              ]);
              $this->logger()->notice($message);

              $alternative_titles = $this->DCMovieTools->getEntitySpecificData($movie['id'], 'alternative_titles');
              $this->manageAPIRequestCount($api_request_count);
              $alternative_titles = json_decode($alternative_titles, TRUE);
              $processed_alternative_titles = [];
              foreach ($alternative_titles['titles'] as $alt_title) {
                $processed_alternative_titles[]['value'] = $alt_title['title'];
              }

              //Similar movies get and process
              $similar_movie_titles = [];
              $similar_movies = $this->DCMovieTools->getEntitySpecificData($movie['id'], 'similar');
              $this->manageAPIRequestCount($api_request_count);
              $similar_movies = json_decode($similar_movies, TRUE);
              foreach ($similar_movies['results'] as $similar_movie) {
                $similar_movie_titles[] = $similar_movie['title'];
              }

              $reviews = $this->DCMovieTools->getEntitySpecificData($movie['id'], 'reviews');
              $this->manageAPIRequestCount($api_request_count);
              $reviews = json_decode($reviews, TRUE);
              $processed_reviews = [];
              foreach ($reviews['results'] as $review) {
                $processed_reviews[]['value'] = $review['author'] . ': ' . $review['content'];
              }

              $videos = $this->DCMovieTools->getEntitySpecificData($movie['id'], 'videos');
              $this->manageAPIRequestCount($api_request_count);
              $videos = json_decode($videos, TRUE);
              if (!empty($videos['results'])) {
                $video = [];
                foreach ($videos['results'] as $video) {
                  if ($video['type'] == 'Trailer') {
                    break;
                  }
                }

                $trailer = NULL;
                if (!empty($video)) {
                  if ($video['site'] == 'YouTube') {
                    $video['url'] = 'https://www.youtube.com/watch?v=' . $video['key'];
                  }
                  else {
                    $video['url'] = 'https://vimeo.com/' . $video['key'];
                  }

                  if (isset($video['url'])) {
                    $media_storage = $this->entityTypeManager->getStorage('media');
                    $trailer = $media_storage->create([
                      'uid' => 1,
                      'bundle' => 'remote_video',
                      'title' => $video['name'],
                      'field_media_oembed_video' => $video['url'],
                    ])->save();
                  }
                }
              }

              if (!empty($full_movie_data['genres'])) {
                $genre_original_ids = array_column($full_movie_data['genres'], 'id');
                $query = \Drupal::service('entity.query')
                  ->get('taxonomy_term')
                  ->condition('field_original_id', $genre_original_ids, 'IN');
                $genres = $query->execute();
              }

              if (!empty($full_movie_data['poster_path'])) {
                $poster_destination = str_replace('/', '', $full_movie_data['poster_path']);
                $poster_full_url = 'https://image.tmdb.org/t/p/original' . $full_movie_data['poster_path'] . '?api_key=' . $this->DCMovieTools->getApiKey();
                $this->manageAPIRequestCount($api_request_count);
                $fid = $this->DCMovieTools->createFileByPath($poster_full_url, $poster_destination);
                $this->manageAPIRequestCount($api_request_count);
              }

              $entity_storage = $this->entityTypeManager->getStorage('node');
              $entity_storage->create([
                'uid' => 1,
                'type' => 'movie',
                'title' => $full_movie_data['title'],
                'field_original_id' => $full_movie_data['id'],
                'field_movie_genre' => $genres,
                'field_movie_trailer' => !empty($trailer) ? ['target_id' => $trailer] : NULL,
                'field_movie_poster' => !empty($fid) ? $fid : NULL,
                'field_movie_status' => $movie_type == 'upcoming' ? 'Upcoming' : $full_movie_data['status'],
                'field_original_language' => $full_movie_data['original_language'],
                'body' => $full_movie_data['overview'],
                'field_popularity' => $full_movie_data['popularity'],
                'field_production_companies' =>
                  !empty($full_movie_data['production_companies']) ?
                    implode(', ', array_column($full_movie_data['production_companies'], 'name')) :
                    NULL,
                'field_release_date' => $full_movie_data['release_date'] . 'T00:00:00',
                'field_reviews' => $processed_reviews,
                'field_alternative_titles' => $processed_alternative_titles,
                'field_similar_movies' => !empty($similar_movie_titles) ? implode(' | ', $similar_movie_titles) : '',
              ])->save();

              unset($fid, $genres, $processed_reviews);
            }
            else {
              $message = t('This movie has not full information. ID: @original_id', ['@original_id' => $movie['id']]);
              $this->logger()->warning($message);
            }
          }
          else {
            $message = t('The movie @original_id:@title already exist', [
              '@original_id' => $movie['id'],
              '@title' => $movie['title'],
            ]);
            $this->logger()->notice($message);
          }
        }
      }

    }

    $this->logger()->notice("Import movies operations end.");
  }


  /**
   * Import actors
   *
   * @param int $pages_to_import
   *   cuantity of pages to import.
   *
   * @command import:actors
   * @aliases import-actors
   *
   * @usage drush import:actors 500
   */
  public function importActors($pages_to_import = 500) {

    $this->logger()->notice('Import Actors operations start');

    $api_request_count = 0;
    for ($i = 1; $i <= $pages_to_import; $i++) {
      $popular_actors = $this->DCMovieTools->getAPIEntities('person', 'popular', $i);
      $this->manageAPIRequestCount($api_request_count);
      $decoded_popular_actors = json_decode($popular_actors, TRUE);

      if (!empty($decoded_popular_actors['results'])) {
        foreach ($decoded_popular_actors['results'] as $actor) {
          $query = \Drupal::service('entity.query')
            ->get('node')
            ->condition('type', 'actors')
            ->condition('field_original_id', $actor['id']);

          $result = $query->execute();
          if (empty($result)) {
            $full_actor_data = $this->DCMovieTools->getEntityFullData('person', $actor['id']);
            $this->manageAPIRequestCount($api_request_count);
            $full_actor_data = json_decode($full_actor_data, TRUE);
            if ($full_actor_data) {
              $message = t('Creating actor @original_id: @name', [
                '@original_id' => $full_actor_data['id'],
                '@name' => $full_actor_data['name'],
              ]);
              $this->logger()->notice($message);

              if (!empty($full_actor_data['profile_path'])) {
                $photo_destination = str_replace('/', '', $full_actor_data['profile_path']);
                $photo_full_url = 'https://image.tmdb.org/t/p/original' . $full_actor_data['profile_path'] . '?api_key=' . $this->DCMovieTools->getApiKey();
                $this->manageAPIRequestCount($api_request_count);
                $fid = $this->DCMovieTools->createFileByPath($photo_full_url, $photo_destination);
                $this->manageAPIRequestCount($api_request_count);
              }

              $gallery_images = [];
              $images = $this->DCMovieTools->getEntitySpecificData($full_actor_data['id'], 'images', 'person');
              $this->manageAPIRequestCount($api_request_count);
              $images = json_decode($images, TRUE);
              if (!empty($images['profiles'])) {
                $i = 0;
                foreach ($images['profiles'] as $image) {
                  $photo_destination = str_replace('/', '', $image['file_path']);
                  $photo_full_url = 'https://image.tmdb.org/t/p/original' . $image['file_path'] . '?api_key=' . $this->DCMovieTools->getApiKey();
                  $this->manageAPIRequestCount($api_request_count);
                  $fid = $this->DCMovieTools->createFileByPath($photo_full_url, $photo_destination);
                  $gallery_images[]['target_id'] = $fid;
                  $this->manageAPIRequestCount($api_request_count);

                  $i++;
                  if ($i == 10) {
                    break;
                  }
                }
              }

              $entity_storage = $this->entityTypeManager->getStorage('node');
              $entity_storage->create([
                'type' => 'actors',
                'title' => $full_actor_data['name'],
                'field_original_id' => $full_actor_data['id'],
                'field_photo' => !empty($fid) ? $fid : NULL,
                'field_birthday' => $full_actor_data['birthday'] . 'T00:00:00',
                'field_deathday' => !empty($full_actor_data['deathday']) ? $full_actor_data['deathday'] . 'T00:00:00' : NULL,
                'body' => $full_actor_data['biography'],
                'field_place_of_birth' => $full_actor_data['place_of_birth'],
                'field_popularity' => $full_actor_data['popularity'],
                'field_website' => !empty($full_actor_data['homepage']) ?
                  [
                    'uri' => $full_actor_data['homepage'],
                    'title' => $full_actor_data['homepage'],
                  ] : NULL,
                'field_gallery_of_images' => $gallery_images,
              ])->save();

              $query = \Drupal::service('entity.query')
                ->get('node')
                ->condition('type', 'actors')
                ->condition('field_original_id', $full_actor_data['id']);
              $new_id = $query->execute();
              $new_id = reset($new_id);

              //Populate the field cast in movies entity
              $movies = [];
              $movie_credits = $this->DCMovieTools->getEntitySpecificData($full_actor_data['id'], 'movie_credits', 'person');
              $this->manageAPIRequestCount($api_request_count);
              $movie_credits = json_decode($movie_credits, TRUE);
              if (!empty($movie_credits['cast'])) {
                foreach ($movie_credits['cast'] as $movie_credit) {
                  $movies[] = $movie_credit['id'];
                }

                if (!empty($movies)) {
                  $query = \Drupal::service('entity.query')
                    ->get('node')
                    ->condition('type', 'movie')
                    ->condition('field_original_id', $movies, 'IN');
                  $result = $query->execute();

                  $movies = $this->entityTypeManager->getStorage('node')
                    ->loadMultiple($result);

                  foreach ($movies as $movie) {
                    $movie->get('field_cast')->appendItem([
                      'target_id' => $new_id,
                    ]);
                    $movie->save();

                    $message = t('Actor @original_id:@name was linked to movie @movie_id (drupal nid)', [
                      '@movie_id' => $movie->id(),
                      '@original_id' => $full_actor_data['id'],
                      '@name' => $full_actor_data['name'],
                    ]);
                    $this->logger()->notice($message);
                  }
                }
              }
            }
            else {
              $message = t('This actor / actress has not full information. ID: @original_id', ['@original_id' => $actor['id']]);
              $this->logger()->warning($message);
            }
          }
          else {
            $message = t('The actor / actress  @original_id:@name already exist', [
              '@original_id' => $actor['id'],
              '@name' => $actor['name'],
            ]);
            $this->logger()->notice($message);
          }
        }
      }
    }

    $this->logger()->notice("Import movies operations end.");
  }
}
