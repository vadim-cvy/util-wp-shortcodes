<?php
namespace Cvy\WP\Shortcodes;

abstract class Shortcode extends \Cvy\DesignPatterns\Singleton
{
  private $are_assets_enqueued = false;

  final protected function __construct()
  {
    add_shortcode( $this->get_name(), fn() => $this->get_content() );

    add_action( 'wp_enqueue_scripts', fn() => $this->maybe_enqueue_assets() );
  }

  abstract protected function get_name() : string;

  private function get_content() : string
  {
    if ( ! $this->should_render() )
    {
      return '';
    }

    $err = $this->get_error();

    if ( $err )
    {
      trigger_error( $err, E_USER_WARNING );

      return '<b>Error. Can\'t render this content.</b>';
    }

    ob_start();

    echo $this->get_wrapper_opening_tag();

    $this->render();

    echo $this->get_wrapper_closing_tag();

    $output = ob_get_contents();

    ob_end_clean();

    return $output;
  }

  private function get_wrapper_opening_tag() : string
  {
    $css_class = str_replace( '_', '-', $this->get_name() );

    return sprintf( '<%s class="%s">',
      $this->get_wrapper_tag_name(),
      esc_attr( $css_class )
    );
  }

  private function get_wrapper_closing_tag() : string
  {
    return sprintf( '</%s>', $this->get_wrapper_tag_name() );
  }

  abstract protected function get_wrapper_tag_name() : string;

  private function get_error() : string
  {
    if ( ! $this->should_render() )
    {
      return '';
    }

    $err = $this->get_custom_error();

    if ( ! $err )
    {
      $err = $this->get_assets_error();
    }

    return $err;
  }

  abstract protected function get_custom_error() : string;

  private function get_assets_error() : string
  {
    if ( ! $this->are_assets_enqueued )
    {
      return sprintf(
        'Assets for "%s" are not enqueued. Check if "%s::should_render()" implementation is correct.',
        $this->get_name(),
        get_called_class()
      );
    }

    return '';
  }

  abstract protected function render() : void;

  private function maybe_enqueue_assets() : void
  {
    if ( $this->should_render() )
    {
      $this->enqueue_assets();

      $this->are_assets_enqueued = true;
    }
  }

  abstract protected function should_render() : bool;

  abstract protected function enqueue_assets() : void;
}