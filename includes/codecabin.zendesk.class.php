<?php
/* 
 * @author Code Cabin
 * @package CodeCabinZendesk
 */
class CodeCabinZendesk{
    
    private $zaapikey;
    private $zduser;
    private $zdpass;

    function __construct(){
        
        
        
    }
    public function setVar( $var, $val ){
        define($var,$val);
    }
    
    public function curlWrap( $url, $json, $action ){
        
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
    	curl_setopt( $ch, CURLOPT_URL, ZDURL.$url );
    	curl_setopt( $ch, CURLOPT_USERPWD, ZDUSER."/token:".ZDAPIKEY );
        switch($action){
            case "POST":
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
                break;
            case "GET":
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
                break;
            case "PUT":
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
                break;
            case "DELETE":
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "DELETE" );
                break;
            default:
                break;
        }
	
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
    	curl_setopt( $ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0" );
    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	
        $output = curl_exec( $ch );	
	
        curl_close($ch);
	
        $decoded = json_decode($output);
	
        return $decoded;
        
    }
    
    public function return_views(){
    
        $data = $this->curlWrap("/views/compact.json", null, "GET");

        return $data;
    }
    
    public function return_metrics_specific_view( $view_id ){
    
        $data = $this->curlWrap("/views/" . $view_id . "/count.json", null, "GET");

        return $data;
    }
    
    public function return_specific_view_data( $view_id ) {
        
        $data = $this->curlWrap("/views/" . $view_id . ".json", null, "GET");

        return $data;
        
    }
    
    public function return_ticket_fields() {
        
        $data = $this->curlWrap("/ticket_fields.json", null, "GET");

        return $data;
               
    }
    
    public function return_total_ticket_time_spent() {
        
        $data = $this->curlWrap("/ticket_fields.json", null, "GET");

        foreach( $data as $field ) {
            
            if( $field->raw_title == 'Total time spent (sec)') {
                
                $field_id = $field->id;
                
            }
            
        }
        
        return $field_id;
        
    }
    
    public function return_ticket_time_last_update() {
        
        $data = $this->curlWrap("/ticket_fields.json", null, "GET");

        foreach( $data->ticket_fields as $field ) {
        
            if( $field->raw_title == 'Time spent last update (sec)') {
                
                $field_id = $field->id;
                
            }
            
        }
        
        return $field_id;
        
    }
    
    public function return_specific_ticket_field_data( $field_id ) {
        
        $data = $this->curlWrap("/ticket_fields/" . $field_id .".json", null, "GET");        
        
        return $data;
    }
    
    public function return_tickets_assigned_to_agent( $user_id ) {
        
        $data = $this->curlWrap("/users/" . $user_id . "/tickets/assigned.json", null, "GET");   
        
        return $data;
        
    }
    
    public function return_ticket_metrics(){
        
        $data = $this->curlWrap("/ticket_metrics.json", null, "GET");   
        
        return $data;
        
    }
    
    public function return_ticket_metrics_page( $page_no ){
        
        $data = $this->curlWrap("/ticket_metrics.json?page=".$page_no, null, "GET");   
        
        return $data;
        
    }
    
    public function return_all_tags(){
        
        $data = $this->curlWrap("/tags.json", null, "GET");   
        
        return $data;
                
    }
    
    
    
    public function return_tickets_from_specific_address( $email ){
        
        $data = $this->curlWrap("/search.json?query=".$email, null, "GET");   
        
        return $data;
        
    }
    
}