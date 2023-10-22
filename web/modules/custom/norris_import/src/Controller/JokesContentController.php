<?php
namespace Drupal\norris_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the custom routes.
 */
class JokesContentController extends ControllerBase {

  /**
   * Content page handler.
   */
  public function content() {
    // Construct the URL to "admin/content" with appropriate query parameters.
    $path = '/admin/content?title=&type=jokes&status=All&langcode=All';
    // Redirect to the admin/content page with the desired query parameters.
    return new RedirectResponse($path);
  }

  /**
   * Logs page handler for /admin/reports/dblog/first-try-logs.
   */
  public function logs() {
    // Construct the URL to "admin/reports/dblog" with a filter for your module.
    $path = '/admin/reports/dblog?type%5B%5D=norris_import';
    // Redirect to the logs page with the filter for the module.
    return new RedirectResponse($path);
  }
}
