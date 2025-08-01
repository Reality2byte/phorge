<?php

final class PHUIFormationResizerView
  extends PHUIFormationColumnView {

  private $isVisible;

  public function setIsVisible($is_visible) {
    $this->isVisible = $is_visible;
    return $this;
  }

  public function getIsVisible() {
    return $this->isVisible;
  }

  public function getWidth() {
    return 8;
  }

  /**
   * @return PhutilSafeHTML
   */
  public function render() {
    $width = $this->getWidth();
    $style = sprintf('width: %dpx;', $width);

    return phutil_tag(
      'div',
      array(
        'id' => $this->getID(),
        'aria-hidden' => 'true',
        'class' => 'phui-formation-resizer',
        'style' => $style,
      ));
  }

}
