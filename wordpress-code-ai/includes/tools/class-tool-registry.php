<?php
namespace wordpress_code_ai\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Registry {

    private $tools = array();

    






    private $lazy_classes = array();

    







    private $lazy_loader = null;
    private $lazy_loader_ran = false;

    


    public function set_lazy_loader( callable $loader ) {
        $this->lazy_loader = $loader;
    }

    




    private function ensure_loaded() {
        if ( $this->lazy_loader_ran || null === $this->lazy_loader ) {
            return;
        }
        if ( ! empty( $this->tools ) || ! empty( $this->lazy_classes ) ) {
            return;
        }
        $this->lazy_loader_ran = true;
        ( $this->lazy_loader )();
    }

    



















    public function register( Base_Tool $tool ) {
        $name = $tool->get_name();
        if ( isset( $this->tools[ $name ] ) && $this->tools[ $name ] !== $tool ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( sprintf(
                'wordpress-code-ai: tool name collision on "%s" — a tool is already registered under this name; keeping the first and refusing to overwrite it.',
                $name
            ) );
            return;
        }
        if ( $tool instanceof Privileged_Tool && ! self::admit_privileged( $tool ) ) {
            return;
        }
        $this->tools[ $name ] = $tool;
    }

    




    private static function admit_privileged( Privileged_Tool $tool ) {
        return \wordpress_code_ai\Privileged\Privileged_Groups::admit_tool( $tool->get_name(), $tool->get_privileged_group() );
    }

    public function get_tool( $name ) {
        $this->ensure_loaded();
        if ( isset( $this->tools[ $name ] ) ) {
            return $this->tools[ $name ];
        }
        
        
        
        foreach ( $this->lazy_classes as $key => $class ) {
            $instance  = new $class();
            $tool_name = $instance->get_name();
            $this->tools[ $tool_name ] = $instance;
            unset( $this->lazy_classes[ $key ] );
            if ( $tool_name === $name ) {
                return $instance;
            }
        }
        return null;
    }

    public function get_all_definitions() {
        $this->materialize_all();
        $definitions = array();
        foreach ( $this->tools as $tool ) {
            $definitions[] = $tool->get_definition();
        }
        return $definitions;
    }

    public function get_all_tool_names() {
        $this->materialize_all();
        return array_keys( $this->tools );
    }

    public function get_tools_by_category() {
        $this->materialize_all();
        $categorized = array();
        foreach ( $this->tools as $tool ) {
            $category = $tool->get_category();
            if ( ! isset( $categorized[ $category ] ) ) {
                $categorized[ $category ] = array();
            }
            $categorized[ $category ][] = $tool->get_definition();
        }
        return $categorized;
    }

    private function materialize_all() {
        $this->ensure_loaded();
        foreach ( $this->lazy_classes as $key => $class ) {
            $instance = new $class();
            $this->tools[ $instance->get_name() ] = $instance;
            unset( $this->lazy_classes[ $key ] );
        }
    }

    

















    public function auto_discover( bool $quiet = false, ?array $dirs = null ): void {
        $tool_classes = array(
            'wordpress_code_ai\\Tools\\Posts\\List_Posts',
            'wordpress_code_ai\\Tools\\Posts\\Get_Post',
            'wordpress_code_ai\\Tools\\Posts\\Create_Post',
            'wordpress_code_ai\\Tools\\Posts\\Update_Post',
            'wordpress_code_ai\\Tools\\Posts\\Delete_Post',
            'wordpress_code_ai\\Tools\\Posts\\Search_Posts',
            'wordpress_code_ai\\Tools\\Pages\\List_Pages',
            'wordpress_code_ai\\Tools\\Pages\\Get_Page',
            'wordpress_code_ai\\Tools\\Pages\\Create_Page',
            'wordpress_code_ai\\Tools\\Pages\\Update_Page',
            'wordpress_code_ai\\Tools\\Pages\\Delete_Page',
            'wordpress_code_ai\\Tools\\Media\\List_Media',
            'wordpress_code_ai\\Tools\\Media\\Get_Media',
            'wordpress_code_ai\\Tools\\Media\\Upload_Media',
            'wordpress_code_ai\\Tools\\Media\\Update_Media',
            'wordpress_code_ai\\Tools\\Media\\Delete_Media',
            'wordpress_code_ai\\Tools\\Media\\Edit_Media_Image',
            'wordpress_code_ai\\Tools\\Media\\Regenerate_Media_Sizes',
            'wordpress_code_ai\\Tools\\Taxonomy\\List_Categories',
            'wordpress_code_ai\\Tools\\Taxonomy\\Get_Category',
            'wordpress_code_ai\\Tools\\Taxonomy\\Create_Category',
            'wordpress_code_ai\\Tools\\Taxonomy\\Update_Category',
            'wordpress_code_ai\\Tools\\Taxonomy\\Delete_Category',
            'wordpress_code_ai\\Tools\\Taxonomy\\List_Tags',
            'wordpress_code_ai\\Tools\\Taxonomy\\Get_Tag',
            'wordpress_code_ai\\Tools\\Taxonomy\\Create_Tag',
            'wordpress_code_ai\\Tools\\Taxonomy\\Update_Tag',
            'wordpress_code_ai\\Tools\\Taxonomy\\Delete_Tag',
            'wordpress_code_ai\\Tools\\Comments\\List_Comments',
            'wordpress_code_ai\\Tools\\Comments\\Get_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Create_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Update_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Delete_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Approve_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Unapprove_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Spam_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Unspam_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Trash_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Untrash_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Reply_To_Comment',
            'wordpress_code_ai\\Tools\\Comments\\Bulk_Moderate_Comments',
            'wordpress_code_ai\\Tools\\Users\\List_Users',
            'wordpress_code_ai\\Tools\\Users\\Get_User',
            'wordpress_code_ai\\Tools\\Users\\Create_User',
            'wordpress_code_ai\\Tools\\Users\\Update_User',
            'wordpress_code_ai\\Tools\\Users\\Delete_User',
            'wordpress_code_ai\\Tools\\Site\\Get_Site_Settings',
            'wordpress_code_ai\\Tools\\Site\\Update_Site_Settings',
            'wordpress_code_ai\\Tools\\Site\\Get_Post_Types',
            'wordpress_code_ai\\Tools\\Site\\Get_Taxonomies',
            'wordpress_code_ai\\Tools\\Menus\\List_Menus',
            'wordpress_code_ai\\Tools\\Menus\\Get_Menu',
            'wordpress_code_ai\\Tools\\Menus\\List_Menu_Items',
            'wordpress_code_ai\\Tools\\Menus\\Create_Menu_Item',
            'wordpress_code_ai\\Tools\\Plugins\\List_Plugins',
            'wordpress_code_ai\\Tools\\Plugins\\Activate_Plugin',
            'wordpress_code_ai\\Tools\\Plugins\\Deactivate_Plugin',
            'wordpress_code_ai\\Tools\\Plugins\\List_Plugin_Updates',
            'wordpress_code_ai\\Tools\\Plugins\\Update_Plugin',
            
            'wordpress_code_ai\\Tools\\Plugins\\Install_Plugin',
            'wordpress_code_ai\\Tools\\Plugins\\Delete_Plugin',
            'wordpress_code_ai\\Tools\\Plugins\\Uninstall_Plugin',
            'wordpress_code_ai\\Tools\\Themes\\List_Themes',
            'wordpress_code_ai\\Tools\\Themes\\Get_Active_Theme',
            'wordpress_code_ai\\Tools\\Themes\\Switch_Theme',
            'wordpress_code_ai\\Tools\\Themes\\Update_Theme',
            'wordpress_code_ai\\Tools\\Themes\\Install_Theme',
            'wordpress_code_ai\\Tools\\Themes\\Delete_Theme',
            
            'wordpress_code_ai\\Tools\\Revisions\\List_Revisions',
            'wordpress_code_ai\\Tools\\Revisions\\Get_Revision',
            'wordpress_code_ai\\Tools\\Revisions\\Delete_Revision',
            
            'wordpress_code_ai\\Tools\\Meta\\Get_Post_Meta',
            'wordpress_code_ai\\Tools\\Meta\\Update_Post_Meta',
            
            'wordpress_code_ai\\Tools\\Search\\Search',
            
            'wordpress_code_ai\\Tools\\Blocks\\List_Blocks',
            'wordpress_code_ai\\Tools\\Blocks\\Get_Block',
            'wordpress_code_ai\\Tools\\Blocks\\Create_Block',
            'wordpress_code_ai\\Tools\\Blocks\\Update_Block',
            'wordpress_code_ai\\Tools\\Blocks\\Delete_Block',
            'wordpress_code_ai\\Tools\\Blocks\\List_Patterns',
            'wordpress_code_ai\\Tools\\Blocks\\Get_Post_Blocks',
            'wordpress_code_ai\\Tools\\Blocks\\Update_Post_Blocks',
            'wordpress_code_ai\\Tools\\Blocks\\List_Block_Types',
            
            'wordpress_code_ai\\Tools\\Site\\Get_Post_Statuses',
            
            'wordpress_code_ai\\Tools\\CPT\\List_CPT_Items',
            'wordpress_code_ai\\Tools\\CPT\\Get_CPT_Item',
            'wordpress_code_ai\\Tools\\CPT\\Create_CPT_Item',
            'wordpress_code_ai\\Tools\\CPT\\Update_CPT_Item',
            'wordpress_code_ai\\Tools\\CPT\\Delete_CPT_Item',
            
            'wordpress_code_ai\\Tools\\Menus\\Create_Menu',
            'wordpress_code_ai\\Tools\\Menus\\Update_Menu',
            'wordpress_code_ai\\Tools\\Menus\\Delete_Menu',
            'wordpress_code_ai\\Tools\\Menus\\Update_Menu_Item',
            'wordpress_code_ai\\Tools\\Menus\\Delete_Menu_Item',
            'wordpress_code_ai\\Tools\\Menus\\Reorder_Menu_Items',
            
            'wordpress_code_ai\\Tools\\Templates\\List_Templates',
            'wordpress_code_ai\\Tools\\Templates\\Get_Template',
            'wordpress_code_ai\\Tools\\Templates\\Update_Template',
            
            'wordpress_code_ai\\Tools\\Styles\\Get_Global_Styles',
            'wordpress_code_ai\\Tools\\Styles\\Update_Global_Styles',
            
            'wordpress_code_ai\\Tools\\Appearance\\Get_Theme_Mods',
            'wordpress_code_ai\\Tools\\Appearance\\Update_Theme_Mod',
            'wordpress_code_ai\\Tools\\Appearance\\Delete_Theme_Mod',
            'wordpress_code_ai\\Tools\\Appearance\\Get_Custom_Css',
            'wordpress_code_ai\\Tools\\Appearance\\Update_Custom_Css',
            
            'wordpress_code_ai\\Tools\\Widgets\\List_Sidebars',
            'wordpress_code_ai\\Tools\\Widgets\\List_Widgets',
            'wordpress_code_ai\\Tools\\Widgets\\Get_Widget',
            'wordpress_code_ai\\Tools\\Widgets\\List_Widget_Types',
            'wordpress_code_ai\\Tools\\Widgets\\Create_Widget',
            'wordpress_code_ai\\Tools\\Widgets\\Update_Widget',
            'wordpress_code_ai\\Tools\\Widgets\\Delete_Widget',
            
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Products',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Product',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Product',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Product',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Product',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Product_Variations',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Product_Variation',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Product_Variation',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Product_Variation',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Product_Variation',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Product_Categories',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Orders',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Order',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Order',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Order',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Order_Notes',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Order_Note',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Order_Refunds',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Customers',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Customer',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Customer',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Customer',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Customer',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Coupons',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Coupon',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Coupon',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Coupon',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Coupon',
            'wordpress_code_ai\\Tools\\WooCommerce\\Report_Sales',
            'wordpress_code_ai\\Tools\\WooCommerce\\Report_Top_Sellers',
            'wordpress_code_ai\\Tools\\WooCommerce\\Report_Orders',
            'wordpress_code_ai\\Tools\\WooCommerce\\Report_Products',
            'wordpress_code_ai\\Tools\\WooCommerce\\Report_Customers',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Webhooks',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Webhook',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Webhook',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Webhook',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Shipping_Zones',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Shipping_Methods',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Tax_Rates',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Payment_Gateways',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Logs',
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Log',
            
            'wordpress_code_ai\\Tools\\WooCommerce\\Get_Store_Settings',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Store_Settings',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Countries',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Shipping_Zone',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Shipping_Zone',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Shipping_Zone',
            'wordpress_code_ai\\Tools\\WooCommerce\\Add_Shipping_Method',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Shipping_Method',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Shipping_Method',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Shipping_Classes',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Shipping_Class',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Shipping_Class',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Shipping_Class',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Payment_Gateway',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Tax_Rate',
            'wordpress_code_ai\\Tools\\WooCommerce\\Update_Tax_Rate',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Tax_Rate',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Tax_Classes',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Tax_Class',
            'wordpress_code_ai\\Tools\\WooCommerce\\Delete_Tax_Class',
            
            'wordpress_code_ai\\Tools\\ACF\\Get_Fields',
            'wordpress_code_ai\\Tools\\ACF\\Update_Fields',
            'wordpress_code_ai\\Tools\\ACF\\Get_User_Fields',
            'wordpress_code_ai\\Tools\\ACF\\Update_User_Fields',
            'wordpress_code_ai\\Tools\\ACF\\Get_Term_Fields',
            'wordpress_code_ai\\Tools\\ACF\\List_Field_Groups',
            
            'wordpress_code_ai\\Tools\\Events_Calendar\\List_Events',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Get_Event',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Create_Event',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Update_Event',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Delete_Event',
            'wordpress_code_ai\\Tools\\Events_Calendar\\List_Venues',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Get_Venue',
            'wordpress_code_ai\\Tools\\Events_Calendar\\List_Organizers',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Create_Venue',
            'wordpress_code_ai\\Tools\\Events_Calendar\\Create_Organizer',
            
            'wordpress_code_ai\\Tools\\Redirection\\List_Redirects',
            'wordpress_code_ai\\Tools\\Redirection\\Get_Redirect',
            'wordpress_code_ai\\Tools\\Redirection\\Create_Redirect',
            'wordpress_code_ai\\Tools\\Redirection\\Update_Redirect',
            'wordpress_code_ai\\Tools\\Redirection\\Delete_Redirects',
            'wordpress_code_ai\\Tools\\Redirection\\List_Groups',
            'wordpress_code_ai\\Tools\\Redirection\\List_404s',
            
            'wordpress_code_ai\\Tools\\Site_Kit\\Get_Status',
            'wordpress_code_ai\\Tools\\Site_Kit\\GA_Run_Report',
            'wordpress_code_ai\\Tools\\Site_Kit\\GA_List_Key_Events',
            'wordpress_code_ai\\Tools\\Site_Kit\\GSC_Query_Performance',
            
            'wordpress_code_ai\\Tools\\Contact_Form_7\\List_Forms',
            'wordpress_code_ai\\Tools\\Contact_Form_7\\Get_Form',
            'wordpress_code_ai\\Tools\\Contact_Form_7\\Create_Form',
            'wordpress_code_ai\\Tools\\Contact_Form_7\\Update_Form',
            'wordpress_code_ai\\Tools\\Contact_Form_7\\Delete_Form',
            
            'wordpress_code_ai\\Tools\\BuddyPress\\List_Members',
            'wordpress_code_ai\\Tools\\BuddyPress\\Get_Member',
            'wordpress_code_ai\\Tools\\BuddyPress\\List_Activity',
            'wordpress_code_ai\\Tools\\BuddyPress\\Create_Activity',
            'wordpress_code_ai\\Tools\\BuddyPress\\Delete_Activity',
            'wordpress_code_ai\\Tools\\BuddyPress\\List_Groups',
            'wordpress_code_ai\\Tools\\BuddyPress\\Get_Group',
            'wordpress_code_ai\\Tools\\BuddyPress\\List_Group_Members',
            'wordpress_code_ai\\Tools\\BuddyPress\\List_Message_Threads',
            'wordpress_code_ai\\Tools\\BuddyPress\\Get_Message_Thread',
            
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Get_Head',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Update_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Get_Term_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Update_Term_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Get_Settings',
            'wordpress_code_ai\\Tools\\SEO\\Yoast_Update_Settings',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Get_Head',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Update_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_List_Redirects',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Get_Redirect',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Create_Redirect',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Update_Redirect',
            'wordpress_code_ai\\Tools\\SEO\\Rankmath_Delete_Redirects',
            'wordpress_code_ai\\Tools\\SEO\\Aioseo_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Aioseo_Update_Post_Seo',
            
            'wordpress_code_ai\\Tools\\SEO\\Aioseo_Get_Breadcrumb',
            
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Update_Title_Desc',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Update_Robots',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Update_Social',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Update_Keywords',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Get_Content_Analysis',
            'wordpress_code_ai\\Tools\\SEO\\Seopress_Get_Term_Seo',
            
            'wordpress_code_ai\\Tools\\SEO\\Slimseo_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Slimseo_Update_Post_Seo',
            
            'wordpress_code_ai\\Tools\\SEO\\Tsf_Get_Post_Seo',
            'wordpress_code_ai\\Tools\\SEO\\Tsf_Update_Post_Seo',
            'wordpress_code_ai\\Tools\\GSC\\List_Sites',
            'wordpress_code_ai\\Tools\\GSC\\Get_Site',
            'wordpress_code_ai\\Tools\\GSC\\Query_Performance',
            'wordpress_code_ai\\Tools\\GSC\\List_Sitemaps',
            'wordpress_code_ai\\Tools\\GSC\\Get_Sitemap',
            'wordpress_code_ai\\Tools\\GSC\\Inspect_Url',

            'wordpress_code_ai\\Tools\\GA\\List_Account_Summaries',
            'wordpress_code_ai\\Tools\\GA\\Get_Property',
            'wordpress_code_ai\\Tools\\GA\\List_Data_Streams',
            'wordpress_code_ai\\Tools\\GA\\List_Custom_Dimensions',
            'wordpress_code_ai\\Tools\\GA\\List_Custom_Metrics',
            'wordpress_code_ai\\Tools\\GA\\List_Conversion_Events',
            'wordpress_code_ai\\Tools\\GA\\Get_Metadata',
            'wordpress_code_ai\\Tools\\GA\\Run_Report',
            'wordpress_code_ai\\Tools\\GA\\Run_Pivot_Report',
            'wordpress_code_ai\\Tools\\GA\\Run_Realtime_Report',
            'wordpress_code_ai\\Tools\\GA\\Check_Compatibility',
            'wordpress_code_ai\\Tools\\GA\\Create_Key_Event',
            'wordpress_code_ai\\Tools\\GA\\Delete_Key_Event',

            
            'wordpress_code_ai\\Tools\\DFS\\Serp_Google_Organic_Live',
            'wordpress_code_ai\\Tools\\DFS\\Keywords_Search_Volume_Live',
            'wordpress_code_ai\\Tools\\DFS\\Backlinks_Referring_Domains_Live',
            'wordpress_code_ai\\Tools\\DFS\\On_Page_Instant_Pages',
            'wordpress_code_ai\\Tools\\DFS\\Account_Balance',
            'wordpress_code_ai\\Tools\\DFS\\Labs_Keywords_For_Site_Live',
            'wordpress_code_ai\\Tools\\DFS\\Labs_Ranked_Keywords_Live',
            'wordpress_code_ai\\Tools\\DFS\\Backlinks_Summary_Live',

            
            'wordpress_code_ai\\Tools\\Posts\\Count_Posts',
            'wordpress_code_ai\\Tools\\Taxonomy\\Count_Terms',
            'wordpress_code_ai\\Tools\\Media\\Count_Media',
            
            'wordpress_code_ai\\Tools\\Revisions\\Restore_Revision',
            
            'wordpress_code_ai\\Tools\\Meta\\Delete_Post_Meta',
            
            'wordpress_code_ai\\Tools\\Taxonomy\\Get_Term_Meta',
            'wordpress_code_ai\\Tools\\Taxonomy\\Update_Term_Meta',
            'wordpress_code_ai\\Tools\\Taxonomy\\Delete_Term_Meta',
            
            'wordpress_code_ai\\Tools\\Posts\\Add_Post_Terms',
            
            'wordpress_code_ai\\Tools\\Users\\Get_User_Meta',
            'wordpress_code_ai\\Tools\\Users\\Update_User_Meta',
            'wordpress_code_ai\\Tools\\Users\\Delete_User_Meta',
            
            'wordpress_code_ai\\Tools\\WooCommerce\\Batch_Update_Products',
            'wordpress_code_ai\\Tools\\WooCommerce\\Batch_Update_Orders',
            'wordpress_code_ai\\Tools\\WooCommerce\\Batch_Update_Variations',
            'wordpress_code_ai\\Tools\\WooCommerce\\List_Product_Attributes',
            'wordpress_code_ai\\Tools\\WooCommerce\\Create_Product_Attribute',
            'wordpress_code_ai\\Tools\\WooCommerce\\Set_Product_Attributes',

            
            
            'wordpress_code_ai\\Tools\\Posts\\Get_Post_Full',
            'wordpress_code_ai\\Tools\\Media\\Upload_Media_From_Url',
            'wordpress_code_ai\\Tools\\Taxonomy\\Get_Term',
            'wordpress_code_ai\\Tools\\Taxonomy\\Create_Term',
            'wordpress_code_ai\\Tools\\Taxonomy\\Update_Term',
            'wordpress_code_ai\\Tools\\Taxonomy\\Delete_Term',
            'wordpress_code_ai\\Tools\\Posts\\Replace_In_Post',
            
            'wordpress_code_ai\\Tools\\Posts\\Create_Preview_Link',
            
            'wordpress_code_ai\\Tools\\Posts\\Duplicate_Post',
            
            'wordpress_code_ai\\Tools\\Cache\\Clear_Cache',
            
            'wordpress_code_ai\\Tools\\Mail\\Send_Email',
            
            
            'wordpress_code_ai\\Tools\\Builders\\Get_Page_Layout',
            'wordpress_code_ai\\Tools\\Builders\\List_Layout_Elements',
            'wordpress_code_ai\\Tools\\Builders\\Update_Page_Layout',

            
            'wordpress_code_ai\\Tools\\Taxonomy\\List_Terms',

            
            'wordpress_code_ai\\Tools\\History\\History_List',
            'wordpress_code_ai\\Tools\\History\\History_Get',
            'wordpress_code_ai\\Tools\\History\\History_Diff',
            
            'wordpress_code_ai\\Tools\\History\\History_Apply',

            
            'wordpress_code_ai\\Tools\\Audit\\Audit_List',
            
            'wordpress_code_ai\\Tools\\Approvals\\Approve_Operation',
            'wordpress_code_ai\\Tools\\Approvals\\Deny_Operation',
            
            
            'wordpress_code_ai\\Tools\\Feedback\\Send_Feedback',
            
            'wordpress_code_ai\\Tools\\Skills\\Get_Skill',
            
            'wordpress_code_ai\\Tools\\Site\\List_Disabled_Tools',

            
            'wordpress_code_ai\\Tools\\Cli\\Cli_Run',

            
            'wordpress_code_ai\\Tools\\Site_Health\\Get_Site_Health',
            'wordpress_code_ai\\Tools\\Site_Health\\List_Cron_Events',
            'wordpress_code_ai\\Tools\\Site_Health\\Run_Cron_Event',
            'wordpress_code_ai\\Tools\\Site_Health\\Get_Error_Log',
            'wordpress_code_ai\\Tools\\Site_Health\\Get_Wordpress_Code_Ai_Diagnostics',
            
            'wordpress_code_ai\\Tools\\Site_Health\\Update_Core',

            
            'wordpress_code_ai\\Tools\\Rest\\Rest_Routes',
            'wordpress_code_ai\\Tools\\Rest\\Rest_Read',
            'wordpress_code_ai\\Tools\\Rest\\Rest_Write',
            'wordpress_code_ai\\Tools\\Rest\\Rest_Delete',

            
            'wordpress_code_ai\\Tools\\Semrush\\Domain_Overview',
            'wordpress_code_ai\\Tools\\Semrush\\Domain_Organic_Keywords',
            'wordpress_code_ai\\Tools\\Semrush\\Competitors_Organic',
            'wordpress_code_ai\\Tools\\Semrush\\Keyword_Overview',
            'wordpress_code_ai\\Tools\\Semrush\\Related_Keywords',
            'wordpress_code_ai\\Tools\\Semrush\\Keyword_Difficulty',
            'wordpress_code_ai\\Tools\\Semrush\\Phrase_Questions',
            'wordpress_code_ai\\Tools\\Semrush\\Backlinks_Overview',
            'wordpress_code_ai\\Tools\\Semrush\\Backlinks',
            'wordpress_code_ai\\Tools\\Semrush\\Referring_Domains',
            'wordpress_code_ai\\Tools\\Semrush\\Anchors',
            'wordpress_code_ai\\Tools\\Semrush\\Url_Organic_Keywords',
            'wordpress_code_ai\\Tools\\Semrush\\Api_Units_Balance',

            
            'wordpress_code_ai\\Tools\\Ahrefs\\Domain_Rating_Free',

            
            'wordpress_code_ai\\Tools\\SeRanking\\Account_Balance',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Overview',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Overview_Worldwide',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Keywords',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Competitors',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Keyword_Comparison',
            'wordpress_code_ai\\Tools\\SeRanking\\Domain_Pages',
            'wordpress_code_ai\\Tools\\SeRanking\\Keyword_Research',
            'wordpress_code_ai\\Tools\\SeRanking\\Keywords_Overview',
            'wordpress_code_ai\\Tools\\SeRanking\\Backlinks_Summary',
            'wordpress_code_ai\\Tools\\SeRanking\\Backlinks_List',
            'wordpress_code_ai\\Tools\\SeRanking\\Backlinks_Domain_Authority',
            'wordpress_code_ai\\Tools\\SeRanking\\Ai_Overview',
            'wordpress_code_ai\\Tools\\SeRanking\\Ai_Discover_Brand',
            'wordpress_code_ai\\Tools\\SeRanking\\Ai_Prompts',

        );

        $dirs = null === $dirs ? Tool_Directories::enabled() : $dirs;
        foreach ( $tool_classes as $class ) {
            if ( self::is_withheld( $class ) ) {
                continue;
            }
            $dir = Tool_Directories::of( $class );
            if ( null === $dir ) {
                if ( ! $quiet && ! self::is_plugin_class( $class ) ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'wordpress-code-ai: Tool class not found, skipping: ' . $class );
                }
                continue;
            }
            if ( ! in_array( $dir, $dirs, true ) ) {
                continue;
            }
            $this->lazy_classes[] = $class;
        }

        $this->register_privileged_tools();
    }

    






    private const PLUGIN_NAMESPACES = array(
        'wordpress_code_ai\\Tools\\WooCommerce\\',
        'wordpress_code_ai\\Tools\\ACF\\',
        'wordpress_code_ai\\Tools\\Events_Calendar\\',
        'wordpress_code_ai\\Tools\\Redirection\\',
        'wordpress_code_ai\\Tools\\Site_Kit\\',
        'wordpress_code_ai\\Tools\\Contact_Form_7\\',
        'wordpress_code_ai\\Tools\\BuddyPress\\',
        'wordpress_code_ai\\Tools\\SEO\\',
        'wordpress_code_ai\\Tools\\GSC\\',
        'wordpress_code_ai\\Tools\\GA\\',
        'wordpress_code_ai\\Tools\\DFS\\',
        'wordpress_code_ai\\Tools\\Semrush\\',
        'wordpress_code_ai\\Tools\\Ahrefs\\',
        'wordpress_code_ai\\Tools\\SeRanking\\',
    );

    







    private static function is_withheld( string $class ): bool {
        // wp_send_feedback reports to Themeisle, the original plugin's authors.
        return 'wordpress_code_ai\\Tools\\Feedback\\Send_Feedback' === $class;
    }

    




    private static function is_plugin_class( string $class ): bool {
        foreach ( self::PLUGIN_NAMESPACES as $ns ) {
            if ( strncmp( $class, $ns, strlen( $ns ) ) === 0 ) {
                return true;
            }
        }
        return false;
    }

    





    private function register_privileged_tools(): void {
        if ( ! class_exists( '\\wordpress_code_ai\\Privileged\\Privileged_Groups' ) ) {
            return;
        }
        foreach ( \wordpress_code_ai\Privileged\Privileged_Groups::TOOL_CLASSES as $class ) {
            if ( class_exists( $class ) ) {
                $this->register( new $class() );
            }
        }
    }
}
