<?php
namespace Civi\Angular\Page;

/**
 * This page is simply a container; any Angular modules defined by CiviCRM (or by CiviCRM extensions)
 * will be activated on this page.
 *
 * @link https://issues.civicrm.org/jira/browse/CRM-14479
 */
class Main extends \CRM_Core_Page {
  /**
   * The weight to assign to any Angular JS module files
   */
  const DEFAULT_MODULE_WEIGHT = 200;

  /**
   * @var \CRM_Core_Resources
   */
  protected $res;


  /**
   * @var \Civi\Angular\Manager
   */
  protected $angular;

  /**
   * @param string $title
   *   Title of the page.
   * @param int $mode
   *   Mode of the page.
   * @param \CRM_Core_Resources|null $res
   *   Resource manager.
   */
  public function __construct($title = NULL, $mode = NULL, $res = NULL) {
    parent::__construct($title, $mode);
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = \Civi\Core\Container::singleton()->get('angular');
  }

  /**
   * This function takes care of all the things common to all
   * pages. This typically involves assigning the appropriate
   * smarty variable :)
   *
   * @return string
   *   The content generated by running this page
   */
  public function run() {
    $this->registerResources();
    return parent::run();
  }

  /**
   * Register resources required by Angular.
   */
  public function registerResources() {
    $modules = $this->angular->getModules();

    $this->res->addSettingsFactory(function () use (&$modules) {
      // TODO optimization; client-side caching
      return array(
        'resourceUrls' => \CRM_Extension_System::singleton()->getMapper()->getActiveModuleUrls(),
        'angular' => array(
          'modules' => array_merge(array('ngRoute'), array_keys($modules)),
          'cacheCode' => $this->res->getCacheCode(),
        ),
        'crmAttachment' => array(
          'token' => \CRM_Core_Page_AJAX_Attachment::createToken(),
        ),
      );
    });

    $this->res->addScriptFile('civicrm', 'bower_components/angular/angular.min.js', 100, 'html-header', FALSE);
    $this->res->addScriptFile('civicrm', 'bower_components/angular-route/angular-route.min.js', 110, 'html-header', FALSE);
    $headOffset = 0;
    foreach ($modules as $moduleName => $module) {
      foreach ($this->angular->getStyleUrls($moduleName) as $url) {
        $this->res->addStyleUrl($url, self::DEFAULT_MODULE_WEIGHT + (++$headOffset), 'html-header');
      }
      foreach ($this->angular->getScriptUrls($moduleName) as $url) {
        $this->res->addScriptUrl($url, self::DEFAULT_MODULE_WEIGHT + (++$headOffset), 'html-header');
        // addScriptUrl() bypasses the normal string-localization of addScriptFile(),
        // but that's OK because all Angular strings (JS+HTML) will load via crmResource.
      }
    }
  }
}