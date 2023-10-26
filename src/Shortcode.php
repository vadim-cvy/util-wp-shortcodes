<?php
namespace Cvy\WP\Shortcodes;
use Exception;

abstract class Shortcode extends \Cvy\DesignPatterns\Singleton
{
  private $atts;

  private $user_content;

  final protected function __construct()
  {
    add_shortcode(
      $this->get_name(),
      fn( array | string $atts = [], string $user_content = '' ) => $this->handle( $atts, $user_content )
    );

    add_action( 'wp_enqueue_scripts', fn() => $this->maybe_enqueue_assets() );
  }

  abstract protected function get_name() : string;

  private function handle( array | string $atts, string $user_content ) : string
  {
    if ( is_string( $atts ) )
    {
      $atts = [];
    }

    try
    {
      $this->set_atts( $atts );
    }
    catch ( Exception $e )
    {
      $this->throw_notice( $e->getMessage() );

      $is_error = true;
    }

    if ( empty( $is_error ) )
    {
      $this->set_user_content( $user_content );

      $content = $this->get_content();
    }
    else
    {
      $content = $this->get_ui_error_msg();
    }

    return $this->get_wrapper_opening_tag() . $content . $this->get_wrapper_closing_tag();
  }

  private function set_user_content( string $user_content ) : void
  {
    $this->user_content = $user_content;
  }

  final protected function get_user_content() : string
  {
    return $this->user_content;
  }

  abstract protected function get_allowed_att_names() : array;

  abstract protected function get_required_att_names() : array;

  private function set_atts( array $raw_atts ) : void
  {
    $missed_atts = array_diff(
      $this->get_required_att_names(),
      array_keys( $raw_atts )
    );

    if ( ! empty( $missed_atts ) )
    {
      throw new Exception(sprintf(
        'The following atts are missed: "%s"',
        implode( '", "', $missed_atts )
      ));
    }

    $prepared_atts = [];

    $allowed_atts = $this->get_allowed_att_names();

    foreach ( $raw_atts as $name => $value )
    {
      if ( ! in_array( $name, $allowed_atts ) )
      {
        throw new Exception(sprintf( 'Unexpected attribute passed: "%s"! Allowed attributes are: "%s".',
          $name,
          implode( '", "', $allowed_atts )
        ));
      }

      $prepared_atts[ $name ] = $this->{'prepare_att__' . $name}( $value, $raw_atts );
    }

    $this->atts = $prepared_atts;
  }

  final protected function get_att( string $name ) : mixed
  {
    return $this->atts[ $name ];
  }

  private function get_content() : string
  {
    ob_start();

    $is_render_success = $this->render();

    if ( ! $is_render_success )
    {
      ob_clean();

      echo $this->get_ui_error_msg();
    }

    $content = ob_get_contents();

    ob_end_clean();

    return $content;
  }

  protected function get_ui_error_msg() : string
  {
    return '<b>Error. Can\'t render this content.</b>';
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

  abstract protected function render() : bool;

  private function maybe_enqueue_assets() : void
  {
    if ( $this->should_enqueue_assets() )
    {
      $this->enqueue_assets();
    }
  }

  abstract protected function should_enqueue_assets() : bool;

  abstract protected function enqueue_assets() : void;

  final protected function throw_notice( string $msg ) : void
  {
    $msg .= ' Shortcode name: "' . $this->get_name() . '".';

    trigger_error( $msg );
  }
}