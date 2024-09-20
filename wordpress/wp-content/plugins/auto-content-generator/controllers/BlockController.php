<?php

namespace ACG\Controllers;

use ACG\Views\BlockView;
use ACG\Models\DatabaseModel;

class BlockController
{
    private $blockView;
    private $databaseModel;

    public function __construct(BlockView $blockView, DatabaseModel $databaseModel)
    {
        $this->blockView = $blockView;
        $this->databaseModel = $databaseModel;

        add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock()
    {
        register_block_type('acg/block', [
            'render_callback' => [$this, 'renderBlock'],
            'editor_script' => 'acg-block-editor-script',
            'editor_style' => 'acg-block-editor-style',
            'style' => 'acg-block-style',
        ]);
    }

    public function renderBlock($attributes, $content)
    {
        return $this->blockView->render($attributes, $content);
    }
}
