<?php
namespace ACG\Views;

class DocumentationView{
    public static function documentation_page_html() {
        ?>
        <div class="wrap">
            <h1>ACG Documentation</h1>
            <div class="acg-documentation">
                <h2>Getting Started</h2>
                <p>Follow these steps to set up and use the ACG plugin:</p>
                <ol>
                    <li>Configure your API key in the settings page.</li>
                    <li>Select your preferred content category and title.</li>
                    <li>Click "Generate Content" to create new content.</li>
                    <li>Check the generated content in the designated area.</li>
                </ol>
                <h2>Settings</h2>
                <p>Descriptions of each setting and how they affect content generation:</p>
                <ul>
                    <li><strong>Prompt:</strong> The main idea or topic for the content.</li>
                    <li><strong>Category:</strong> The category under which the content will be generated.</li>
                    <li><strong>Title:</strong> The title for the generated content.</li>
                    <li><strong>Schedule Frequency:</strong> How often new content should be generated.</li>
                </ul>
                <h2>Advanced Usage</h2>
                <p>Here is how you can creat more relevant prompts:</p>
                <ul>
                    <li>Using custom prompts to tailor content more precisely.</li>
                    <li>Managing and editing generated content before publishing.</li>
                    <li>Integrating with other plugins and workflows.</li>
                </ul>
                <h2>FAQ</h2>
                <p>Common questions and answers:</p>
                <ul>
                    <li><strong>Q:</strong> How do I set up my API key?<br><strong>A:</strong> Go to the settings page and enter your API key in the designated field.</li>
                    <li><strong>Q:</strong> What do I do if the generated content is not relevant?<br><strong>A:</strong> Try adjusting the prompt and other settings to better guide the content generation.</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
}
?>