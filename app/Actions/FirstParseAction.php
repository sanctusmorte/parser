<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class FirstParseAction extends AbstractAction
{
    public function getTitle()
    {
        return 'First parse';
    }

    public function getIcon()
    {
        return 'voyager-edit';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        $class = 'btn btn-sm btn-success pull-right';
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
        return route('parse-site-first', $this->data->id);
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'sites';
    }
}
