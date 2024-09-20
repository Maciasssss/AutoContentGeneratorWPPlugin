<?php
namespace ACG\Views;

class ContentListView
{
    public static function renderContentList($contentList, $currentPage, $totalPages)
    {
        ?>
        <div class="wrap">
            <h1>Manage Generated Content</h1>

            <?php 
            // Display success message if available
            $success_message = get_transient('acg_edit_success');
            if ($success_message) : ?>
                <div id="acg-success-notice" class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($success_message); ?></p>
                </div>
                <?php delete_transient('acg_edit_success'); ?>
            <?php endif; ?>

            <?php if (!empty($contentList)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Content</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contentList as $content) : ?>
                            <tr>
                                <td><?php echo esc_html($content->id); ?></td>
                                <td><?php echo esc_html(wp_trim_words($content->content, 20, '...')); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'acg-manage-content', 'edit_id' => $content->id], admin_url('admin.php'))); ?>" class="button">Edit</a>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'acg-manage-content', 'delete_id' => $content->id], admin_url('admin.php'))); ?>" class="button delete-content">Delete</a>
                                    <a href="?page=acg-manage-content&repost_id=<?php echo esc_attr($content->id); ?>" class="button">Repost</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($currentPage > 1) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'acg-manage-content', 'paged' => $currentPage - 1], admin_url('admin.php'))); ?>" class="button">« Previous</a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages) : ?>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'acg-manage-content', 'paged' => $currentPage + 1], admin_url('admin.php'))); ?>" class="button">Next »</a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p>No content found.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}



?>