<?php
namespace ACG\Views;

use ACG\Models\DatabaseModel;
use ACG\Services\GoogleFontsService;

class BlockView
{
    private $databaseModel;

    public function __construct(DatabaseModel $databaseModel)
    {
        $this->databaseModel = $databaseModel;
    }

    public function render($attributes, $content)
    {
        // Fetch data from the database
        $data = $this->databaseModel->getLastGeneratedEvent();
        GoogleFontsService::enqueueDynamicGoogleFonts();
        if (!$data) {
            return '<p>No content available.</p>';
        }
    
        // Allow certain HTML tags and attributes
        $allowed_html = array(
            'div' => array(
                'style' => true,
            ),
            'h1' => array(
                'style' => true,
            ),
            'h2' => array(
                'style' => true,
            ),
            'h3' => array(
                'style' => true,
            ),
            'h4' => array(
                'style' => true,
            ),
            'h5' => array(
                'style' => true,
            ),
            'h6' => array(
                'style' => true,
            ),
            'p' => array(
                'style' => true,
            ),
            'ul' => array(
                'style' => true,
            ),
            'ol' => array(
                'style' => true,
            ),
            'li' => array(
                'style' => true,
            ),
            // Add more allowed tags and attributes if needed
        );
    
        // Build HTML for the block
        $html = '<div class="acg-block">';
        $html .= wp_kses($data['content'], $allowed_html);
        $html .= '</div>';
    
        return $html;
    }
    

}
?>
