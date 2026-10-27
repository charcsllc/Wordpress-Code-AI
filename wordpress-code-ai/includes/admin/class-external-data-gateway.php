<?php








namespace wordpress_code_ai\Admin;

use wordpress_code_ai\Ahrefs\Ahrefs_Client;
use wordpress_code_ai\DFS\DataforSEO_Client;
use wordpress_code_ai\GA\GA_Client;
use wordpress_code_ai\GSC\GSC_Client;
use wordpress_code_ai\Semrush\Semrush_Client;
use wordpress_code_ai\SeRanking\SeRanking_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class External_Data_Gateway {

    
    public function gsc_sites() {
        return GSC_Client::get( 'https://www.googleapis.com/webmasters/v3/sites', true );
    }

    
    public function ga_account_summaries() {
        return GA_Client::get( 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200', true );
    }

    
    public function dfs_balance() {
        return ( new DataforSEO_Client() )->get_balance( true );
    }

    
    public function semrush_balance() {
        return ( new Semrush_Client() )->get_balance();
    }

    
    public function seranking_balance() {
        return ( new SeRanking_Client() )->get_balance();
    }

    
    public function ahrefs_verify() {
        ( new Ahrefs_Client() )->verify_key();
    }
}
