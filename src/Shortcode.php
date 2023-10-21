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
    ob_start();

    $err_msg = $this->get_error_message();

    if ( $err_msg )
    {
      trigger_error( strip_tags( $err_msg ), E_USER_WARNING );

      echo $err_msg;
    }
    else
    {
      $this->render();
    }

    $output = ob_get_contents();

    ob_end_clean();

    return $output;
  }

  private function get_error_message() : string
  {
    if ( $this->are_assets_enqueued )
    {
      return '';
    }

    $err_msg = '<b>Error. Can\'t render this content...</b>';

    if ( current_user_can( 'administrator' ) )
    {
      $err_msg .= sprintf(
        'Assets for "%s" are not enqueued. Check implementation of the %s::will_render() is correct.',
        esc_html( $this->get_name() ),
        get_called_class()
      );
    }

    return $err_msg;
  }

  abstract protected function render() : void;

  private function maybe_enqueue_assets() : void
  {
    if ( $this->will_render() )
    {
      $this->enqueue_assets();

      $this->are_assets_enqueued = true;
    }
  }

  abstract protected function will_render() : bool;

  abstract protected function enqueue_assets() : void;
}