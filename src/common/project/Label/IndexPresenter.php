<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Project\Label;

class IndexPresenter
{
    /**
     * @var LabelPresenter[]
     */
    public $labels;
    public $title;
    public $name_label;
    public $has_labels;
    public $empty_state;
    public $filter_placeholder;
    public $empty_filter;
    public $is_used_label;
    public $this_label_is_used;

    public function __construct($title, CollectionOfLabelPresenter $collection)
    {
        $this->labels = $collection->getPresenters();
        $this->title  = $title;

        $this->has_labels = count($this->labels) > 0;

        $this->name_label         = _('Name');
        $this->is_used_label      = _('Is used?');
        $this->this_label_is_used = _('This label is used in the project');
        $this->empty_state        = _("No labels defined in this project");
        $this->empty_filter       = _("No matching labels");
        $this->filter_placeholder = _('Name');
    }
}
