<?php
/**
 * Plugin Name: Custom Attributes AIQ - Gravity Forms Webhooks
 * Description: Adds a dropdown to webhook field mappings to designate fields as custom attributes
 * Version: 1.0
 * Author: Brian Paknoosh
 */

// Prevent direct access
defined('ABSPATH') || die('No direct script access allowed.');

class GF_Custom_Attributes_Manager {
    
    // Settings key
    private $option_key = 'gf_custom_attributes_settings';
    
    // Admin page slug
    private $page_slug = 'gf-custom-attributes';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Transform webhook data - Order matters! Convert integer first, then handle custom attributes
        add_filter('gform_webhooks_request_data', array($this, 'ensure_integer_favorite_store_id'), 9, 4);
        add_filter('gform_webhooks_request_data', array($this, 'transform_webhook_data'), 10, 4);
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            'GF Custom Attributes',
            'GF Custom Attributes',
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            $this->option_key,
            $this->option_key,
            array('sanitize_callback' => array($this, 'sanitize_settings'))
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        if (!empty($input) && is_array($input)) {
            foreach ($input as $feed_id => $fields) {
                $feed_id = sanitize_text_field($feed_id);
                
                // Handle fields as comma-separated string
                if (is_string($fields)) {
                    $fields = explode(',', $fields);
                    $fields = array_map('trim', $fields);
                    $fields = array_filter($fields);
                }
                
                if (!empty($fields)) {
                    $sanitized_input[$feed_id] = array_map('sanitize_text_field', $fields);
                }
            }
        }
        
        return $sanitized_input;
    }
    
    /**
     * Convert favoriteStoreID to integer
     */
    public function ensure_integer_favorite_store_id($request_data, $feed, $entry, $form) {
        if (isset($request_data['favoriteStoreID'])) {
            // Convert to integer
            $favoriteStoreID = intval($request_data['favoriteStoreID']);
            
            // Ensure it's a positive integer
            if ($favoriteStoreID > 0) {
                $request_data['favoriteStoreID'] = $favoriteStoreID;
            } else {
                // If conversion fails or results in 0 or negative, remove the field
                unset($request_data['favoriteStoreID']);
                
                // Optionally, log this issue
                error_log('Invalid favoriteStoreID value: ' . $request_data['favoriteStoreID']);
            }
        }
        
        return $request_data;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current settings
        $settings = get_option($this->option_key, array());
        
        // Save settings
        if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'gf_custom_attributes_save')) {
            $new_settings = array();
            
            if (isset($_POST['feed_settings']) && is_array($_POST['feed_settings'])) {
                foreach ($_POST['feed_settings'] as $index => $feed_data) {
                    if (!empty($feed_data['feed_id']) && isset($feed_data['fields'])) {
                        $feed_id = sanitize_text_field($feed_data['feed_id']);
                        $fields = explode(',', $feed_data['fields']);
                        $fields = array_map('trim', $fields);
                        $fields = array_filter($fields);
                        
                        if (!empty($fields)) {
                            $new_settings[$feed_id] = $fields;
                        }
                    }
                }
            }
            
            update_option($this->option_key, $new_settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
            
            // Refresh settings
            $settings = $new_settings;
        }
        
        ?>
        <div class="wrap">
            <h1>Gravity Forms Custom Attributes Manager</h1>
            
            <p>
                Configure which fields should be sent as custom attributes in your webhook payloads.
                Enter the webhook feed ID and a comma-separated list of field keys to include as custom attributes.
            </p>
            
            <form method="post" action="">
                <?php wp_nonce_field('gf_custom_attributes_save'); ?>
                
                <table class="widefat" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Webhook Feed ID</th>
                            <th>Custom Attribute Fields (comma-separated)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="feed-settings">
                        <?php 
                        // Output existing settings
                        $row_count = 0;
                        if (!empty($settings)) {
                            foreach ($settings as $feed_id => $fields) {
                                $row_count++;
                                ?>
                                <tr class="feed-row">
                                    <td>
                                        <input type="text" name="feed_settings[<?php echo $row_count; ?>][feed_id]" 
                                               value="<?php echo esc_attr($feed_id); ?>" class="regular-text">
                                    </td>
                                    <td>
                                        <input type="text" name="feed_settings[<?php echo $row_count; ?>][fields]" 
                                               value="<?php echo esc_attr(implode(', ', $fields)); ?>" class="regular-text">
                                    </td>
                                    <td>
                                        <button type="button" class="button remove-row">Remove</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        
                        // Always add an empty row
                        $row_count++;
                        ?>
                        <tr class="feed-row">
                            <td>
                                <input type="text" name="feed_settings[<?php echo $row_count; ?>][feed_id]" 
                                       value="" class="regular-text" placeholder="Enter Feed ID">
                            </td>
                            <td>
                                <input type="text" name="feed_settings[<?php echo $row_count; ?>][fields]" 
                                       value="" class="regular-text" placeholder="website, bajolalunarsvp, etc.">
                            </td>
                            <td>
                                <button type="button" class="button remove-row">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p>
                    <button type="button" id="add-row" class="button">Add New Feed</button>
                </p>
                
                <p class="description">
                    <strong>How to find your feed ID:</strong> Go to the webhook feed settings page and look at the URL. 
                    The "fid" parameter is your feed ID (e.g., in the URL "...&fid=5", the feed ID is 5).
                </p>
                
                <p>
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>
            
            <hr>
            
            <h2>Additional Features</h2>
            <p><strong>favoriteStoreID Conversion:</strong> This plugin automatically converts the 'favoriteStoreID' field to a proper integer in all webhook payloads.</p>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Add new row
                $('#add-row').on('click', function() {
                    var rowCount = $('.feed-row').length;
                    var newRow = `
                        <tr class="feed-row">
                            <td>
                                <input type="text" name="feed_settings[${rowCount + 1}][feed_id]" 
                                       value="" class="regular-text" placeholder="Enter Feed ID">
                            </td>
                            <td>
                                <input type="text" name="feed_settings[${rowCount + 1}][fields]" 
                                       value="" class="regular-text" placeholder="website, bajolalunarsvp, etc.">
                            </td>
                            <td>
                                <button type="button" class="button remove-row">Remove</button>
                            </td>
                        </tr>
                    `;
                    $('#feed-settings').append(newRow);
                });
                
                // Remove row
                $(document).on('click', '.remove-row', function() {
                    if ($('.feed-row').length > 1) {
                        $(this).closest('tr').remove();
                    } else {
                        alert('You need at least one row.');
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Transform webhook data to include customAttributes
     */
    public function transform_webhook_data($request_data, $feed, $entry, $form) {
        // Get settings
        $settings = get_option($this->option_key, array());
        
        // Get feed ID
        $feed_id = $feed['id'];
        
        // Check if we have settings for this feed
        if (!isset($settings[$feed_id])) {
            return $request_data;
        }
        
        // Get the custom attribute fields
        $custom_attr_fields = $settings[$feed_id];
        
        // Initialize custom attributes array
        $custom_attributes = array();
        
        // Build custom attributes
        foreach ($custom_attr_fields as $field_key) {
            if (isset($request_data[$field_key])) {
                $custom_attributes[] = array(
                    'key' => $field_key,
                    'value' => $request_data[$field_key]
                );
                
                // Remove from standard request data
                unset($request_data[$field_key]);
            }
        }
        
        // Add custom attributes to request data
        if (!empty($custom_attributes)) {
            $request_data['customAttributes'] = $custom_attributes;
        }
        
        return $request_data;
    }
}

// Initialize plugin
new GF_Custom_Attributes_Manager();