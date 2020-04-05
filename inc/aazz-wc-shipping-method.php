<?php
defined( 'ABSPATH' ) || die( 'Cheating, huh? Direct access to this file is not allowed !!!!' );

add_action('woocommerce_shipping_init', 'aazz_wc_table_rate_init');

function aazz_wc_shipping_method($methods) {
    $methods['aazz_wc_table_rate'] = 'Aazz_Wc_Table_Rate_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'aazz_wc_shipping_method');


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function aazz_wc_table_rate_init ()
    {
        if (!class_exists('Aazz_Wc_Table_Rate_Shipping_Method')) {
            class Aazz_Wc_Table_Rate_Shipping_Method extends WC_Shipping_Method
            {
                //all variables
                public $aazz_wc_shipping_method_order_option;
                public $aazz_wc_zones_settings;
                public $aazz_wc_rates_settings;
                public $aazz_wc_option_key;
                public $aazz_wc_shipping_methods_option;
                public $aazz_wc_condition_array;
                public $options;
                public $aazz_wc_country_array;
                public $counter;

                public function __construct($instance_id = 0) {
                    $this->instance_id = absint($instance_id);
                    $this->id = 'aazz_wc_table_rate';      // Id for your shipping method. Should be uunique.
                    $this->method_title = __('Table Rate Shipping', AAZZ_WC_TEXTDOMAIN);  // Title shown in admin
                    $this->method_description = __('Charge varying rates based on total price and weight', AAZZ_WC_TEXTDOMAIN); // Description shown in admin
                    $this->aazz_wc_shipping_method_order_option = 'aazz_wc_table_rate_shipping_method_order_' . $this->instance_id;
                    $this->supports = array(
                        'shipping-zones',
                        'instance-settings',
                    );
                    $this->aazz_wc_zones_settings = $this->id . 'aazz_wc_zones_settings';
                    $this->aazz_wc_rates_settings = $this->id . 'aazz_wc_rates_settings';
                    $this->enabled = "yes";         // This can be added as an setting but for this example its forced enabled
                    $this->title = "Table Rate Shipping";     // This can be added as an setting but for this example its forced.

                    $this->aazz_wc_option_key = $this->id . 'aazz_wc_table_rates';   //The key for wordpress options
                    $this->aazz_wc_shipping_methods_option = 'aazz_wc_table_rate_shipping_methods_' . $this->instance_id;
                    $this->options = array();         //the actual tabel rate options saved
                    $this->aazz_wc_condition_array = array();    //holds an array of CONDITIONS for the select
                    $this->aazz_wc_country_array = array();     //holds an array of COUNTRIES for the select
                    $this->counter = 0;         //we use this to keep unique names for the rows


                    $this->title = $this->get_option('title');

                    $this->init();
                    if ( version_compare( WC()->version, '2.6' ) < 0  && $this->get_option( 'enabled', 'yes' ) == 'no' ) {
                        $this->enabled		    = $this->get_option( 'enabled' );
                    }
                    $this->title = $this->get_option('title');

                    $this->get_options();           //load the options
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                public function init() {
                    $this->instance_form_fields = array(
                        'title' => array(
                            'title' => __('Checkout Title', AAZZ_WC_TEXTDOMAIN),
                            'description' => __('This controls the title which the user sees during checkout.', AAZZ_WC_TEXTDOMAIN),
                            'type' => 'text',
                            'default' => 'Table Rate Shipping',
                            'desc_tip' => true
                        ),

                    );
                    // Load the settings API
                    $this->init_form_fields();  // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings();  // This is part of the settings API. Loads settings you previously init.


                    //select array
                    $this->create_select_arrays();

                    // save settings features
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

                }

                //woocommerce default form field
                public function init_form_fields() {

                    $this->form_fields = array(
                        'shipping_list' => array(
                            'title' => __('Shipping Methods', AAZZ_WC_TEXTDOMAIN),
                            'type' => 'shipping_list',
                            'description' => '',
                        )
                    );
                }


                //generate html for all options
                public function generate_table_rates_table_html($key, $data) {
                    ob_start();
                    if (isset($_GET['action'])) {
                        $get_action_name = $_GET['action'];
                    }
                    ?>
                    <script>
                        jQuery(document).ready(function(){
                            //add shipping box on page load by default. // removes an ability to click on "Add New Shipping Zone" button
                            if( jQuery('.aazz-raterow').length == 0 ){
                                var zoneID = "#" + pluginID + "_settings";
                                //ok lets add a row!
                                var id = "#" + pluginID + "_settings table tbody tr:last";
                                //create empty row
                                var row = {};
                                row.key = "";
                                row.min = [];
                                row.rates = [];
                                row.condition = [];
                                row.countries = [];
                                jQuery(id).before(create_zone_row(row));
                            }
                        });
                        jQuery("#mc_button").click(function (e) {
                            e.preventDefault();
                            console.log('clicked');
                            data = {};

                        });

                    </script>

                    <tr>
                        <th scope="row" class="titledesc"><?php _e('Table Rates', AAZZ_WC_TEXTDOMAIN); ?></th>
                        <td id="<?php echo $this->id; ?>_settings">
                            <table class="shippingrows widefat">
                                <col style="width:0%">
                                <col style="width:0%">
                                <col style="width:0%">
                                <col style="width:100%;">
                                <!--<thead>
                                    <tr>
                                        <th class="check-column"></th>
                                        <th>Shipping Zone Name</th>
                                        <th>Condition</th>
                                        <th>Countries</th>
                                    </tr>
                                </thead> -->
                                <tbody style="border: 1px solid black;">
                                <tr style="border: 1px solid black;">
                                    <!--<td colspan="5" class="add-zone-buttons">
                                        <a href="#" class="add button">Add New Shipping Zone</a>
                                        <a href="#" class="delete button">Delete Selected Zones</a>
                                    </td>-->
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php
                    $zone = WC_Shipping_Zones::get_zone_by('instance_id', $_GET['instance_id']);
                    $get_shipping_method_by_instance_id = WC_Shipping_Zones::get_shipping_method($_GET['instance_id']);
                    $link_content = '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping') . '">' . __('Shipping Zones', 'woocommerce') . '</a> &gt ';
                    $link_content .= '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&zone_id=' . absint($zone->get_id())) . '">' . esc_html($zone->get_zone_name()) . '</a> &gt ';
                    $link_content .= '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&instance_id=' . $_GET['instance_id']) . '">' . esc_html($get_shipping_method_by_instance_id->get_title()) . '</a>';
//                                        <!--check action is new or edit-->
                    if ($get_action_name == 'new') {
                        $link_content .= ' &gt ';
                        $link_content .= __('Add New', AAZZ_WC_TEXTDOMAIN);
                        ?>
                        <script>
                            jQuery("#mainform h2").first().replaceWith('<h2>' + '<?php echo $link_content; ?>' + '</h2>');
                            var options = <?php echo json_encode($this->aazz_wc_dropdown()); ?>;

                            var aazz_wc_country_array = <?php echo json_encode($this->aazz_wc_country_array); ?>;
                            var aazz_wc_condition_array = <?php echo json_encode($this->aazz_wc_condition_array); ?>;
                            var pluginID = <?php echo json_encode($this->id); ?>;
                            console.log('test NISL 1');
                            var lastID = 0;

                            <?php
                            //
                            foreach ($this->options as $key => $value) {
                                global $row;
                                //add the key back into the json object
                                $value['key'] = $key;
                                $row = json_encode($value);
                                echo "jQuery('#{$this->id}_settings table tbody tr:last').before(create_zone_row({$row}));\n";
                            }
                            ?>

                            /**
                             * This creates a new ZONE row
                             */
                            function create_zone_row(row) {

                                //lets get the ID of the last one

                                var el = '#' + pluginID + '_settings .aazz-zone-row';
                                lastID = jQuery(el).last().attr('id');

                                //Handle no rows
                                if (typeof lastID == 'undefined' || lastID == "") {
                                    lastID = 1;
                                } else {
                                    lastID = Number(lastID) + 1;
                                }

                                var html = '\
                                                        <tr style="display:none;" id="' + lastID + '" class="aazz-zone-row" >\
                                                                <input type="hidden" value="' + lastID + '" name="key[' + lastID + ']"></input>\
                                                                <td><input type="hidden" size="30" name="zone-name[' + lastID + ']"/></td>\
                                                        </tr>\
                                        ';

                                //This is the expandable/collapsable row for that holds the rates
                                html += '\
                                                <tr class="aazz-rate-holder">\
                                                        <td colspan="3">\
                                                                <table class="aazz-rate-table shippingrows widefat" id="' + lastID + '_rates">\
                                                                        <thead>\
                                                                                <tr>\
                                                                                        <th></th>\
																						<th style="width: 30%">Based on</th>\
                                                                                        <th style="width: 30%">Min Value</th>\
                                                                                        <th style="width: 30%">Max Value</th>\
                                                                                        <th style="width: 40%">Shipping Rate</th>\
                                                                                </tr>\
                                                                        </thead>\
                                                                        ' + create_rate_row(lastID, row) + '\
                                                                        <tr>\
                                                                                <td colspan="4" class="add-rate-buttons">\
                                                                                        <a href="#" class="add button" name="key_' + lastID + '">Add New Rate</a>\
                                                                                        <a href="#" class="delete button">Delete Selected Rates</a>\
                                                                                </td>\
                                                                        </tr>\
                                                                </table>\
                                                        </td>\
                                                </tr>\
                                        ';

                                return html;
                            }

                            /**
                             * This creates a new RATE row
                             * The container Table is passed in and this row is added to it
                             */
                            function create_rate_row(lastID, row) {


                                if (row == null || row.rates.length == 0) {
                                    //lets manufacture a rows
                                    //create dummy row
                                    var row = {};
                                    row.key = "";
                                    row.condition = [""];
                                    row.countries = [];
                                    row.rates = [];
                                    row.rates.push([]);
                                    row.rates[0].min = "";
                                    row.rates[0].max = "";
                                    row.rates[0].shipping = "";
                                }
                                //loop thru all the rate data and create rows

                                //handles if there are no rate rows yet
                                if (typeof (row.min) == 'undefined' || row.min == null) {
                                    row.min = [];
                                }

                                var html = '';
                                for (var i = 0; i < 1; i++) {
                                    html += '\
                                                        <tr>\
                                                                <td>\
                                                                        <input type="checkbox" class="aazz-rate-checkbox" id="' + lastID + '"></input>\
                                                                </td>\
																<td>\
                                                                        <select name="conditions[' + lastID + '][]">\
                                                                        ' + generate_condition_html() + '\
                                                                        </select>\
                                                                </td>\
                                                                <td>\
                                                                        <input type="text" size="20" placeholder="" name="min[' + lastID + '][]"></input>\
                                                                </td>\
                                                                <td>\
                                                                        <input type="text" size="20" placeholder="" name="max[' + lastID + '][]"></input>\
                                                                </td>\
                                                                <td>\
                                                                        <input type="text" size="10" placeholder="" name="shipping[' + lastID + '][]"></input>\
                                                                </td>\
                                                        </tr>\
                                                ';



                                }


                                return html;
                            }

                            /**
                             * Handles the expansion contraction of the rate table for the zone
                             */
                            function expand_contract() {

                                var row = jQuery(this).parent('td').parent('tr').next();

                                if (jQuery(row).hasClass('aazz-hidden-row')) {
                                    jQuery(row).removeClass('aazz-hidden-row').addClass('aazz-show-row');
                                    jQuery(this).removeClass('expand-icon').addClass('collapse-icon');
                                } else {
                                    jQuery(row).removeClass('aazz-show-row').addClass('aazz-hidden-row');
                                    jQuery(this).removeClass('collapse-icon').addClass('expand-icon');
                                }



                            }


                            //**************************************
                            // Generates the HTML for the country
                            // select. Uses an array of keys to
                            // determine which ones are selected
                            //**************************************
                            function generate_country_html(keys) {

                                html = "";

                                for (var key in aazz_wc_country_array) {

                                    html += '<option value="' + key + '">' + aazz_wc_country_array[key] + '</option>';

                                }

                                return html;
                            }


                            //**************************************
                            // Generates the HTML for the CONDITION
                            // select. Uses an array of keys to
                            // determine which ones are selected
                            //**************************************
                            function generate_condition_html(keys) {

                                html = "";

                                for (var key in aazz_wc_condition_array) {

                                    html += '<option value="' + key + '">' + aazz_wc_condition_array[key] + '</option>';
                                }

                                return html;
                            }

                            //***************************
                            // Handle add/delete clicks
                            //***************************

                            //ZONE TABLE


                            /*
                             * add new ZONE row
                             */
                            var zoneID = "#" + pluginID + "_settings";

                            jQuery(zoneID).on('click', '.add-zone-buttons a.add', function () {

                                //ok lets add a row!


                                var id = "#" + pluginID + "_settings table tbody tr:last";
                                //create empty row
                                var row = {};
                                row.key = "";
                                row.min = [];
                                row.rates = [];
                                row.condition = [];
                                row.countries = [];
                                jQuery(id).before(create_zone_row(row));

                                //turn on select2 for our row
                                if (jQuery().chosen) {
                                    jQuery("select.chosen_select").chosen({
                                        width: '350px',
                                        disable_search_threshold: 5
                                    });
                                } else {
                                    jQuery("select.chosen_select").select2();
                                }


                                return false;
                            });

                            /**
                             * Delete ZONE row
                             */
                            jQuery(zoneID).on('click', '.add-zone-buttons a.delete', function () {

                                //loop thru and see what is checked - if it is zap it!
                                var rowsToDelete = jQuery(this).closest('table').find('.aazz-zone-checkbox:checked');

                                jQuery.each(rowsToDelete, function () {

                                    var thisRow = jQuery(this).closest('tr');
                                    //first lets get the next sibl;ing to this row
                                    var nextRow = jQuery(thisRow).next();

                                    //it should be a rate row
                                    if (jQuery(nextRow).hasClass('aazz-rate-holder')) {
                                        //remove it!
                                        jQuery(nextRow).remove();
                                    } else {
                                        //trouble at mill
                                        return;
                                    }

                                    jQuery(thisRow).remove();
                                });

                                //TODO - need to delete associated RATES

                                return false;
                            });


                            //RATE TABLES

                            /**
                             * ADD RATE BUTTON
                             */
                            jQuery(zoneID).on('click', '.add-rate-buttons a.add', function () {

                                //we need to get the key of this zone - it's in the name of of the button
                                var name = jQuery(this).attr('name');
                                name = name.substring(4);

                                //remove key_
                                //ok lets add a row!


                                var row = create_rate_row(name, null);
                                jQuery(this).closest('tr').before(row);

                                return false;
                            });

                            /**
                             * Delete RATE roe
                             */
                            jQuery(zoneID).on('click', '.add-rate-buttons a.delete', function () {

                                //loop thru and see what is checked - if it is zap it!
                                var rowsToDelete = jQuery(this).closest('table').find('.aazz-rate-checkbox:checked');

                                jQuery.each(rowsToDelete, function () {
                                    jQuery(this).closest('tr').remove();
                                });


                                return false;
                            });

                            //These handle building the select arras


                            <?php
                            echo "jQuery('#{$this->id}_settings').on('click', '.aazz-expansion', expand_contract) ;\n";
                            ?>
                        </script>
                        <?php
                    } else {
                        $method_id = $_GET['method_id'];
                        $get_shipping_methods_options = get_option($this->aazz_wc_shipping_methods_option, array());
                        $shipping_method_array = $get_shipping_methods_options[$method_id];
                        $get_selected_method_title = $shipping_method_array['method_title'];
                        if (isset($shipping_method_array['method_title']) && $shipping_method_array['method_title'] != '') {
                            $link_content .= ' &gt ';
                            $link_content .= esc_html($get_selected_method_title);
                        }
                        ?>
                        <script>
                            jQuery('#mainform h2').first().replaceWith('<h2>' + '<?php echo $link_content; ?>' + '</h2>');
                            var options = <?php echo json_encode($this->aazz_wc_dropdown()); ?>;

                            var aazz_wc_country_array = <?php echo json_encode($this->aazz_wc_country_array); ?>;
                            var aazz_wc_condition_array = <?php echo json_encode($this->aazz_wc_condition_array); ?>;
                            var pluginID = <?php echo json_encode($this->id); ?>;
                            console.log('test NISL 2');
                            var lastID = 0;

                            <?php
                                    //!!
                            $shipping_method_key = $this->aazz_wc_option_key . '_' . $method_id;
                            if (isset($data['default'])) {
                                foreach ($data['default'] as $key => $value) {
                                    global $row;
                                    //add the key back into the json object
                                    $value['key'] = $key;
                                    $row = json_encode($value);
                                    echo "jQuery('#{$this->id}_settings table tbody tr:last').before(create_zone_row({$row}));\n";
                                }
                            }
                            ?>





                            /**
                             * This creates a new ZONE row
                             */
                            function create_zone_row(row) {

                                //lets get the ID of the last one

                                var el = '#' + pluginID + '_settings .aazz-zone-row';
                                lastID = jQuery(el).last().attr('id');

                                //Handle no rows
                                if (typeof lastID == 'undefined' || lastID == "") {
                                    lastID = 1;
                                } else {
                                    lastID = Number(lastID) + 1;
                                }

                                var html = '\
                                                        <tr style="display:none;" id="' + lastID + '" class="aazz-zone-row" >\
                                                                <input type="hidden" value="' + lastID + '" name="key[' + lastID + ']"></input>\
                                                                <td><input type="hidden" size="30" value="zone-' + lastID + '"  name="zone-name[' + lastID + ']"/></td>\
                                                        </tr>\
                                        ';

                                //This is the expandable/collapsable row for that holds the rates
                                html += '\
                                                <tr class="aazz-rate-holder">\
                                                        <td colspan="3">\
                                                                <table class="aazz-rate-table shippingrows widefat" id="' + lastID + '_rates">\
                                                                        <thead>\
                                                                                <tr>\
                                                                                        <th></th>\
																						<th style="width: 25%"><?php _e('Based on', AAZZ_WC_TEXTDOMAIN); ?><span class="woocommerce-help-tip" data-tip="<?php _e( 'Shipping cost will be calculated based on the selected parameter.', AAZZ_WC_TEXTDOMAIN ); ?>"></span></th>\
                                                                                        <th style="width: 25%"><?php _e('Min Value', AAZZ_WC_TEXTDOMAIN); ?> <span class="woocommerce-help-tip" data-tip="<?php _e( 'Enter minimum value for the &quot;Based on&quot; parameter. Value based on the price will be calculated by WooCommerce tax settings &quot;Display prices during cart and checkout&quot;', AAZZ_WC_TEXTDOMAIN ); ?>"></span></th>\
                                                                                                                    <th style="width: 25%"><?php _e('Max Value', AAZZ_WC_TEXTDOMAIN); ?><span class="woocommerce-help-tip" data-tip="<?php _e('Enter maximum value for the &quot;Based on&quot; parameter. Value based on the price will be calculated by WooCommerce tax settings &quot;Display prices during cart and checkout&quot;', AAZZ_WC_TEXTDOMAIN ); ?>"></th>\
                                                                                                                                                <th style="width: 25%">Shipping Rate</th>\
                                                                                                                                        </tr>\
                                                                                                                                </thead>\
                                                                                                                                ' + create_rate_row(lastID, row) + '\
                                                                                                                                <tr>\
                                                                                                                                        <td colspan="5" class="add-rate-buttons">\
                                                                                                                                                <a href="#" class="add button" name="key_' + lastID + '">Add New Rate</a>\
                                                                                                                                                <a href="#" class="delete button">Delete Selected Rates</a>\
                                                                                                                                        </td>\
                                                                                                                                </tr>\
                                                                                                                        </table>\
                                                                                                                </td>\
                                                                                                        </tr>\
                                                                                                ';

                                                                                        return html;
                                                                                }

                                                                                    /**
                                                                                     * This creates a new RATE row
                                                                                     * The container Table is passed in and this row is added to it
                                                                                     */
                                                                                    function create_rate_row(lastID, row) {

                                                                                        if (row == null || row.rates.length == 0) {
                                                                                            //lets manufacture a rows
                                                                                            //create dummy row
                                                                                            var row = {};
                                                                                            row.key = "";
                                                                                            row.condition = [""];
                                                                                            // row.countries = [];
                                                                                            row.rates = [];
                                                                                            row.rates.push([]);
                                                                                            row.rates[0].condition = "";
                                                                                            row.rates[0].min = "";
                                                                                            row.rates[0].max = "";
                                                                                            row.rates[0].shipping = "";
                                                                                        }
                                                                                        //loop thru all the rate data and create rows

                                                                                        //handles if there are no rate rows yet
                                                                                        if (typeof (row.min) == 'undefined' || row.min == null) {
                                                                                            row.min = [];
                                                                                        }
                                                                                        var html = '';
                                                                                        for (var i = 0; i < row.rates.length; i++) {
                                                                                            html += '\
                                                                                                                <tr class="aazz-raterow">\
                                                                                                                        <td>\
                                                                                                                                <input type="checkbox" class="aazz-rate-checkbox" id="' + lastID + '"></input>\
                                                                                                                        </td>\
                                                                                                                        <td>\
                                                                                                                                <select class="'+ row.rates[i].condition +'" name="conditions[' + lastID + '][]">\
                                                                                                                                ' + generate_condition_html(row.rates[i].condition) + '\
                                                                                                                                </select>\
                                                                                                                        </td>\
                                                                                                                        <td>\
                                                                                                                                <input type="text" size="20" placeholder="" name="min[' + lastID + '][]" value="' + row.rates[i].min + '"/>\
                                                                                                                        </td>\
                                                                                                                        <td>\
                                                                                                                                <input type="text" size="20" placeholder="" name="max[' + lastID + '][]" value="' + row.rates[i].max + '"></input>\
                                                                                                                        </td>\
                                                                                                                        <td>\
                                                                                                                                <input type="text" size="10" placeholder="" name="shipping[' + lastID + '][]" value="' + row.rates[i].shipping + '"></input>\
                                                                                                                        </td>\
                                                                                                                </tr>\
                                                                                                        ';



                                                                                        }


                                                                                        return html;
                                                                                    }

                                                                                    /**
                                                                                     * Handles the expansion contraction of the rate table for the zone
                                                                                     */
                                                                                    function expand_contract() {

                                                                                        var row = jQuery(this).parent('td').parent('tr').next();

                                                                                        if (jQuery(row).hasClass('aazz-hidden-row')) {
                                                                                            jQuery(row).removeClass('aazz-hidden-row').addClass('aazz-show-row');
                                                                                            jQuery(this).removeClass('expand-icon').addClass('collapse-icon');
                                                                                        } else {
                                                                                            jQuery(row).removeClass('aazz-show-row').addClass('aazz-hidden-row');
                                                                                            jQuery(this).removeClass('collapse-icon').addClass('expand-icon');
                                                                                        }



                                                                                    }


                                                                                    //TODO - these seem to be copies of the functions above - test commenting them out
                                                                                    //**************************************
                                                                                    // Generates the HTML for the country
                                                                                    // select. Uses an array of keys to
                                                                                    // determine which ones are selected
                                                                                    //**************************************
                                                                                    function generate_country_html(keys) {

                                                                                        html = "";

                                                                                        for (var key in aazz_wc_country_array) {

                                                                                            if (keys.indexOf(key) != -1) {
                                                                                                //we have a match
                                                                                                html += '<option value="' + key + '" selected="selected">' + aazz_wc_country_array[key] + '</option>';
                                                                                            } else {
                                                                                                html += '<option value="' + key + '">' + aazz_wc_country_array[key] + '</option>';

                                                                                            }
                                                                                        }

                                                                                        return html;
                                                                                    }


                                                                                    //**************************************
                                                                                    // Generates the HTML for the CONDITION
                                                                                    // select. Uses an array of keys to
                                                                                    // determine which ones are selected
                                                                                    //**************************************
                                                                                    function generate_condition_html(keys) {

                                                                                        html = "";

                                                                                        for (var key in aazz_wc_condition_array) {

                                                                                            if (keys.indexOf(key) != -1) {
                                                                                                //we have a match
                                                                                                html += '<option value="' + key + '" selected="selected">' + aazz_wc_condition_array[key] + '</option>';
                                                                                            } else {
                                                                                                html += '<option value="' + key + '">' + aazz_wc_condition_array[key] + '</option>';

                                                                                            }
                                                                                        }

                                                                                        return html;
                                                                                    }


                                                                                    /*
                                                                                     * add new ZONE row
                                                                                     */
                                                                                    var zoneID = "#" + pluginID + "_settings";

                                                                                    jQuery(zoneID).on('click', '.add-zone-buttons a.add', function () {

                                                                                        //ok lets add a row!


                                                                                        var id = "#" + pluginID + "_settings table tbody tr:last";
                                                                                        //create empty row
                                                                                        var row = {};
                                                                                        row.key = "";
                                                                                        row.min = [];
                                                                                        row.rates = [];
                                                                                        row.condition = [];
                                                                                        row.countries = [];
                                                                                        jQuery(id).before(create_zone_row(row));

                                                                                        //turn on select2 for our row
                                                                                        if (jQuery().chosen) {
                                                                                            jQuery("select.chosen_select").chosen({
                                                                                                width: '350px',
                                                                                                disable_search_threshold: 5
                                                                                            });
                                                                                        } else {
                                                                                            jQuery("select.chosen_select").select2();
                                                                                        }


                                                                                        return false;
                                                                                    });

                                                                                    /**
                                                                                     * Delete ZONE row
                                                                                     */
                                                                                    jQuery(zoneID).on('click', '.add-zone-buttons a.delete', function () {

                                                                                        //loop thru and see what is checked - if it is zap it!
                                                                                        var rowsToDelete = jQuery(this).closest('table').find('.aazz-zone-checkbox:checked');

                                                                                        jQuery.each(rowsToDelete, function () {

                                                                                            var thisRow = jQuery(this).closest('tr');
                                                                                            //first lets get the next sibl;ing to this row
                                                                                            var nextRow = jQuery(thisRow).next();

                                                                                            //it should be a rate row
                                                                                            if (jQuery(nextRow).hasClass('aazz-rate-holder')) {
                                                                                                //remove it!
                                                                                                jQuery(nextRow).remove();
                                                                                            } else {
                                                                                                //trouble at mill
                                                                                                return;
                                                                                            }

                                                                                            jQuery(thisRow).remove();
                                                                                        });

                                                                                        //TODO - need to delete associated RATES

                                                                                        return false;
                                                                                    });


                                                                                    //RATE TABLES

                                                                                    /**
                                                                                     * ADD RATE BUTTON
                                                                                     */
                                                                                    jQuery(zoneID).on('click', '.add-rate-buttons a.add', function () {

                                                                                        //we need to get the key of this zone - it's in the name of of the button
                                                                                        var name = jQuery(this).attr('name');
                                                                                        name = name.substring(4);

                                                                                        //remove key_
                                                                                        //ok lets add a row!


                                                                                        var row = create_rate_row(name, null);
                                                                                        jQuery(this).closest('tr').before(row);

                                                                                        return false;
                                                                                    });

                                                                                    /**
                                                                                     * Delete RATE roe
                                                                                     */
                                                                                    jQuery(zoneID).on('click', '.add-rate-buttons a.delete', function () {

                                                                                        //loop thru and see what is checked - if it is zap it!
                                                                                        var rowsToDelete = jQuery(this).closest('table').find('.aazz-rate-checkbox:checked');

                                                                                        jQuery.each(rowsToDelete, function () {
                                                                                            jQuery(this).closest('tr').remove();
                                                                                        });


                                                                                        return false;
                                                                                    });

                                                                                    //These handle building the select arras


                                                                                    <?php
                                                                                    echo "jQuery('#{$this->id}_settings').on('click', '.aazz-expansion', expand_contract) ;\n";
                                                                                    ?>
                        </script>
                        <?php
                    }
                    //NIPL

                    return ob_get_clean();
                }

                public function generate_shipping_list_html() {
                    ob_start();

                    ?>
                    <div class="aazz-wc-table-rate-shipping-pro-box">
                        <div class="metabox-holder">
                            <div class="stuffbox">
                                <h3 class="hndle"><?php _e( 'Need more features?', AAZZ_WC_TEXTDOMAIN ); ?></h3>
                                <div class="inside">
                                    <div class="main">
                                        <ul>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Support woocommerce build-in shipping classes', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Support Handling fees for each order', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Shipping rate can be guided by Country, State or Zip/Postal Code', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Calculate shipping based on the weight of items (lbs/kg)', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Calculate shipping Based on the number of item in the cart', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Calculate shipping Based on the item count', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Unlimited shipping services', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Option to add Estimated Delivery Date', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                            <li><span class="dashicons dashicons-yes"></span> <?php _e( 'And much more...', AAZZ_WC_TEXTDOMAIN ); ?></li>
                                        </ul>
                                        <p class="text-center"><a target="_blank" href="https://aazztech.com/product/easy-table-rate-shipping-for-woocommerce-pro" class="button button-primary">Get the Pro Version Now!</a></p>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h3 class="add_shipping_method" id="shiping_methods_h3">All shipping methods
                        <a href="<?php echo remove_query_arg('shipping_methods_id', add_query_arg('action', 'new')); ?>" class="add-new-h2"><?php echo __('Add New', AAZZ_WC_TEXTDOMAIN); ?></a>
                    </h3>


                    <table class="form-table">
                        <tr valign="top">
                            <td>
                                <table class="aazz_wc_table_rate_shipping_methods_class widefat wc_shipping wp-list-table" cellspacing="0">
                                    <thead>
                                    <tr>
                                        <th class="sort" style="width: 1%;">&nbsp;</th>
                                        <th class="method_title" style="width: 30%;"><?php _e('Title', AAZZ_WC_TEXTDOMAIN); ?></th>
                                        <th class="method_status" style="width: 1%;text-align: center;"><?php _e('Enabled', AAZZ_WC_TEXTDOMAIN); ?></th>
                                        <th class="method_vasibility" style="width: 1%;text-align: center;"><?php _e('Vasibility', AAZZ_WC_TEXTDOMAIN); ?></th>

                                        <th class="method_select" style="width: 0%;"><input type="checkbox" class="tips checkbox-select-all" data-tip="<?php _e('Select all', AAZZ_WC_TEXTDOMAIN); ?> " class="checkall-checkbox-class" id="checkall_checkbox" /></th>
                                    </tr>
                                    </thead>
                                    <!--get option for saved methods details-->
                                    <?php
                                    $get_shipping_methods_options = get_option($this->aazz_wc_shipping_methods_option, array());
                                    $get_shipping_method_order = get_option( $this->aazz_wc_shipping_method_order_option, array() );
                                    $shipping_methods_options_array = array();
                                    if (is_array($get_shipping_method_order)) {
                                        foreach ($get_shipping_method_order as $method_id) {
                                            if (isset($get_shipping_methods_options[$method_id])){
                                                $shipping_methods_options_array[$method_id] = $get_shipping_methods_options[$method_id];
                                            }
                                        }
                                    }
                                    ?>
                                    <!--display shipping method data-->
                                    <tbody>
                                    <?php foreach ($shipping_methods_options_array as $shipping_method_options) {
                                        ?>
                                        <tr id="shipping_method_id_<?php echo $shipping_method_options['method_id']; ?>" class="<?php //echo $tr_class; ?>">
                                            <td class="sort">
                                                <input type="hidden" name="method_order[<?php echo esc_attr( $shipping_method_options['method_id'] ); ?>]" value="<?php echo esc_attr( $shipping_method_options['method_id'] ); ?>" />
                                            </td>
                                            <td class="method-title">
                                                <a href="<?php echo remove_query_arg('shipping_methods_id', add_query_arg('method_id', $shipping_method_options['method_id'], add_query_arg('action', 'edit'))); ?>">
                                                    <strong><?php echo esc_html($shipping_method_options['method_title']); ?></strong>
                                                </a>
                                            </td>
                                            <td class="method-status" style="width: 524px;display: -moz-stack; margin:0 auto;"">
                                                <?php if (isset($shipping_method_options['method_enabled']) && 'yes' === $shipping_method_options['method_enabled']) : ?>
                                                    <span class="status-enabled tips" style="margin:0 auto;" data-tip="<?php _e('yes', AAZZ_WC_TEXTDOMAIN); ?>"><?php _e('yes', AAZZ_WC_TEXTDOMAIN); ?></span>
                                                <?php else : ?>
                                                    <span class="na">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="method-status" style="width:1%;display: -moz-stack;">
                                                <?php if (isset($shipping_method_options['method_visibility']) && 'yes' === $shipping_method_options['method_visibility']) : ?>
                                                    <span class="status-enabled tips" style="margin:0 auto; data-tip="<?php _e('yes', AAZZ_WC_TEXTDOMAIN); ?>"><?php _e('yes', AAZZ_WC_TEXTDOMAIN); ?></span>
                                                <?php else : ?>
                                                    <span class="na">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="method-select" style="width: 2% !important; margin-top:0" nowrap>
                                                <input type="checkbox" class="tips checkbox-select select_shipping" value="<?php echo esc_attr($shipping_method_options['method_id']); ?>" data-tip="<?php echo esc_html($shipping_method_options['method_title']); ?>" />
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    //                                                        
                                    ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <th colspan="8">
                                            <button id="aazz_wc_table_rate_remove_selected_method" class="button" disabled><?php _e('Remove selected Method', AAZZ_WC_TEXTDOMAIN); ?></button>


                                        </th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <script type="text/javascript">
                        jQuery('.aazz_wc_table_rate_shipping_methods_class input[type="checkbox"]').click(function () {
                            jQuery('#aazz_wc_table_rate_remove_selected_method').attr('disabled', !jQuery('.aazz_wc_table_rate_shipping_methods_class td input[type="checkbox"]').is(':checked'));
                        });

                        jQuery('#aazz_wc_table_rate_remove_selected_method').click(function () {
                            var url = '<?php echo add_query_arg('shipping_methods_id', '', add_query_arg('action', 'delete')); ?>';
                            var first = true;
                            jQuery('input.checkbox-select').each(function () {
                                if (jQuery(this).is(':checked')) {
                                    if (!first) {
                                        url = url + ',';
                                    } else {
                                        url = url + '=';
                                    }
                                    url = url + jQuery(this).val();
                                    first = false;
                                }
                            })
                            if (first) {
                                alert('<?php _e('Please select shipping methods to remove', AAZZ_WC_TEXTDOMAIN); ?>');
                                return false;
                            }
                            if (url != '<?php echo add_query_arg('method_id', '', add_query_arg('action', 'delete')); ?>') {
                                jQuery('#aazz_wc_table_rate_remove_selected_method').prop('disabled', true);
                                jQuery('.woocommerce-save-button').prop('disabled', true);
                                window.location.href = url;
                            }
                            return false;
                        })
                    </script>
                    <?php
                    return ob_get_clean();
                }

                public function aazz_wc_new_shipping_method_form($shipping_method_array) {
                    $this->form_fields = include AAZZ_WC_DIR . 'inc/new-shipping-method-form.php';

                }

                /**
                 * Generates HTML for table_rate settings table.
                 * this gets called automagically!
                 */
                function admin_options() {
                    $wc_shipping_methods_option = $this->aazz_wc_shipping_methods_option;
                    $wc_shipping_method_order_option = $this->aazz_wc_shipping_method_order_option;
                    $wc_id = $this->id;
                    $wc_instance_id = $this->instance_id;
                    include AAZZ_WC_DIR . 'inc/aazz-wc-admin-options.php';
                }

                /**
                 * Returns the latest counter
                 */
                function get_counter() {
                    $this->counter = $this->counter + 1;
                    return $this->counter;
                }

                //*********************
                // PHP functions
                //***********************

                function create_select_arrays() {

                    //first the CONDITION html
                    $this->aazz_wc_condition_array = array();
                    $this->aazz_wc_condition_array['total'] = sprintf(__('Total Price (%s)', AAZZ_WC_TEXTDOMAIN), get_woocommerce_currency_symbol());
                    $this->aazz_wc_condition_array['weight'] = sprintf(__('Weight (%s)', AAZZ_WC_TEXTDOMAIN), get_option('woocommerce_weight_unit'));



                    //Now the countries
                    $this->aazz_wc_country_array = array();

                    // Get the country list from Woo....
                    foreach (WC()->countries->get_shipping_countries() as $id => $value) :
                        $this->aazz_wc_country_array[esc_attr($id)] = esc_js($value);
                    endforeach;
                }

                //TODO - do we need this function?
                /**
                 * This generates the select option HTML for teh zones & rates tables
                 */
                function create_select_html() {
                    //first the CONDITION html
                    $arr = array();
                    $arr['total'] = sprintf(__('Total Price (%s)', AAZZ_WC_TEXTDOMAIN), get_woocommerce_currency_symbol());
                    $arr['weight'] = sprintf(__('Weight (%s)', AAZZ_WC_TEXTDOMAIN), get_option('woocommerce_weight_unit'));


                    //now create the html from the array
                    $html = '';
                    foreach ($arr as $key => $value) {
                        $html .= '<option value=">' . $key . '">' . $value . '</option>';
                    }

                    $this->condition_html = $html;

                    $html = '';
                    $arr = array();
                    //Now the countries
                    // Get the country list from Woo....
                    foreach (WC()->countries->get_shipping_countries() as $id => $value) :
                        $arr[esc_attr($id)] = esc_js($value);
                    endforeach;

                    //And create the HTML
                    foreach ($arr as $key => $value) {
                        $html .= '<option value=">' . $key . '">' . $value . '</option>';
                    }

                    $this->country_html = $html;
                }

                //Creates the HTML options for the selected

                function create_dropdown_html($arr) {

                    $arr = array();

                    $this->condition_html = html;
                }

                //drop down option
                function aazz_wc_dropdown() {

                    $options = array();


                    // Get the country list from Woo....
                    foreach (WC()->countries->get_shipping_countries() as $id => $value) :
                        $options['country'][esc_attr($id)] = esc_js($value);
                    endforeach;

                    // Now the conditions - cater for language & woo
                    $option['condition']['price'] = sprintf(__('Total (%s)', AAZZ_WC_TEXTDOMAIN), get_woocommerce_currency_symbol());
                    $option['condition']['weight'] = sprintf(__('Weight (%s)', AAZZ_WC_TEXTDOMAIN), get_option('woocommerce_weight_unit'));


                    return $options;
                }

                /**
                 * This saves all of our custom table settings
                 */
                function process_admin_options() {

                    $wc_methods_option = $this->aazz_wc_shipping_methods_option;
                    $wc_method_order_option = $this->aazz_wc_shipping_method_order_option;
                    $shipping_method_action = false;

                    include AAZZ_WC_DIR . 'inc/aazz-wc-process-admin.php';
                }

                //Comparision function for usort of associative arrays
                public static function aazz_comparision($a, $b) {
                    return $a['min'] - $b['min'];
                }

                /**
                 * This RETIEVES  all of our custom table settings

                 */
                function get_options() {

                    //Retrieve the zones & rates
                    $this->options = array_filter((array) get_option($this->aazz_wc_option_key));

                    $x = 5;
                }

                //calculate shipping method
                public function calculate_shipping($package = Array()) {
                    $wc_methods_option      = $this->aazz_wc_shipping_methods_option;
                    $wc_method_order_option = $this->aazz_wc_shipping_method_order_option;
                    $id                     = $this->id;
                    $instance_id            = $this->instance_id;

                    include AAZZ_WC_DIR . 'inc/aazz-wc-calculate-shipping.php';
                }  //end calculate_shipping

                function get_rates_for_country($country) {

                    //Loop thru and see if we can find one
                    $get_shipping_methods_options = get_option($this->aazz_wc_shipping_methods_option, array());

                    $shipping_methods_options_array = array();
                    foreach ($get_shipping_methods_options as $shipping_method) {
                        if (!isset($shipping_methods_options_array[$shipping_method['method_id']]))
                            $shipping_methods_options_array[$shipping_method['method_id']] = $shipping_method;
                    }

                    // Remove table rates if shipping method is disable
                    foreach ($shipping_methods_options_array as $key => $shipping_method) {
                        if (isset($shipping_method['method_enabled']) && 'yes' != $shipping_method['method_enabled'])
                            unset($shipping_methods_options_array[$key]);
                    }
                    $shipping_methods_options = $shipping_methods_options_array;
                    $ret = array();

                    foreach ($shipping_methods_options as $shipping_methods_option) {

                        foreach ($shipping_methods_option['method_table_rates'] as $rate) {
                            if (in_array($country, $rate['countries'])) {
                                $ret[] = $rate;
                            }
                        }
                    }

                    //if we found something return it, otherwise a null.
                    if (count($ret) > 0) {
                        return $ret;
                    } else {
                        return null;
                    }
                }

                //Here we find the matching rate
                function find_matching_rate($value, $zones) {

                    $zone = $zones;
                    foreach ($zone as $zones_array) {

                        // * means infinity!
                        for ($i = 0; $i < 1; $i++) {
                            if ($zone['max'][$i] == '*') {
                                if ($value >= $zone['min'][$i]) {
                                    $handling_fee = $zone['method_handling_fee'];
                                    $total_fee = $zone['shipping'][$i] + $handling_fee;
                                    return $total_fee;
                                }
                            } else {
                                if ($value >= $zone['min'][$i] && $value <= $zone['max'][$i]) {
                                    $handling_fee = $zone['method_handling_fee'];
                                    $total_fee = $zone['shipping'][$i] + $handling_fee;
                                    return $total_fee;
                                }
                            }
                        }

                        //OK if we got all the way to here, then we have NO match
                        return null;
                    }
                }

                //It uses an asterisk for infinite
                function find_matching_rate_custom($value, $rates) {
                    $rate = $rates;
                    if ($rate['max'] == '*') {
                        if ($value >= $rate['min']) {
                            $total_fee = $rate['shipping'];
                            return $total_fee;
                        }
                    } else {
                        if ($value >= $rate['min'] && $value <= $rate['max']) {
                            $total_fee = $rate['shipping'];
                            return $total_fee;
                        }
                    }
                    //OK if we got all the way to here, then we have NO match
                    return null;
                }
                function get_shipping_zone_from_method_rate_id( $method_rate_id ){
                    global $wpdb;

                    $data = explode( ':', $method_rate_id );
                    $method_id = $data[0];
                    $instance_id = $data[1];

                    // The first SQL query
                    $zone_id = $wpdb->get_col( "
						SELECT wszm.zone_id
						FROM {$wpdb->prefix}woocommerce_shipping_zone_methods as wszm
						WHERE wszm.instance_id = '$instance_id'
						AND wszm.method_id LIKE '$method_id'
					" );
                    $zone_id = reset($zone_id); // converting to string

                    // 1. Wrong Shipping method rate id
                    if( empty($zone_id) )
                    {
                        return __("Error! doesn't exist…");
                    }
                    // 2. Default WC Zone name
                    elseif( $zone_id == 0 )
                    {
                        return __("All Other countries");
                    }
                    // 3. Created Zone name
                    else
                    {
                        return $zone_id;
                    }
                }


            }
        }
    }
}


