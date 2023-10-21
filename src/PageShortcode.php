<?php
namespace Cvy\WP\Shortcodes;

abstract class PageShortcode extends Shortcode
{
  final protected function will_render() : bool
  {
    return get_the_ID() === $this->get_page_id();
  }

  abstract protected function get_page_id() : int;
}