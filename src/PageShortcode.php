<?php
namespace Cvy\WP\Shortcodes;
use Cvy\WP\SitePages\SitePage;

abstract class PageShortcode extends Shortcode
{
  abstract protected function get_page() : SitePage;

  protected function should_render() : bool
  {
    return $this->get_page()->is_current();
  }
}