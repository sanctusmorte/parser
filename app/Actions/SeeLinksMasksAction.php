<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class SeeLinksMasksAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Links masks';
    }

    public function getIcon()
    {
        return 'voyager-info-circled';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        $class = 'btn btn-sm btn-dark pull-right';
        $style = 'margin-right:20px;';

        if ($this->data->is_first_parsed) {
            $class = $class . ' disabled';
            $style = $style . 'background:gray;';
        }

        return [
            'class' => $class,
            'style' => $style,
        ];
    }

    public function getDefaultRoute()
    {
        return route('voyager.sites-filtered.show', $this->data->id);
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'sites';
    }
}
