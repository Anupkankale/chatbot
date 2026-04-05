<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - Leads</h1>
    
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'yallo_chatbot_leads';
    
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['lead_id'])) {
        check_admin_referer('delete_lead_' . $_GET['lead_id']);
        $wpdb->delete($table_name, array('id' => intval($_GET['lead_id'])), array('%d'));
        echo '<div class="notice notice-success"><p>Lead deleted successfully.</p></div>';
    }
    
    // Handle bulk delete
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && !empty($_POST['lead_ids'])) {
        check_admin_referer('yallo_bulk_action');
        $lead_ids = array_map('intval', $_POST['lead_ids']);
        foreach ($lead_ids as $lead_id) {
            $wpdb->delete($table_name, array('id' => $lead_id), array('%d'));
        }
        echo '<div class="notice notice-success"><p>' . count($lead_ids) . ' lead(s) deleted successfully.</p></div>';
    }
    
    // Get leads with pagination
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_leads / $per_page);
    
    $leads = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    ?>
    
    <div class="yallo-leads-stats" style="display: flex; gap: 20px; margin: 20px 0;">
        <div class="yallo-stat-card" style="background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; flex: 1;">
            <h3 style="margin: 0 0 10px 0; color: #BFA25E;">Total Leads</h3>
            <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo number_format($total_leads); ?></p>
        </div>
        
        <?php
        $today_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()");
        ?>
        <div class="yallo-stat-card" style="background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; flex: 1;">
            <h3 style="margin: 0 0 10px 0; color: #BFA25E;">Today's Leads</h3>
            <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo number_format($today_leads); ?></p>
        </div>
        
        <?php
        $week_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        ?>
        <div class="yallo-stat-card" style="background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; flex: 1;">
            <h3 style="margin: 0 0 10px 0; color: #BFA25E;">This Week</h3>
            <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo number_format($week_leads); ?></p>
        </div>
    </div>
    
    <?php if (empty($leads)): ?>
        <div class="notice notice-info">
            <p>No leads have been submitted yet. When visitors complete the chatbot consultation, their information will appear here.</p>
        </div>
    <?php else: ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('yallo_bulk_action'); ?>
            <input type="hidden" name="action" value="bulk_delete">
            
            <!-- Export CSV -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 10px;">
                <?php wp_nonce_field('yallo_export_leads'); ?>
                <input type="hidden" name="action" value="yallo_export_leads">
                <button type="submit" class="button button-secondary">Export CSV</button>
            </form>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <button type="submit" class="button action" onclick="return confirm('Are you sure you want to delete selected leads?');">Delete Selected</button>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total_leads); ?> items</span>
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php if ($page > 1): ?>
                                <a class="prev-page button" href="?page=yallo-chatbot-leads&paged=<?php echo ($page - 1); ?>">‹</a>
                            <?php endif; ?>
                            <span class="paging-input">
                                <span class="current-page"><?php echo $page; ?></span> of 
                                <span class="total-pages"><?php echo $total_pages; ?></span>
                            </span>
                            <?php if ($page < $total_pages): ?>
                                <a class="next-page button" href="?page=yallo-chatbot-leads&paged=<?php echo ($page + 1); ?>">›</a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" id="cb-select-all"></td>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Intent</th>
                        <th>Service Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="lead_ids[]" value="<?php echo esc_attr($lead->id); ?>">
                            </th>
                            <td><strong><?php echo esc_html($lead->name); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a></td>
                            <td><?php echo esc_html($lead->company); ?></td>
                            <td><?php echo esc_html($lead->initial_intent); ?></td>
                            <td><?php echo esc_html($lead->service_type); ?></td>
                            <td>
                                <select class="yallo-lead-status" data-lead-id="<?php echo esc_attr($lead->id); ?>">
                                    <?php
                                    $current_status = isset($lead->status) ? $lead->status : 'new';
                                    $statuses = array('new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'lost' => 'Lost');
                                    foreach ($statuses as $val => $label) {
                                        printf(
                                            '<option value="%s"%s>%s</option>',
                                            esc_attr($val),
                                            selected($current_status, $val, false),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($lead->created_at))); ?></td>
                            <td>
                                <a href="#" class="button button-small view-lead-details" data-lead-id="<?php echo esc_attr($lead->id); ?>">View Details</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yallo-chatbot-leads&action=delete&lead_id=' . $lead->id), 'delete_lead_' . $lead->id); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Are you sure you want to delete this lead?');">Delete</a>
                            </td>
                        </tr>
                        
                        <!-- Hidden details row -->
                        <tr id="lead-details-<?php echo esc_attr($lead->id); ?>" style="display: none;">
                            <td colspan="9" style="background: #f9f9f9; padding: 20px;">
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                    <div>
                                        <strong style="color: #BFA25E;">Full Name:</strong><br>
                                        <?php echo esc_html($lead->name); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Email:</strong><br>
                                        <a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Company:</strong><br>
                                        <?php echo esc_html($lead->company); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Location:</strong><br>
                                        <?php echo esc_html($lead->location); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Industry:</strong><br>
                                        <?php echo esc_html($lead->industry); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Platforms:</strong><br>
                                        <?php echo esc_html($lead->platforms); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Capabilities Gap:</strong><br>
                                        <?php echo esc_html($lead->capabilities); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Service Type:</strong><br>
                                        <?php echo esc_html($lead->service_type); ?>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <strong style="color: #BFA25E;">Pain Point:</strong><br>
                                        <?php echo nl2br(esc_html($lead->pain)); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Initial Intent:</strong><br>
                                        <?php echo esc_html($lead->initial_intent); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Lead Type:</strong><br>
                                        <?php echo esc_html($lead->lead_type); ?>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <strong style="color: #BFA25E;">Page URL:</strong><br>
                                        <a href="<?php echo esc_url($lead->page_url); ?>" target="_blank"><?php echo esc_html($lead->page_url); ?></a>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">IP Address:</strong><br>
                                        <?php echo esc_html($lead->ip_address); ?>
                                    </div>
                                    <div>
                                        <strong style="color: #BFA25E;">Submitted:</strong><br>
                                        <?php echo esc_html(date('F j, Y g:i A', strtotime($lead->created_at))); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a class="prev-page button" href="?page=yallo-chatbot-leads&paged=<?php echo ($page - 1); ?>">‹</a>
                        <?php endif; ?>
                        <span class="paging-input">
                            <span class="current-page"><?php echo $page; ?></span> of 
                            <span class="total-pages"><?php echo $total_pages; ?></span>
                        </span>
                        <?php if ($page < $total_pages): ?>
                            <a class="next-page button" href="?page=yallo-chatbot-leads&paged=<?php echo ($page + 1); ?>">›</a>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="lead_ids[]"]').prop('checked', this.checked);
    });
    
    // View lead details toggle
    $('.view-lead-details').on('click', function(e) {
        e.preventDefault();
        var leadId = $(this).data('lead-id');
        $('#lead-details-' + leadId).toggle();

        if ($('#lead-details-' + leadId).is(':visible')) {
            $(this).text('Hide Details');
        } else {
            $(this).text('View Details');
        }
    });

    // Lead status update via AJAX
    $(document).on('change', '.yallo-lead-status', function() {
        var $select = $(this);
        var leadId  = $select.data('lead-id');
        var status  = $select.val();

        $.post(ajaxurl, {
            action:  'yallo_update_lead_status',
            nonce:   '<?php echo wp_create_nonce('yallo_chatbot_nonce'); ?>',
            lead_id: leadId,
            status:  status
        }, function(response) {
            if (response.success) {
                $select.css('border-color', '#46b450');
                setTimeout(function() { $select.css('border-color', ''); }, 1500);
            } else {
                alert('Failed to update status.');
                location.reload();
            }
        });
    });
});
</script>

<style>
.yallo-leads-stats {
    margin: 20px 0;
}

.yallo-stat-card h3 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.wp-list-table th {
    font-weight: 600;
}

.button.view-lead-details {
    background: #BFA25E;
    color: #000;
    border-color: #BFA25E;
}

.button.view-lead-details:hover {
    background: #d4b670;
    border-color: #d4b670;
}

.yallo-lead-status {
    font-size: 12px;
    padding: 3px 6px;
    border-radius: 4px;
    border: 1px solid #ccc;
    transition: border-color 0.3s;
}

.yallo-lead-status option[value="new"]       { color: #0073aa; }
.yallo-lead-status option[value="contacted"] { color: #ff8c00; }
.yallo-lead-status option[value="converted"] { color: #46b450; }
.yallo-lead-status option[value="lost"]      { color: #dc3232; }
</style>
