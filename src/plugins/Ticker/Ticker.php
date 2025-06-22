<?php

namespace Opensitez\Simplicity\Plugins;

/* inspired from https://codepen.io/KenanYusuf/pen/eRprzN */

class Ticker extends \Opensitez\Simplicity\Plugin
{
  public $name = "Ticker";
  public $description = "Simple Ticker";
  public function get_css()
  {

    $css = "<style>
            .ticker {
                display: flex;
              }
              .ticker__list {
                display: flex;
                margin-top: 20px;
                animation: ticker 15s infinite linear;
              }
              .ticker:hover .ticker__list {
                animation-play-state: paused;
              }
              .ticker__item {
                margin-right: 20px;
              }
              @-moz-keyframes ticker {
                100% {
                  transform: translateX(-100%);
                }
              }
              @-webkit-keyframes ticker {
                100% {
                  transform: translateX(-100%);
                }
              }
              @-o-keyframes ticker {
                100% {
                  transform: translateX(-100%);
                }
              }
              @keyframes ticker {
                100% {
                  transform: translateX(-100%);
                }
              }\n</style>\n";

    return $css;
  }
  public function render_page($app)
  {
    $entries = $app['values'] ?? [];
    $output = $this->get_css();
    $output .= '<div class="ticker">';
    $output .= "<div class='ticker__list'>";
    foreach ($entries as $entry) {
      $output .= '<div class="ticker__item">';
      if (is_array($entry)) {
        $entry = implode("\n", $entry) . "\n";
      }
      $output .= $entry;
      $output .= "</div>\n";
    }
    $output .= "</div>\n\</div>";
    return $output;
  }
  public function on_render_page($app)
  {

    $output = $this->render_page($app);
    //print "hi world" . $output;
    return $output;
  }
}
