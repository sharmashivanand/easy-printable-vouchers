<?php
/**
 * Plugin Name: Easy Printable Vouchers
 * Description: Easy printable gift vouchers and coupons. Easily design and issue to build customer loyality.
 * Version:     0.1
 * Plugin URI:  https://wordpress.org/plugins/easy-printable-vouchers/
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: epv
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Tags: gift, coupon, voucher, easy, printable
 */

/*
Copyright 2018 Shivanand Sharma

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace epv;

define( 'EPV_URI', trailingslashit(plugin_dir_url( __FILE__ )));
define( 'EPV_DIR', trailingslashit(plugin_dir_url( __FILE__ )));

final class Plugin {

    public $dir = '';
    public $uri = '';

    public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->includes();
			$instance->setup_actions();
		}
		return $instance;
    }

    private function __construct() {}
    
        private function setup() {
        // Main plugin directory path and URI.
        $this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->uri  = trailingslashit( plugin_dir_url(  __FILE__ ) );
    }

    private function includes() {
		// Include functions files.
		require_once( $this->dir . 'inc/functions.php'    );
		require_once( $this->dir . 'inc/options.php'    );
        
        if ( is_admin() ) {
            require_once( $this->dir . 'admin/class-settings.php' );
            require_once( $this->dir . 'lib/tcpdf/tcpdf.php');
		}
    }
    
    private function setup_actions() {
        add_action( 'init', array( $this, 'register_post_types' ));
        add_action( 'add_meta_boxes', array( $this,'voucher_meta_boxes' ));
        add_action( 'save_post', array($this, 'save_voucher_background_meta_box_data' ));

        add_action( 'admin_print_scripts-post-new.php', array( $this, 'voucher_admin_script' ), 11 );
        add_action( 'admin_print_scripts-post.php', array( $this, 'voucher_admin_script' ), 11 );

        register_activation_hook( __FILE__, array( $this, 'activation' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links'), 10, 5 );
    }
    
    function voucher_admin_script(){
        global $post_type;
        
        if($post_type == 'voucher') {
            wp_enqueue_media();
            //add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
            wp_enqueue_script( 'epv', EPV_URI .'admin/script.js', array(), null, true);
        }
    }

    function save_voucher_background_meta_box_data($post_id){
    
        // Check if our nonce is set.
        if ( ! isset( $_POST['voucher_background_nonce'] ) ) {
            return;
        }
        
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['voucher_background_nonce'], 'voucher_background_nonce' ) ) {
            return;
        }
    
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
    
        update_post_meta( $post_id, '_voucher_logo', sanitize_text_field( $_POST['voucher_logo'] ) );
        update_post_meta( $post_id, '_voucher_value', sanitize_text_field( $_POST['voucher_value'] ) );
        update_post_meta( $post_id, '_voucher_currency', sanitize_text_field( $_POST['voucher_currency'] ) );
        // Update the meta field in the database.
        update_post_meta( $post_id, '_voucher_background', sanitize_text_field( $_POST['voucher_background'] ) );

        if(isset( $_POST['voucher-print'])) {

            $value = get_post_meta( $post_id, '_voucher_value', true );
            $currency = get_post_meta( $post_id, '_voucher_currency', true );
            $logo = get_post_meta( $post_id, '_voucher_logo', true );
            $logo_src = wp_get_attachment_image_src( $logo, 'full' );
            $bg = get_post_meta( $post_id, '_voucher_background', true );
            $bg_src = wp_get_attachment_image_src( $bg, 'full' );
            //$this->llog( ( $bg_src[1] / 300)  );
            //$this->llog( ( $logo_src[1] / 300 ) );
            //$this->llog( ( $bg_src[1] / 300) - ( $logo_src[1] / 300 ) - .5 );
           // $this->llog(( $bg_src[1] / 300) - ( $logo_src[1] / 300 ) ) - 100 , ( ( $bg_src[2] / 300)  - ( $logo_src[2] / 300 ) ) - 100);
            //$this->llog();
            //$this->llog();
            //$this->llog();
            //die();
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, 'in', PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // set document information
            $pdf->SetCreator('Easy Printable Vouchers. Write to us at hello@converticacommerce.com');
            $pdf->SetAuthor(get_bloginfo( 'name'));
            $pdf->SetTitle('');
            $pdf->SetSubject('');
            $pdf->SetKeywords('Easy Printable Vouchers');

            // remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            //$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetMargins(0,0,0,false);

            // set auto page breaks
            //$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // set image scale factor
            //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->setImageScale(1);

            

            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }

            // ---------------------------------------------------------

            // set font
            //$pdf->SetFont('times', 'BI', 20);

            // add a page
            $pdf->AddPage( $bg_src[1] > $bg_src[2] ? 'L' : 'P', array( $bg_src[1] / 300 , $bg_src[2] / 300 ) );
            $pdf->setJPEGQuality(100);


            // set some text to print
            //$txt = "TCPDF Example 002";

            // print a block of text using Write()
            $pdf->SetAutoPageBreak(TRUE, 0);
            //$pdf->Write(0, , '', 0, 'C', true, 0, false, false, 0);

            // ---------------------------------------------------------
            $pdf->Image($bg_src[0], '', '', $bg_src[1] / 300, $bg_src[2] / 300 , '', '', '', false, 300);
            $pdf->Image($logo_src[0], ( ( $bg_src[1] / 300) - ( $logo_src[1] / 300 ) ) - .5 , ( ( $bg_src[2] / 300)  - ( $logo_src[2] / 300 ) ) - .5 , $logo_src[1] / 300, $logo_src[2] / 300 , '', '', '', false, 300);
            
            //$pdf->SetX( 100 );
            //$pdf->SetY( 100 );
            $pdf->SetAutoPageBreak(FALSE, 0);
            //$pdf->SetXY( $bg_src[1] / 600, $bg_src[2] / 600);
            //$tcpdf->SetXY(50,120);$tcpdf->Write(10,'Write some text',...);
            //$html = "<h1>$currency$value</h1>";

            //$pdf->writeHTML($html, true, false, true, false, '');
            $pdf->SetY( ($pdf->getPageHeight() / 2 ) - .5); // divide the page height by 2 and then substract the half of line height used in the Write command to center text
            //$pdf->Write(0, $currency.$value, '', 0, 'C', true, 0, false, false, 0);
            $pdf->setFontSubsetting(true);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Write(1, $currency.$value, '', 0, 'C', true, 0, false, false, 0);
            //$pdf->WriteHTML("<table><tr><td style=\"vertical-align: middle;\">$currency$value</td></tr></table>", false, false, false, false, 'C');
            //Close and output PDF document
            $pdf->Output('easy-printable-voucher.pdf', 'D');
        }
    
    }

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    function voucher_meta_boxes(){
        //add_meta_box( 'voucher-background', __( 'Print Size', 'epv' ), array($this, 'voucher_size_meta_box_callback'), 'voucher');
        add_meta_box( 'voucher-value', __( 'Discount', 'epv' ), array($this, 'voucher_value_meta_box_callback'), 'voucher');
        add_meta_box( 'voucher-logo', __( 'Company Logo', 'epv' ), array($this, 'voucher_logo_meta_box_callback'), 'voucher');
        add_meta_box( 'voucher-background', __( 'Voucher Background', 'epv' ), array($this, 'voucher_background_meta_box_callback'), 'voucher');
        add_meta_box( 'voucher-print', __( 'Download for Print', 'epv' ), array($this, 'voucher_print_meta_box_callback'), 'voucher','side');
    }

    function voucher_logo_meta_box_callback($post){
        $value = get_post_meta( $post->ID, '_voucher_logo', true );
        
        $your_img_src = wp_get_attachment_image_src( $value, 'full' );
        //echo '<textarea style="width:100%" id="voucher_background" name="voucher_background">' . esc_attr( $value ) . '</textarea>';
        ?>
        <div class="upload">
            <img src="<?php echo $your_img_src[0] ?>" alt="" style="max-width:200px;height:auto;" id="logo_preview"  />
            <div>
                <input type="hidden" name="voucher_logo" id="voucher_logo" value="<?php echo $value ?>" />
                <button type="submit" id="upload_logo_button" class="upload_image_button button">Upload</button>
                <button type="submit" id="remove_logo_button" class="remove_image_button button">&times;</button>
            </div>
        </div>
        <?php
    }

    function voucher_print_meta_box_callback(){
        submit_button('Download for Print','primary','voucher-print');
    }

    function voucher_value_meta_box_callback($post){
        $value = get_post_meta( $post->ID, '_voucher_value', true );
        $curr = get_post_meta( $post->ID, '_voucher_currency', true );
        ?>
        <label>Amount: <input name="voucher_value" value="<?php echo $value; ?>" /><br /></label>
        <label>Currency:<select name="voucher_currency">
            <option value="USD" <?php selected( $curr, 'USD' ); ?>>United States Dollars</option>
            <option value="EUR" <?php selected( $curr, 'EUR' ); ?>>Euro</option>
            <option value="GBP" <?php selected( $curr, 'GBP' ); ?>>United Kingdom Pounds</option>
            <option value="INR" <?php selected( $curr, 'INR' ); ?>>India Rupees</option>
            <option value="ILS" <?php selected( $curr, 'ILS' ); ?>>Israel New Shekels</option>
            <option value="JPY" <?php selected( $curr, 'JPY' ); ?>>Japan Yen</option>
            <option value="RUR" <?php selected( $curr, 'RUR' ); ?>>Russia Rubles</option>
            <option value="EUR" <?php selected( $curr, 'EUR' ); ?>>Euro</option>
        </select></label>
        <?php
    }

    function voucher_background_meta_box_callback($post){
        wp_nonce_field( 'voucher_background_nonce', 'voucher_background_nonce' );
        $value = get_post_meta( $post->ID, '_voucher_background', true );
        $your_img_src = wp_get_attachment_image_src( $value, 'full' );
        //echo '<textarea style="width:100%" id="voucher_background" name="voucher_background">' . esc_attr( $value ) . '</textarea>';
        ?>
        <div class="upload">
            <img src="<?php echo $your_img_src[0] ?>" alt="" style="max-width:200px;height:auto;" id="background_preview"  />
            <div>
                <input type="hidden" name="voucher_background" id="voucher_background" value="<?php echo $value ?>" />
                <button type="submit" id="upload_background_button" class="upload_image_button button">Upload</button>
                <button type="submit" id="remove_background_button" class="remove_image_button button">&times;</button>
            </div>
        </div>
        <?php
    }

    function register_post_types(){
        $cpt_args = array(
            'description'         => 'Gift Vouchers',
            'public'              => false,
            'show_ui'               => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-awards',
            'can_export'          => true,
            'delete_with_user'    => false,
            'hierarchical'        => false,
            'has_archive'         => false,
            'labels'              => array('name' => 'Voucher'),
            'template_lock' => true,
    
            // What features the post type supports.
            'supports' => array(
                'title',
                //'editor',
                //'thumbnail',
                // Theme/Plugin feature support.
                //'custom-background', // Custom Background Extended
                //'custom-header',     // Custom Header Extended
                //'wpcom-markdown',    // Jetpack Markdown
            )
        );

        register_post_type( 'voucher', apply_filters( 'vouchers_post_type_args', $cpt_args ) );
    }

    public function activation() {}
    
    function plugin_action_links($links){
        $links[] = '<a href="https://www.converticacommerce.com/?item_name=Donate%20to%20Easy%20Printable%20Vouchers&cmd=_xclick&business=shivanand@converticacommerce.com">Donate</a>';
        return $links;
    }    

}

function plugin() {
	return Plugin::get_instance();
}
// Let's roll!
plugin();