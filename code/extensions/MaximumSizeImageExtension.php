<?php

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class MaximumSizeImageExtension extends DataExtension {
	public function MaxWidth($width) {
		$curWidth = $this->owner->getWidth();
		if ($curWidth > $width) {
			return $this->owner->SetWidth($width);
		}
		return $this->owner;
	}

	public function MaxHeight($height) {
		$curHeight = $this->owner->getWidth();
		if ($curHeight > $height) {
			return $this->owner->SetHeight($height);
		}
		return $this->owner;
	}
}
