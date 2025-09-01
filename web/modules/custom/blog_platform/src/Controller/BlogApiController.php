<?php

namespace Drupal\blog_platform\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implement blog api.
 */
class BlogApiController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity;

  /**
   * The config factory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Create constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity
   *   The entity type manager service.
   */
  public function __Construct(EntityTypeManagerInterface $entity, ConfigFactoryInterface $config) {
    $this->entity = $entity;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * List blogs.
   */
  public function showBlog(Request $request) {
    $config = $this->config('blog_platform.settings');

    // Read admin defaults.
    $cfg_date_from = $config->get('api_date_from');
    $cfg_date_to = $config->get('api_date_to');
    $cfg_authors = $config->get('api_authors');
    $cfg_tags = $config->get('api_tags');

    $request = \Drupal::request();
    $params = $request->query->all();

    // Allow request overrides; otherwise use admin-configured defaults.
    $date_from = $params['date_from'] ?? $cfg_date_from;
    $date_to = $params['date_to'] ?? $cfg_date_to;
    $authors = $params['authors'] ?? $cfg_authors;
    $tags = $params['tags'] ?? $cfg_tags;

    // Build entity query.
    $storage = $this->entity->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'blog')
      ->condition('status', 1)
      ->sort('created', 'DESC');

    if (!empty($date_from)) {
      // Convert to timestamp start of day.
      $from_ts = strtotime($date_from . ' 00:00:00');
      if ($from_ts !== FALSE) {
        $query->condition('created', $from_ts, '>=');
      }
    }
    if (!empty($date_to)) {
      $to_ts = strtotime($date_to . ' 23:59:59');
      if ($to_ts !== FALSE) {
        $query->condition('created', $to_ts, '<=');
      }
    }
    if (!empty($authors)) {
      $author_list = array_filter(array_map('trim', explode(',', $authors)));
      $query->condition('uid', $author_list, 'IN');
    }
    if (!empty($tags)) {
      $tag_list = array_filter(array_map('trim', explode(',', $tags)));
      // field_tags is term reference; store term ids in field_tags.target_id.
      $query->condition('field_blog_tags.target_id', $tag_list, 'IN');
    }

    $nids = $query->execute();
    $nodes = $storage->loadMultiple($nids);

    $result = [];
    foreach ($nodes as $node) {
      $body = $node->hasField('body') ? $node->get('body')->value : '';
      $tags_out = [];
      if ($node->hasField('field_blog_tags')) {
        foreach ($node->get('field_blog_tags')->target_id as $term) {
          $tags_out[] = [
            'tid' => $term->id(),
            'name' => $term->label(),
            'url' => $term->toUrl()->setAbsolute()->toString(),
          ];
        }
      }
      $author = $node->getOwner();
      $result[] = [
        'nid' => $node->id(),
        'title' => $node->label(),
        'body' => $body,
        'published_date' => $node->get('field_published_date')->value,
        'author' => [
          'uid' => $author->id(),
          'name' => $author->getDisplayName(),
        ],
        'tags' => $tags_out,
        'url' => $node->toUrl()->setAbsolute()->toString(),
      ];
    }

    return new JsonResponse($result);
  }

}
