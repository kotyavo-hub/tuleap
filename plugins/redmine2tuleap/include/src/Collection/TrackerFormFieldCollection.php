<?php

namespace Maximaster\Redmine2TuleapPlugin\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Tracker_FormElement_Field;

/**
 * @method Tracker_FormElement_Field[] getValues()
 */
class TrackerFormFieldCollection extends ArrayCollection
{
    /** @var Tracker_FormElement_Field[] */
    private $byLabel;

    public function byLabel(): array
    {
        if ($this->byLabel !== null) {
            return $this->byLabel;
        }

        $this->byLabel = [];

        foreach ($this->getValues() as $field) {
            $this->byLabel[ $field->getLabel() ] = $field;
        }

        return $this->byLabel;
    }
}
